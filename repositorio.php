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
    <title>Repositorio - GesDoc</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
    <style>
        /* Layout de dos columnas */
        #repoLayout { display: flex; gap: 1rem; align-items: flex-start; }
        #folderPanel {
            width: 280px; flex-shrink: 0;
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 8px; padding: 1rem;
            position: sticky; top: 80px;
            max-height: calc(100vh - 100px); overflow-y: auto;
        }
        #docsPanel { flex: 1; min-width: 0; }

        /* Árbol de carpetas */
        .folder-tree { list-style: none; padding-left: 0; margin: 0; }
        .folder-tree ul { list-style: none; padding-left: 1.2rem; margin: 0; }
        .folder-item {
            padding: 5px 8px; border-radius: 6px; cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            font-size: 0.85rem; user-select: none;
            color: #374151;
        }
        .folder-item:hover { background: var(--light-blue); color: var(--primary-blue); }
        .folder-item.active { background: var(--primary-blue); color: #fff; font-weight: 600; }
        .folder-item .fa-folder { color: #f59e0b; }
        .folder-item.active .fa-folder { color: #fde68a; }
        .folder-item .actions { margin-left: auto; display: none; gap: 4px; }
        .folder-item:hover .actions { display: flex; }
        .folder-item.active .actions { display: flex; }
        .folder-item .actions button {
            background: none; border: none; padding: 0 3px;
            cursor: pointer; font-size: 0.75rem; opacity: 0.7;
        }
        .folder-item .actions button:hover { opacity: 1; }

        /* Zona de drop */
        #dropZone {
            border: 2px dashed #94a3b8; border-radius: 10px;
            padding: 2rem; text-align: center; background: #f8fafc;
            transition: all 0.2s; cursor: pointer;
        }
        #dropZone.drag-over { border-color: var(--primary-blue); background: var(--light-blue); }
        #dropZone i { font-size: 2.5rem; color: #94a3b8; }
        #dropZone.drag-over i { color: var(--primary-blue); }

        /* Badges de estado */
        .badge-borrador  { background:#e2e8f0; color:#475569; }
        .badge-revision  { background:#fef3c7; color:#92400e; }
        .badge-aprobado  { background:#d1fae5; color:#065f46; }
        .badge-archivado { background:#f3e8ff; color:#6b21a8; }

        /* Tamaño columnas tabla */
        #docsTable td:first-child { width: 30px; text-align: center; }

        @media (max-width:768px) {
            #repoLayout { flex-direction: column; }
            #folderPanel { width: 100%; position: static; }
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
    <!-- Breadcrumb dinámico -->
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb p-2 bg-white rounded shadow-sm border" id="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:var(--primary-blue);"><i class="fa-solid fa-house"></i> Inicio</a></li>
        <li class="breadcrumb-item"><a href="repositorio.php" class="text-decoration-none" style="color:var(--primary-blue);">Repositorio</a></li>
      </ol>
    </nav>

    <div id="repoLayout">
        <!-- ── PANEL IZQUIERDO: Árbol ── -->
        <div id="folderPanel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold" style="color:var(--primary-blue);font-size:0.9rem;"><i class="fa-solid fa-folder-tree me-1"></i> Carpetas</span>
                <button class="btn btn-sm btn-outline-primary py-0" onclick="promptNewFolder(null)" title="Nueva carpeta raíz">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            <hr class="mt-1 mb-2">
            <!-- Raíz (todos los documentos sin carpeta) -->
            <div class="folder-item <?= '' ?>" id="root-item" onclick="selectFolder(null, 'Todos los documentos')">
                <i class="fa-solid fa-inbox"></i> <span>Todos</span>
            </div>
            <ul class="folder-tree mt-1" id="folderTree"></ul>
        </div>

        <!-- ── PANEL DERECHO: Documentos ── -->
        <div id="docsPanel">
            <!-- Cabecera del panel -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0" id="currentFolderTitle" style="color:var(--primary-blue);">
                    <i class="fa-solid fa-inbox me-1"></i> Todos los documentos
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="promptNewFolder(currentFolderId)">
                        <i class="fa-solid fa-folder-plus me-1"></i> Subcarpeta
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="openUploadModal()">
                        <i class="fa-solid fa-upload me-1"></i> Subir Documento
                    </button>
                </div>
            </div>

            <!-- DataTable de documentos -->
            <div class="card shadow-sm border-0">
                <div class="card-body p-2">
                    <table id="docsTable" class="table table-striped table-hover w-100 align-middle">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Nombre del Documento</th>
                                <th>Versión</th>
                                <th>Estado</th>
                                <th>Vinculado a</th>
                                <th>Tamaño</th>
                                <th>Actualizado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL SUBIDA ══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title" style="color:var(--primary-blue);" id="uploadModalTitle">
            <i class="fa-solid fa-upload me-2"></i>Subir Documento
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="uploadForm" enctype="multipart/form-data">
          <div class="modal-body">
              <input type="hidden" id="uDocId"  name="doc_id">
              <input type="hidden" id="uFolder" name="folder_id">

              <!-- Zona de arrastre -->
              <div id="dropZone" onclick="document.getElementById('fileInput').click()">
                  <i class="fa-solid fa-cloud-arrow-up d-block mb-2"></i>
                  <p class="mb-1 fw-bold">Arrastra tu archivo aquí</p>
                  <p class="text-muted small mb-0">o haz clic para seleccionar</p>
                  <p id="selectedFileName" class="mt-2 text-primary fw-bold small"></p>
              </div>
              <input type="file" id="fileInput" name="file" class="d-none"
                     accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.zip,.rar,.txt,.csv">

              <div class="row mt-3">
                  <div class="col-md-6 mb-2">
                      <label class="form-label fw-bold">Estado</label>
                      <select class="form-select" name="status" id="uStatus">
                          <option value="Borrador">Borrador</option>
                          <option value="En Revisión">En Revisión</option>
                          <option value="Aprobado">Aprobado</option>
                          <option value="Archivado">Archivado</option>
                      </select>
                  </div>
                  <div class="col-md-6 mb-2" id="entitySection">
                      <label class="form-label fw-bold">Vincular a <small class="text-muted fw-normal">(Empresa o Consorcio)</small></label>
                      <select class="form-select" name="entity_combined" id="entityCombined">
                          <option value="">-- Sin vinculación --</option>
                          <optgroup label="Empresas" id="entCompanies"></optgroup>
                          <optgroup label="Consorcios" id="entConsortiums"></optgroup>
                      </select>
                      <input type="hidden" name="entity_type" id="uEntityType">
                      <input type="hidden" name="entity_id"   id="uEntityId">
                  </div>
              </div>

              <div class="mb-2">
                  <label class="form-label fw-bold">Descripción <small class="text-muted fw-normal">(opcional)</small></label>
                  <textarea class="form-control" name="description" id="uDesc" rows="2" placeholder="Breve descripción del documento..."></textarea>
              </div>
          </div>
          <div class="modal-footer bg-light border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="btnUpload">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Subir Archivo
            </button>
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
let currentFolderId   = null;
let currentFolderName = 'Todos los documentos';
let entities = [];

// ── EXTENSIÓN → ÍCONO ───────────────────────────────────────────────────────
const extIcons = {
    pdf:'fa-file-pdf text-danger', doc:'fa-file-word text-primary', docx:'fa-file-word text-primary',
    xls:'fa-file-excel text-success', xlsx:'fa-file-excel text-success',
    ppt:'fa-file-powerpoint text-warning', pptx:'fa-file-powerpoint text-warning',
    jpg:'fa-file-image text-info', jpeg:'fa-file-image text-info', png:'fa-file-image text-info',
    gif:'fa-file-image text-info', zip:'fa-file-zipper text-secondary', rar:'fa-file-zipper text-secondary',
    txt:'fa-file-lines text-muted', csv:'fa-file-csv text-success'
};
function fileIcon(path) {
    const ext = (path||'').split('.').pop().toLowerCase();
    return extIcons[ext] || 'fa-file text-muted';
}

// ── ESTADO → BADGE ──────────────────────────────────────────────────────────
function statusBadge(s) {
    const map = {
        'Borrador':   'badge-borrador',
        'En Revisión':'badge-revision',
        'Aprobado':   'badge-aprobado',
        'Archivado':  'badge-archivado'
    };
    return `<span class="badge ${map[s]||'badge-borrador'}">${s}</span>`;
}

// ── TAMAÑO LEGIBLE ───────────────────────────────────────────────────────────
function readableSize(bytes) {
    if (!bytes) return '-';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1048576).toFixed(2) + ' MB';
}

// ── INICIALIZACIÓN ───────────────────────────────────────────────────────────
$(document).ready(function() {
    loadFolderTree();
    loadEntities();
    initDocsTable();

    // Drag & drop
    const dz = document.getElementById('dropZone');
    dz.addEventListener('dragover',  e => { e.preventDefault(); dz.classList.add('drag-over'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('drag-over'));
    dz.addEventListener('drop', e => {
        e.preventDefault(); dz.classList.remove('drag-over');
        if (e.dataTransfer.files.length) {
            document.getElementById('fileInput').files = e.dataTransfer.files;
            document.getElementById('selectedFileName').textContent = e.dataTransfer.files[0].name;
        }
    });
    document.getElementById('fileInput').addEventListener('change', function() {
        document.getElementById('selectedFileName').textContent = this.files[0]?.name || '';
    });

    // Cambio de entidad combinada
    $('#entityCombined').on('change', function() {
        const val = this.value;
        if (!val) { $('#uEntityType').val(''); $('#uEntityId').val(''); return; }
        const parts = val.split('|');
        $('#uEntityType').val(parts[0]);
        $('#uEntityId').val(parts[1]);
    });

    // Submit subida
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        if (!$('#fileInput')[0].files.length && !$('#uDocId').val()) {
            Swal.fire('Atención','Selecciona un archivo primero.','warning'); return;
        }
        const fd = new FormData(this);
        fd.append('action','upload');
        $('#btnUpload').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Subiendo...');

        $.ajax({
            url: 'api/repositorio.php', type: 'POST', data: fd,
            processData: false, contentType: false,
            success: function(res) {
                if (res.success) {
                    $('#uploadModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({ icon:'success', title:'Éxito', text:res.message, timer:1800, showConfirmButton:false });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: GesDocHelpers.handleApiError
        }).always(function() {
            $('#btnUpload').prop('disabled', false).html('<i class="fa-solid fa-cloud-arrow-up me-1"></i> Subir Archivo');
        });
    });
});

// ── ÁRBOL DE CARPETAS ────────────────────────────────────────────────────────
function loadFolderTree() {
    $.get('api/repositorio.php?action=get_tree', function(res) {
        $('#folderTree').empty();
        renderTree(res.data || [], $('#folderTree'));
    }, 'json');
}

function renderTree(nodes, $parent) {
    nodes.forEach(function(node) {
        const $li   = $('<li>');
        const $item = $(`
            <div class="folder-item" id="folder-${node.id}" onclick="selectFolder(${node.id},'${node.name.replace(/'/g,"\\'")}')">
                <i class="fa-solid fa-folder"></i>
                <span class="me-1">${node.name}</span>
                <span class="actions">
                    <button onclick="event.stopPropagation();promptNewFolder(${node.id})" title="Subcarpeta">
                        <i class="fa-solid fa-plus" style="color:#0A4275;"></i>
                    </button>
                    <button onclick="event.stopPropagation();renameFolder(${node.id},'${node.name.replace(/'/g,"\\'")}')">
                        <i class="fa-solid fa-pen" style="color:#0A4275;"></i>
                    </button>
                    <button onclick="event.stopPropagation();deleteFolder(${node.id})">
                        <i class="fa-solid fa-trash" style="color:#dc2626;"></i>
                    </button>
                </span>
            </div>`);
        $li.append($item);
        if (node.children && node.children.length) {
            const $ul = $('<ul class="folder-tree">');
            renderTree(node.children, $ul);
            $li.append($ul);
        }
        $parent.append($li);
    });
}

function selectFolder(id, name) {
    currentFolderId   = id;
    currentFolderName = name;
    // Actualizar breadcrumb
    updateBreadcrumb(name);
    // Marcar activo
    $('.folder-item').removeClass('active');
    if (id) $(`#folder-${id}`).addClass('active');
    else     $('#root-item').addClass('active');
    // Actualizar título
    const icon = id ? 'fa-folder-open' : 'fa-inbox';
    $('#currentFolderTitle').html(`<i class="fa-solid ${icon} me-1"></i> ${name}`);
    // Recargar tabla
    const url = id ? `api/repositorio.php?action=list_docs&folder_id=${id}` : 'api/repositorio.php?action=list_docs';
    table.ajax.url(url).load();
}

function updateBreadcrumb(folderName) {
    const $bc = $('#breadcrumb');
    // Quitar items anteriores de carpeta
    $bc.find('.folder-crumb').remove();
    if (currentFolderId) {
        $bc.append(`<li class="breadcrumb-item folder-crumb active">${folderName}</li>`);
    }
}

// ── CRUD CARPETAS ─────────────────────────────────────────────────────────────
function promptNewFolder(parentId) {
    Swal.fire({
        title: parentId ? 'Nueva subcarpeta' : 'Nueva carpeta',
        input: 'text', inputPlaceholder: 'Nombre de la carpeta',
        showCancelButton: true, confirmButtonText: 'Crear',
        confirmButtonColor: '#0A4275', cancelButtonText: 'Cancelar',
        inputValidator: v => !v.trim() ? 'El nombre no puede estar vacío' : null
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/repositorio.php', { action:'create_folder', name:r.value, parent_id: parentId||'' }, function(res) {
                if (res.success) { loadFolderTree(); Swal.fire({ icon:'success', title:'Carpeta creada', timer:1200, showConfirmButton:false }); }
                else Swal.fire('Error', res.message, 'error');
            }, 'json');
        }
    });
}

function renameFolder(id, currentName) {
    Swal.fire({
        title: 'Renombrar carpeta', input:'text', inputValue: currentName,
        showCancelButton: true, confirmButtonText: 'Guardar',
        confirmButtonColor: '#0A4275', cancelButtonText: 'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/repositorio.php', { action:'rename_folder', id, name:r.value }, function(res) {
                if (res.success) loadFolderTree();
            }, 'json');
        }
    });
}

function deleteFolder(id) {
    Swal.fire({
        title:'¿Eliminar carpeta?', text:'Se eliminarán todos los documentos y subcarpetas dentro.', icon:'warning',
        showCancelButton:true, confirmButtonColor:'#d33', cancelButtonText:'Cancelar', confirmButtonText:'Sí, eliminar'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/repositorio.php', { action:'delete_folder', id }, function(res) {
                if (res.success) {
                    loadFolderTree();
                    if (currentFolderId == id) selectFolder(null,'Todos los documentos');
                }
            }, 'json');
        }
    });
}

// ── TABLA DOCUMENTOS ──────────────────────────────────────────────────────────
function initDocsTable() {
    table = $('#docsTable').DataTable({
        ajax: { url:'api/repositorio.php?action=list_docs', type:'GET', error: GesDocHelpers.handleApiError },
        columns: [
            {
                data: 'file_path',
                render: d => `<i class="fa-solid ${fileIcon(d)} fs-5"></i>`
            },
            { data: 'name' },
            {
                data: 'version',
                render: d => d ? `<span class="badge bg-secondary">${d}</span>` : '-'
            },
            {
                data: 'status',
                render: d => statusBadge(d)
            },
            {
                data: 'assignments',
                render: function(data) {
                    if (!data || !data.length) return '<span class="text-muted fst-italic">-</span>';
                    return data.slice(0,2).map(a =>
                        `<span class="badge" style="background:var(--light-blue);color:var(--primary-blue);font-weight:500;">
                            <i class="fa-solid ${a.entity_type==='Consortium'?'fa-handshake':'fa-building'} me-1"></i>${a.entity_name}
                         </span>`
                    ).join(' ') + (data.length>2 ? ` <small class="text-muted">+${data.length-2}</small>` : '');
                }
            },
            {
                data: 'file_size',
                render: d => readableSize(d)
            },
            {
                data: 'updated_at',
                render: d => d ? new Date(d).toLocaleDateString('es-ES') : '-'
            },
            {
                data: null, className:'text-center',
                render: function(data, type, row) {
                    return `
                        <button class="btn btn-sm btn-outline-success me-1" onclick="downloadDoc(${row.id})" title="Descargar">
                            <i class="fa-solid fa-download"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="newVersion(${row.id},'${row.name.replace(/'/g,"\\'")}',${row.id})" title="Nueva versión">
                            <i class="fa-solid fa-code-branch"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteDoc(${row.id})" title="Eliminar">
                            <i class="fa-solid fa-trash"></i>
                        </button>`;
                }
            }
        ],
        language: GesDocHelpers.dataTablesLang,
        order: [[6,'desc']]
    });
}

// ── ACCIONES DOCUMENTOS ───────────────────────────────────────────────────────
function openUploadModal(docId, docName) {
    $('#uploadForm')[0].reset();
    $('#selectedFileName').text('');
    $('#uDocId').val('');
    $('#uFolder').val(currentFolderId || '');
    $('#entitySection').show();
    $('#uploadModalTitle').html('<i class="fa-solid fa-upload me-2"></i>Subir Documento');
    if (docId) {
        $('#uDocId').val(docId);
        $('#entitySection').hide();
        $('#uploadModalTitle').html(`<i class="fa-solid fa-code-branch me-2"></i>Nueva versión: ${docName}`);
    }
    $('#uploadModal').modal('show');
}

function newVersion(docId, docName) {
    openUploadModal(docId, docName);
}

function downloadDoc(id) {
    window.open(`api/repositorio.php?action=download&id=${id}`, '_blank');
}

function deleteDoc(id) {
    Swal.fire({
        title:'¿Eliminar documento?', text:'Se eliminará el archivo físico y todas sus versiones.', icon:'warning',
        showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'Sí, eliminar', cancelButtonText:'Cancelar'
    }).then(r => {
        if (r.isConfirmed) {
            $.post('api/repositorio.php', { action:'delete_doc', id }, function(res) {
                if (res.success) { table.ajax.reload(); Swal.fire('Eliminado', res.message, 'success'); }
                else Swal.fire('Error', res.message, 'error');
            }, 'json');
        }
    });
}

// ── CARGAR ENTIDADES ──────────────────────────────────────────────────────────
function loadEntities() {
    $.get('api/repositorio.php?action=get_entities', function(res) {
        entities = res.data || [];
        const $companies   = $('#entCompanies').empty();
        const $consortiums = $('#entConsortiums').empty();
        entities.forEach(e => {
            const opt = `<option value="${e.type}|${e.id}">${e.name}</option>`;
            if (e.type === 'Company')    $companies.append(opt);
            else                         $consortiums.append(opt);
        });
    }, 'json');
}
</script>
</body>
</html>
