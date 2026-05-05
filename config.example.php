<?php
// config.php
// INSTRUCCIONES: Copia este archivo como config.php y completa los valores reales.
// NUNCA subas config.php al repositorio (está en .gitignore).

session_start();

// ── Seguridad JWT ────────────────────────────────────────────────────────────
define('JWT_SECRET', 'CAMBIA_ESTO_POR_UNA_CLAVE_SECRETA_LARGA_Y_ALEATORIA');

// ── Versión de la app (para cache-busting de CSS/JS) ────────────────────────
define('APP_VERSION', '1.0.1');

// ── Base de Datos ─────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Usuario de MySQL
define('DB_PASS', '');               // Contraseña de MySQL
define('DB_NAME', 'gesdoc');

// ── Conexión PDO ──────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// ── Almacenamiento Externo ────────────────────────────────────────────────────
// Windows (desarrollo): 'D:\gesdoc_storage'
// Linux/Hostinger:      '/home/usuario/gesdoc_storage'
define('EXTERNAL_STORAGE_PATH', 'D:' . DIRECTORY_SEPARATOR . 'gesdoc_storage');

if (!file_exists(EXTERNAL_STORAGE_PATH)) {
    @mkdir(EXTERNAL_STORAGE_PATH, 0777, true);
}

// ── SMTP (Hostinger u otro proveedor) ─────────────────────────────────────────
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_USER', 'correo@tudominio.com');
define('SMTP_PASS', 'TuPasswordSMTP');
define('SMTP_PORT', 465);

// ── URL base del proyecto ─────────────────────────────────────────────────────
define('BASE_URL', 'http://localhost/gesdoc');
?>
