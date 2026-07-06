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

// Catálogo fijo de indicadores estándar
const INDICADORES = [
    'razon_corriente'      => ['label' => 'Razón Corriente',             'unidad' => 'ratio',      'decimales' => 2],
    'nivel_endeudamiento'  => ['label' => 'Nivel de Endeudamiento',      'unidad' => '%',          'decimales' => 2],
    'capital_trabajo'      => ['label' => 'Capital de Trabajo',          'unidad' => '$',          'decimales' => 0],
    'patrimonio'           => ['label' => 'Patrimonio',                   'unidad' => '$',          'decimales' => 0],
    'roa'                  => ['label' => 'Rentabilidad sobre Activos',   'unidad' => '%',          'decimales' => 2],
    'roe'                  => ['label' => 'Rentabilidad sobre Patrimonio','unidad' => '%',          'decimales' => 2],
];

try {
    switch ($action) {

        // ── CATÁLOGO DE INDICADORES ───────────────────────────────
        case 'get_indicadores_catalogo':
            $cat = [];
            foreach (INDICADORES as $key => $meta) {
                $cat[] = array_merge(['key' => $key], $meta);
            }
            echo json_encode(['data' => $cat]);
            break;

        // ── CRUD PROCESOS ─────────────────────────────────────────
        case 'list':
            $stmt = $pdo->query("
                SELECT p.*,
                       (SELECT COUNT(*) FROM proceso_participaciones pp WHERE pp.proceso_id = p.id) AS total_participantes
                FROM procesos p
                ORDER BY p.created_at DESC
            ");
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'create':
            $nombre             = strtoupper(trim(htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8')));
            $entidad            = strtoupper(trim(htmlspecialchars($_POST['entidad_contratante'] ?? '', ENT_QUOTES, 'UTF-8')));
            $numero             = trim(htmlspecialchars($_POST['numero_proceso'] ?? '', ENT_QUOTES, 'UTF-8'));
            $objeto             = trim(htmlspecialchars($_POST['objeto'] ?? '', ENT_QUOTES, 'UTF-8'));
            $modalidad          = $_POST['modalidad'] ?? 'Licitación Pública';
            $presupuesto        = !empty($_POST['presupuesto_oficial']) ? (float)$_POST['presupuesto_oficial'] : null;
            $fecha_apertura     = !empty($_POST['fecha_apertura']) ? $_POST['fecha_apertura'] : null;
            $fecha_cierre       = !empty($_POST['fecha_cierre']) ? $_POST['fecha_cierre'] : null;
            $estado             = $_POST['estado'] ?? 'En Preparación';
            $periodo_financiero = (int)($_POST['periodo_financiero'] ?? date('Y'));

            if (empty($nombre) || empty($entidad)) {
                echo json_encode(['success' => false, 'message' => 'Nombre y entidad contratante son requeridos.']);
                exit;
            }

            $pdo->prepare("INSERT INTO procesos
                (nombre, entidad_contratante, numero_proceso, objeto, modalidad,
                 presupuesto_oficial, fecha_apertura, fecha_cierre, estado, periodo_financiero)
                VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([$nombre, $entidad, $numero ?: null, $objeto ?: null, $modalidad,
                           $presupuesto, $fecha_apertura, $fecha_cierre, $estado, $periodo_financiero]);
            $newId = $pdo->lastInsertId();
            logAudit($pdo, $userId, 'proceso.create', "ID: $newId | $nombre");
            echo json_encode(['success' => true, 'message' => 'Proceso creado.', 'id' => $newId]);
            break;

        case 'update':
            $id                 = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $nombre             = strtoupper(trim(htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8')));
            $entidad            = strtoupper(trim(htmlspecialchars($_POST['entidad_contratante'] ?? '', ENT_QUOTES, 'UTF-8')));
            $numero             = trim(htmlspecialchars($_POST['numero_proceso'] ?? '', ENT_QUOTES, 'UTF-8'));
            $objeto             = trim(htmlspecialchars($_POST['objeto'] ?? '', ENT_QUOTES, 'UTF-8'));
            $modalidad          = $_POST['modalidad'] ?? 'Licitación Pública';
            $presupuesto        = !empty($_POST['presupuesto_oficial']) ? (float)$_POST['presupuesto_oficial'] : null;
            $fecha_apertura     = !empty($_POST['fecha_apertura']) ? $_POST['fecha_apertura'] : null;
            $fecha_cierre       = !empty($_POST['fecha_cierre']) ? $_POST['fecha_cierre'] : null;
            $estado             = $_POST['estado'] ?? 'En Preparación';
            $periodo_financiero = (int)($_POST['periodo_financiero'] ?? date('Y'));

            if (empty($id) || empty($nombre) || empty($entidad)) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
                exit;
            }

            $pdo->prepare("UPDATE procesos SET
                nombre=?, entidad_contratante=?, numero_proceso=?, objeto=?, modalidad=?,
                presupuesto_oficial=?, fecha_apertura=?, fecha_cierre=?, estado=?, periodo_financiero=?
                WHERE id=?")
                ->execute([$nombre, $entidad, $numero ?: null, $objeto ?: null, $modalidad,
                           $presupuesto, $fecha_apertura, $fecha_cierre, $estado, $periodo_financiero, $id]);
            logAudit($pdo, $userId, 'proceso.update', "ID: $id | $nombre");
            echo json_encode(['success' => true, 'message' => 'Proceso actualizado.']);
            break;

        case 'delete':
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            if (empty($id)) { echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']); exit; }
            $pdo->prepare("DELETE FROM procesos WHERE id=?")->execute([$id]);
            logAudit($pdo, $userId, 'proceso.delete', "ID: $id");
            echo json_encode(['success' => true, 'message' => 'Proceso eliminado.']);
            break;

        case 'get':
            $id   = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $pdo->prepare("SELECT * FROM procesos WHERE id=?");
            $stmt->execute([$id]);
            $proc = $stmt->fetch();
            if (!$proc) { echo json_encode(['success' => false, 'message' => 'Proceso no encontrado.']); exit; }
            echo json_encode(['success' => true, 'data' => $proc]);
            break;

        // ── INDICADORES REQUERIDOS ────────────────────────────────
        case 'indicadores_list':
            $proceso_id = filter_input(INPUT_GET, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $pdo->prepare("SELECT * FROM proceso_indicadores WHERE proceso_id=?");
            $stmt->execute([$proceso_id]);
            $rows = $stmt->fetchAll();
            // Enriquecer con label del catálogo
            foreach ($rows as &$r) {
                $r['label']    = INDICADORES[$r['indicador']]['label']    ?? $r['indicador'];
                $r['unidad']   = INDICADORES[$r['indicador']]['unidad']   ?? '';
                $r['decimales']= INDICADORES[$r['indicador']]['decimales'] ?? 2;
            }
            echo json_encode(['data' => $rows]);
            break;

        case 'indicadores_save':
            // Recibe array JSON: [{indicador, operador, valor_requerido, habilitado}, ...]
            $proceso_id  = filter_input(INPUT_POST, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);
            $indicadores = json_decode($_POST['indicadores'] ?? '[]', true);
            if (empty($proceso_id)) { echo json_encode(['success' => false, 'message' => 'proceso_id requerido.']); exit; }

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM proceso_indicadores WHERE proceso_id=?")->execute([$proceso_id]);
            $stmt = $pdo->prepare("INSERT INTO proceso_indicadores (proceso_id, indicador, operador, valor_requerido, habilitado) VALUES (?,?,?,?,?)");
            foreach ($indicadores as $ind) {
                if (empty($ind['indicador']) || !array_key_exists($ind['indicador'], INDICADORES)) continue;
                $stmt->execute([
                    $proceso_id,
                    $ind['indicador'],
                    $ind['operador']       ?? '>=',
                    (float)($ind['valor_requerido'] ?? 0),
                    isset($ind['habilitado']) ? (int)(bool)$ind['habilitado'] : 1
                ]);
            }
            $pdo->commit();
            logAudit($pdo, $userId, 'proceso.indicadores_save', "ProcesoID: $proceso_id");
            echo json_encode(['success' => true, 'message' => 'Indicadores guardados.']);
            break;

        // ── CRITERIOS PUNTUABLES ──────────────────────────────────
        case 'criterios_list':
            $proceso_id = filter_input(INPUT_GET, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $pdo->prepare("SELECT * FROM proceso_criterios_puntuables WHERE proceso_id=? ORDER BY id ASC");
            $stmt->execute([$proceso_id]);
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'criterio_save':
            $proceso_id     = filter_input(INPUT_POST, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);
            $id             = filter_input(INPUT_POST, 'id',         FILTER_SANITIZE_NUMBER_INT);
            $nombre         = trim(htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8'));
            $puntaje_maximo = (float)($_POST['puntaje_maximo'] ?? 0);
            $descripcion    = trim(htmlspecialchars($_POST['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'));

            if (empty($proceso_id) || empty($nombre)) {
                echo json_encode(['success' => false, 'message' => 'Nombre del criterio requerido.']); exit;
            }
            if ($id) {
                $pdo->prepare("UPDATE proceso_criterios_puntuables SET nombre=?, puntaje_maximo=?, descripcion=? WHERE id=? AND proceso_id=?")
                    ->execute([$nombre, $puntaje_maximo, $descripcion ?: null, $id, $proceso_id]);
            } else {
                $pdo->prepare("INSERT INTO proceso_criterios_puntuables (proceso_id, nombre, puntaje_maximo, descripcion) VALUES (?,?,?,?)")
                    ->execute([$proceso_id, $nombre, $puntaje_maximo, $descripcion ?: null]);
            }
            logAudit($pdo, $userId, 'proceso.criterio_save', "ProcesoID: $proceso_id | $nombre");
            echo json_encode(['success' => true, 'message' => 'Criterio guardado.']);
            break;

        case 'criterio_delete':
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $pdo->prepare("DELETE FROM proceso_criterios_puntuables WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Criterio eliminado.']);
            break;

        // ── PARTICIPACIONES ───────────────────────────────────────
        case 'participaciones_list':
            $proceso_id = filter_input(INPUT_GET, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $pdo->prepare("
                SELECT pp.*,
                    CASE WHEN pp.entity_type='Company'    THEN co.name
                         WHEN pp.entity_type='Consortium' THEN cs.name END AS entity_name,
                    CASE WHEN pp.entity_type='Company'    THEN co.nit
                         WHEN pp.entity_type='Consortium' THEN cs.nit  END AS entity_nit
                FROM proceso_participaciones pp
                LEFT JOIN companies   co ON pp.entity_type='Company'    AND co.id=pp.entity_id
                LEFT JOIN consortiums cs ON pp.entity_type='Consortium' AND cs.id=pp.entity_id
                WHERE pp.proceso_id=?
                ORDER BY pp.created_at ASC
            ");
            $stmt->execute([$proceso_id]);
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'participacion_save':
            $proceso_id  = filter_input(INPUT_POST, 'proceso_id',  FILTER_SANITIZE_NUMBER_INT);
            $entity_type = in_array($_POST['entity_type'] ?? '', ['Company','Consortium']) ? $_POST['entity_type'] : null;
            $entity_id   = filter_input(INPUT_POST, 'entity_id',   FILTER_SANITIZE_NUMBER_INT);
            $porcentaje  = isset($_POST['porcentaje_participacion'])
                           ? min(100, max(0.01, (float)$_POST['porcentaje_participacion']))
                           : 100.00;
            $estado      = $_POST['estado'] ?? 'En Análisis';
            $notas       = trim(htmlspecialchars($_POST['notas'] ?? '', ENT_QUOTES, 'UTF-8'));

            if (!$proceso_id || !$entity_type || !$entity_id) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']); exit;
            }

            // Verificar que no se supere 100% sumando con los ya registrados (solo Company)
            if ($entity_type === 'Company') {
                $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(porcentaje_participacion),0) FROM proceso_participaciones WHERE proceso_id=? AND entity_type='Company'");
                $stmtSum->execute([$proceso_id]);
                $sumaActual = (float)$stmtSum->fetchColumn();
                if (($sumaActual + $porcentaje) > 100.01) {
                    echo json_encode(['success' => false,
                        'message' => "El porcentaje excede el 100%. Actualmente hay {$sumaActual}% asignado."]); exit;
                }
            }

            $pdo->prepare("INSERT IGNORE INTO proceso_participaciones
                (proceso_id, entity_type, entity_id, porcentaje_participacion, estado, notas)
                VALUES (?,?,?,?,?,?)")
                ->execute([$proceso_id, $entity_type, $entity_id, $porcentaje, $estado, $notas ?: null]);
            logAudit($pdo, $userId, 'proceso.participacion_add', "ProcesoID: $proceso_id | $entity_type:$entity_id | {$porcentaje}%");
            echo json_encode(['success' => true, 'message' => 'Participante agregado.']);
            break;

        case 'participacion_update_estado':
            $id         = filter_input(INPUT_POST, 'id',     FILTER_SANITIZE_NUMBER_INT);
            $estado     = trim($_POST['estado'] ?? '');
            $notas      = trim(htmlspecialchars($_POST['notas'] ?? '', ENT_QUOTES, 'UTF-8'));
            $porcentaje = isset($_POST['porcentaje_participacion'])
                          ? min(100, max(0.01, (float)$_POST['porcentaje_participacion']))
                          : null;

            if ($porcentaje !== null) {
                $pdo->prepare("UPDATE proceso_participaciones SET estado=?, notas=?, porcentaje_participacion=? WHERE id=?")
                    ->execute([$estado, $notas ?: null, $porcentaje, $id]);
            } else {
                $pdo->prepare("UPDATE proceso_participaciones SET estado=?, notas=? WHERE id=?")
                    ->execute([$estado, $notas ?: null, $id]);
            }
            echo json_encode(['success' => true, 'message' => 'Participante actualizado.']);
            break;

        case 'participacion_delete':
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $pdo->prepare("DELETE FROM proceso_participaciones WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Participante eliminado.']);
            break;

        case 'get_entities':
            $companies   = $pdo->query("SELECT id, name, nit, 'Company' AS type FROM companies ORDER BY name")->fetchAll();
            $consortiums = $pdo->query("SELECT id, name, nit, 'Consortium' AS type FROM consortiums ORDER BY name")->fetchAll();
            echo json_encode(['data' => array_merge($companies, $consortiums)]);
            break;

        // ── REQUISITOS HABILITANTES ───────────────────────────────
        case 'requisitos_list':
            $proceso_id = filter_input(INPUT_GET, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);
            $stmt = $pdo->prepare("SELECT * FROM proceso_requisitos_habilitantes WHERE proceso_id=? ORDER BY tipo, id");
            $stmt->execute([$proceso_id]);
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;

        case 'requisito_save':
            $id         = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $proceso_id = filter_input(INPUT_POST, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);
            $tipo       = in_array($_POST['tipo'] ?? '', ['Jurídico','Técnico','Financiero'])
                          ? $_POST['tipo'] : null;
            $descripcion = trim(htmlspecialchars($_POST['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'));

            if (!$proceso_id || !$tipo || !$descripcion) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']); exit;
            }
            if ($id) {
                $pdo->prepare("UPDATE proceso_requisitos_habilitantes SET tipo=?, descripcion=? WHERE id=? AND proceso_id=?")
                    ->execute([$tipo, $descripcion, $id, $proceso_id]);
                $msg = 'Requisito actualizado.';
                logAudit($pdo, $userId, 'proceso.requisito_update', "ID:$id");
            } else {
                $pdo->prepare("INSERT INTO proceso_requisitos_habilitantes (proceso_id, tipo, descripcion) VALUES (?,?,?)")
                    ->execute([$proceso_id, $tipo, $descripcion]);
                $msg = 'Requisito agregado.';
                logAudit($pdo, $userId, 'proceso.requisito_create', "ProcesoID:$proceso_id | $tipo");
            }
            echo json_encode(['success' => true, 'message' => $msg]);
            break;

        case 'requisito_delete':
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            $pdo->prepare("DELETE FROM proceso_requisitos_habilitantes WHERE id=?")->execute([$id]);
            logAudit($pdo, $userId, 'proceso.requisito_delete', "ID:$id");
            echo json_encode(['success' => true, 'message' => 'Requisito eliminado.']);
            break;

        // ── CUMPLIMIENTO DE REQUISITOS POR PARTICIPANTE ───────────
        case 'cumplimiento_list':
            // Devuelve todos los requisitos del proceso con el estado de cumplimiento
            // para cada participante. Estructura: [{requisito, cumplimientos:[{participacion_id, cumple, observacion}]}]
            $proceso_id = filter_input(INPUT_GET, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);

            $stmtReq = $pdo->prepare("SELECT * FROM proceso_requisitos_habilitantes WHERE proceso_id=? ORDER BY tipo, id");
            $stmtReq->execute([$proceso_id]);
            $requisitos = $stmtReq->fetchAll();

            $stmtPart = $pdo->prepare("
                SELECT pp.id, pp.entity_type, pp.entity_id, pp.porcentaje_participacion,
                       COALESCE(c.name, co.name) AS entity_name
                FROM proceso_participaciones pp
                LEFT JOIN companies    c  ON pp.entity_type='Company'    AND pp.entity_id=c.id
                LEFT JOIN consortiums  co ON pp.entity_type='Consortium' AND pp.entity_id=co.id
                WHERE pp.proceso_id=?
                ORDER BY pp.id
            ");
            $stmtPart->execute([$proceso_id]);
            $participaciones = $stmtPart->fetchAll();

            // Para cada requisito, obtener el cumplimiento de cada participante
            foreach ($requisitos as &$req) {
                $stmtC = $pdo->prepare("
                    SELECT participacion_id, cumple, observacion
                    FROM proceso_requisitos_cumplimiento
                    WHERE requisito_id=?
                ");
                $stmtC->execute([$req['id']]);
                $cumplimientos = $stmtC->fetchAll();
                // Indexar por participacion_id para acceso rápido en el frontend
                $req['cumplimientos'] = array_column($cumplimientos, null, 'participacion_id');
            }
            unset($req);

            echo json_encode([
                'requisitos'     => $requisitos,
                'participaciones' => $participaciones,
            ]);
            break;

        case 'cumplimiento_save':
            $requisito_id    = filter_input(INPUT_POST, 'requisito_id',    FILTER_SANITIZE_NUMBER_INT);
            $participacion_id = filter_input(INPUT_POST, 'participacion_id', FILTER_SANITIZE_NUMBER_INT);
            $cumple          = in_array($_POST['cumple'] ?? '', ['Pendiente','Cumple','No Cumple'])
                               ? $_POST['cumple'] : 'Pendiente';
            $observacion     = trim(htmlspecialchars($_POST['observacion'] ?? '', ENT_QUOTES, 'UTF-8'));

            if (!$requisito_id || !$participacion_id) {
                echo json_encode(['success' => false, 'message' => 'Faltan datos.']); exit;
            }
            $pdo->prepare("
                INSERT INTO proceso_requisitos_cumplimiento
                    (requisito_id, participacion_id, cumple, observacion)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY UPDATE cumple=VALUES(cumple), observacion=VALUES(observacion)
            ")->execute([$requisito_id, $participacion_id, $cumple, $observacion]);

            logAudit($pdo, $userId, 'proceso.cumplimiento_save', "RequisitID: $requisito_id | ParticID: $participacion_id | Cumple: $cumple");
            echo json_encode(['success' => true, 'message' => 'Cumplimiento guardado.']);
            break;

        // ── ANÁLISIS INDIVIDUAL ───────────────────────────────────
        case 'calcular_cotejo':
            $participacion_id = filter_input(INPUT_GET, 'participacion_id', FILTER_SANITIZE_NUMBER_INT);
            if (!$participacion_id) { echo json_encode(['success'=>false,'message'=>'participacion_id requerido.']); exit; }

            $stmtP = $pdo->prepare("
                SELECT pp.*, COALESCE(c.name, co.name) AS entity_name
                FROM proceso_participaciones pp
                LEFT JOIN companies   c  ON pp.entity_type='Company'    AND pp.entity_id=c.id
                LEFT JOIN consortiums co ON pp.entity_type='Consortium' AND pp.entity_id=co.id
                WHERE pp.id=?
            ");
            $stmtP->execute([$participacion_id]);
            $part = $stmtP->fetch();
            if (!$part) { echo json_encode(['success'=>false,'message'=>'Participante no encontrado.']); exit; }

            $stmtPr = $pdo->prepare("SELECT * FROM procesos WHERE id=?");
            $stmtPr->execute([$part['proceso_id']]);
            $proceso = $stmtPr->fetch();
            $periodo = (int)($proceso['periodo_financiero'] ?? date('Y') - 1);

            $stmtI = $pdo->prepare("SELECT * FROM proceso_indicadores WHERE proceso_id=? AND habilitado=1");
            $stmtI->execute([$part['proceso_id']]);
            $indicadores = $stmtI->fetchAll();

            $pct    = (float)($part['porcentaje_participacion'] ?? 100.0);
            $cifras = calcularCifrasBase($pdo, $part['entity_type'], (int)$part['entity_id'], $periodo, $pct);

            if (!empty($cifras['missing'])) {
                echo json_encode(['success'=>false,'message'=>'Sin datos financieros para '.$part['entity_name'].' en el período '.$periodo.'.']);
                exit;
            }

            $cotejo = calcularIndicadores($cifras, $indicadores);
            echo json_encode([
                'success'     => true,
                'entity_name' => $part['entity_name'],
                'proceso'     => $proceso,          // JS usa res.proceso.periodo_financiero
                'cifras_base' => $cifras,            // JS usa res.cifras_base
                'cotejo'      => $cotejo,            // JS usa res.cotejo
            ]);
            break;

        // ── ANÁLISIS CONJUNTO ─────────────────────────────────────
        case 'calcular_cotejo_proceso':
            $proceso_id = filter_input(INPUT_GET, 'proceso_id', FILTER_SANITIZE_NUMBER_INT);
            if (!$proceso_id) { echo json_encode(['success'=>false,'message'=>'proceso_id requerido.']); exit; }

            $stmtPr = $pdo->prepare("SELECT * FROM procesos WHERE id=?");
            $stmtPr->execute([$proceso_id]);
            $proceso = $stmtPr->fetch();
            $periodo = (int)($proceso['periodo_financiero'] ?? date('Y') - 1);

            $stmtParts = $pdo->prepare("
                SELECT pp.*, COALESCE(c.name, co.name) AS entity_name
                FROM proceso_participaciones pp
                LEFT JOIN companies   c  ON pp.entity_type='Company'    AND pp.entity_id=c.id
                LEFT JOIN consortiums co ON pp.entity_type='Consortium' AND pp.entity_id=co.id
                WHERE pp.proceso_id=?
            ");
            $stmtParts->execute([$proceso_id]);
            $participaciones = $stmtParts->fetchAll();

            if (empty($participaciones)) {
                echo json_encode(['success'=>false,'message'=>'No hay participantes registrados.']); exit;
            }

            $stmtI = $pdo->prepare("SELECT * FROM proceso_indicadores WHERE proceso_id=? AND habilitado=1");
            $stmtI->execute([$proceso_id]);
            $indicadores = $stmtI->fetchAll();

            $camposBase    = ['activo_corriente','activo_no_corriente','total_activos',
                              'pasivo_corriente','pasivo_no_corriente','total_pasivos',
                              'patrimonio','ingresos_operacionales','utilidad_neta'];
            $cifrasAgregadas  = array_fill_keys($camposBase, 0.0);
            $detallePartes    = [];
            $errores          = [];
            $pctTotal         = 0.0;

            foreach ($participaciones as $part) {
                $pct    = (float)($part['porcentaje_participacion'] ?? 100.0);
                $cifras = calcularCifrasBase($pdo, $part['entity_type'], (int)$part['entity_id'], $periodo, $pct);
                if (!empty($cifras['missing'])) {
                    $errores[] = $part['entity_name'].' no tiene datos financieros para el período '.$periodo.'.';
                    continue;
                }
                foreach ($camposBase as $campo) {
                    $cifrasAgregadas[$campo] += $cifras[$campo];
                }
                $pctTotal += $pct;
                $detallePartes[] = [
                    'entity_name' => $part['entity_name'],
                    'entity_type' => $part['entity_type'],
                    'porcentaje'  => $pct,
                    'cifras'      => $cifras,          // JS usa p.cifras.activo_corriente etc.
                ];
            }

            $cotejoConjunto = calcularIndicadores($cifrasAgregadas, $indicadores);
            echo json_encode([
                'success'              => true,
                'periodo'              => $periodo,
                'cotejo_conjunto'      => $cotejoConjunto,      // JS usa res.cotejo_conjunto
                'cifras_agregadas'     => $cifrasAgregadas,     // JS usa res.cifras_agregadas
                'detalle_participantes'=> $detallePartes,       // JS usa res.detalle_participantes
                'porcentaje_total'     => $pctTotal,            // JS usa res.porcentaje_total
                'errores'              => $errores,             // JS usa res.errores
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    }

} catch (PDOException $e) {
    error_log("Error API Procesos: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Devuelve las cifras base ponderadas por el % de participación en el proceso.
 * - Company: cifras propias × (pct/100)
 * - Consortium: suma de cifras de cada miembro × (peso_miembro/100) × (pct/100)
 */
function calcularCifrasBase(PDO $pdo, string $entityType, int $entityId, int $periodo, float $porcentaje = 100.0): array {
    $campos = ['activo_corriente','activo_no_corriente','total_activos',
               'pasivo_corriente','pasivo_no_corriente','total_pasivos',
               'patrimonio','ingresos_operacionales','utilidad_neta'];
    $zeros  = array_fill_keys($campos, 0.0);
    $factor = $porcentaje / 100.0;

    if ($entityType === 'Company') {
        $stmt = $pdo->prepare("SELECT * FROM empresa_estados_financieros WHERE company_id=? AND periodo=? LIMIT 1");
        $stmt->execute([$entityId, $periodo]);
        $row = $stmt->fetch();
        if (!$row) return array_merge($zeros, ['missing' => true]);
        $cifras = [];
        foreach ($campos as $c) { $cifras[$c] = (float)$row[$c] * $factor; }
        return $cifras;
    }

    if ($entityType === 'Consortium') {
        $stmt = $pdo->prepare("SELECT company_id, participation_percentage FROM consortium_members WHERE consortium_id=?");
        $stmt->execute([$entityId]);
        $members = $stmt->fetchAll();
        $cifras  = $zeros;
        foreach ($members as $m) {
            $mFactor = ((float)$m['participation_percentage'] / 100.0) * $factor;
            $stmt2   = $pdo->prepare("SELECT * FROM empresa_estados_financieros WHERE company_id=? AND periodo=? LIMIT 1");
            $stmt2->execute([$m['company_id'], $periodo]);
            $row = $stmt2->fetch();
            if (!$row) continue;
            foreach ($campos as $c) { $cifras[$c] += (float)$row[$c] * $mFactor; }
        }
        return $cifras;
    }

    return array_merge($zeros, ['missing' => true]);
}

/**
 * Calcula los indicadores financieros a partir de cifras base y los compara
 * con los valores requeridos del proceso.
 */
function calcularIndicadores(array $cifras, array $indicadores): array {
    $ac  = $cifras['activo_corriente'];
    $anc = $cifras['activo_no_corriente'];
    $ta  = $cifras['total_activos'];
    $pc  = $cifras['pasivo_corriente'];
    $tp  = $cifras['total_pasivos'];
    $pat = $cifras['patrimonio'];
    $ut  = $cifras['utilidad_neta'];

    $calculos = [
        'razon_corriente'     => $pc  > 0 ? $ac  / $pc          : null,
        'nivel_endeudamiento' => $ta  > 0 ? ($tp  / $ta) * 100  : null,
        'capital_trabajo'     => $ac  - $pc,
        'patrimonio'          => $pat,
        'roa'                 => $ta  > 0 ? ($ut  / $ta) * 100  : null,
        'roe'                 => $pat > 0 ? ($ut  / $pat) * 100 : null,
    ];

    $resultados = [];
    foreach ($indicadores as $ind) {
        $key    = $ind['indicador'];
        $valor  = $calculos[$key] ?? null;
        $req    = (float)$ind['valor_requerido'];
        $op     = $ind['operador'];

        $cumple = null;
        if ($valor !== null) {
            if ($op === '>=') $cumple = $valor >= $req;
            elseif ($op === '<=') $cumple = $valor <= $req;
            elseif ($op === '=')  $cumple = abs($valor - $req) < 0.001;
        }

        $meta = INDICADORES[$key] ?? [];
        $resultados[] = [
            'indicador'       => $key,
            'label'           => $meta['label']    ?? ($ind['label'] ?? $key),
            'unidad'          => $meta['unidad']   ?? '',
            'decimales'       => $meta['decimales'] ?? 2,
            'valor_calculado' => $valor,
            'operador'        => $op,
            'valor_requerido' => $req,
            'cumple'          => $cumple,
        ];
    }
    return $resultados;
}
