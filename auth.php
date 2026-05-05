<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, complete todos los campos.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login exitoso - Generar JWT
            require_once 'jwt_helper.php';
            
            $payload = [
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'user_role' => $user['role_name'],
                'role_id' => $user['role_id'],
                'iat' => time(),
                'exp' => time() + (30 * 60) // 30 minutos
            ];
            
            $jwt = JWT::encode($payload, JWT_SECRET);
            
            // Guardar en HTTPOnly Cookie para navegación tradicional (PHP)
            setcookie('gesdoc_token', $jwt, time() + (30 * 60), "/", "", false, true);

            echo json_encode(['success' => true, 'message' => 'Login exitoso', 'token' => $jwt, 'redirect' => 'index.php']);
        } else {
            // Credenciales inválidas
            echo json_encode(['success' => false, 'message' => 'Correo o contraseña incorrectos.']);
        }
    } catch (PDOException $e) {
        // En producción no se debe mostrar el error real de la base de datos
        error_log("Error en login: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
