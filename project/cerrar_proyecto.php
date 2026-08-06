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

include '../main/config.php';
include 'conect-proyecto.php';

// 2. Procesar únicamente peticiones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar de forma directa y segura
    $proyectoId = isset($_POST['proyecto_id']) ? (int)$_POST['proyecto_id'] : 0;

    // Si el ID es inválido o 0, mostramos qué llegó exactamente para diagnosticar
    if ($proyectoId <= 0) {
        echo "<div style='font-family: monospace; padding: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #f87171; border-radius: 8px;'>";
        echo "<h3>⚠️ Error de Diagnóstico: ID de proyecto no detectado</h3>";
        echo "<p>El script no recibió un <strong>proyecto_id</strong> válido por el método POST.</p>";
        echo "<hr><p><strong>Contenido recibido en \$_POST:</strong></p>";
        echo "<pre>" . htmlspecialchars(print_r($_POST, true)) . "</pre>";
        echo "<p><a href='javascript:history.back()'>← Regresar al formulario</a></p>";
        echo "</div>";
        exit;
    }

    try {
        // 3. Actualizar el statusId a 2 en la tabla 'proyectos'
        $stmt = $pdo->prepare("
            UPDATE proyectos 
            SET statusId = 2 
            WHERE id = :proyecto_id
        ");
        
        $stmt->execute([
            ':proyecto_id' => $proyectoId
        ]);

        // 4. Redirección exitosa de vuelta a actividades
        header("Location: actividades.php?proyectoId={$proyectoId}&success=project_closed");
        exit;

    } catch (Exception $e) {
        echo "<div style='font-family: monospace; padding: 20px; background: #fee2e2; color: #991b1b; border: 1px solid #f87171; border-radius: 8px;'>";
        echo "<h3>⚠️ Error de Base de Datos:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><a href='javascript:history.back()'>← Regresar</a></p>";
        echo "</div>";
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}