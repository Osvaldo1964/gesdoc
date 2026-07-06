<?php
require_once 'config.php';
require_once 'jwt_helper.php';

$jwt = $_COOKIE['gesdoc_token'] ?? null;
$userData = $jwt ? JWT::decode($jwt, JWT_SECRET) : null;
if (!$userData) { setcookie('gesdoc_token','',time()-3600,"/"); header("Location: login.php"); exit; }
$userName = $userData['user_name'];
$userRole = $userData['user_role'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procesos Licitatorios - GesDoc</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
    <style>
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
               <small class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($userRole) ?></small>
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
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:var(--primary-blue);"><i class="fa-solid fa-house"></i> Inicio</a></li>
        <li class="breadcrumb-item active">Procesos Licitatorios</li>
      </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1" style="color:var(--primary-blue);">Procesos Licitatorios</h2>
            <p class="text-muted mb-0">Gestión de licitaciones y análisis financiero de participantes.</p>
        </div>
        <button class="btn btn-primary" onclick="openModal()">
            <i class="fa-solid fa-plus me-1"></i> Nuevo Proceso
        </button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-2">
            <table id="procesosTable" class="table table-hover w-100 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre del Proceso</th>
                        <th>Entidad Contratante</th>
                        <th>Modalidad</th>
                        <th>Período</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Estado</th>
                        <th class="text-center">Part.</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Proceso -->
<div class="modal fade" id="procesoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="procesoModalLabel" style="color:var(--primary-blue);">
            <i class="fa-solid fa-file-contract me-2"></i>Nuevo Proceso
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="procesoForm">
        <div class="modal-body">
          <input type="hidden" id="procesoId" name="id">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Nombre del Proceso *</label>
              <input type="text" class="form-control text-uppercase" id="pNombre" name="nombre" required
                     oninput="this.value=this.value.toUpperCase()">
            </div>
            <div class="col-md-4">
              <label class="form-label">Número de Proceso</label>
              <input type="text" class="form-control" id="pNumero" name="numero_proceso">
            </div>
            <div class="col-md-8">
              <label class="form-label">Entidad Contratante *</label>
              <input type="text" class="form-control text-uppercase" id="pEntidad" name="entidad_contratante" required
                     oninput="this.value=this.value.toUpperCase()">
            </div>
            <div class="col-md-4">
              <label class="form-label">Modalidad</label>
              <select class="form-select" id="pModalidad" name="modalidad">
                <option>Licitación Pública</option>
                <option>Selección Abreviada</option>
                <option>Concurso de Méritos</option>
                <option>Contratación Directa</option>
                <option>Mínima Cuantía</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Objeto del Contrato</label>
              <textarea class="form-control" id="pObjeto" name="objeto" rows="2"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Presupuesto Oficial ($)</label>
              <input type="number" step="0.01" class="form-control" id="pPresupuesto" name="presupuesto_oficial">
            </div>
            <div class="col-md-2">
              <label class="form-label">Período Financiero *</label>
              <input type="number" class="form-control" id="pPeriodo" name="periodo_financiero"
                     min="2000" max="2099" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Fecha Apertura</label>
              <input type="date" class="form-control" id="pApertura" name="fecha_apertura">
            </div>
            <div class="col-md-3">
              <label class="form-label">Fecha Cierre</label>
              <input type="date" class="form-control" id="pCierre" name="fecha_cierre">
            </div>
            <div class="col-md-3">
              <label class="form-label">Estado</label>
              <select class="form-select" id="pEstado" name="estado">
                <option>En Preparación</option>
                <option>Presentada</option>
                <option>Adjudicada</option>
                <option>Desierta</option>
                <option>Cancelada</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="btnSaveProceso">
            <i class="fa-solid fa-save me-1"></i> Guardar
          </button>
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
const jwtToken = localStorage.getItem('gesdoc_jwt');
$.ajaxSetup({ headers: { 'Authorization': 'Bearer ' + jwtToken } });

const estadoBadge = {
    'En Preparación': 'badge-preparacion',
    'Presentada':     'badge-presentada',
    'Adjudicada':     'badge-adjudicada',
    'Desierta':       'badge-desierta',
    'Cancelada':      'badge-cancelada',
};

let table;

$(document).ready(function() {
    table = $('#procesosTable').DataTable({
        ajax: { url: 'api/procesos.php?action=list', type: 'GET', error: GesDocHelpers.handleApiError },
        columns: [
            { data: 'id', width: '40px' },
            { data: 'nombre' },
            { data: 'entidad_contratante' },
            { data: 'modalidad', render: d => `<span class="badge bg-secondary">${d}</span>` },
            { data: 'periodo_financiero', className: 'text-center' },
            { data: 'fecha_apertura',  render: d => d ? new Date(d+'T00:00:00').toLocaleDateString('es-ES') : '-' },
            { data: 'fecha_cierre',    render: d => d ? new Date(d+'T00:00:00').toLocaleDateString('es-ES') : '-' },
            { data: 'estado', render: d => `<span class="badge ${estadoBadge[d]||'badge-preparacion'}">${d}</span>` },
            { data: 'total_participantes', className: 'text-center',
              render: d => `<span class="badge bg-primary rounded-pill">${d}</span>` },
            { data: null, className: 'text-center', orderable: false,
              render: (d, t, row) => `
                <a href="proceso_detalle.php?id=${row.id}" class="btn btn-sm btn-outline-primary me-1" title="Ver detalle">
                    <i class="fa-solid fa-eye"></i>
                </a>
                <button class="btn btn-sm btn-outline-secondary me-1" onclick='editProceso(${JSON.stringify(row)})' title="Editar datos">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteProceso(${row.id})" title="Eliminar">
                    <i class="fa-solid fa-trash"></i>
                </button>`
            }
        ],
        language: GesDocHelpers.dataTablesLang,
        order: [[0, 'desc']]
    });

    $('#procesoForm').on('submit', function(e) {
        e.preventDefault();
        const actionStr = $('#procesoId').val() ? 'update' : 'create';
        const $btn = $('#btnSaveProceso').prop('disabled', true);
        $.post('api/procesos.php', $(this).serialize() + '&action=' + actionStr, function(res) {
            if (res.success) {
                $('#procesoModal').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon:'success', title:res.message, timer:1500, showConfirmButton:false });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').always(() => $btn.prop('disabled', false));
    });
});

function openModal() {
    $('#procesoForm')[0].reset();
    $('#procesoId').val('');
    $('#pPeriodo').val(new Date().getFullYear());
    $('#procesoModalLabel').html('<i class="fa-solid fa-file-contract me-2"></i>Nuevo Proceso');
    $('#procesoModal').modal('show');
}

function editProceso(row) {
    $('#procesoId').val(row.id);
    $('#pNombre').val(row.nombre);
    $('#pNumero').val(row.numero_proceso);
    $('#pEntidad').val(row.entidad_contratante);
    $('#pModalidad').val(row.modalidad);
    $('#pObjeto').val(row.objeto);
    $('#pPresupuesto').val(row.presupuesto_oficial);
    $('#pPeriodo').val(row.periodo_financiero);
    $('#pApertura').val(row.fecha_apertura);
    $('#pCierre').val(row.fecha_cierre);
    $('#pEstado').val(row.estado);
    $('#procesoModalLabel').html('<i class="fa-solid fa-pen me-2"></i>Editar Proceso');
    $('#procesoModal').modal('show');
}

function deleteProceso(id) {
    Swal.fire({ title:'¿Eliminar este proceso?', text:'Se eliminarán también sus indicadores, criterios y participantes.',
        icon:'warning', showCancelButton:true, confirmButtonColor:'#d33',
        confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/procesos.php', { action:'delete', id }, function(res) {
                if (res.success) { table.ajax.reload(); Swal.fire({icon:'success',title:res.message,timer:1500,showConfirmButton:false}); }
                else Swal.fire('Error', res.message, 'error');
            }, 'json');
        }
    });
}
</script>
</body>
</html>
