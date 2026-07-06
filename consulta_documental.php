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
    <title>Consulta Documental - GesDoc</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
    <style>
        /* Panel de filtros */
        #filterPanel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.25rem;
        }
        #filterPanel .filter-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 4px;
        }
        /* Tarjeta de resultado (mobile first) */
        .result-entity-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 500;
        }
        .badge-company    { background: #dbeafe; color: #1e40af; }
        .badge-consortium { background: #ede9fe; color: #6d28d9; }

        /* Badges de estado */
        .badge-borrador   { background:#e2e8f0; color:#475569; }
        .badge-revision   { background:#fef3c7; color:#92400e; }
        .badge-aprobado   { background:#d1fae5; color:#065f46; }
        .badge-archivado  { background:#f3e8ff; color:#6b21a8; }

        /* Contador resultados */
        #resultCount {
            font-size: 0.82rem;
            background: var(--light-blue);
            color: var(--primary-blue);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        /* Highlight de búsqueda */
        mark { background: #fef08a; border-radius: 2px; padding: 0 2px; }

        /* Fila de empresa indirecta (participa en consorcio) */
        .indirect-row td { background: #f8f7ff !important; }
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
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb p-2 bg-white rounded shadow-sm border">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:var(--primary-blue);"><i class="fa-solid fa-house"></i> Inicio</a></li>
        <li class="breadcrumb-item active">Consulta Documental</li>
      </ol>
    </nav>

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1" style="color:var(--primary-blue);">Consulta Documental</h2>
                <p class="text-muted mb-0">Búsqueda cruzada de documentos por empresa o consorcio.</p>
            </div>
            <div id="resultCount" class="d-none">0 resultados</div>
        </div>
    </div>

    <!-- ── PANEL DE FILTROS ──────────────────────────────────── -->
    <div id="filterPanel" class="mb-4 shadow-sm">
        <form id="searchForm">
            <div class="row g-3 align-items-end">
                <!-- Búsqueda libre -->
                <div class="col-md-3">
                    <div class="filter-label"><i class="fa-solid fa-magnifying-glass me-1"></i> Búsqueda libre</div>
                    <input type="text" class="form-control" id="fKeyword" name="keyword"
                           placeholder="Nombre de documento, descripción...">
                </div>

                <!-- Empresa -->
                <div class="col-md-3">
                    <div class="filter-label"><i class="fa-solid fa-building me-1"></i> Empresa</div>
                    <select class="form-select" id="fCompany" name="company_id">
                        <option value="">-- Todas las empresas --</option>
                    </select>
                </div>

                <!-- Consorcio -->
                <div class="col-md-2">
                    <div class="filter-label"><i class="fa-solid fa-handshake me-1"></i> Consorcio</div>
                    <select class="form-select" id="fConsortium" name="consortium_id">
                        <option value="">-- Todos --</option>
                    </select>
                </div>

                <!-- Estado -->
                <div class="col-md-2">
                    <div class="filter-label"><i class="fa-solid fa-circle-half-stroke me-1"></i> Estado</div>
                    <select class="form-select" id="fStatus" name="status">
                        <option value="">-- Cualquier estado --</option>
                        <option value="Borrador">Borrador</option>
                        <option value="En Revisión">En Revisión</option>
                        <option value="Aprobado">Aprobado</option>
                        <option value="Archivado">Archivado</option>
                    </select>
                </div>

                <!-- Botones -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 mb-1">
                        <i class="fa-solid fa-search me-1"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                        <i class="fa-solid fa-rotate-left me-1"></i> Limpiar
                    </button>
                </div>
            </div>

            <!-- Segunda fila: rango de fechas -->
            <div class="row g-3 mt-1 align-items-end">
                <div class="col-md-3">
                    <div class="filter-label"><i class="fa-solid fa-calendar me-1"></i> Fecha desde</div>
                    <input type="date" class="form-control" id="fDateFrom" name="date_from">
                </div>
                <div class="col-md-3">
                    <div class="filter-label"><i class="fa-solid fa-calendar-check me-1"></i> Fecha hasta</div>
                    <input type="date" class="form-control" id="fDateTo" name="date_to">
                </div>
                <div class="col-md-6">
                    <!-- Info contextual cuando se selecciona empresa -->
                    <div id="companyInfo" class="d-none p-2 rounded" style="background:var(--light-blue);font-size:0.82rem;">
                        <i class="fa-solid fa-circle-info me-1" style="color:var(--primary-blue);"></i>
                        <span id="companyInfoText"></span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ── TABLA DE RESULTADOS ────────────────────────────────── -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-2">
            <table id="licitacionesTable" class="table table-striped table-hover w-100 align-middle">
                <thead>
                    <tr>
                        <th></th>
                        <th>Documento</th>
                        <th>Estado</th>
                        <th>Versión</th>
                        <th>Entidad Vinculada</th>
                        <th>Tipo</th>
                        <th>% Participación</th>
                        <th>Carpeta</th>
                        <th>Actualizado</th>
                        <th class="text-center">Descargar</th>
                    </tr>
                </thead>
                <tbody id="resultsBody">
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-magnifying-glass fa-2x mb-2 d-block" style="color:#cbd5e1;"></i>
                            Usa los filtros de arriba para buscar documentos.
                        </td>
                    </tr>
                </tbody>
            </table>
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
const jwtToken = localStorage.getItem('gesdoc_jwt');
$.ajaxSetup({ headers: { 'Authorization': 'Bearer ' + jwtToken } });

let table;
let filters = {}; // Cache de filtros para el API

// ── Extensión → ícono ────────────────────────────────────────────────────────
const extIcons = {
    pdf:'fa-file-pdf text-danger', doc:'fa-file-word text-primary', docx:'fa-file-word text-primary',
    xls:'fa-file-excel text-success', xlsx:'fa-file-excel text-success',
    ppt:'fa-file-powerpoint text-warning', pptx:'fa-file-powerpoint text-warning',
    jpg:'fa-file-image text-info', jpeg:'fa-file-image text-info', png:'fa-file-image text-info',
    zip:'fa-file-zipper text-secondary', rar:'fa-file-zipper text-secondary',
    txt:'fa-file-lines text-muted', csv:'fa-file-csv text-success'
};
function fileIcon(p) {
    const ext = (p||'').split('.').pop().toLowerCase();
    return extIcons[ext] || 'fa-file text-muted';
}

function statusBadge(s) {
    const map = { 'Borrador':'badge-borrador','En Revisión':'badge-revision','Aprobado':'badge-aprobado','Archivado':'badge-archivado' };
    return `<span class="badge ${map[s]||'badge-borrador'}">${s}</span>`;
}

// ── Inicialización ───────────────────────────────────────────────────────────
$(document).ready(function() {
    // Cargar filtros de empresa/consorcio — parseo manual para evitar
    // problemas de jQuery con caracteres invisibles en la respuesta
    fetch('api/consulta_documental.php?action=get_filters', {
        headers: { 'Authorization': 'Bearer ' + jwtToken }
    })
    .then(function(response) { return response.text(); })
    .then(function(raw) {
        // Eliminar posible BOM y espacios antes de parsear
        const res = JSON.parse(raw.replace(/^﻿/, '').trim());
        (res.companies || []).forEach(function(c) {
            $('#fCompany').append(
                `<option value="${c.id}" data-nit="${c.nit||''}">${c.name} (${c.nit||'—'})</option>`
            );
        });
        (res.consortiums || []).forEach(function(c) {
            $('#fConsortium').append(`<option value="${c.id}">${c.name}</option>`);
        });
        filters = res;
    })
    .catch(function(err) {
        console.error('Error al cargar filtros:', err);
        Swal.fire({ icon:'error', title:'Error al cargar filtros',
            text: err.message, timer: 3000 });
    });

    // Info contextual al seleccionar empresa
    $('#fCompany').on('change', function() {
        const val  = this.value;
        const $info = $('#companyInfo');
        if (!val) { $info.addClass('d-none'); return; }
        const nit = $(this).find(':selected').data('nit');
        // Buscar consorcios donde participa
        const consorcios = [];
        $.get(`api/consulta_documental.php?action=search&company_id=${val}&keyword=`, function(res) {
            const tipos = new Set(res.data.filter(r=>r.entity_type==='Consortium').map(r=>r.entity_name));
            let msg = `Buscando documentos de <strong>${$(this).find(':selected').text()}</strong> (NIT: ${nit})`;
            if (tipos.size) msg += ` · Participa en <strong>${tipos.size}</strong> consorcio(s): ${[...tipos].join(', ')}`;
            $('#companyInfoText').html(msg);
            $info.removeClass('d-none');
        }.bind(this), 'json');
    });

    // Inicializar DataTable vacía
    table = $('#licitacionesTable').DataTable({
        data: [],
        columns: buildColumns(),
        language: GesDocHelpers.dataTablesLang,
        order: [[8,'desc']],
        createdRow: function(row, data) {
            // Resaltar filas donde el vínculo es un consorcio (conexión indirecta con empresa)
            if (data.entity_type === 'Consortium') $(row).addClass('indirect-row');
        }
    });

    // Submit del formulario
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        runSearch();
    });
});

// ── Búsqueda ─────────────────────────────────────────────────────────────────
function runSearch() {
    const params = {
        action:         'search',
        keyword:        $('#fKeyword').val().trim(),
        company_id:     $('#fCompany').val(),
        consortium_id:  $('#fConsortium').val(),
        status:         $('#fStatus').val(),
        date_from:      $('#fDateFrom').val(),
        date_to:        $('#fDateTo').val()
    };

    // Validar que al menos un filtro esté activo
    const hasFilter = params.keyword || params.company_id || params.consortium_id || params.status || params.date_from || params.date_to;
    if (!hasFilter) {
        Swal.fire({ icon:'info', title:'Selecciona al menos un filtro', timer:1800, showConfirmButton:false });
        return;
    }

    $.get('api/consulta_documental.php', params, function(res) {
        const kw = params.keyword;
        table.clear();
        table.rows.add(res.data || []);
        table.draw();

        const count = res.data ? res.data.length : 0;
        $('#resultCount').text(`${count} resultado${count!==1?'s':''}`).removeClass('d-none');
    }, 'json').fail(GesDocHelpers.handleApiError);
}

function clearFilters() {
    $('#searchForm')[0].reset();
    $('#companyInfo').addClass('d-none');
    $('#resultCount').addClass('d-none');
    table.clear().draw();
}

// ── Definición de columnas ───────────────────────────────────────────────────
function buildColumns() {
    return [
        {
            data: 'file_path',
            render: d => `<i class="fa-solid ${fileIcon(d)} fs-5"></i>`
        },
        { data: 'doc_name' },
        {
            data: 'status',
            render: d => statusBadge(d)
        },
        {
            data: 'version',
            render: d => d ? `<span class="badge bg-secondary">${d}</span>` : '-'
        },
        {
            data: 'entity_name',
            render: function(data, type, row) {
                if (!data) return '<span class="text-muted">-</span>';
                return `<span class="result-entity-badge ${row.entity_type==='Company'?'badge-company':'badge-consortium'}">${data}</span>`;
            }
        },
        {
            data: 'entity_type',
            render: d => {
                if (!d) return '-';
                return d === 'Company'
                    ? '<i class="fa-solid fa-building me-1 text-primary"></i>Empresa'
                    : '<i class="fa-solid fa-handshake me-1" style="color:#6d28d9;"></i>Consorcio';
            }
        },
        {
            data: 'participation_percentage',
            className: 'text-center',
            render: d => d ? `<span class="fw-bold" style="color:var(--primary-blue);">${parseFloat(d).toFixed(2)}%</span>` : '-'
        },
        {
            data: 'folder_name',
            render: d => d ? `<i class="fa-solid fa-folder me-1" style="color:#f59e0b;"></i>${d}` : '<span class="text-muted">Raíz</span>'
        },
        {
            data: 'updated_at',
            render: d => d ? new Date(d).toLocaleDateString('es-ES') : '-'
        },
        {
            data: null,
            className: 'text-center',
            render: function(data, type, row) {
                if (!row.file_path) return '<span class="text-muted">-</span>';
                return `<a href="api/consulta_documental.php?action=download&id=${row.id}"
                            class="btn btn-sm btn-outline-success" target="_blank" title="Descargar">
                            <i class="fa-solid fa-download"></i>
                        </a>`;
            }
        }
    ];
}
</script>

</body>
</html>
