<?php
/**
 * Configuración y Conexión a Base de Datos - PDO
 */
$host = 'localhost';
$db_name = 'sagracom_alberto_1';
$username = 'sagracom_alberto_t';
$password = 'sagragp2705';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Registrar el error en los logs del servidor por seguridad
    error_log("Error de conexión a BD: " . $e->getMessage());
    
    // Detener la ejecución con un mensaje limpio para evitar romper la interfaz
    http_response_code(500);
    exit('Error crítico: No se pudo establecer la conexión con la base de datos.');
}