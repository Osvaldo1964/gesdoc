<?php
require_once '../config.php';
require_once '../jwt_helper.php';

header('Content-Type: application/json');

// Función para obtener y validar el JWT
function getValidUser() {
    $headers = apache_request_headers();
    $jwt = null;
    if (isset($headers['Authorization'])) {
        $matches = array();
        preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
        if (isset($matches[1])) {
            $jwt = $matches[1];
        }
    }
    
    // Fallback a Cookie si no hay header
    if (!$jwt && isset($_COOKIE['gesdoc_token'])) {
        $jwt = $_COOKIE['gesdoc_token'];
    }

    if (!$jwt) return false;

    return JWT::decode($jwt, JWT_SECRET);
}

$user = getValidUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado o sesión expirada.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT * FROM companies ORDER BY name ASC");
            $companies = $stmt->fetchAll();
            echo json_encode(['data' => $companies]); // Formato requerido por DataTables
            break;

        case 'create':
            $name = strtoupper(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
            $nit = filter_input(INPUT_POST, 'nit', FILTER_SANITIZE_STRING);
            $legal_representative = strtoupper(filter_input(INPUT_POST, 'legal_representative', FILTER_SANITIZE_STRING));
            $rut_updated_at = !empty($_POST['rut_updated_at']) ? $_POST['rut_updated_at'] : null;
            $rup_updated_at = !empty($_POST['rup_updated_at']) ? $_POST['rup_updated_at'] : null;
            
            if (empty($name) || empty($nit)) {
                echo json_encode(['success' => false, 'message' => 'Nombre y NIT son requeridos.']);
                exit;
            }
            
            // Verificar si el NIT ya existe
            $check = $pdo->prepare("SELECT id FROM companies WHERE nit = ?");
            $check->execute([$nit]);
            if ($check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'El NIT ya se encuentra registrado.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO companies (name, nit, legal_representative, rut_updated_at, rup_updated_at) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $nit, $legal_representative, $rut_updated_at, $rup_updated_at])) {
                echo json_encode(['success' => true, 'message' => 'Empresa creada exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear la empresa.']);
            }
            break;

        case 'update':
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $name = strtoupper(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
            $nit = filter_input(INPUT_POST, 'nit', FILTER_SANITIZE_STRING);
            $legal_representative = strtoupper(filter_input(INPUT_POST, 'legal_representative', FILTER_SANITIZE_STRING));
            $rut_updated_at = !empty($_POST['rut_updated_at']) ? $_POST['rut_updated_at'] : null;
            $rup_updated_at = !empty($_POST['rup_updated_at']) ? $_POST['rup_updated_at'] : null;
            
            if (empty($id) || empty($name) || empty($nit)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
                exit;
            }
            
            // Verificar NIT duplicado
            $check = $pdo->prepare("SELECT id FROM companies WHERE nit = ? AND id != ?");
            $check->execute([$nit, $id]);
            if ($check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'El NIT ya pertenece a otra empresa.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE companies SET name = ?, nit = ?, legal_representative = ?, rut_updated_at = ?, rup_updated_at = ? WHERE id = ?");
            if ($stmt->execute([$name, $nit, $legal_representative, $rut_updated_at, $rup_updated_at, $id])) {
                echo json_encode(['success' => true, 'message' => 'Empresa actualizada exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la empresa.']);
            }
            break;

        case 'delete':
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
                exit;
            }

            // Aquí se debería validar si la empresa pertenece a algún consorcio antes de borrar
            // O si tiene documentos asociados, aunque tenemos ON DELETE CASCADE.
            // Para mayor seguridad en producción, mejor restringir el borrado si hay dependencias.
            $check = $pdo->prepare("SELECT consortium_id FROM consortium_members WHERE company_id = ?");
            $check->execute([$id]);
            if ($check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar la empresa porque pertenece a uno o más consorcios.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Empresa eliminada exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la empresa.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
            break;
    }
} catch (PDOException $e) {
    error_log("Error API Empresas: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor en la base de datos.']);
}
?>
