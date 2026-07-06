<?php
require_once 'config.php';
require_once 'jwt_helper.php';

$jwt = $_COOKIE['gesdoc_token'] ?? null;
$userData = null;

if ($jwt) {
    $userData = JWT::decode($jwt, JWT_SECRET);
}

if (!$userData) {
    setcookie('gesdoc_token', '', time() - 3600, "/");
    header("Location: login.php");
    exit;
}

$userName = $userData['user_name'];
$userRole = $userData['user_role'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empresas - GesDoc</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-gesdoc">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
        <i class="fa-solid fa-folder-tree me-2"></i> GesDoc
    </a>
    <div class="d-flex align-items-center ms-auto">
      <div class="dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
           <i class="fa-solid fa-circle-user fs-4 me-2" style="color: var(--primary-blue);"></i>
           <div>
               <span class="d-block fw-bold text-dark" style="line-height: 1.2;"><?= htmlspecialchars($userName) ?></span>
               <small class="text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($userRole) ?></small>
           </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
          <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear me-2"></i> Configuración</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Cerrar Sesión</a></li>
        </ul>
      </div>
    </div>
  </div>
</nav>

<div class="container py-4">
    <!-- Breadcrumb System -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb p-3 bg-white rounded shadow-sm border">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary-blue);"><i class="fa-solid fa-house"></i> Inicio</a></li>
        <li class="breadcrumb-item active" aria-current="page">Empresas</li>
      </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1" style="color: var(--primary-blue);">Gestión de Empresas</h2>
                <p class="text-muted">Directorio de empresas individuales del sistema.</p>
            </div>
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fa-solid fa-plus me-1"></i> Nueva Empresa
            </button>
        </div>
    </div>

    <!-- DataTables Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table id="empresasTable" class="table table-striped table-hover w-100 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NIT</th>
                        <th>Razón Social</th>
                        <th>Representante Legal</th>
                        <th>RUT</th>
                        <th>RUP</th>
                        <th>Creación</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Llenado vía AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Empresa -->
<div class="modal fade" id="empresaModal" tabindex="-1" aria-labelledby="empresaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="empresaModalLabel" style="color: var(--primary-blue);"><i class="fa-solid fa-building me-2"></i>Nueva Empresa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="empresaForm">
          <div class="modal-body">
              <input type="hidden" id="empresaId" name="id">
              <input type="hidden" id="action" name="action" value="create">
              
              <div class="mb-3">
                  <label for="nit" class="form-label fw-bold">NIT</label>
                  <input type="text" class="form-control" id="nit" name="nit" required placeholder="Ej. 900.123.456-7">
              </div>
              
              <div class="mb-3">
                  <label for="name" class="form-label fw-bold">Razón Social</label>
                  <input type="text" class="form-control" id="name" name="name" required placeholder="Nombre de la empresa" oninput="this.value = this.value.toUpperCase();">
              </div>
              
              <div class="mb-3">
                  <label for="legal_representative" class="form-label fw-bold">Representante Legal</label>
                  <input type="text" class="form-control" id="legal_representative" name="legal_representative" placeholder="Nombre completo" oninput="this.value = this.value.toUpperCase();">
              </div>
              
              <div class="row">
                  <div class="col-md-6 mb-3">
                      <label for="rut_updated_at" class="form-label fw-bold">Actualización RUT</label>
                      <input type="date" class="form-control" id="rut_updated_at" name="rut_updated_at">
                  </div>
                  <div class="col-md-6 mb-3">
                      <label for="rup_updated_at" class="form-label fw-bold">Actualización RUP</label>
                      <input type="date" class="form-control" id="rup_updated_at" name="rup_updated_at">
                  </div>
              </div>
          </div>
          <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="btnSave"><i class="fa-solid fa-save me-1"></i> Guardar</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Estados Financieros -->
<div class="modal fade" id="efModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="efModalLabel" style="color:var(--primary-blue);"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Tabla de períodos existentes -->
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-semibold text-muted" style="font-size:.85rem;">PERÍODOS REGISTRADOS</span>
          <button class="btn btn-sm btn-success" onclick="newEF()">
            <i class="fa-solid fa-plus me-1"></i> Nuevo período
          </button>
        </div>
        <div class="table-responsive mb-3">
          <table class="table table-sm table-bordered align-middle" style="font-size:.82rem;">
            <thead class="table-light">
              <tr>
                <th>Año</th>
                <th class="text-end">Activo Cte</th>
                <th class="text-end">Activo No Cte</th>
                <th class="text-end">Total Activos</th>
                <th class="text-end">Pasivo Cte</th>
                <th class="text-end">Pasivo No Cte</th>
                <th class="text-end">Total Pasivos</th>
                <th class="text-end">Patrimonio</th>
                <th class="text-end">Ingresos Op.</th>
                <th class="text-end">Utilidad Neta</th>
                <th class="text-center">Acc.</th>
              </tr>
            </thead>
            <tbody id="efTableBody">
              <tr><td colspan="11" class="text-center text-muted py-3">Cargando...</td></tr>
            </tbody>
          </table>
        </div>

        <!-- Formulario inline (oculto por defecto) -->
        <div id="efFormSection" style="display:none;">
          <hr>
          <p class="fw-semibold mb-2" style="color:var(--primary-blue);font-size:.88rem;">
            <i class="fa-solid fa-pen me-1"></i> INGRESO DE DATOS
          </p>
          <form id="efForm">
            <input type="hidden" id="efId" name="id">
            <div class="row g-2">
              <div class="col-md-2">
                <label class="form-label form-label-sm">Año *</label>
                <input type="number" class="form-control form-control-sm" id="efPeriodo" name="periodo"
                       min="2000" max="2099" required placeholder="2024">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm">Activo Corriente</label>
                <input type="number" step="0.01" class="form-control form-control-sm" id="efActivoCorriente" name="activo_corriente" value="0">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm">Activo No Corriente</label>
                <input type="number" step="0.01" class="form-control form-control-sm" id="efActivoNoCorriente" name="activo_no_corriente" value="0">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm fw-bold">Total Activos <small class="text-muted fw-normal">(auto)</small></label>
                <input type="number" step="0.01" class="form-control form-control-sm fw-bold bg-light" id="efTotalActivos" name="total_activos" value="0" readonly tabindex="-1">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm">Pasivo Corriente</label>
                <input type="number" step="0.01" class="form-control form-control-sm" id="efPasivoCorriente" name="pasivo_corriente" value="0">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm">Pasivo No Corriente</label>
                <input type="number" step="0.01" class="form-control form-control-sm" id="efPasivoNoCorriente" name="pasivo_no_corriente" value="0">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm fw-bold">Total Pasivos <small class="text-muted fw-normal">(auto)</small></label>
                <input type="number" step="0.01" class="form-control form-control-sm fw-bold bg-light" id="efTotalPasivos" name="total_pasivos" value="0" readonly tabindex="-1">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm fw-bold">Patrimonio</label>
                <input type="number" step="0.01" class="form-control form-control-sm fw-bold" id="efPatrimonio" name="patrimonio" value="0">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm">Ingresos Operacionales</label>
                <input type="number" step="0.01" class="form-control form-control-sm" id="efIngresos" name="ingresos_operacionales" value="0">
              </div>
              <div class="col-md-2">
                <label class="form-label form-label-sm">Utilidad Neta</label>
                <input type="number" step="0.01" class="form-control form-control-sm" id="efUtilidad" name="utilidad_neta" value="0">
              </div>
              <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                  <i class="fa-solid fa-save me-1"></i> Guardar
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="$('#efFormSection').slideUp()">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </form>
        </div>

      </div>
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
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
let table;
const jwtToken = localStorage.getItem('gesdoc_jwt');

// Configuración global para AJAX (enviar Token)
$.ajaxSetup({
    headers: { 'Authorization': 'Bearer ' + jwtToken }
});

$(document).ready(function() {
    // Inicializar DataTable
    table = $('#empresasTable').DataTable({
        ajax: {
            url: 'api/empresas.php?action=list',
            type: 'GET',
            error: GesDocHelpers.handleApiError
        },
        columns: [
            { data: 'id' },
            { data: 'nit' },
            { data: 'name' },
            { data: 'legal_representative', defaultContent: '<span class="text-muted fst-italic">No definido</span>' },
            { 
                data: 'rut_updated_at',
                render: function(data) {
                    return data ? new Date(data + 'T00:00:00').toLocaleDateString('es-ES') : '-';
                }
            },
            { 
                data: 'rup_updated_at',
                render: function(data) {
                    return data ? new Date(data + 'T00:00:00').toLocaleDateString('es-ES') : '-';
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString('es-ES') : '';
                }
            },
            {
                data: null,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-success me-1" onclick='openEF(${row.id}, "${row.name.replace(/"/g,"&quot;")}")' title="Estados Financieros">
                            <i class="fa-solid fa-chart-line"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick='editEmpresa(${JSON.stringify(row)})' title="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEmpresa(${row.id})" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        language: GesDocHelpers.dataTablesLang
    });

    // Formulario Submit
    $('#empresaForm').on('submit', function(e) {
        e.preventDefault();
        
        // Si el id está vacío o es null, forzamos action a create
        let actionStr = $('#empresaId').val() ? 'update' : 'create';
        
        let formData = $(this).serialize() + '&action=' + actionStr;
        let $btn = $('#btnSave');
        $btn.prop('disabled', true);

        $.post('api/empresas.php', formData, function(response) {
            if (response.success) {
                $('#empresaModal').modal('hide');
                table.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }, 'json').fail(function() {
            Swal.fire('Error', 'Ocurrió un error en la conexión.', 'error');
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
});

function openModal() {
    $('#empresaForm')[0].reset();
    $('#empresaId').val('');
    $('#empresaModalLabel').html('<i class="fa-solid fa-building me-2"></i>Nueva Empresa');
    $('#empresaModal').modal('show');
}

function editEmpresa(row) {
    $('#empresaId').val(row.id);
    $('#nit').val(row.nit);
    $('#name').val(row.name);
    $('#legal_representative').val(row.legal_representative);
    $('#rut_updated_at').val(row.rut_updated_at);
    $('#rup_updated_at').val(row.rup_updated_at);
    $('#empresaModalLabel').html('<i class="fa-solid fa-pen me-2"></i>Editar Empresa');
    $('#empresaModal').modal('show');
}

function deleteEmpresa(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡No podrás revertir esto!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/empresas.php', { action: 'delete', id: id }, function(response) {
                if (response.success) {
                    table.ajax.reload();
                    Swal.fire('Eliminado!', response.message, 'success');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            }, 'json');
        }
    });
}

// ── ESTADOS FINANCIEROS ──────────────────────────────────────────────────────
let efCompanyId = null;
let efCompanyName = '';

function openEF(companyId, companyName) {
    efCompanyId   = companyId;
    efCompanyName = companyName;
    $('#efModalLabel').html(`<i class="fa-solid fa-chart-line me-2"></i>Estados Financieros — ${companyName}`);
    loadEF();
    $('#efModal').modal('show');
}

function loadEF() {
    $.get('api/empresas.php', { action: 'ef_list', company_id: efCompanyId }, function(res) {
        const rows = res.data || [];
        const fmt  = v => GesDocHelpers.formatCurrency(v);
        if (!rows.length) {
            $('#efTableBody').html('<tr><td colspan="11" class="text-center text-muted py-3">Sin registros. Agrega el primer período.</td></tr>');
            return;
        }
        let html = '';
        rows.forEach(r => {
            html += `<tr>
                <td class="fw-bold">${r.periodo}</td>
                <td class="text-end">${fmt(r.activo_corriente)}</td>
                <td class="text-end">${fmt(r.activo_no_corriente)}</td>
                <td class="text-end fw-bold" style="color:var(--primary-blue)">${fmt(r.total_activos)}</td>
                <td class="text-end">${fmt(r.pasivo_corriente)}</td>
                <td class="text-end">${fmt(r.pasivo_no_corriente)}</td>
                <td class="text-end fw-bold text-danger">${fmt(r.total_pasivos)}</td>
                <td class="text-end fw-bold text-success">${fmt(r.patrimonio)}</td>
                <td class="text-end">${fmt(r.ingresos_operacionales)}</td>
                <td class="text-end">${fmt(r.utilidad_neta)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary me-1" onclick='editEF(${JSON.stringify(r)})' title="Editar">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEF(${r.id})" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        });
        $('#efTableBody').html(html);
    }, 'json');
}

function newEF() {
    $('#efForm')[0].reset();
    $('#efId').val('');
    $('#efPeriodo').val(new Date().getFullYear());
    $('#efFormSection').slideDown();
    $('#efPeriodo').focus();
}

function editEF(r) {
    $('#efId').val(r.id);
    $('#efPeriodo').val(r.periodo);
    $('#efActivoCorriente').val(r.activo_corriente);
    $('#efActivoNoCorriente').val(r.activo_no_corriente);
    $('#efTotalActivos').val(r.total_activos);
    $('#efPasivoCorriente').val(r.pasivo_corriente);
    $('#efPasivoNoCorriente').val(r.pasivo_no_corriente);
    $('#efTotalPasivos').val(r.total_pasivos);
    $('#efPatrimonio').val(r.patrimonio);
    $('#efIngresos').val(r.ingresos_operacionales);
    $('#efUtilidad').val(r.utilidad_neta);
    $('#efFormSection').slideDown();
    $('#efPeriodo').focus();
}

function deleteEF(id) {
    Swal.fire({ title:'¿Eliminar este período?', icon:'warning', showCancelButton:true,
        confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar', confirmButtonColor:'#d33'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/empresas.php', { action:'ef_delete', id }, function(res) {
                if (res.success) { loadEF(); Swal.fire({icon:'success',title:res.message,timer:1200,showConfirmButton:false}); }
                else Swal.fire('Error', res.message, 'error');
            }, 'json');
        }
    });
}

// ── Cálculo automático de totales ─────────────────────────────────────────
function recalcTotales() {
    const ac  = parseFloat($('#efActivoCorriente').val())  || 0;
    const anc = parseFloat($('#efActivoNoCorriente').val()) || 0;
    const pc  = parseFloat($('#efPasivoCorriente').val())  || 0;
    const pnc = parseFloat($('#efPasivoNoCorriente').val()) || 0;
    $('#efTotalActivos').val((ac + anc).toFixed(2));
    $('#efTotalPasivos').val((pc + pnc).toFixed(2));
}
$('#efActivoCorriente, #efActivoNoCorriente').on('input', recalcTotales);
$('#efPasivoCorriente, #efPasivoNoCorriente').on('input', recalcTotales);

$(document).on('submit', '#efForm', function(e) {
    e.preventDefault();
    const data = $(this).serialize() + `&action=ef_save&company_id=${efCompanyId}`;
    $.post('api/empresas.php', data, function(res) {
        if (res.success) {
            $('#efFormSection').slideUp();
            $('#efForm')[0].reset();
            loadEF();
            Swal.fire({icon:'success', title:res.message, timer:1500, showConfirmButton:false});
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    }, 'json');
});
</script>

</body>
</html>
