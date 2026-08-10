<?php
// guardar_feedback_norma.php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificación de autenticación (Ajustar según la sesión de la app)
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada o no autorizada.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método HTTP no permitido.']);
    exit;
}

try {
    // Sanitización y Validación de entradas
    $pruebaId = filter_var($_POST['prueba_id'] ?? null, FILTER_VALIDATE_INT);
    $campo    = filter_var($_POST['campo'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $valor    = filter_var($_POST['valor'] ?? null, FILTER_VALIDATE_INT);

    // Validación estricta de parámetros
    if ($pruebaId === false || $pruebaId === null || $campo !== 'texto_inadecuado2' || $valor === false) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parámetros de solicitud inválidos.']);
        exit;
    }

    $valorNormalizado = ($valor === 1) ? 1 : 0;

    // Instancia de conexión PDO (Usar singleton/configuración global del proyecto)
    // $pdo = DB::getInstance(); 
    $pdo = new PDO(
        'mysql:host=localhost;dbname=audit_db;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Sentencia preparada para prevenir Inyección SQL
    $sql = "UPDATE pruebas 
            SET texto_inadecuado2 = :valor,
                updated_at = NOW() 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':valor', $valorNormalizado, PDO::PARAM_INT);
    $stmt->bindValue(':id', $pruebaId, PDO::PARAM_INT);
    
    $stmt->execute();

    echo json_encode([
        'success' => true, 
        'message' => 'Estado de la norma actualizado correctamente.',
        'campo'   => 'texto_inadecuado2',
        'valor'   => $valorNormalizado
    ]);

} catch (PDOException $e) {
    error_log(sprintf('[DB Error] File: %s Line: %d Msg: %s', $e->getFile(), $e->getLine(), $e->getMessage()));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos al guardar la evaluación.']);
} catch (Throwable $e) {
    error_log(sprintf('[System Error] File: %s Line: %d Msg: %s', $e->getFile(), $e->getLine(), $e->getMessage()));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno en el servidor.']);
}