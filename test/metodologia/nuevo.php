<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación ANTES de imprimir cualquier HTML
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Conexión a la base de datos necesaria para la transacción
require_once '../main/config.php';

$mensajeError = '';

// 2. Procesar formulario POST e iniciar redirección de inmediato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceName = trim($_POST['serviceName'] ?? '');

    if (empty($serviceName)) {
        $mensajeError = 'El nombre del servicio de auditoría es obligatorio.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO audit_servicios (serviceName) VALUES (:serviceName)");
            $stmt->execute([':serviceName' => $serviceName]);

            $_SESSION['flash_success'] = 'Servicio creado exitosamente.';
            
            // Limpiamos cualquier búfer activo antes de enviar el header
            if (ob_get_length()) {
                ob_clean();
            }

            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            error_log("Error al crear servicio: " . $e->getMessage());
            $mensajeError = 'Ocurrió un error al intentar guardar el servicio en la base de datos.';
        }
    }
}

// 3. Carga de componentes de la vista SOLO si NO hubo redirección POST exitosa
include '../main/h.php';
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'metodologia';

include '../main/layout_header.php';
?>

<div class="view-container">
    <div class="view-header">
        <h1 class="page-main-title">
            <i class="ri-add-circle-line"></i> Crear Nuevo Servicio de Auditoría
        </h1>
    </div>

    <div class="table-actions-container">
        <a href="index.php" class="btn btn-primary" data-tooltip="Volver al Listado">
            <i class="ri-arrow-go-back-line"></i> Volver
        </a>
    </div>

    <?php if (!empty($mensajeError)): ?>
        <div style="background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 0.8rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 600;">
            <?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="table-container" style="padding: 2rem; background: #ffffff; border-radius: 8px;">
        <form action="nuevo.php" method="POST" style="max-width: 600px;">
            <div style="margin-bottom: 1.5rem;">
                <label for="serviceName" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">
                    Nombre del Servicio <span style="color: red;">*</span>
                </label>
                <input 
                    type="text" 
                    id="serviceName" 
                    name="serviceName" 
                    required 
                    placeholder="Ej. Auditoría de Sistemas de Información" 
                    style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box;"
                >
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="background-color: #08855b; border: none; padding: 0.75rem 1.5rem; font-weight: 600; cursor: pointer; border-radius: 6px; color: #fff;">
                    <i class="ri-save-line"></i> Guardar Servicio
                </button>
                <a href="index.php" class="btn" style="background-color: #64748b; color: #fff; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600;">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php
include '../main/layout_footer.php';
include '../main/footer.php';
?>