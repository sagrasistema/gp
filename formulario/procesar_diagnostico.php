<?php
// procesar_diagnostico.php
header('Content-Type: application/json; charset=utf-8');

// Cargar PDO desde tu archivo de configuración
require_once __DIR__ . '/config.php'; // Asegúrate de definir $pdo aquí

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    // 1. Sanitizar y validar datos de la empresa
    $empresa = filter_input(INPUT_POST, 'nombre_empresa', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $contacto = filter_input(INPUT_POST, 'nombre_contacto', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email_contacto', FILTER_VALIDATE_EMAIL);
    $sector = filter_input(INPUT_POST, 'sector', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $respuestas = $_POST['preguntas'] ?? [];

    if (!$empresa || !$contacto || !$email) {
        throw new InvalidArgumentException('Por favor, completa todos los datos de contacto correctamente.');
    }

    if (!is_array($respuestas) || count($respuestas) === 0) {
        throw new InvalidArgumentException('Debes responder las preguntas del diagnóstico.');
    }

    // 2. Validar que los puntajes estén entre 1 y 5
    $puntuacionTotal = 0;
    $respuestasValidadas = [];

    foreach ($respuestas as $preguntaId => $val) {
        $score = filter_var($val, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5]
        ]);

        if ($score === false) {
            throw new InvalidArgumentException("Respuesta inválida en la pregunta: {$preguntaId}");
        }

        $respuestasValidadas[$preguntaId] = $score;
        $puntuacionTotal += $score;
    }

    // 3. Persistir en BD con Transacción
    $pdo->beginTransaction();

    // Insertar Empresa
    $stmtEmpresa = $pdo->prepare("
        INSERT INTO empresas_diagnostico (nombre_empresa, nombre_contacto, email_contacto, sector, puntuacion_total)
        VALUES (:empresa, :contacto, :email, :sector, :total)
    ");
    $stmtEmpresa->execute([
        ':empresa'  => $empresa,
        ':contacto' => $contacto,
        ':email'    => $email,
        ':sector'   => $sector ?: 'No especificado',
        ':total'    => $puntuacionTotal
    ]);

    $empresaId = (int)$pdo->lastInsertId();

    // Insertar Respuestas Detalladas
    $stmtRespuesta = $pdo->prepare("
        INSERT INTO respuestas_diagnostico (empresa_id, pregunta_id, puntaje)
        VALUES (:empresa_id, :pregunta_id, :puntaje)
    ");

    foreach ($respuestasValidadas as $preguntaId => $score) {
        $stmtRespuesta->execute([
            ':empresa_id'  => $empresaId,
            ':pregunta_id' => (string)$preguntaId,
            ':puntaje'     => $score
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Diagnóstico registrado con éxito.',
        'puntuacion_total' => $puntuacionTotal,
        'max_posible' => count($respuestasValidadas) * 5
    ]);

} catch (InvalidArgumentException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error BD Diagnóstico: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno en el servidor al guardar el diagnóstico.']);
}