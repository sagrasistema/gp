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

    // Validar parámetro obligatorio
    if (!$proyectoId) {
        header('Location: index.php?error=invalid_params');
        exit;
    }

    try {
        // 3. Actualización segura en la tabla 'proyectos' estableciendo statusId = 2
        $stmt = $pdo->prepare("
            UPDATE proyectos 
            SET statusId = 2, 
                updated_at = NOW() 
            WHERE id = :proyecto_id
        ");
        
        $stmt->execute([
            ':proyecto_id' => $proyectoId
        ]);

        // 4. Redirección exitosa
        header("Location: actividades.php?proyectoId={$proyectoId}&success=project_closed");
        exit;

    } catch (Exception $e) {
        error_log("Error al cerrar el proyecto: " . $e->getMessage());
        header("Location: actividades.php?proyectoId={$proyectoId}&error=close_failed");
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}