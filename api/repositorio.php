<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

require_once 'auth_helper.php';

ob_clean();
header('Content-Type: application/json');

$user = getValidUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

$userId = (int) $user['user_id'];
$action = $_REQUEST['action'] ?? '';

// Valores permitidos para el campo status
$validStatuses = ['Borrador', 'En Revisión', 'Aprobado', 'Archivado'];
// Valores permitidos para entity_type
$validEntityTypes = ['Company', 'Consortium'];

try {
    switch ($action) {

        // ── CARPETAS ──────────────────────────────────────────────
        case 'get_tree':
            $stmt = $pdo->query("SELECT id, parent_id, name FROM folders ORDER BY name ASC");
            $all  = $stmt->fetchAll();
            echo json_encode(['data' => buildTree($all)]);
            break;

        case 'create_folder':
            $name      = strtoupper(trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8')));
            $parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_SANITIZE_NUMBER_INT) ?: null;
            if (empty($name)) { echo json_encode(['success'=>false,'message'=>'El nombre es requerido.']); exit; }
            $pdo->prepare("INSERT INTO folders (name, parent_id) VALUES (?,?)")->execute([$name, $parent_id]);
            $folderId = $pdo->lastInsertId();
            logAudit($pdo, $userId, 'carpeta.create', "ID: $folderId | Nombre: $name");
            echo json_encode(['success'=>true, 'message'=>'Carpeta creada.', 'id'=>$folderId]);
            break;

        case 'rename_folder':
            $id   = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $name = strtoupper(trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8')));
            $pdo->prepare("UPDATE folders SET name=? WHERE id=?")->execute([$name, $id]);
            logAudit($pdo, $userId, 'carpeta.rename', "ID: $id | Nuevo nombre: $name");
            echo json_encode(['success'=>true, 'message'=>'Carpeta renombrada.']);
            break;

        case 'delete_folder':
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            // Los documentos dentro se eliminan por CASCADE
            $pdo->prepare("DELETE FROM folders WHERE id=?")->execute([$id]);
            logAudit($pdo, $userId, 'carpeta.delete', "ID: $id");
            echo json_encode(['success'=>true, 'message'=>'Carpeta eliminada.']);
            break;

        // ── DOCUMENTOS ───────────────────────────────────────────
        case 'list_docs':
            $folder_id = filter_input(INPUT_GET, 'folder_id', FILTER_SANITIZE_NUMBER_INT) ?: null;
            $stmt = $pdo->prepare("
                SELECT d.id, d.name, d.description, d.status, d.created_at, d.updated_at,
                       dv.version, dv.file_path, dv.file_size,
                       u.name AS uploaded_by
                FROM documents d
                LEFT JOIN document_versions dv ON dv.document_id = d.id
                    AND dv.id = (SELECT MAX(dv2.id) FROM document_versions dv2 WHERE dv2.document_id = d.id)
                LEFT JOIN users u ON u.id = dv.uploaded_by
                WHERE d.folder_id " . ($folder_id ? "= ?" : "IS ?") . "
                ORDER BY d.name ASC
            ");
            $stmt->execute([$folder_id]);
            $docs = $stmt->fetchAll();
            foreach ($docs as &$doc) {
                $stmtA = $pdo->prepare("
                    SELECT da.entity_type, da.entity_id,
                           IF(da.entity_type='Company', co.name, cs.name) AS entity_name
                    FROM document_assignments da
                    LEFT JOIN companies co ON da.entity_type='Company' AND co.id=da.entity_id
                    LEFT JOIN consortiums cs ON da.entity_type='Consortium' AND cs.id=da.entity_id
                    WHERE da.document_id = ?
                ");
                $stmtA->execute([$doc['id']]);
                $doc['assignments'] = $stmtA->fetchAll();
            }
            echo json_encode(['data' => $docs]);
            break;

        case 'upload':
            $folder_id   = filter_input(INPUT_POST, 'folder_id', FILTER_SANITIZE_NUMBER_INT) ?: null;
            $description = trim(htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8'));
            $keywords    = trim(htmlspecialchars($_POST['keywords'] ?? '', ENT_QUOTES, 'UTF-8'));
            $statusRaw   = $_POST['status'] ?? 'Borrador';
            $status      = in_array($statusRaw, $validStatuses) ? $statusRaw : 'Borrador';
            $entityTypeRaw = $_POST['entity_type'] ?? null;
            $entity_type = ($entityTypeRaw && in_array($entityTypeRaw, $validEntityTypes)) ? $entityTypeRaw : null;
            $entity_id   = filter_input(INPUT_POST, 'entity_id', FILTER_SANITIZE_NUMBER_INT) ?: null;
            $doc_id      = filter_input(INPUT_POST, 'doc_id', FILTER_SANITIZE_NUMBER_INT) ?: null;

            if (empty($_FILES['file'])) { echo json_encode(['success'=>false,'message'=>'No se recibió ningún archivo.']); exit; }

            $file     = $_FILES['file'];
            $origName = basename($file['name']);
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','jpg','jpeg','png','gif','zip','rar','txt','csv'];
            if (!in_array($ext, $allowed)) { echo json_encode(['success'=>false,'message'=>"Tipo de archivo .$ext no permitido."]); exit; }

            $pdo->beginTransaction();

            if ($doc_id) {
                // Nueva versión de documento existente
                $stmt  = $pdo->prepare("SELECT MAX(CAST(REPLACE(version,'v','') AS DECIMAL(5,2))) AS last FROM document_versions WHERE document_id=?");
                $stmt->execute([$doc_id]);
                $last  = $stmt->fetchColumn() ?: 1.0;
                $ver   = 'v' . number_format($last + 0.1, 1);
                $pdo->prepare("UPDATE documents SET description=?, keywords=?, status=?, updated_at=NOW() WHERE id=?")->execute([$description, $keywords ?: null, $status, $doc_id]);
            } else {
                // Documento nuevo
                $docName = strtoupper(pathinfo($origName, PATHINFO_FILENAME));
                $stmt    = $pdo->prepare("INSERT INTO documents (folder_id, name, description, keywords, status) VALUES (?,?,?,?,?)");
                $stmt->execute([$folder_id, $docName, $description, $keywords ?: null, $status]);
                $doc_id  = $pdo->lastInsertId();
                $ver     = 'v1.0';

                if ($entity_type && $entity_id) {
                    $pdo->prepare("INSERT INTO document_assignments (document_id, entity_type, entity_id) VALUES (?,?,?)")
                        ->execute([$doc_id, $entity_type, $entity_id]);

                    if ($entity_type === 'Consortium') {
                        $members = $pdo->prepare("SELECT company_id FROM consortium_members WHERE consortium_id=?");
                        $members->execute([$entity_id]);
                        foreach ($members->fetchAll() as $m) {
                            $pdo->prepare("INSERT IGNORE INTO document_assignments (document_id, entity_type, entity_id) VALUES (?,?,?)")
                                ->execute([$doc_id, 'Company', $m['company_id']]);
                        }
                    }
                }
            }

            // Guardar archivo físico
            $safeFile = "doc_{$doc_id}_{$ver}_{$origName}";
            $destPath = EXTERNAL_STORAGE_PATH . DIRECTORY_SEPARATOR . $safeFile;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                $pdo->rollBack();
                echo json_encode(['success'=>false,'message'=>'Error al mover el archivo al storage.']);
                exit;
            }

            // Registrar versión con el usuario real del JWT
            $pdo->prepare("INSERT INTO document_versions (document_id, version, file_path, file_size, uploaded_by) VALUES (?,?,?,?,?)")
                ->execute([$doc_id, $ver, $destPath, $file['size'], $userId]);

            $pdo->commit();
            logAudit($pdo, $userId, 'documento.upload', "DocID: $doc_id | Versión: $ver | Archivo: $origName");
            echo json_encode(['success'=>true, 'message'=>"Archivo subido como $ver."]);
            break;

        case 'download':
            $doc_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt   = $pdo->prepare("SELECT dv.file_path, d.name FROM document_versions dv JOIN documents d ON d.id=dv.document_id WHERE dv.document_id=? ORDER BY dv.id DESC LIMIT 1");
            $stmt->execute([$doc_id]);
            $row    = $stmt->fetch();
            if (!$row || !file_exists($row['file_path'])) {
                header('Content-Type: application/json');
                echo json_encode(['success'=>false,'message'=>'Archivo no encontrado.']);
                exit;
            }
            $ext  = pathinfo($row['file_path'], PATHINFO_EXTENSION);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $row['name'] . '.' . $ext . '"');
            header('Content-Length: ' . filesize($row['file_path']));
            readfile($row['file_path']);
            exit;

        case 'delete_doc':
            $doc_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $vers = $pdo->prepare("SELECT file_path FROM document_versions WHERE document_id=?");
            $vers->execute([$doc_id]);
            foreach ($vers->fetchAll() as $v) {
                if (file_exists($v['file_path'])) @unlink($v['file_path']);
            }
            $pdo->prepare("DELETE FROM documents WHERE id=?")->execute([$doc_id]);
            logAudit($pdo, $userId, 'documento.delete', "DocID: $doc_id");
            echo json_encode(['success'=>true,'message'=>'Documento eliminado.']);
            break;

        case 'get_entities':
            $companies   = $pdo->query("SELECT id, name, 'Company' AS type FROM companies ORDER BY name")->fetchAll();
            $consortiums = $pdo->query("SELECT id, name, 'Consortium' AS type FROM consortiums ORDER BY name")->fetchAll();
            echo json_encode(['data' => array_merge($companies, $consortiums)]);
            break;

        default:
            echo json_encode(['success'=>false,'message'=>'Acción no válida.']);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error API Repositorio: " . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Error interno del servidor.']);
}

// ─── Helper: árbol jerárquico ──────────────────────────────────────────────
function buildTree(array $items, $parentId = null): array {
    $branch = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = buildTree($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $branch[] = $item;
        }
    }
    return $branch;
}
