<?php
// api/auth_helper.php
// Funciones compartidas de autenticación y auditoría para todos los endpoints API.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt_helper.php';

/**
 * Valida el JWT desde el header Authorization o la Cookie HttpOnly.
 * Retorna el payload del token si es válido, false en caso contrario.
 */
function getValidUser(): array|false {
    $jwt = null;

    $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
    if (!empty($headers['Authorization'])) {
        preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
        if (isset($matches[1])) {
            $jwt = $matches[1];
        }
    }

    if (!$jwt && !empty($_COOKIE['gesdoc_token'])) {
        $jwt = $_COOKIE['gesdoc_token'];
    }

    if (!$jwt) {
        return false;
    }

    return JWT::decode($jwt, JWT_SECRET);
}

/**
 * Registra una acción en la tabla audit_logs.
 *
 * @param PDO        $pdo      Conexión activa a la BD.
 * @param int|null   $user_id  ID del usuario que ejecuta la acción.
 * @param string     $action   Descripción corta de la acción (ej. "empresa.create").
 * @param string     $details  Detalle adicional en texto libre (ej. JSON de los datos).
 */
function logAudit(PDO $pdo, ?int $user_id, string $action, string $details = ''): void {
    try {
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)")
            ->execute([$user_id, $action, $details]);
    } catch (PDOException $e) {
        // El log de auditoría no debe interrumpir la operación principal.
        error_log("Error en audit_log: " . $e->getMessage());
    }
}
