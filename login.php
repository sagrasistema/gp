<?php
session_start();
require_once 'conect-proyecto.php'; // Asume que este archivo inicializa $pdo de forma segura

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            // Consulta preparada con PDO para prevenir Inyección SQL
            $stmt = $pdo->prepare('SELECT id, username, password, nombre_completo FROM usuarios WHERE username = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si el usuario existe y la contraseña es correcta
            if ($user && password_verify($password, $user['password'])) {
                // Prevenir ataques de fijación de sesión
                session_regenerate_id(true);

                // Asignar variables de sesión globales
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];

                // Redirigir al panel principal del sistema de auditoría
                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            // Manejo de errores seguro: no exponer detalles técnicos al usuario final
            error_log('Error en login: ' . $e->getMessage());
            $error = 'Ocurrió un error en el sistema. Intente más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso al Sistema - Auditoría</title>
    <link rel="stylesheet" href="assets/css/estilos.css"> <!-- Ajusta según tu estructura -->
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primary">Ingresar</button>
        </form>
    </div>
</body>
</html>