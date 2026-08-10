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
    
    // Captura dinámica o explícita del booleano (acepta 'texto_inadecuado2' o 'valor')
    $textoInadecuado2 = filter_input(INPUT_POST, 'texto_inadecuado2', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($textoInadecuado2 === null) {
        $textoInadecuado2 = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    if ($pruebaId === false || $pruebaId === null) {
        throw new InvalidArgumentException('El ID de la prueba es inválido o no fue suministrado.');
    }

    if ($textoInadecuado2 === null) {
        throw new InvalidArgumentException('El estado de evaluación del texto es inválido.');
    }

    // Convertir booleano a entero para MySQL (TINYINT)
    $estadoValor = $textoInadecuado2 ? 1 : 0;

    // 3. Ejecutar actualización mediante Prepared Statement en $pdo
    $sql = "UPDATE audit_pruebas 
            SET texto_inadecuado2 = :texto_inadecuado2 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':texto_inadecuado2', $estadoValor, PDO::PARAM_INT);
    $stmt->bindValue(':id', $pruebaId, PDO::PARAM_INT);
    
    $stmt->execute();

    // 4. Retornar respuesta estructurada idéntica a update_feedback.php
    echo json_encode([
        'success' => true,
        'message' => 'Feedback de norma registrado correctamente.',
        'data' => [
            'prueba_id' => $pruebaId,
            'texto_inadecuado2' => (bool)$estadoValor
        ]
    ]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    // Log interno en servidor sin revelar credenciales ni detalles de estructura
    error_log("Database Error en guardar_feedback_norma.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al procesar la actualización en la base de datos.']);
} catch (Exception $e) {
    $code = ($e->getCode() >= 400 && $e->getCode() <= 599) ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}