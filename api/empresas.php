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
            $stmt = $pdo->query("SELECT * FROM companies ORDER BY name ASC");
            $companies = $stmt->fetchAll();
            echo json_encode(['data' => $companies]);
            break;

        case 'create':
            $name                = strtoupper(trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8')));
            $nit                 = trim(htmlspecialchars($_POST['nit'] ?? '', ENT_QUOTES, 'UTF-8'));
            $legal_representative = strtoupper(trim(htmlspecialchars($_POST['legal_representative'] ?? '', ENT_QUOTES, 'UTF-8')));
            $rut_updated_at      = !empty($_POST['rut_updated_at']) ? $_POST['rut_updated_at'] : null;
            $rup_updated_at      = !empty($_POST['rup_updated_at']) ? $_POST['rup_updated_at'] : null;

            if (empty($name) || empty($nit)) {
                echo json_encode(['success' => false, 'message' => 'Nombre y NIT son requeridos.']);
                exit;
            }

            $check = $pdo->prepare("SELECT id FROM companies WHERE nit = ?");
            $check->execute([$nit]);
            if ($check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'El NIT ya se encuentra registrado.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO companies (name, nit, legal_representative, rut_updated_at, rup_updated_at) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $nit, $legal_representative, $rut_updated_at, $rup_updated_at])) {
                $newId = $pdo->lastInsertId();
                logAudit($pdo, $userId, 'empresa.create', "ID: $newId | NIT: $nit | Nombre: $name");
                echo json_encode(['success' => true, 'message' => 'Empresa creada exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al crear la empresa.']);
            }
            break;

        case 'update':
            $id                  = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $name                = strtoupper(trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8')));
            $nit                 = trim(htmlspecialchars($_POST['nit'] ?? '', ENT_QUOTES, 'UTF-8'));
            $legal_representative = strtoupper(trim(htmlspecialchars($_POST['legal_representative'] ?? '', ENT_QUOTES, 'UTF-8')));
            $rut_updated_at      = !empty($_POST['rut_updated_at']) ? $_POST['rut_updated_at'] : null;
            $rup_updated_at      = !empty($_POST['rup_updated_at']) ? $_POST['rup_updated_at'] : null;

            if (empty($id) || empty($name) || empty($nit)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
                exit;
            }

            $check = $pdo->prepare("SELECT id FROM companies WHERE nit = ? AND id != ?");
            $check->execute([$nit, $id]);
            if ($check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'El NIT ya pertenece a otra empresa.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE companies SET name = ?, nit = ?, legal_representative = ?, rut_updated_at = ?, rup_updated_at = ? WHERE id = ?");
            if ($stmt->execute([$name, $nit, $legal_representative, $rut_updated_at, $rup_updated_at, $id])) {
                logAudit($pdo, $userId, 'empresa.update', "ID: $id | NIT: $nit | Nombre: $name");
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

            $check = $pdo->prepare("SELECT consortium_id FROM consortium_members WHERE company_id = ?");
            $check->execute([$id]);
            if ($check->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar la empresa porque pertenece a uno o más consorcios.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ?");
            if ($stmt->execute([$id])) {
                logAudit($pdo, $userId, 'empresa.delete', "ID: $id");
                echo json_encode(['success' => true, 'message' => 'Empresa eliminada exitosamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar la empresa.']);
            }
            break;

        // ── ESTADOS FINANCIEROS ──────────────────────────────────
        case 'ef_list':
            $company_id = filter_input(INPUT_GET, 'company_id', FILTER_SANITIZE_NUMBER_INT);
            if (empty($company_id)) { echo json_encode(['data' => []]); exit; }
            $stmt = $pdo->prepare("SELECT * FROM empresa_estados_financieros WHERE company_id = ? ORDER BY periodo DESC");
            $stmt->execute([$company_id]);
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'ef_save':
            // Crea o actualiza (upsert) el estado financiero de un año
            $company_id           = filter_input(INPUT_POST, 'company_id', FILTER_SANITIZE_NUMBER_INT);
            $periodo              = filter_input(INPUT_POST, 'periodo', FILTER_SANITIZE_NUMBER_INT);

            if (empty($company_id) || empty($periodo)) {
                echo json_encode(['success' => false, 'message' => 'Empresa y período son requeridos.']);
                exit;
            }

            $activo_corriente         = (float)($_POST['activo_corriente']         ?? 0);
            $activo_no_corriente      = (float)($_POST['activo_no_corriente']      ?? 0);
            $total_activos            = (float)($_POST['total_activos']            ?? 0);
            $pasivo_corriente         = (float)($_POST['pasivo_corriente']         ?? 0);
            $pasivo_no_corriente      = (float)($_POST['pasivo_no_corriente']      ?? 0);
            $total_pasivos            = (float)($_POST['total_pasivos']            ?? 0);
            $patrimonio               = (float)($_POST['patrimonio']               ?? 0);
            $ingresos_operacionales   = (float)($_POST['ingresos_operacionales']   ?? 0);
            $utilidad_neta            = (float)($_POST['utilidad_neta']            ?? 0);

            $pdo->prepare("
                INSERT INTO empresa_estados_financieros
                    (company_id, periodo, activo_corriente, activo_no_corriente, total_activos,
                     pasivo_corriente, pasivo_no_corriente, total_pasivos, patrimonio,
                     ingresos_operacionales, utilidad_neta)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE
                    activo_corriente=VALUES(activo_corriente),
                    activo_no_corriente=VALUES(activo_no_corriente),
                    total_activos=VALUES(total_activos),
                    pasivo_corriente=VALUES(pasivo_corriente),
                    pasivo_no_corriente=VALUES(pasivo_no_corriente),
                    total_pasivos=VALUES(total_pasivos),
                    patrimonio=VALUES(patrimonio),
                    ingresos_operacionales=VALUES(ingresos_operacionales),
                    utilidad_neta=VALUES(utilidad_neta),
                    updated_at=NOW()
            ")->execute([
                $company_id, $periodo,
                $activo_corriente, $activo_no_corriente, $total_activos,
                $pasivo_corriente, $pasivo_no_corriente, $total_pasivos,
                $patrimonio, $ingresos_operacionales, $utilidad_neta
            ]);

            logAudit($pdo, $userId, 'empresa.ef_save', "CompanyID: $company_id | Período: $periodo");
            echo json_encode(['success' => true, 'message' => 'Estado financiero guardado.']);
            break;

        case 'ef_delete':
            $ef_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            if (empty($ef_id)) {
                echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
                exit;
            }
            $pdo->prepare("DELETE FROM empresa_estados_financieros WHERE id=?")->execute([$ef_id]);
            logAudit($pdo, $userId, 'empresa.ef_delete', "EF_ID: $ef_id");
            echo json_encode(['success' => true, 'message' => 'Estado financiero eliminado.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    }

} catch (PDOException $e) {
    error_log("Error API Empresas: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
