<?php
require_once 'config.php';
require_once 'jwt_helper.php';

$jwt = $_COOKIE['gesdoc_token'] ?? null;
$userData = $jwt ? JWT::decode($jwt, JWT_SECRET) : null;
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
    <title>Consorcios - GesDoc</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
    <style>
        .member-row td { vertical-align: middle; }
        .member-row .btn-remove { cursor: pointer; }
        #membersTable { font-size: 0.85rem; }
        .pct-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .pct-ok  { background: #d1fae5; color: #065f46; }
        .pct-warn { background: #fef3c7; color: #92400e; }
        .pct-total {
            font-weight: 700;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-gesdoc">
  <div class="container-fluid">
    <a class="navbar-brand d-flex align-items-center" href="index.php">
        <i class="fa-solid fa-folder-tree me-2"></i> GesDoc
    </a>
    <div class="d-flex align-items-center ms-auto">
      <div class="dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
           <i class="fa-solid fa-circle-user fs-4 me-2" style="color: var(--primary-blue);"></i>
           <div>
               <span class="d-block fw-bold text-dark" style="line-height:1.2;"><?= htmlspecialchars($userName) ?></span>
               <small class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($userRole) ?></small>
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
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb p-3 bg-white rounded shadow-sm border">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:var(--primary-blue);"><i class="fa-solid fa-house"></i> Inicio</a></li>
        <li class="breadcrumb-item active">Consorcios</li>
      </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1" style="color:var(--primary-blue);">Gestión de Consorcios</h2>
                <p class="text-muted">Uniones temporales y su composición empresarial.</p>
            </div>
            <button class="btn btn-primary" onclick="openModal()">
                <i class="fa-solid fa-plus me-1"></i> Nuevo Consorcio
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table id="consorciosTable" class="table table-striped table-hover w-100 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NIT</th>
                        <th>Nombre del Consorcio</th>
                        <th class="text-center">N° Miembros</th>
                        <th>Creación</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal Consorcio -->
<div class="modal fade" id="consorcioModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="consorcioModalLabel" style="color:var(--primary-blue);">
            <i class="fa-solid fa-handshake me-2"></i>Nuevo Consorcio
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="consorcioForm">
          <div class="modal-body">
              <input type="hidden" id="consorcioId" name="id">

              <div class="row">
                  <div class="col-md-8 mb-3">
                      <label for="cName" class="form-label fw-bold">Nombre del Consorcio</label>
                      <input type="text" class="form-control" id="cName" name="name" required
                             placeholder="Ej. CONSORCIO VIAL NORTE"
                             oninput="this.value = this.value.toUpperCase();">
                  </div>
                  <div class="col-md-4 mb-3">
                      <label for="cNit" class="form-label fw-bold">NIT <small class="text-muted fw-normal">(opcional)</small></label>
                      <input type="text" class="form-control" id="cNit" name="nit" placeholder="900.123.456-7">
                  </div>
              </div>

              <!-- Sección Miembros -->
              <div class="border rounded p-3 mt-1">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="mb-0" style="color:var(--primary-blue);"><i class="fa-solid fa-building-user me-1"></i> Empresas Miembro</h6>
                      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMemberRow()">
                          <i class="fa-solid fa-plus me-1"></i> Agregar Empresa
                      </button>
                  </div>
                  <table class="table table-bordered table-sm mb-2" id="membersTable">
                      <thead class="table-light">
                          <tr>
                              <th>Empresa</th>
                              <th style="width:140px;">% Participación</th>
                              <th style="width:50px;"></th>
                          </tr>
                      </thead>
                      <tbody id="membersTbody">
                          <!-- Filas dinámicas -->
                      </tbody>
                  </table>
                  <div class="d-flex justify-content-end">
                      <span class="text-muted me-2">Total participación:</span>
                      <span id="totalPct" class="pct-total">0.00%</span>
                  </div>
              </div>
          </div>
          <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="btnSaveCons"><i class="fa-solid fa-save me-1"></i> Guardar</button>
          </div>
      </form>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="assets/js/jquery-3.7.0.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>
<script src="assets/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/sweetalert2.all.min.js"></script>
<script src="assets/js/helpers.js?v=<?= APP_VERSION ?>"></script>

<script>
let table;
let companiesList = []; // Cache de empresas para los selects

$(document).ready(function() {
    // Cargar lista de empresas
    $.get('api/consorcios.php?action=get_companies', function(res) {
        companiesList = res.data || [];
    }, 'json');

    // Inicializar DataTable
    table = $('#consorciosTable').DataTable({
        ajax: { url: 'api/consorcios.php?action=list', type: 'GET', error: GesDocHelpers.handleApiError },
        columns: [
            { data: 'id' },
            { data: 'nit', defaultContent: '<span class="text-muted fst-italic">-</span>' },
            { data: 'name' },
            {
                data: 'total_members',
                className: 'text-center',
                render: function(data) {
                    return `<span class="badge" style="background:var(--primary-blue);">${data}</span>`;
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
                        <button class="btn btn-sm btn-outline-primary me-1" onclick='editConsorcio(${row.id})' title="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteConsorcio(${row.id})" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        language: GesDocHelpers.dataTablesLang
    });

    // Submit del formulario
    $('#consorcioForm').on('submit', function(e) {
        e.preventDefault();
        const members = collectMembers();
        const actionStr = $('#consorcioId').val() ? 'update' : 'create';
        const $btn = $('#btnSaveCons').prop('disabled', true);

        $.post('api/consorcios.php', {
            action:  actionStr,
            id:      $('#consorcioId').val(),
            name:    $('#cName').val(),
            nit:     $('#cNit').val(),
            members: JSON.stringify(members)
        }, function(res) {
            if (res.success) {
                $('#consorcioModal').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon:'success', title:'Éxito', text: res.message, timer:1500, showConfirmButton:false });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').fail(function(xhr) {
            GesDocHelpers.handleApiError(xhr);
        }).always(function() {
            $btn.prop('disabled', false);
        });
    });
});

/* ---- Helpers de Miembros ---- */

function buildCompanyOptions(selectedId) {
    let opts = '<option value="">-- Seleccione empresa --</option>';
    companiesList.forEach(c => {
        const sel = (c.id == selectedId) ? 'selected' : '';
        opts += `<option value="${c.id}" ${sel}>${c.name} (${c.nit})</option>`;
    });
    return opts;
}

function addMemberRow(companyId = '', percentage = '') {
    const row = `
        <tr class="member-row">
            <td>
                <select class="form-select form-select-sm member-company" required onchange="updateTotal()">
                    ${buildCompanyOptions(companyId)}
                </select>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" class="form-control member-pct" min="0.01" max="100" step="0.01"
                           value="${percentage}" placeholder="0.00" oninput="updateTotal()">
                    <span class="input-group-text">%</span>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove" onclick="removeMemberRow(this)">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </td>
        </tr>`;
    $('#membersTbody').append(row);
    updateTotal();
}

function removeMemberRow(btn) {
    $(btn).closest('tr').remove();
    updateTotal();
}

function updateTotal() {
    let total = 0;
    $('.member-pct').each(function() {
        total += parseFloat($(this).val()) || 0;
    });
    total = Math.round(total * 100) / 100;
    const $el = $('#totalPct');
    $el.text(total.toFixed(2) + '%');
    $el.css('color', total > 100 ? '#dc2626' : total === 100 ? '#065f46' : '#92400e');
}

function collectMembers() {
    const members = [];
    $('.member-row').each(function() {
        const company_id = $(this).find('.member-company').val();
        const percentage = $(this).find('.member-pct').val();
        if (company_id && percentage) {
            members.push({ company_id, percentage });
        }
    });
    return members;
}

/* ---- Acciones CRUD ---- */

function openModal() {
    $('#consorcioForm')[0].reset();
    $('#consorcioId').val('');
    $('#membersTbody').empty();
    updateTotal();
    $('#consorcioModalLabel').html('<i class="fa-solid fa-handshake me-2"></i>Nuevo Consorcio');
    $('#consorcioModal').modal('show');
}

function editConsorcio(id) {
    // Cargar datos del consorcio y sus miembros
    $.when(
        $.get(`api/consorcios.php?action=get_members&id=${id}`)
    ).done(function(resM) {
        // Buscar datos del consorcio en la tabla
        const rowData = table.rows().data().toArray().find(r => r.id == id);
        if (!rowData) return;

        $('#consorcioId').val(rowData.id);
        $('#cName').val(rowData.name);
        $('#cNit').val(rowData.nit || '');
        $('#membersTbody').empty();

        (resM.data || []).forEach(m => {
            addMemberRow(m.company_id, m.participation_percentage);
        });

        $('#consorcioModalLabel').html('<i class="fa-solid fa-pen me-2"></i>Editar Consorcio');
        $('#consorcioModal').modal('show');
    });
}

function deleteConsorcio(id) {
    Swal.fire({
        title: '¿Eliminar Consorcio?',
        text: 'Se desvincularán todos los miembros. Esta acción no se puede revertir.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            $.post('api/consorcios.php', { action: 'delete', id }, function(res) {
                if (res.success) {
                    table.ajax.reload();
                    Swal.fire('Eliminado', res.message, 'success');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }, 'json');
        }
    });
}
</script>

</body>
</html>
