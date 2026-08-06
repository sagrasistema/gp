<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación de sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once 'conect-actividades.php';

// 2. Procesar únicamente peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proyectoId = filter_input(INPUT_POST, 'proyecto_id', FILTER_VALIDATE_INT);
    $pruebaId = filter_input(INPUT_POST, 'prueba_id', FILTER_VALIDATE_INT);
    $estadoPrueba = trim($_POST['estado_prueba'] ?? '');

    // Validar parámetros obligatorios
    if (!$proyectoId || !$pruebaId || empty($estadoPrueba)) {
        header('Location: actividades.php?proyectoId=' . ($proyectoId ?? 0) . '&pruebaId=' . ($pruebaId ?? 0) . '&error=invalid_params');
        exit;
    }

    try {
        // 3. Actualización segura usando sentencias preparadas PDO 
        // Nota: Se omite updated_at temporalmente por compatibilidad con tablas legacy si no existe la columna.
        $stmt = $pdo->prepare("
            UPDATE pruebas_proyecto 
            SET estado_prueba = :estado_prueba 
            WHERE id = :prueba_id AND proyecto_id = :proyecto_id
        ");
        
        $stmt->execute([
            ':estado_prueba' => $estadoPrueba,
            ':prueba_id'     => $pruebaId,
            ':proyecto_id'   => $proyectoId
        ]);

        // 4. Redirección exitosa
        header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&success=1");
        exit;

    } catch (Exception $e) {
        // Registrar error internamente en el servidor sin exponerlo al usuario
        error_log("Error crítico al actualizar el estado de la prueba: " . $e->getMessage());
        header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&error=save_failed");
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}