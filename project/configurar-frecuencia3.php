<?php

declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// v/proyectos/configurar-frecuencia3.php
include '../main/config.php';

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) 
    ?? filter_input(INPUT_POST, 'proyecto_id', FILTER_VALIDATE_INT);

if (!$proyectoId) {
    die("Error: ID de proyecto no especificado o inválido.");
}

// -------------------------------------------------------------------------
// PROCESAR GUARDADO DE FRECUENCIA (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_frequency'])) {
    $frecuenciaCant = filter_input(INPUT_POST, 'frecuencia_cantidad', FILTER_VALIDATE_INT);

    if ($frecuenciaCant && $frecuenciaCant >= 1 && $frecuenciaCant <= 12) {
        try {
            $stmtUpdate = $pdo->prepare("
                UPDATE proyectos 
                SET frecuencia_cantidad = :frecuencia 
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':frecuencia' => $frecuenciaCant,
                ':id'         => $proyectoId
            ]);

            header("Location: seleccionar-pruebas3.php?proyectoId={$proyectoId}&frecuencia=1");
            exit();
        } catch (PDOException $e) {
            error_log("Error al guardar frecuencia del proyecto: " . $e->getMessage());
            $errorMessage = "Error al actualizar la frecuencia en la base de datos.";
        }
    } else {
        $errorMessage = "Por favor selecciona un número de frecuencias válido (entre 1 y 12).";
    }
}

// -------------------------------------------------------------------------
// CARGAR DATOS DEL PROYECTO
// -------------------------------------------------------------------------
try {
    $stmtProj = $pdo->prepare("
        SELECT p.*, c.name AS clientName 
        FROM proyectos p 
        INNER JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = :id
    ");
    $stmtProj->execute([':id' => $proyectoId]);
    $projectData = $stmtProj->fetch(PDO::FETCH_OBJ);

    if (!$projectData) {
        die("Error: Proyecto no encontrado.");
    }
} catch (PDOException $e) {
    error_log("Error al consultar proyecto: " . $e->getMessage());
    die("Error al cargar la vista de configuración.");
}

$pageTitle = "Configurar Frecuencia del Proyecto";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container" style="padding: 2rem; max-width: 800px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0;">
                <i class="ri-calendar-event-line" style="color: #2563eb;"></i> Configuración de Periodicidad
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Cliente: <strong><?= htmlspecialchars($projectData->clientName, ENT_QUOTES, 'UTF-8') ?></strong> | 
                Proyecto: <strong><?= htmlspecialchars($projectData->nombre, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
        <a href="responder3.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
            <i class="ri-arrow-left-line"></i> Volver
        </a>
    </div>

    <?php if (isset($errorMessage)): ?>
        <div style="padding:1rem; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="configurar-frecuencia3.php">
        <input type="hidden" name="action_save_frequency" value="1">
        <input type="hidden" name="proyecto_id" value="<?= $proyectoId ?>">

        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <label for="frecuencia_cantidad" style="display: block; font-weight: 700; color: #334155; margin-bottom: 0.5rem;">
                Número de Frecuencias / Revisiones al Año
            </label>
            <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 1rem;">
                Indica cuántas ejecuciones periódicas se realizarán durante la auditoría (Ejemplo: 1 para Anual, 2 para Semestral, 4 para Trimestral, 12 para Mensual).
            </p>

            <select name="frecuencia_cantidad" id="frecuencia_cantidad" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; color: #0f172a; margin-bottom: 1.5rem;">
                <?php 
                $currentFreq = (int)($projectData->frecuencia_cantidad ?? 1);
                for ($i = 1; $i <= 12; $i++): 
                ?>
                    <option value="<?= $i ?>" <?= $i === $currentFreq ? 'selected' : '' ?>>
                        <?= $i ?> <?= $i === 1 ? 'Frecuencia (Única / Anual)' : "Frecuencias (" . $i . " revisiones en el año)" ?>
                    </option>
                <?php endfor; ?>
            </select>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a href="responder3.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" style="background: #2563eb; color: #ffffff; border: none; padding: 0.6rem 1.25rem; border-radius: 6px; font-weight: 600; cursor: pointer;">
                    <i class="ri-save-line"></i> Guardar y Configurar Pruebas
                </button>
            </div>
        </div>
    </form>
</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>