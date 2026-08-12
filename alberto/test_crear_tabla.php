<?php

declare(strict_types=1);

// Encabezado para visualizar la respuesta en formato JSON estructurado
header('Content-Type: application/json; charset=utf-8');

// Incluir tu archivo de configuración donde se define la conexión $pdo

include 'main/config.php'; 

try {
    // 1. Validar la disponibilidad de la conexión PDO
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('La variable $pdo no está definida o no es una instancia válida de PDO.');
    }

    // Configurar PDO para que lance excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Sentencia DDL para la creación de la tabla de prueba
    $sql = "CREATE TABLE IF NOT EXISTS `tabla_prueba_hosting` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `nombre_prueba` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    // 3. Ejecutar la sentencia en el servidor MySQL
    $pdo->exec($sql);

    // 4. Retornar respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => '¡Permiso confirmado! La tabla "tabla_prueba_hosting" se creó correctamente en el hosting.',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // Si el usuario de la BD no tiene permisos DDL (CREATE), se capturará aquí
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de Base de Datos (Posible falta de permisos DDL): ' . $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    // Captura de otros errores de ejecución/servidor
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de ejecución: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}