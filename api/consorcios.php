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
    echo json_encode(['success' => false, 'message' => 'No autorizado o sesión expirada.']);
    exit;
}

$userId = (int) $user['user_id'];
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {

        case 'list':
            $stmt = $pdo->query("
                SELECT c.id, c.name, c.nit, c.created_at,
                       COUNT(cm.company_id) as total_members
                FROM consortiums c
                LEFT JOIN consortium_members cm ON cm.consortium_id = c.id
                GROUP BY c.id
                ORDER BY c.name ASC
            ");
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'get_members':
            $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $pdo->prepare("
                SELECT cm.company_id, cm.participation_percentage,
                       co.name as company_name, co.nit as company_nit
                FROM consortium_members cm
                INNER JOIN companies co ON co.id = cm.company_id
                WHERE cm.consortium_id = ?
            ");
            $stmt->execute([$id]);
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'get_companies':
            $stmt = $pdo->query("SELECT id, name, nit FROM companies ORDER BY name ASC");
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'create':
            $name    = strtoupper(trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8')));
            $nit     = trim(htmlspecialchars($_POST['nit'] ?? '', ENT_QUOTES, 'UTF-8'));
            $members = json_decode($_POST['members'] ?? '[]', true);

            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'El nombre del consorcio es requerido.']);
                exit;
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO consortiums (name, nit) VALUES (?, ?)");
            $stmt->execute([$name, $nit ?: null]);
            $consortium_id = $pdo->lastInsertId();

            if (!empty($members)) {
                $stmtM = $pdo->prepare("INSERT INTO consortium_members (consortium_id, company_id, participation_percentage) VALUES (?, ?, ?)");
                foreach ($members as $m) {
                    $stmtM->execute([$consortium_id, (int)$m['company_id'], (float)$m['percentage']]);
                }
            }
            $pdo->commit();
            logAudit($pdo, $userId, 'consorcio.create', "ID: $consortium_id | Nombre: $name");
            echo json_encode(['success' => true, 'message' => 'Consorcio creado exitosamente.']);
            break;

        case 'update':
            $id      = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $name    = strtoupper(trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8')));
            $nit     = trim(htmlspecialchars($_POST['nit'] ?? '', ENT_QUOTES, 'UTF-8'));
            $members = json_decode($_POST['members'] ?? '[]', true);

            if (empty($id) || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
                exit;
            }

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE consortiums SET name = ?, nit = ? WHERE id = ?")->execute([$name, $nit ?: null, $id]);
            $pdo->prepare("DELETE FROM consortium_members WHERE consortium_id = ?")->execute([$id]);

            if (!empty($members)) {
                $stmtM = $pdo->prepare("INSERT INTO consortium_members (consortium_id, company_id, participation_percentage) VALUES (?, ?, ?)");
                foreach ($members as $m) {
                    $stmtM->execute([$id, (int)$m['company_id'], (float)$m['percentage']]);
                }
            }
            $pdo->commit();
            logAudit($pdo, $userId, 'consorcio.update', "ID: $id | Nombre: $name");
            echo json_encode(['success' => true, 'message' => 'Consorcio actualizado exitosamente.']);
            break;

        case 'delete':
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
                exit;
            }
            $pdo->prepare("DELETE FROM consortiums WHERE id = ?")->execute([$id]);
            logAudit($pdo, $userId, 'consorcio.delete', "ID: $id");
            echo json_encode(['success' => true, 'message' => 'Consorcio eliminado exitosamente.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error API Consorcios: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
