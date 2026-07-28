<?php
declare(strict_types=1);

// Establecer cabecera estricta para respuestas JSON
header('Content-Type: application/json; charset=UTF-8');

session_start();

try {
    // 1. Validar estrictamente el método HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido.');
    }

    // 2. Incluir la conexión a la base de datos (ajusta la ruta según tu estructura)
    require_once '../main/config.php'; // Debe retornar una instancia válida de PDO ($pdo)

    // 3. Sanitizar y validar los datos recibidos del formulario modal
    $idPrueba = filter_input(INPUT_POST, 'id_prueba', FILTER_VALIDATE_INT);
    $tipo = trim($_POST['tipo'] ?? ''); // Recibe 'activo', 'pasivo' o 'patrimonio' del input oculto
    $descripcion = trim($_POST['descripcion'] ?? '');
    $monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);

    // Validaciones de negocio
    if (!$idPrueba) {
        throw new Exception('Identificador de prueba inválido.');
    }

    $tiposPermitidos = ['activo', 'pasivo', 'patrimonio'];
    if (!in_array($tipo, $tiposPermitidos, true)) {
        throw new Exception('El tipo de partida analítica especificado no es válido.');
    }

    if (empty($descripcion)) {
        throw new Exception('La descripción o nombre de la cuenta es obligatoria.');
    }

    // 4. Inserción segura en base de datos usando PDO (Sentencias Preparadas)
    $sql = "INSERT INTO partidas_analiticas_prueba11 (id_prueba, tipo, descripcion, monto, fecha_creacion) 
            VALUES (:id_prueba, :tipo, :descripcion, :monto, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_prueba'   => $idPrueba,
        ':tipo'        => $tipo,
        ':descripcion' => htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8'),
        ':monto'       => $monto !== false ? $monto : 0.00
    ]);

    // 5. Respuesta exitosa al cliente
    echo json_encode([
        'status'  => 'success',
        'message' => 'Partida analítica guardada exitosamente.'
    ]);

} catch (PDOException $e) {
    // Registro interno del error de BD (no exponer detalles sensibles al usuario)
    error_log('Error en BD (guardar-analitica-11.php): ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Ocurrió un error interno al intentar guardar la información.'
    ]);

} catch (Exception $e) {
    // Manejo de errores de validación de negocio
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}