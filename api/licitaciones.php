<?php
require_once '../config.php';
require_once '../jwt_helper.php';

header('Content-Type: application/json');

function getValidUser() {
    $headers = apache_request_headers();
    $jwt = null;
    if (isset($headers['Authorization'])) {
        preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
        if (isset($matches[1])) $jwt = $matches[1];
    }
    if (!$jwt && isset($_COOKIE['gesdoc_token'])) $jwt = $_COOKIE['gesdoc_token'];
    if (!$jwt) return false;
    return JWT::decode($jwt, JWT_SECRET);
}

$user = getValidUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        // ── Listas de filtros ───────────────────────────────────
        case 'get_filters':
            $companies   = $pdo->query("SELECT id, name, nit FROM companies ORDER BY name")->fetchAll();
            $consortiums = $pdo->query("SELECT id, name, nit FROM consortiums ORDER BY name")->fetchAll();
            echo json_encode([
                'companies'   => $companies,
                'consortiums' => $consortiums
            ]);
            break;

        // ── Búsqueda principal ─────────────────────────────────
        case 'search':
            $keyword      = trim($_GET['keyword']  ?? '');
            $company_id   = intval($_GET['company_id']   ?? 0);
            $consortium_id= intval($_GET['consortium_id'] ?? 0);
            $status       = trim($_GET['status'] ?? '');
            $date_from    = trim($_GET['date_from'] ?? '');
            $date_to      = trim($_GET['date_to']   ?? '');

            // ── Query base: documentos con última versión ──────
            // La clave: si el usuario filtra por empresa, traemos documentos
            // asociados a esa empresa directamente O a consorcios donde participa.
            $sql = "
                SELECT DISTINCT
                    d.id,
                    d.name          AS doc_name,
                    d.description,
                    d.status,
                    d.created_at,
                    d.updated_at,
                    dv.version,
                    dv.file_path,
                    dv.file_size,
                    da.entity_type,
                    da.entity_id,
                    CASE
                        WHEN da.entity_type = 'Company'    THEN co.name
                        WHEN da.entity_type = 'Consortium' THEN cs.name
                    END AS entity_name,
                    CASE
                        WHEN da.entity_type = 'Company'    THEN co.nit
                        WHEN da.entity_type = 'Consortium' THEN cs.nit
                    END AS entity_nit,
                    f.name AS folder_name,
                    u.name AS uploaded_by,
                    -- Participación si el vínculo es indirecto (empresa dentro de consorcio)
                    cm.participation_percentage
                FROM documents d
                JOIN document_assignments da ON da.document_id = d.id
                LEFT JOIN document_versions dv
                    ON dv.document_id = d.id
                    AND dv.id = (SELECT MAX(dv2.id) FROM document_versions dv2 WHERE dv2.document_id = d.id)
                LEFT JOIN companies   co ON da.entity_type = 'Company'    AND co.id = da.entity_id
                LEFT JOIN consortiums cs ON da.entity_type = 'Consortium' AND cs.id = da.entity_id
                LEFT JOIN folders     f  ON f.id = d.folder_id
                LEFT JOIN users       u  ON u.id = dv.uploaded_by
                LEFT JOIN consortium_members cm
                    ON cm.consortium_id = da.entity_id AND da.entity_type = 'Consortium'
            ";

            $conditions = [];
            $params     = [];

            // Filtro por texto libre
            if ($keyword !== '') {
                $conditions[] = "(d.name LIKE ? OR d.description LIKE ? OR co.name LIKE ? OR cs.name LIKE ?)";
                $like = "%$keyword%";
                $params = array_merge($params, [$like, $like, $like, $like]);
            }

            // Filtro por empresa (directo + indirecto vía consorcios)
            if ($company_id > 0) {
                $conditions[] = "
                    (
                        (da.entity_type = 'Company' AND da.entity_id = ?)
                        OR
                        (da.entity_type = 'Consortium' AND da.entity_id IN (
                            SELECT consortium_id FROM consortium_members WHERE company_id = ?
                        ))
                    )
                ";
                $params[] = $company_id;
                $params[] = $company_id;
            }

            // Filtro por consorcio
            if ($consortium_id > 0) {
                $conditions[] = "(da.entity_type = 'Consortium' AND da.entity_id = ?)";
                $params[] = $consortium_id;
            }

            // Filtro por estado
            if ($status !== '') {
                $conditions[] = "d.status = ?";
                $params[] = $status;
            }

            // Filtro por rango de fechas (sobre la fecha de actualización del doc)
            if ($date_from !== '') {
                $conditions[] = "DATE(d.updated_at) >= ?";
                $params[] = $date_from;
            }
            if ($date_to !== '') {
                $conditions[] = "DATE(d.updated_at) <= ?";
                $params[] = $date_to;
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $sql .= " ORDER BY d.updated_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();

            echo json_encode(['data' => $results]);
            break;

        // ── Descarga (reutilizamos lógica del repositorio) ─────
        case 'download':
            $doc_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt   = $pdo->prepare("
                SELECT dv.file_path, d.name
                FROM document_versions dv
                JOIN documents d ON d.id = dv.document_id
                WHERE dv.document_id = ? ORDER BY dv.id DESC LIMIT 1
            ");
            $stmt->execute([$doc_id]);
            $row = $stmt->fetch();
            if (!$row || !file_exists($row['file_path'])) {
                header('Content-Type: application/json');
                echo json_encode(['success'=>false,'message'=>'Archivo no encontrado.']);
                exit;
            }
            $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION);
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $row['name'] . '.' . $ext . '"');
            header('Content-Length: ' . filesize($row['file_path']));
            readfile($row['file_path']);
            exit;

        default:
            echo json_encode(['success'=>false,'message'=>'Acción no válida.']);
    }
} catch (PDOException $e) {
    error_log("Error API Licitaciones: " . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Error interno del servidor.']);
}
?>
