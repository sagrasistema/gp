<?php
// update_feedback.php

header('Content-Type: application/json; charset=utf-8');

// Cargar configuración de base de datosa
include '../main/config.php';

try {
    // 1. Validar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido.', 405);
    }

    // 2. Sanitizar y validar entradas
    $pruebaId = filter_input(INPUT_POST, 'prueba_id', FILTER_VALIDATE_INT);
    $textoInadecuado = filter_input(INPUT_POST, 'texto_inadecuado', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    if ($pruebaId === false || $pruebaId === null) {
        throw new InvalidArgumentException('El ID de la prueba es inválido o no fue suministrado.');
    }

    if ($textoInadecuado === null) {
        throw new InvalidArgumentException('El estado de evaluación del texto es inválido.');
    }

    // Convertir booleano a entero para MySQL TINYINT
    $estadoValor = $textoInadecuado ? 1 : 0;

    // 3. Ejecutar actualización mediante Prepared Statement
    $sql = "UPDATE audit_pruebas 
            SET texto_inadecuado = :texto_inadecuado 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':texto_inadecuado', $estadoValor, PDO::PARAM_INT);
    $stmt->bindValue(':id', $pruebaId, PDO::PARAM_INT);
    
    $stmt->execute();

    // 4. Retornar respuesta estructurada
    echo json_encode([
        'success' => true,
        'message' => 'Feedback registrado correctamente.',
        'data' => [
            'prueba_id' => $pruebaId,
            'texto_inadecuado' => (bool)$estadoValor
        ]
    ]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    // Registrar el error real en logs del servidor, no exponerlo al cliente
    error_log("Database Error en update_feedback.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al procesar la actualización en la base de datos.']);
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}