<?php
require_once 'config.php';
require_once 'jwt_helper.php';

$jwt = $_COOKIE['gesdoc_token'] ?? null;
$userData = null;

if ($jwt) {
    $userData = JWT::decode($jwt, JWT_SECRET);
}

// Verificación de sesión JWT
if (!$userData) {
    // Si el token es inválido o expiró, limpiamos y redirigimos
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
    <title>Inicio - GesDoc</title>
    <!-- Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="assets/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= APP_VERSION ?>">
    
    <style>
        .module-card {
            cursor: pointer;
            border-left: 4px solid var(--primary-blue);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 .5rem 1rem rgba(10, 66, 117, 0.15) !important;
            border-color: var(--accent-blue);
        }
        .module-icon-container {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--light-blue);
            border-radius: 8px;
            color: var(--primary-blue);
        }
    </style>
</head>
<body>

<!-- Navbar Profesional (Sin Menú Principal) -->
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
        <!-- Aquí se irán agregando los items dinámicamente según la página -->
      </ol>
    </nav>

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: var(--primary-blue);">Panel Principal</h2>
            <p class="text-muted">Seleccione el módulo al que desea acceder.</p>
        </div>
    </div>

    <!-- Tarjetas de Navegación -->
    <div class="row g-4">
        <!-- Módulo de Empresas -->
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 module-card shadow-sm border-0" onclick="window.location.href='empresas.php'">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="module-icon-container me-3">
                            <i class="fa-solid fa-building fs-4"></i>
                        </div>
                        <h5 class="card-title mb-0 text-dark">Empresas</h5>
                    </div>
                    <p class="card-text text-muted small">Gestión de empresas individuales y su información básica.</p>
                </div>
            </div>
        </div>

        <!-- Módulo de Consorcios -->
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 module-card shadow-sm border-0" onclick="window.location.href='consorcios.php'">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="module-icon-container me-3">
                            <i class="fa-solid fa-handshake fs-4"></i>
                        </div>
                        <h5 class="card-title mb-0 text-dark">Consorcios</h5>
                    </div>
                    <p class="card-text text-muted small">Creación de uniones temporales y control de participación.</p>
                </div>
            </div>
        </div>
        
        <!-- Módulo de Documentos -->
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 module-card shadow-sm border-0" onclick="window.location.href='repositorio.php'">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="module-icon-container me-3">
                            <i class="fa-solid fa-folder-open fs-4"></i>
                        </div>
                        <h5 class="card-title mb-0 text-dark">Repositorio</h5>
                    </div>
                    <p class="card-text text-muted small">Explorador de archivos, subida y control de versiones documental.</p>
                </div>
            </div>
        </div>

        <!-- Módulo de Consulta Documental -->
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 module-card shadow-sm border-0" onclick="window.location.href='consulta_documental.php'">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="module-icon-container me-3">
                            <i class="fa-solid fa-magnifying-glass fs-4"></i>
                        </div>
                        <h5 class="card-title mb-0 text-dark">Consulta Documental</h5>
                    </div>
                    <p class="card-text text-muted small">Buscador avanzado de documentos y contratos por consorcio/empresa.</p>
                </div>
            </div>
        </div>

        <!-- Módulo de Procesos Licitatorios -->
        <div class="col-md-4 col-sm-6">
            <div class="card h-100 module-card shadow-sm border-0" onclick="window.location.href='procesos.php'">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="module-icon-container me-3">
                            <i class="fa-solid fa-file-contract fs-4"></i>
                        </div>
                        <h5 class="card-title mb-0 text-dark">Procesos</h5>
                    </div>
                    <p class="card-text text-muted small">Gestión de licitaciones, análisis financiero y cotejo de indicadores.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/jquery-3.7.0.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>
<script src="assets/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/js/sweetalert2.all.min.js"></script>

</body>
</html>
