<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include '../main/h.php';
include '../main/config.php';

// Validar parámetros GET
$serviceId   = filter_input(INPUT_GET, 'serviceId', FILTER_VALIDATE_INT);
$etapaActiva = filter_input(INPUT_GET, 'etapa', FILTER_VALIDATE_INT) ?: 1;

if (!$serviceId) {
    header('Location: index.php');
    exit;
}

$mensajeError = '';
$mensajeExito = '';

// 2. Obtener datos del Servicio activo
try {
    $stmtServ = $pdo->prepare("SELECT Id, serviceName FROM audit_servicios WHERE Id = :serviceId");
    $stmtServ->execute([':serviceId' => $serviceId]);
    $servicio = $stmtServ->fetch(PDO::FETCH_OBJ);

    if (!$servicio) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error al consultar servicio: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

// 3. Procesar creación de Categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'crear_categoria') {
    $nombreCat = trim($_POST['nombre_categoria'] ?? '');

    if (empty($nombreCat)) {
        $mensajeError = 'El nombre de la categoría es obligatorio.';
    } else {
        try {
            $stmtInsCat = $pdo->prepare("
                INSERT INTO audit_categorias (serviceId, etapa_id, nombre) 
                VALUES (:serviceId, :etapa_id, :nombre)
            ");
            $stmtInsCat->execute([
                ':serviceId' => $serviceId,
                ':etapa_id'  => $etapaActiva,
                ':nombre'    => $nombreCat
            ]);

            $mensajeExito = 'Categoría registrada correctamente.';
        } catch (PDOException $e) {
            error_log("Error al insertar categoría: " . $e->getMessage());
            $mensajeError = 'No se pudo guardar la categoría. Verifique la estructura de la tabla audit_categorias.';
        }
    }
}

// 4. Consultar Categorías asociadas a este Servicio y a la Etapa actual
$categorias = [];
try {
    $stmtCat = $pdo->prepare("
        SELECT id, nombre 
        FROM audit_categorias 
        WHERE serviceId = :serviceId AND etapa_id = :etapa_id 
        ORDER BY id ASC
    ");
    $stmtCat->execute([
        ':serviceId' => $serviceId,
        ':etapa_id'  => $etapaActiva
    ]);
    $categorias = $stmtCat->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al consultar categorías: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="../main/layout.css">

<!-- Estilos específicos para la barra de navegación por etapas -->
<style>
    .project-stages-bar { display: flex; gap: 12px; margin: 20px 0; flex-wrap: wrap; }
    .stage-btn { flex: 1; min-width: 180px; padding: 14px 20px; background-color: #1e3a5f; border: 1px solid #2b4c7e; border-radius: 8px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 10px; text-transform: uppercase; transition: all 0.3s ease; }
    .stage-btn:hover { background-color: #2b4c7e; color: #ffffff; }
    .stage-btn.active { background-color: #0f1c2e; border: 2px solid #00bcd4; box-shadow: 0 4px 15px rgba(0, 188, 212, 0.25); color: #ffffff; }
</style>

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
            <i class="ri-node-tree"></i> Metodología: <?= htmlspecialchars($servicio->serviceName, ENT_QUOTES, 'UTF-8') ?>
        </h1>
    </div>

    <div class="table-actions-container">
        <a href="index.php" class="btn btn-primary" data-tooltip="Volver a Servicios">
            <i class="ri-arrow-go-back-line"></i> Volver a Servicios
        </a>
    </div>

    <?php if (!empty($mensajeExito)): ?>
        <div style="background-color: #d1fae5; border: 1px solid #34d399; color: #065f46; padding: 0.8rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-weight: 600;">
            <?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($mensajeError)): ?>
        <div style="background-color: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 0.8rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-weight: 600;">
            <?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- NAVEGACIÓN ENTRE LAS 4 ETAPAS ESTÁNDAR -->
    <div class="project-stages-bar">
        <a href="servicio_etapas.php?serviceId=<?= $serviceId ?>&etapa=1" class="stage-btn <?= $etapaActiva === 1 ? 'active' : '' ?>">
            <i class="ri-calendar-check-line"></i> 1. Planificación[cite: 2]
        </a>
        <a href="servicio_etapas.php?serviceId=<?= $serviceId ?>&etapa=2" class="stage-btn <?= $etapaActiva === 2 ? 'active' : '' ?>">
            <i class="ri-compass-3-line"></i> 2. Estrategia[cite: 2]
        </a>
        <a href="servicio_etapas.php?serviceId=<?= $serviceId ?>&etapa=3" class="stage-btn <?= $etapaActiva === 3 ? 'active' : '' ?>">
            <i class="ri-play-circle-line"></i> 3. Ejecución[cite: 2]
        </a>
        <a href="servicio_etapas.php?serviceId=<?= $serviceId ?>&etapa=4" class="stage-btn <?= $etapaActiva === 4 ? 'active' : '' ?>">
            <i class="ri-flag-line"></i> 4. Conclusión[cite: 2]
        </a>
    </div>

    <!-- FORMULARIO PARA AGREGAR NUEVA CATEGORÍA A LA ETAPA ACTIVA -->
    <div style="background: #ffffff; padding: 1.25rem; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem;">
        <form action="servicio_etapas.php?serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="action_type" value="crear_categoria">
            <input 
                type="text" 
                name="nombre_categoria" 
                placeholder="Escribe el nombre de la nueva categoría para esta etapa..." 
                required 
                style="flex: 1; padding: 0.65rem 1rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;"
            >
            <button type="submit" class="btn btn-primary" style="background: #08855b; border: none; padding: 0.65rem 1.25rem; font-weight: 600; cursor: pointer; border-radius: 6px; color: #fff;">
                <i class="ri-add-line"></i> Agregar Categoría
            </button>
        </form>
    </div>

    <!-- LISTADO DE CATEGORÍAS ASOCIADAS -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 15%;">ID Categoría</th>
                    <th style="width: 65%;">Nombre de la Categoría</th>
                    <th style="width: 20%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categorias)): ?>
                    <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td><strong>#<?= (int)$cat->id ?></strong></td>
                            <td><?= htmlspecialchars($cat->nombre, ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="text-align: center;">
                                <a href="editar_categoria.php?id=<?= (int)$cat->id ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; background: #0284c7; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600;">
                                    <i class="ri-pencil-line"></i> Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #64748b; padding: 2.5rem;">
                            No hay categorías registradas para esta etapa en el servicio seleccionado.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include '../main/layout_footer.php';
include '../main/footer.php';
?>