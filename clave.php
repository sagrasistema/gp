<?php
/**
 * Script Temporal de Actualización de Credenciales
 * Sistema SAGRAGP
 * PHP 8.x + PDO
 */

// Inclusión del archivo de configuración (Asegúrate de que la ruta sea correcta)
require_once 'main/config.php';

// 1. Validar que la conexión PDO se haya establecido correctamente en 'main/config'
if (!isset($pdo) || !$pdo instanceof PDO) {
    error_log('Error crítico: La variable $pdo no está instanciada en clave.php.');
    exit('Error del sistema: No hay conexión disponible con la base de datos.');
}

// 2. Definición de la contraseña y generación segura del hash
$passwordClara = 'Sagra2026*';
$nuevoHash = password_hash($passwordClara, PASSWORD_DEFAULT);

// Nombre de la variable corregido (sin espacios)
$usuarios = ['jgodoy', 'avalera'];

try {
    // 3. Preparar la consulta UPDATE (Previene Inyección SQL)
    $sql = "UPDATE usuarios SET password = :password WHERE username = :username";
    $stmt = $pdo->prepare($sql);
    
    echo "<h2>Actualización de Contraseñas</h2>";
    
    // 4. Iterar sobre el array de usuarios y ejecutar la consulta
    foreach ($usuarios as $username) {
        $stmt->execute([
            'password' => $nuevoHash,
            'username' => $username
        ]);
        
        // Sanitizar el output visual por buenas prácticas de seguridad (XSS)
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        echo "Contraseña actualizada exitosamente para el usuario: <b>{$safeUsername}</b><br>";
    }
    
    echo "<br><b style='color: #00bcd4;'>¡Proceso completado! Ya puedes eliminar este archivo del servidor y <a href='login.php' style='color: #000000;'>iniciar sesión</a>.</b>";

} catch (PDOException $e) {
    // 5. Manejo robusto de excepciones (Registrar error en log, no mostrar al usuario)
    error_log("Error PDO en actualización de claves (clave.php): " . $e->getMessage());
    echo "Ocurrió un error al actualizar la base de datos. Por favor, revisa el log de errores del servidor.";
}