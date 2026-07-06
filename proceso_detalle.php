<?php
require_once 'config.php';
require_once 'jwt_helper.php';

$jwt = $_COOKIE['gesdoc_token'] ?? null;
$userData = $jwt ? JWT::decode($jwt, JWT_SECRET) : null;
if (!$userData) { setcookie('gesdoc_token','',time()-3600,"/"); header("Location: login.php"); exit; }

$procesoId = (int)($_GET['id'] ?? 0);
if (!$procesoId) { header("Location: procesos.php"); exit; }

$userName = $userData['user_name'];
$userRole = $userData['user_role'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle Proceso - GesDoc</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
    <style>
        .nav-tabs .nav-link { color: #64748b; font-weight: 500; }
        .nav-tabs .nav-link.active { color: var(--primary-blue); font-weight: 700; border-bottom: 2px solid var(--primary-blue); }
        .semaforo-ok   { color: #16a34a; }
        .semaforo-fail { color: #dc2626; }
        .semaforo-nd   { color: #94a3b8; }
        .cotejo-row td { vertical-align: middle; }
        .ind-toggle { cursor: pointer; }
        .cifras-base-table { font-size:.8rem; }
        .badge-company    { background:#dbeafe; color:#1e40af; }
        .badge-consortium { background:#ede9fe; color:#6d28d9; }
        .badge-preparacion { background:#e2e8f0; color:#475569; }
        .badge-presentada  { background:#dbeafe; color:#1e40af; }
        .badge-adjudicada  { background:#d1fae5; color:#065f46; }
        .badge-desierta    { background:#fef3c7; color:#92400e; }
        .badge-cancelada   { background:#fee2e2; color:#991b1b; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-gesdoc">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
        <i class="fa-solid fa-folder-tree me-2"></i> GesDoc
    </a>
    <div class="d-flex align-items-center ms-auto">
      <div class="dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
           <i class="fa-solid fa-circle-user fs-4 me-2" style="color:var(--primary-blue);"></i>
           <div>
               <span class="d-block fw-bold text-dark" style="line-height:1.2;"><?= htmlspecialchars($userName) ?></span>
               <small class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($userRole) ?></small>
           </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<div class="container-fluid py-4 px-4">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb p-2 bg-white rounded shadow-sm border">
        <li class="breadcrumb-item"><a href="index.php" style="color:var(--primary-blue);text-decoration:none;"><i class="fa-solid fa-house"></i> Inicio</a></li>
        <li class="breadcrumb-item"><a href="procesos.php" style="color:var(--primary-blue);text-decoration:none;">Procesos</a></li>
        <li class="breadcrumb-item active" id="breadcrumbNombre">Cargando...</li>
      </ol>
    </nav>

    <!-- Cabecera del proceso -->
    <div class="card shadow-sm border-0 mb-3">
      <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div>
            <h4 class="mb-1" id="headerNombre" style="color:var(--primary-blue);">—</h4>
            <span class="text-muted me-3" id="headerEntidad"></span>
            <span class="badge bg-secondary me-2" id="headerModalidad"></span>
            <span class="badge" id="headerEstado"></span>
          </div>
          <div class="text-end">
            <div class="text-muted" style="font-size:.82rem;">Período financiero</div>
            <div class="fw-bold fs-5" id="headerPeriodo" style="color:var(--primary-blue);">—</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pestañas -->
    <ul class="nav nav-tabs mb-0" id="detalleTab">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-general">
            <i class="fa-solid fa-circle-info me-1"></i> Datos Generales</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-requisitos">
            <i class="fa-solid fa-shield-halved me-1"></i> Requisitos Habilitantes</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-indicadores">
            <i class="fa-solid fa-chart-bar me-1"></i> Indicadores Requeridos</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-criterios">
            <i class="fa-solid fa-list-check me-1"></i> Criterios Puntuables</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-participantes">
            <i class="fa-solid fa-users me-1"></i> Participantes y Análisis</a></li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-white p-4 shadow-sm">

        <!-- ══ PESTAÑA 1: DATOS GENERALES ══════════════════════════ -->
        <div class="tab-pane fade show active" id="tab-general">
            <form id="formGeneral">
              <input type="hidden" name="id" value="<?= $procesoId ?>">
              <input type="hidden" name="action" value="update">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label">Nombre del Proceso *</label>
                  <input type="text" class="form-control text-uppercase" id="gNombre" name="nombre" required
                         oninput="this.value=this.value.toUpperCase()">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Número de Proceso</label>
                  <input type="text" class="form-control" id="gNumero" name="numero_proceso">
                </div>
                <div class="col-md-8">
                  <label class="form-label">Entidad Contratante *</label>
                  <input type="text" class="form-control text-uppercase" id="gEntidad" name="entidad_contratante" required
                         oninput="this.value=this.value.toUpperCase()">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Modalidad</label>
                  <select class="form-select" id="gModalidad" name="modalidad">
                    <option>Licitación Pública</option>
                    <option>Selección Abreviada</option>
                    <option>Concurso de Méritos</option>
                    <option>Contratación Directa</option>
                    <option>Mínima Cuantía</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Objeto del Contrato</label>
                  <textarea class="form-control" id="gObjeto" name="objeto" rows="3"></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Presupuesto Oficial ($)</label>
                  <input type="number" step="0.01" class="form-control" id="gPresupuesto" name="presupuesto_oficial">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Período Financiero *</label>
                  <input type="number" class="form-control" id="gPeriodo" name="periodo_financiero" min="2000" max="2099" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Fecha Apertura</label>
                  <input type="date" class="form-control" id="gApertura" name="fecha_apertura">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Fecha Cierre</label>
                  <input type="date" class="form-control" id="gCierre" name="fecha_cierre">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Estado</label>
                  <select class="form-select" id="gEstado" name="estado">
                    <option>En Preparación</option>
                    <option>Presentada</option>
                    <option>Adjudicada</option>
                    <option>Desierta</option>
                    <option>Cancelada</option>
                  </select>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save me-1"></i> Guardar cambios
                  </button>
                </div>
              </div>
            </form>
        </div>

        <!-- ══ PESTAÑA 2: REQUISITOS HABILITANTES ══════════════════ -->
        <div class="tab-pane fade" id="tab-requisitos">

            <!-- Agregar requisito -->
            <div class="card border-0 bg-light p-3 mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold">Tipo</label>
                        <select class="form-select form-select-sm" id="selTipoReq">
                            <option>Jurídico</option>
                            <option>Técnico</option>
                            <option>Financiero</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label form-label-sm fw-semibold">Descripción del requisito *</label>
                        <input type="text" class="form-control form-control-sm" id="inputDescReq"
                               placeholder="Ej: RUT vigente con actividad económica relacionada">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm w-100" onclick="saveRequisito()">
                            <i class="fa-solid fa-plus me-1"></i> Agregar
                        </button>
                    </div>
                </div>
                <input type="hidden" id="editReqId" value="">
            </div>

            <!-- Lista de requisitos agrupada por tipo -->
            <div id="requisitosLista">
                <div class="text-center text-muted py-4">
                    <i class="fa-solid fa-shield-halved fa-2x mb-2 d-block" style="color:#cbd5e1;"></i>
                    Sin requisitos registrados.
                </div>
            </div>

            <!-- Matriz de cumplimiento -->
            <div id="matrizCumplimientoWrap" class="mt-4" style="display:none">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0" style="color:var(--primary-blue);">
                        <i class="fa-solid fa-table me-1"></i> Matriz de Cumplimiento
                    </h6>
                    <small class="text-muted">Registra si cada participante cumple cada requisito</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="matrizTable">
                        <thead id="matrizHead" class="table-light"></thead>
                        <tbody id="matrizBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ══ PESTAÑA 3: INDICADORES REQUERIDOS ═══════════════════ -->
        <div class="tab-pane fade" id="tab-indicadores">
            <p class="text-muted mb-3" style="font-size:.88rem;">
                Activa los indicadores que exige esta licitación y define el valor umbral requerido.
            </p>
            <div id="indForm">
                <!-- Se rellena dinámicamente -->
            </div>
            <button class="btn btn-primary mt-3" onclick="saveIndicadores()">
                <i class="fa-solid fa-save me-1"></i> Guardar indicadores
            </button>
        </div>

        <!-- ══ PESTAÑA 3: CRITERIOS PUNTUABLES ═════════════════════ -->
        <div class="tab-pane fade" id="tab-criterios">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="text-muted mb-0" style="font-size:.88rem;">Factores de calificación y su puntaje máximo.</p>
                <button class="btn btn-sm btn-success" onclick="newCriterio()">
                    <i class="fa-solid fa-plus me-1"></i> Agregar criterio
                </button>
            </div>
            <table class="table table-sm table-bordered" id="criteriosTable">
                <thead class="table-light">
                    <tr>
                        <th>Criterio</th>
                        <th class="text-center" style="width:130px">Puntaje Máx.</th>
                        <th>Descripción</th>
                        <th class="text-center" style="width:90px">Acc.</th>
                    </tr>
                </thead>
                <tbody id="criteriosTbody"></tbody>
                <tfoot>
                    <tr class="table-light">
                        <td class="fw-bold">TOTAL</td>
                        <td class="text-center fw-bold" id="criteriosTotalPuntaje">0</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- ══ PESTAÑA 4: PARTICIPANTES Y ANÁLISIS ═════════════════ -->
        <div class="tab-pane fade" id="tab-participantes">
            <!-- Agregar participante -->
            <div class="card border-0 bg-light p-3 mb-4">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label form-label-sm fw-semibold">Agregar participante</label>
                        <select class="form-select form-select-sm" id="selEntity">
                            <option value="">-- Seleccionar empresa o consorcio --</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold">% Participación</label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control form-control-sm" id="inputPorcentaje"
                                   value="100" min="0.01" max="100" step="0.01">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label form-label-sm fw-semibold">Estado inicial</label>
                        <select class="form-select form-select-sm" id="selEstadoPart">
                            <option>En Análisis</option>
                            <option>Presentada</option>
                            <option>No Presentada</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex flex-column gap-1">
                        <button class="btn btn-primary btn-sm" onclick="addParticipante()">
                            <i class="fa-solid fa-plus me-1"></i> Agregar
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="runCotejoConjunto()" id="btnCotejoConjunto" style="display:none">
                            <i class="fa-solid fa-layer-group me-1"></i> Análisis conjunto
                        </button>
                    </div>
                </div>
                <!-- Indicador total % -->
                <div class="mt-2" id="totalPctRow" style="display:none">
                    <small>Total participación:
                        <strong id="totalPctVal">0%</strong>
                        <span id="totalPctMsg" class="ms-1"></span>
                    </small>
                </div>
            </div>

            <!-- Lista de participantes con análisis -->
            <div id="participantesList"></div>

            <!-- Panel de análisis conjunto -->
            <div id="cotejoConjuntoPanel" style="display:none" class="mt-2"></div>
        </div>

    </div><!-- /tab-content -->
</div><!-- /container -->

<!-- Modal Criterio -->
<div class="modal fade" id="criterioModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title" style="color:var(--primary-blue);">Criterio Puntuable</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="criterioForm">
        <div class="modal-body">
          <input type="hidden" id="criterioId" name="id">
          <input type="hidden" name="proceso_id" value="<?= $procesoId ?>">
          <div class="mb-3">
            <label class="form-label">Nombre del criterio *</label>
            <input type="text" class="form-control" id="cNombre" name="nombre" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Puntaje máximo</label>
            <input type="number" step="0.01" class="form-control" id="cPuntaje" name="puntaje_maximo" value="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" id="cDesc" name="descripcion" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save me-1"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="assets/js/jquery-3.7.0.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>
<script src="assets/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/sweetalert2.all.min.js"></script>
<script src="assets/js/helpers.js?v=<?= APP_VERSION ?>"></script>

<script>
const PROCESO_ID = <?= $procesoId ?>;
const jwtToken   = localStorage.getItem('gesdoc_jwt');
$.ajaxSetup({ headers: { 'Authorization': 'Bearer ' + jwtToken } });

const estadoBadgeMap = {
    'En Preparación': 'badge-preparacion',
    'Presentada':     'badge-presentada',
    'Adjudicada':     'badge-adjudicada',
    'Desierta':       'badge-desierta',
    'Cancelada':      'badge-cancelada',
};
const partEstadoBadge = {
    'En Análisis':    'bg-secondary',
    'Presentada':     'bg-primary',
    'No Presentada':  'bg-danger',
};

// ── INICIALIZACIÓN ────────────────────────────────────────────────────────────
$(document).ready(function() {
    loadProceso();
    loadIndicadoresForm();
    loadCriterios();
    loadEntities();
    loadParticipantes();

    // Cambio de pestaña → recargar datos
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        const t = $(e.target).attr('href');
        if (t === '#tab-requisitos')   loadRequisitos();
        if (t === '#tab-indicadores') loadIndicadoresForm();
        if (t === '#tab-criterios')   loadCriterios();
        if (t === '#tab-participantes') { loadEntities(); loadParticipantes(); }
    });

    // Formulario datos generales
    $('#formGeneral').on('submit', function(e) {
        e.preventDefault();
        $.post('api/procesos.php', $(this).serialize(), function(res) {
            if (res.success) {
                loadProceso();
                Swal.fire({icon:'success', title:res.message, timer:1500, showConfirmButton:false});
            } else Swal.fire('Error', res.message, 'error');
        }, 'json');
    });

    // Formulario criterio
    $('#criterioForm').on('submit', function(e) {
        e.preventDefault();
        $.post('api/procesos.php', $(this).serialize() + '&action=criterio_save', function(res) {
            if (res.success) {
                $('#criterioModal').modal('hide');
                loadCriterios();
                Swal.fire({icon:'success', title:res.message, timer:1200, showConfirmButton:false});
            } else Swal.fire('Error', res.message, 'error');
        }, 'json');
    });
});

// ── DATOS GENERALES ───────────────────────────────────────────────────────────
function loadProceso() {
    $.get('api/procesos.php', { action:'get', id:PROCESO_ID }, function(res) {
        if (!res.success) return;
        const p = res.data;
        // Cabecera
        $('#breadcrumbNombre').text(p.nombre);
        $('#headerNombre').text(p.nombre);
        $('#headerEntidad').text(p.entidad_contratante);
        $('#headerModalidad').text(p.modalidad);
        $('#headerEstado').attr('class', `badge ${estadoBadgeMap[p.estado]||'badge-preparacion'}`).text(p.estado);
        $('#headerPeriodo').text(p.periodo_financiero);
        document.title = p.nombre + ' — GesDoc';
        // Formulario
        $('#gNombre').val(p.nombre);
        $('#gNumero').val(p.numero_proceso);
        $('#gEntidad').val(p.entidad_contratante);
        $('#gModalidad').val(p.modalidad);
        $('#gObjeto').val(p.objeto);
        $('#gPresupuesto').val(p.presupuesto_oficial);
        $('#gPeriodo').val(p.periodo_financiero);
        $('#gApertura').val(p.fecha_apertura);
        $('#gCierre').val(p.fecha_cierre);
        $('#gEstado').val(p.estado);
    }, 'json');
}

// ── INDICADORES ───────────────────────────────────────────────────────────────
let catalogoIndicadores = [];

function loadIndicadoresForm() {
    // Cargar catálogo y datos guardados en paralelo
    $.when(
        $.get('api/procesos.php?action=get_indicadores_catalogo'),
        $.get(`api/procesos.php?action=indicadores_list&proceso_id=${PROCESO_ID}`)
    ).done(function(catRes, savedRes) {
        catalogoIndicadores = catRes[0].data || [];
        const saved = {};
        (savedRes[0].data || []).forEach(r => { saved[r.indicador] = r; });

        let html = '<div class="table-responsive"><table class="table table-bordered align-middle">';
        html += '<thead class="table-light"><tr><th style="width:40px">Activo</th><th>Indicador</th><th style="width:120px">Operador</th><th style="width:200px">Valor Requerido</th><th>Unidad</th></tr></thead><tbody>';

        catalogoIndicadores.forEach(ind => {
            const sv   = saved[ind.key] || {};
            const chk  = sv.habilitado !== undefined ? sv.habilitado : 1;
            const op   = sv.operador || '>=';
            const val  = sv.valor_requerido !== undefined ? sv.valor_requerido : '';
            html += `<tr>
                <td class="text-center">
                    <div class="form-check form-switch d-flex justify-content-center">
                        <input class="form-check-input ind-toggle" type="checkbox" data-key="${ind.key}"
                               id="chk_${ind.key}" ${chk ? 'checked' : ''}>
                    </div>
                </td>
                <td><label for="chk_${ind.key}" class="form-label mb-0 fw-semibold">${ind.label}</label></td>
                <td>
                    <select class="form-select form-select-sm" id="op_${ind.key}">
                        ${['>=','<=','>','<','='].map(o=>`<option ${o===op?'selected':''}>${o}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <input type="number" step="0.0001" class="form-control form-control-sm"
                           id="val_${ind.key}" value="${val}" placeholder="0">
                </td>
                <td><span class="text-muted">${ind.unidad}</span></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        $('#indForm').html(html);
    });
}

function saveIndicadores() {
    const indicadores = catalogoIndicadores.map(ind => ({
        indicador:       ind.key,
        operador:        $(`#op_${ind.key}`).val(),
        valor_requerido: $(`#val_${ind.key}`).val() || 0,
        habilitado:      $(`#chk_${ind.key}`).is(':checked') ? 1 : 0,
    }));
    $.post('api/procesos.php', {
        action:      'indicadores_save',
        proceso_id:  PROCESO_ID,
        indicadores: JSON.stringify(indicadores)
    }, function(res) {
        if (res.success) Swal.fire({icon:'success', title:res.message, timer:1500, showConfirmButton:false});
        else Swal.fire('Error', res.message, 'error');
    }, 'json');
}

// ── CRITERIOS ─────────────────────────────────────────────────────────────────
function loadCriterios() {
    $.get(`api/procesos.php?action=criterios_list&proceso_id=${PROCESO_ID}`, function(res) {
        const rows = res.data || [];
        let total = 0, html = '';
        rows.forEach(r => {
            total += parseFloat(r.puntaje_maximo) || 0;
            html += `<tr>
                <td>${r.nombre}</td>
                <td class="text-center">${parseFloat(r.puntaje_maximo).toFixed(2)}</td>
                <td><small class="text-muted">${r.descripcion || '—'}</small></td>
                <td class="text-center">
                    <button class="btn btn-xs btn-outline-primary me-1 btn-sm" onclick='editCriterio(${JSON.stringify(r)})'>
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-danger btn-sm" onclick="deleteCriterio(${r.id})">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        });
        if (!rows.length) html = '<tr><td colspan="4" class="text-center text-muted py-3">Sin criterios registrados.</td></tr>';
        $('#criteriosTbody').html(html);
        $('#criteriosTotalPuntaje').text(total.toFixed(2));
    }, 'json');
}

function newCriterio() {
    $('#criterioForm')[0].reset();
    $('#criterioId').val('');
    $('#criterioModal').modal('show');
}

function editCriterio(r) {
    $('#criterioId').val(r.id);
    $('#cNombre').val(r.nombre);
    $('#cPuntaje').val(r.puntaje_maximo);
    $('#cDesc').val(r.descripcion);
    $('#criterioModal').modal('show');
}

function deleteCriterio(id) {
    Swal.fire({ title:'¿Eliminar criterio?', icon:'warning', showCancelButton:true,
        confirmButtonText:'Sí', cancelButtonText:'Cancelar', confirmButtonColor:'#d33'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/procesos.php', { action:'criterio_delete', id }, function(res) {
                if (res.success) { loadCriterios(); }
            }, 'json');
        }
    });
}

// ── PARTICIPANTES ─────────────────────────────────────────────────────────────
function loadEntities() {
    $.get('api/procesos.php?action=get_entities', function(res) {
        const $sel = $('#selEntity').html('<option value="">-- Seleccionar empresa o consorcio --</option>');
        (res.data||[]).forEach(e => {
            const label = `${e.type === 'Company' ? '🏢' : '🤝'} ${e.name} (${e.nit||'—'})`;
            $sel.append(`<option value="${e.type}|${e.id}">${label}</option>`);
        });
    }, 'json');
}

function addParticipante() {
    const val = $('#selEntity').val();
    if (!val) { Swal.fire({icon:'warning', title:'Selecciona un participante', timer:1200, showConfirmButton:false}); return; }
    const [type, id] = val.split('|');
    const pct = parseFloat($('#inputPorcentaje').val()) || 100;
    $.post('api/procesos.php', {
        action:                   'participacion_save',
        proceso_id:               PROCESO_ID,
        entity_type:              type,
        entity_id:                id,
        porcentaje_participacion: pct,
        estado:                   $('#selEstadoPart').val()
    }, function(res) {
        if (res.success) {
            loadParticipantes();
            $('#selEntity').val('');
            $('#inputPorcentaje').val(100);
            Swal.fire({icon:'success',title:res.message,timer:1200,showConfirmButton:false});
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

function loadParticipantes() {
    $.get(`api/procesos.php?action=participaciones_list&proceso_id=${PROCESO_ID}`, function(res) {
        const parts = res.data || [];

        // ─ Actualizar contador de % ─
        const companies = parts.filter(p => p.entity_type === 'Company');
        const totalPct  = companies.reduce((s, p) => s + parseFloat(p.porcentaje_participacion || 0), 0);
        if (parts.length) {
            $('#totalPctRow').show();
            $('#btnCotejoConjunto').show();
            const pctRound = Math.round(totalPct * 100) / 100;
            $('#totalPctVal').text(pctRound + '%');
            if (Math.abs(totalPct - 100) < 0.01) {
                $('#totalPctVal').css('color','#16a34a');
                $('#totalPctMsg').html('<span style="color:#16a34a"><i class="fa-solid fa-check-circle"></i> Suma 100%</span>');
            } else if (totalPct < 100) {
                $('#totalPctVal').css('color','#d97706');
                $('#totalPctMsg').html('<span style="color:#d97706"><i class="fa-solid fa-triangle-exclamation"></i> Falta ' + Math.round((100-totalPct)*100)/100 + '%</span>');
            } else {
                $('#totalPctVal').css('color','#dc2626');
                $('#totalPctMsg').html('<span style="color:#dc2626"><i class="fa-solid fa-circle-xmark"></i> Excede 100%</span>');
            }
        } else {
            $('#totalPctRow').hide();
            $('#btnCotejoConjunto').hide();
        }

        if (!parts.length) {
            $('#participantesList').html('<div class="text-center text-muted py-4"><i class="fa-solid fa-users fa-2x mb-2 d-block" style="color:#cbd5e1;"></i>Sin participantes aún.</div>');
            return;
        }
        let html = '';
        parts.forEach(p => {
            const badgeType = p.entity_type === 'Company' ? 'badge-company' : 'badge-consortium';
            const icon      = p.entity_type === 'Company' ? 'fa-building' : 'fa-handshake';
            const pct       = parseFloat(p.porcentaje_participacion || 100).toFixed(2);
            html += `
            <div class="card border-0 shadow-sm mb-3" id="part-card-${p.id}">
              <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge ${badgeType}">
                    <i class="fa-solid ${icon} me-1"></i>${p.entity_type === 'Company' ? 'Empresa' : 'Consorcio'}
                  </span>
                  <strong>${p.entity_name}</strong>
                  <small class="text-muted">${p.entity_nit || ''}</small>
                  <span class="badge bg-secondary" title="Porcentaje de participación en el proceso">${pct}%</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <!-- Editar % inline -->
                  <div class="input-group input-group-sm" style="width:120px" title="Editar % participación">
                    <span class="input-group-text px-1" style="font-size:.75rem">%</span>
                    <input type="number" class="form-control form-control-sm pct-input text-center" style="max-width:60px"
                           value="${pct}" min="0.01" max="100" step="0.01" id="pct-${p.id}"
                           onkeydown="if(event.key==='Enter'){event.preventDefault();updatePorcentaje(${p.id});}">
                    <button class="btn btn-success btn-sm" onclick="updatePorcentaje(${p.id})" title="Guardar %">
                        <i class="fa-solid fa-check" style="font-size:.7rem"></i>
                    </button>
                  </div>
                  <select class="form-select form-select-sm" style="width:150px"
                          onchange="updateEstadoPart(${p.id}, this.value)">
                    ${['En Análisis','Presentada','No Presentada'].map(s =>
                        `<option ${s===p.estado?'selected':''}>${s}</option>`).join('')}
                  </select>
                  <button class="btn btn-sm btn-success" onclick="runCotejo(${p.id})" title="Calcular indicadores">
                    <i class="fa-solid fa-calculator me-1"></i> Analizar
                  </button>
                  <button class="btn btn-sm btn-outline-danger" onclick="deleteParticipante(${p.id})" title="Quitar">
                    <i class="fa-solid fa-xmark"></i>
                  </button>
                </div>
              </div>
              <div class="card-body p-0" id="cotejo-${p.id}">
                <div class="text-center text-muted py-3" style="font-size:.85rem;">
                  Haz clic en <strong>Analizar</strong> para calcular los indicadores financieros.
                </div>
              </div>
            </div>`;
        });
        $('#participantesList').html(html);
    }, 'json');
}

function updateEstadoPart(id, estado) {
    $.post('api/procesos.php', { action:'participacion_update_estado', id, estado }, function(){}, 'json');
}

function updatePorcentaje(id) {
    const pct = parseFloat($(`#pct-${id}`).val());
    if (!pct || pct <= 0 || pct > 100) {
        Swal.fire({icon:'warning', title:'Porcentaje inválido', text:'Debe estar entre 0.01 y 100', timer:1800, showConfirmButton:false});
        return;
    }
    $.post('api/procesos.php', {
        action:                   'participacion_update_estado',
        id,
        porcentaje_participacion: pct,
        estado:                   $(`#part-card-${id} select`).val()
    }, function(res) {
        if (res.success) {
            loadParticipantes();
            Swal.fire({icon:'success', title:'% actualizado', timer:1000, showConfirmButton:false});
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

function deleteParticipante(id) {
    Swal.fire({ title:'¿Quitar participante?', icon:'warning', showCancelButton:true,
        confirmButtonText:'Sí', cancelButtonText:'Cancelar', confirmButtonColor:'#d33'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/procesos.php', { action:'participacion_delete', id }, function(res) {
                if (res.success) loadParticipantes();
            }, 'json');
        }
    });
}

// ── COTEJO ────────────────────────────────────────────────────────────────────
function runCotejo(participacionId) {
    const $c = $(`#cotejo-${participacionId}`);
    $c.html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Calculando...</div>');

    $.get('api/procesos.php', { action:'calcular_cotejo', participacion_id: participacionId }, function(res) {
        if (!res.success) {
            $c.html(`<div class="alert alert-warning m-3"><i class="fa-solid fa-triangle-exclamation me-2"></i>${res.message}</div>`);
            return;
        }

        const cotejo = res.cotejo;
        const cifras = res.cifras_base;
        const fmt    = v => GesDocHelpers.formatCurrency(v);
        const cumplidos = cotejo.filter(r => r.cumple === true).length;
        const total     = cotejo.length;

        // ─ Resumen ─
        let html = `<div class="p-3">`;

        // Cifras base ponderadas
        if (cifras._detalle && cifras._detalle.length > 1) {
            html += `<div class="mb-3">
                <p class="fw-semibold mb-1" style="font-size:.82rem;color:#64748b;">CIFRAS PONDERADAS DEL CONSORCIO (período ${res.proceso.periodo_financiero})</p>
                <div class="table-responsive">
                <table class="table table-xs table-bordered cifras-base-table">
                  <thead class="table-light"><tr>
                    <th>Empresa</th><th>Part. %</th>
                    <th class="text-end">Act. Cte.</th><th class="text-end">Act. Total</th>
                    <th class="text-end">Pas. Cte.</th><th class="text-end">Pas. Total</th>
                    <th class="text-end">Patrimonio</th><th class="text-end">Util. Neta</th>
                  </tr></thead><tbody>`;
            cifras._detalle.forEach(d => {
                const ef = d.ef;
                html += `<tr>
                    <td>${ef.company_id}</td>
                    <td class="text-center">${d.participacion}%</td>
                    <td class="text-end">${fmt(ef.activo_corriente)}</td>
                    <td class="text-end">${fmt(ef.total_activos)}</td>
                    <td class="text-end">${fmt(ef.pasivo_corriente)}</td>
                    <td class="text-end">${fmt(ef.total_pasivos)}</td>
                    <td class="text-end">${fmt(parseFloat(ef.total_activos)-parseFloat(ef.total_pasivos))}</td>
                    <td class="text-end">${fmt(ef.utilidad_neta)}</td>
                </tr>`;
            });
            html += `</tbody><tfoot class="table-light fw-bold"><tr>
                    <td>PONDERADO</td><td></td>
                    <td class="text-end">${fmt(cifras.activo_corriente)}</td>
                    <td class="text-end">${fmt(cifras.total_activos)}</td>
                    <td class="text-end">${fmt(cifras.pasivo_corriente)}</td>
                    <td class="text-end">${fmt(cifras.total_pasivos)}</td>
                    <td class="text-end">${fmt(cifras.total_activos - cifras.total_pasivos)}</td>
                    <td class="text-end">${fmt(cifras.utilidad_neta)}</td>
                </tr></tfoot></table></div></div>`;
        }

        // Tabla de cotejo
        const semColor = ok => ok === true ? 'semaforo-ok' : ok === false ? 'semaforo-fail' : 'semaforo-nd';
        const semIcon  = ok => ok === true ? 'fa-circle-check' : ok === false ? 'fa-circle-xmark' : 'fa-circle-question';
        const semText  = ok => ok === true ? 'CUMPLE' : ok === false ? 'NO CUMPLE' : 'S/D';

        html += `
        <div class="d-flex align-items-center mb-2 gap-2">
            <span class="fw-semibold" style="font-size:.85rem;color:#64748b;">COTEJO DE INDICADORES</span>
            <span class="badge ${cumplidos === total ? 'bg-success' : cumplidos > 0 ? 'bg-warning text-dark' : 'bg-danger'}">
                ${cumplidos} / ${total} indicadores cumplidos
            </span>
        </div>
        <div class="table-responsive">
        <table class="table table-bordered align-middle cotejo-row">
          <thead class="table-light">
            <tr>
              <th>Indicador</th>
              <th class="text-center">Valor Calculado</th>
              <th class="text-center">Condición</th>
              <th class="text-center">Valor Requerido</th>
              <th class="text-center">Resultado</th>
            </tr>
          </thead>
          <tbody>`;

        cotejo.forEach(r => {
            const dec   = r.decimales;
            const valC  = r.valor_calculado !== null ? parseFloat(r.valor_calculado).toFixed(dec) : 'N/D';
            const valR  = parseFloat(r.valor_requerido).toFixed(dec);
            const unidad= r.unidad === '$' ? `<small class="text-muted ms-1">$</small>` : r.unidad === '%' ? '<small class="text-muted">%</small>' : '';
            html += `<tr>
                <td class="fw-semibold">${r.label}</td>
                <td class="text-center fs-6">${valC}${unidad}</td>
                <td class="text-center"><span class="badge bg-secondary fs-6">${r.operador}</span></td>
                <td class="text-center fs-6">${valR}${unidad}</td>
                <td class="text-center">
                    <span class="${semColor(r.cumple)} fw-bold">
                        <i class="fa-solid ${semIcon(r.cumple)} me-1"></i>${semText(r.cumple)}
                    </span>
                </td>
            </tr>`;
        });

        html += `</tbody></table></div></div>`;
        $c.html(html);
    }, 'json');
}

// ── REQUISITOS HABILITANTES ───────────────────────────────────────────────────
const TIPO_CONFIG = {
    'Jurídico':   { icon: 'fa-landmark',       color: '#1e40af', bg: '#dbeafe' },
    'Técnico':    { icon: 'fa-screwdriver-wrench', color: '#065f46', bg: '#d1fae5' },
    'Financiero': { icon: 'fa-coins',           color: '#92400e', bg: '#fef3c7' },
};

function loadRequisitos() {
    $.get(`api/procesos.php?action=cumplimiento_list&proceso_id=${PROCESO_ID}`, function(res) {
        const reqs  = res.requisitos     || [];
        const parts = res.participaciones || [];

        // ─ Lista de requisitos agrupada por tipo ─
        const grupos = { 'Jurídico': [], 'Técnico': [], 'Financiero': [] };
        reqs.forEach(r => { if (grupos[r.tipo]) grupos[r.tipo].push(r); });

        let listaHtml = '';
        Object.entries(grupos).forEach(([tipo, items]) => {
            if (!items.length) return;
            const cfg = TIPO_CONFIG[tipo];
            listaHtml += `
            <div class="mb-3">
              <div class="d-flex align-items-center mb-2">
                <span class="badge me-2 px-3 py-1" style="background:${cfg.bg};color:${cfg.color};font-size:.8rem;">
                    <i class="fa-solid ${cfg.icon} me-1"></i>${tipo}
                </span>
              </div>
              <table class="table table-sm table-bordered mb-0">
                <tbody>`;
            items.forEach(r => {
                listaHtml += `<tr>
                    <td>${r.descripcion}</td>
                    <td class="text-center" style="width:100px">
                        <button class="btn btn-xs btn-outline-primary btn-sm me-1" onclick='editRequisito(${JSON.stringify(r)})'>
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-xs btn-outline-danger btn-sm" onclick="deleteRequisito(${r.id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });
            listaHtml += `</tbody></table></div>`;
        });

        if (!reqs.length) {
            listaHtml = `<div class="text-center text-muted py-4">
                <i class="fa-solid fa-shield-halved fa-2x mb-2 d-block" style="color:#cbd5e1;"></i>Sin requisitos registrados.</div>`;
        }
        $('#requisitosLista').html(listaHtml);

        // ─ Matriz de cumplimiento ─
        if (!reqs.length || !parts.length) {
            $('#matrizCumplimientoWrap').hide();
            return;
        }
        $('#matrizCumplimientoWrap').show();

        // Cabecera: columna por participante
        let thead = `<tr><th style="min-width:200px">Requisito</th><th style="width:100px">Tipo</th>`;
        parts.forEach(p => {
            thead += `<th class="text-center" style="min-width:130px">${p.entity_name}<br>
                <small class="text-muted" style="font-size:.7rem">${parseFloat(p.porcentaje_participacion||100).toFixed(0)}%</small>
            </th>`;
        });
        thead += '</tr>';
        $('#matrizHead').html(thead);

        // Cuerpo: fila por requisito
        const cumpleOpts = ['Pendiente','Cumple','No Cumple'];
        const cumpleColor = { 'Pendiente': '#64748b', 'Cumple': '#16a34a', 'No Cumple': '#dc2626' };
        const cumpleIcon  = { 'Pendiente': 'fa-circle-question', 'Cumple': 'fa-circle-check', 'No Cumple': 'fa-circle-xmark' };

        let tbody = '';
        reqs.forEach(r => {
            const cfg = TIPO_CONFIG[r.tipo];
            tbody += `<tr>
                <td>${r.descripcion}</td>
                <td><span class="badge" style="background:${cfg.bg};color:${cfg.color};font-size:.7rem">
                    <i class="fa-solid ${cfg.icon} me-1"></i>${r.tipo}</span></td>`;
            parts.forEach(p => {
                const c   = r.cumplimientos?.[p.id] || {};
                const val = c.cumple || 'Pendiente';
                const col = cumpleColor[val];
                const ico = cumpleIcon[val];
                const obs = (c.observacion || '').replace(/'/g,"&#39;");
                tbody += `<td class="text-center p-1">
                    <select class="form-select form-select-sm" style="font-size:.75rem;border-color:${col};color:${col}"
                            onchange="saveCumplimiento(${r.id}, ${p.id}, this.value, '${obs}')"
                            title="${obs}">
                        ${cumpleOpts.map(o => `<option value="${o}" ${o===val?'selected':''}
                            style="color:${cumpleColor[o]}">${o}</option>`).join('')}
                    </select>
                </td>`;
            });
            tbody += '</tr>';
        });
        $('#matrizBody').html(tbody);
    }, 'json');
}

function saveRequisito() {
    const id   = $('#editReqId').val();
    const tipo = $('#selTipoReq').val();
    const desc = $('#inputDescReq').val().trim();
    if (!desc) { Swal.fire({icon:'warning', title:'Ingresa la descripción', timer:1200, showConfirmButton:false}); return; }

    $.post('api/procesos.php', {
        action:      'requisito_save',
        id:          id || '',
        proceso_id:  PROCESO_ID,
        tipo,
        descripcion: desc
    }, function(res) {
        if (res.success) {
            $('#editReqId').val('');
            $('#inputDescReq').val('');
            loadRequisitos();
            Swal.fire({icon:'success', title:res.message, timer:1200, showConfirmButton:false});
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
}

function editRequisito(r) {
    $('#editReqId').val(r.id);
    $('#selTipoReq').val(r.tipo);
    $('#inputDescReq').val(r.descripcion);
    $('#inputDescReq').focus();
}

function deleteRequisito(id) {
    Swal.fire({ title:'¿Eliminar requisito?', icon:'warning', showCancelButton:true,
        confirmButtonText:'Sí', cancelButtonText:'Cancelar', confirmButtonColor:'#d33'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/procesos.php', { action:'requisito_delete', id }, function(res) {
                if (res.success) loadRequisitos();
            }, 'json');
        }
    });
}

function saveCumplimiento(requisitoId, participacionId, cumple, observacion) {
    $.post('api/procesos.php', {
        action:           'cumplimiento_save',
        requisito_id:     requisitoId,
        participacion_id: participacionId,
        cumple,
        observacion
    }, function(res) {
        if (!res.success) Swal.fire('Error', res.message || 'No se pudo guardar.', 'error');
        // Recargar para actualizar colores del select
        else loadRequisitos();
    }, 'json');
}

// ── COTEJO CONJUNTO DEL PROCESO ───────────────────────────────────────────────
function runCotejoConjunto() {
    const $panel = $('#cotejoConjuntoPanel');
    $panel.html('<div class="text-center py-4"><div class="spinner-border text-primary"></div> Calculando análisis conjunto...</div>').show();
    // Scroll al panel
    $('html,body').animate({ scrollTop: $panel.offset().top - 80 }, 400);

    $.get('api/procesos.php', { action:'calcular_cotejo_proceso', proceso_id: PROCESO_ID }, function(res) {
        if (!res.success) {
            $panel.html(`<div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>${res.message}</div>`);
            return;
        }

        const fmt     = v => GesDocHelpers.formatCurrency(v);
        const semColor = ok => ok === true ? 'semaforo-ok' : ok === false ? 'semaforo-fail' : 'semaforo-nd';
        const semIcon  = ok => ok === true ? 'fa-circle-check' : ok === false ? 'fa-circle-xmark' : 'fa-circle-question';
        const semText  = ok => ok === true ? 'CUMPLE' : ok === false ? 'NO CUMPLE' : 'S/D';

        const cotejo    = res.cotejo_conjunto || [];
        const cumplidos = cotejo.filter(r => r.cumple === true).length;
        const total     = cotejo.length;
        const partes    = res.detalle_participantes || [];
        const pctTotal  = parseFloat(res.porcentaje_total || 0).toFixed(2);

        let html = `<div class="card border-0 shadow mb-3">
          <div class="card-header" style="background:var(--primary-blue);color:#fff;">
            <i class="fa-solid fa-layer-group me-2"></i>
            Análisis Conjunto del Proceso — ${partes.length} participante(s) — Total: ${pctTotal}%
          </div>
          <div class="card-body p-3">`;

        // Errores
        if (res.errores && res.errores.length) {
            html += `<div class="alert alert-warning py-2"><strong>Advertencias:</strong><ul class="mb-0 mt-1">` +
                res.errores.map(e => `<li>${e}</li>`).join('') + `</ul></div>`;
        }

        // Tabla de participantes (cifras individuales ponderadas)
        html += `<p class="fw-semibold mb-1" style="font-size:.82rem;color:#64748b;">DETALLE POR PARTICIPANTE (cifras ponderadas)</p>
        <div class="table-responsive mb-3">
        <table class="table table-xs table-bordered cifras-base-table align-middle">
          <thead class="table-light"><tr>
            <th>Participante</th><th class="text-center">Tipo</th><th class="text-center">%</th>
            <th class="text-end">Act. Cte.</th><th class="text-end">Act. Total</th>
            <th class="text-end">Pas. Cte.</th><th class="text-end">Pas. Total</th>
            <th class="text-end">Patrimonio</th><th class="text-end">Util. Neta</th>
          </tr></thead><tbody>`;
        partes.forEach(p => {
            const c = p.cifras;
            const badgeCls = p.entity_type === 'Company' ? 'badge-company' : 'badge-consortium';
            html += `<tr>
                <td class="fw-semibold">${p.entity_name}</td>
                <td class="text-center"><span class="badge ${badgeCls}" style="font-size:.7rem">${p.entity_type === 'Company' ? 'Empresa' : 'Consorcio'}</span></td>
                <td class="text-center">${parseFloat(p.porcentaje).toFixed(2)}%</td>
                <td class="text-end">${fmt(c.activo_corriente)}</td>
                <td class="text-end">${fmt(c.total_activos)}</td>
                <td class="text-end">${fmt(c.pasivo_corriente)}</td>
                <td class="text-end">${fmt(c.total_pasivos)}</td>
                <td class="text-end">${fmt(parseFloat(c.total_activos||0)-parseFloat(c.total_pasivos||0))}</td>
                <td class="text-end">${fmt(c.utilidad_neta)}</td>
            </tr>`;
        });
        const ca = res.cifras_agregadas;
        html += `</tbody><tfoot class="table-light fw-bold"><tr>
            <td colspan="3">TOTAL AGREGADO</td>
            <td class="text-end">${fmt(ca.activo_corriente)}</td>
            <td class="text-end">${fmt(ca.total_activos)}</td>
            <td class="text-end">${fmt(ca.pasivo_corriente)}</td>
            <td class="text-end">${fmt(ca.total_pasivos)}</td>
            <td class="text-end">${fmt(parseFloat(ca.total_activos||0)-parseFloat(ca.total_pasivos||0))}</td>
            <td class="text-end">${fmt(ca.utilidad_neta)}</td>
        </tr></tfoot></table></div>`;

        // Cotejo conjunto
        html += `<p class="fw-semibold mb-1" style="font-size:.82rem;color:#64748b;">COTEJO CONJUNTO DE INDICADORES
            <span class="badge ${cumplidos === total ? 'bg-success' : cumplidos > 0 ? 'bg-warning text-dark' : 'bg-danger'} ms-2">
                ${cumplidos}/${total} cumplidos
            </span></p>
        <div class="table-responsive">
        <table class="table table-bordered align-middle cotejo-row">
          <thead class="table-light"><tr>
            <th>Indicador</th>
            <th class="text-center">Valor Calculado</th>
            <th class="text-center">Condición</th>
            <th class="text-center">Valor Requerido</th>
            <th class="text-center">Resultado</th>
          </tr></thead><tbody>`;
        cotejo.forEach(r => {
            const dec  = r.decimales;
            const valC = r.valor_calculado !== null ? parseFloat(r.valor_calculado).toFixed(dec) : 'N/D';
            const valR = parseFloat(r.valor_requerido).toFixed(dec);
            const unidad= r.unidad === '$' ? '<small class="text-muted ms-1">$</small>' : r.unidad === '%' ? '<small class="text-muted">%</small>' : '';
            html += `<tr>
                <td class="fw-semibold">${r.label}</td>
                <td class="text-center">${valC}${unidad}</td>
                <td class="text-center"><span class="badge bg-secondary">${r.operador}</span></td>
                <td class="text-center">${valR}${unidad}</td>
                <td class="text-center">
                    <span class="${semColor(r.cumple)} fw-bold">
                        <i class="fa-solid ${semIcon(r.cumple)} me-1"></i>${semText(r.cumple)}
                    </span>
                </td>
            </tr>`;
        });
        html += `</tbody></table></div>`;
        html += `</div></div>`;
        $panel.html(html);
    }, 'json');
}
</script>
</body>
</html>
