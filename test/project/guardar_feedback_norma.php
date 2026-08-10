<?php
// guardar_feedback_norma.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Cargar configuración de base de datos
require_once '../main/config.php';

try {
    // 1. Validar método de solicitud HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido.', 405);
    }

    // 2. Sanitizar y validar entradas desde POST
    $pruebaId = filter_input(INPUT_POST, 'prueba_id', FILTER_VALIDATE_INT);
    
    // Captura dinámica del booleano (acepta 'texto_inadecuado2' o 'valor')
    $textoInadecuado2 = filter_input(INPUT_POST, 'texto_inadecuado2', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($textoInadecuado2 === null) {
        $textoInadecuado2 = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    if ($pruebaId === false || $pruebaId === null || $pruebaId <= 0) {
        throw new InvalidArgumentException('El ID de la prueba es inválido o no fue suministrado.');
    }

    if ($textoInadecuado2 === null) {
        throw new InvalidArgumentException('El estado de evaluación de la norma es inválido.');
    }

    // Convertir booleano a entero para MySQL (TINYINT)
    $estadoValor = $textoInadecuado2 ? 1 : 0;

    // Asegurar que exista la conexión PDO
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Error de configuración: La conexión PDO ($pdo) no está disponible.');
    }

    // 3. Autocommit / Transacción segura
    $inTransaction = $pdo->inTransaction();
    if (!$inTransaction) {
        $pdo->beginTransaction();
    }

    // 4. Ejecutar actualización mediante Prepared Statement
    $sql = "UPDATE audit_pruebas 
            SET texto_inadecuado2 = :texto_inadecuado2 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':texto_inadecuado2', $estadoValor, PDO::PARAM_INT);
    $stmt->bindValue(':id', $pruebaId, PDO::PARAM_INT);
    $stmt->execute();

    $filasAfectadas = $stmt->rowCount();

    // Confirmar transacción si nosotros la iniciamos
    if (!$inTransaction && $pdo->inTransaction()) {
        $pdo->commit();
    }

    // 5. Verificar si el registro existía
    if ($filasAfectadas === 0) {
        // Verificar si la fila existe pero no cambió de valor, o si el ID no existe
        $checkStmt = $pdo->prepare("SELECT id, texto_inadecuado2 FROM audit_pruebas WHERE id = :id LIMIT 1");
        $checkStmt->bindValue(':id', $pruebaId, PDO::PARAM_INT);
        $checkStmt->execute();
        $registroActual = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$registroActual) {
            throw new InvalidArgumentException("No se encontró ningún registro con el ID: {$pruebaId} en la tabla audit_pruebas.");
        }
    }

    // 6. Retornar respuesta estructurada
    echo json_encode([
        'success' => true,
        'message' => 'Feedback de norma actualizado correctamente en la base de datos.',
        'data' => [
            'prueba_id' => $pruebaId,
            'texto_inadecuado2' => (bool)$estadoValor,
            'rows_affected' => $filasAfectadas
        ]
    ]);

} catch (InvalidArgumentException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database Error en guardar_feedback_norma.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al procesar la actualización en la base de datos.']);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $code = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}