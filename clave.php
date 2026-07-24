<?php
/**
 * Script Temporal de Actualización de Credenciales
 * Sistema SAGRAGP
 */

require_once 'main/config';

$passwordClara = 'Sagra2026*';
// Generar un hash BCRYPT válido y seguro en el servidor actual
$nuevoHash = password_hash($passwordClara, PASSWORD_DEFAULT);

$usuariosAC Actualizar = ['jgodoy', 'avalera'];

try {
    $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE username = :username");
    
    echo "<h2>Actualización de Contraseñas</h2>";
    foreach ($usuariosAC Actualizar as $username) {
        $stmt->execute([
            'password' => $nuevoHash,
            'username' => $username
        ]);
        echo "Contraseña actualizada exitosamente para el usuario: <b>{$username}</b><br>";
    }
    
    echo "<br><b style='color: #00bcd4;'>¡Proceso completado! Ya puedes eliminar este archivo del servidor y <a href='login.php' style='color: #ffffff;'>iniciar sesión</a>.</b>";

} catch (PDOException $e) {
    error_log("Error en actualización de claves: " . $e->getMessage());
    echo "Ocurrió un error al actualizar la base de datos.";
}