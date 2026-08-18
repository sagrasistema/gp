<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Conexión a BD
require_once '../main/config.php';

// Validar parámetros GET de navegación
$pruebaId    = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$categoriaId = filter_input(INPUT_GET, 'categoriaId', FILTER_VALIDATE_INT);
$serviceId   = filter_input(INPUT_GET, 'serviceId', FILTER_VALIDATE_INT);
$etapaActiva = filter_input(INPUT_GET, 'etapa', FILTER_VALIDATE_INT) ?: 1;

if (!$pruebaId || !$categoriaId) {
    header('Location: index.php');
    exit;
}

$mensajeError = '';
$mensajeExito = '';

// 2. PROCESAR ACCIONES POST (Crear, Editar, Eliminar Actividad)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? '';

    // A. Agregar nueva Actividad
    if ($action === 'crear_actividad') {
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (empty($descripcion)) {
            $mensajeError = 'La descripción de la actividad es requerida.';
        } else {
            try {
                $stmtIns = $pdo->prepare("
                    INSERT INTO audit_actividades (prueba_id, descripcion) 
                    VALUES (:prueba_id, :descripcion)
                ");
                $stmtIns->execute([
                    ':prueba_id'  => $pruebaId,
                    ':descripcion' => $descripcion
                ]);
                $mensajeExito = 'Actividad agregada con éxito.';
            } catch (PDOException $e) {
                error_log("Error al crear actividad: " . $e->getMessage());
                $mensajeError = 'No se pudo registrar la actividad en la base de datos.';
            }
        }
    }

    // B. Actualizar Actividad existente
    if ($action === 'actualizar_actividad') {
        $actividadId = filter_input(INPUT_POST, 'actividad_id', FILTER_VALIDATE_INT);
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!$actividadId || empty($descripcion)) {
            $mensajeError = 'Datos incompletos para actualizar la actividad.';
        } else {
            try {
                $stmtUpd = $pdo->prepare("
                    UPDATE audit_actividades 
                    SET descripcion = :descripcion 
                    WHERE id = :id AND prueba_id = :prueba_id
                ");
                $stmtUpd->execute([
                    ':descripcion' => $descripcion,
                    ':id'          => $actividadId,
                    ':prueba_id'   => $pruebaId
                ]);
                $mensajeExito = 'Actividad actualizada correctamente.';
            } catch (PDOException $e) {
                error_log("Error al actualizar actividad: " . $e->getMessage());
                $mensajeError = 'No se pudo actualizar la actividad.';
            }
        }
    }

    // C. Eliminar Actividad
    if ($action === 'eliminar_actividad') {
        $actividadId = filter_input(INPUT_POST, 'actividad_id', FILTER_VALIDATE_INT);

        if ($actividadId) {
            try {
                $stmtDel = $pdo->prepare("
                    DELETE FROM audit_actividades 
                    WHERE id = :id AND prueba_id = :prueba_id
                ");
                $stmtDel->execute([
                    ':id'        => $actividadId,
                    ':prueba_id' => $pruebaId
                ]);
                $mensajeExito = 'Actividad eliminada correctamente.';
            } catch (PDOException $e) {
                error_log("Error al eliminar actividad: " . $e->getMessage());
                $mensajeError = 'No se pudo eliminar la actividad seleccionada.';
            }
        }
    }
}

// 3. CONSULTAR DATOS (Prueba Actual + Lista de Actividades)
try {
    // Consultar información general de la prueba
    $stmtPru = $pdo->prepare("
        SELECT id, nombre, informacion, norma, modelo 
        FROM audit_pruebas 
        WHERE id = :id AND categoria_id = :cat_id
    ");
    $stmtPru->execute([':id' => $pruebaId, ':cat_id' => $categoriaId]);
    $prueba = $stmtPru->fetch(PDO::FETCH_OBJ);

    if (!$prueba) {
        header('Location: index.php');
        exit;
    }

    // Consultar sus actividades asociadas
    $stmtAct = $pdo->prepare("
        SELECT id, descripcion 
        FROM audit_actividades 
        WHERE prueba_id = :prueba_id 
        ORDER BY id ASC
    ");
    $stmtAct->execute([':prueba_id' => $pruebaId]);
    $actividades = $stmtAct->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al consultar prueba/actividades: " . $e->getMessage());
    $prueba      = null;
    $actividades = [];
}

// 4. Cargar Interfaz Visual
include '../main/h.php';
?>

<link rel="stylesheet" href="../main/layout.css">

<style>
    textarea.auto-expand {
        overflow-y: hidden;
        resize: none;
        box-sizing: border-box;
        transition: height 0.05s ease-out;
        min-height: 70px;
    }
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
            <i class="ri-list-check"></i> Actividades de la Prueba: <?= htmlspecialchars($prueba->nombre ?? '', ENT_QUOTES, 'UTF-8') ?>
        </h1>
    </div>

    <!-- Navegación de Regreso -->
    <div class="table-actions-container">
        <a href="editar_categoria.php?id=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" class="btn btn-primary" data-tooltip="Volver a la Categoría">
            <i class="ri-arrow-go-back-line"></i> Volver a la Categoría
        </a>
    </div>

    <!-- Alertas Flash -->
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

    <!-- TARJETA INFORMATIVA: RESUMEN DE LA PRUEBA -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; color: #0f172a; font-size: 1.05rem; margin-bottom: 0.5rem;">
            <?= htmlspecialchars($prueba->nombre, ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($prueba->modelo)): ?>
                <span style="background: #e0f2fe; color: #0369a1; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; margin-left: 8px;">
                    Modelo <?= (int)$prueba->modelo ?>
                </span>
            <?php endif; ?>
        </h3>
        
        <?php if (!empty($prueba->informacion)): ?>
            <p style="margin: 0.25rem 0; color: #475569; font-size: 0.88rem; line-height: 1.4;">
                <strong>Instrucciones:</strong> <?= nl2br(htmlspecialchars($prueba->informacion, ENT_QUOTES, 'UTF-8')) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($prueba->norma)): ?>
            <p style="margin: 0.25rem 0 0 0; color: #475569; font-size: 0.88rem; line-height: 1.4;">
                <strong>Norma Aplicable:</strong> <?= nl2br(htmlspecialchars($prueba->norma, ENT_QUOTES, 'UTF-8')) ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- SECCIÓN 1: REGISTRAR NUEVA ACTIVIDAD -->
    <div style="background: #ffffff; padding: 1.5rem; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem;">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; margin-bottom: 1rem;">+ Agregar Nueva Actividad</h3>
        <form action="editar_prueba.php?id=<?= $pruebaId ?>&categoriaId=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST">
            <input type="hidden" name="action_type" value="crear_actividad">
            
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #334155;">Descripción / Procedimiento de la Actividad *</label>
                <textarea name="descripcion" class="auto-expand" required placeholder="Escriba los pasos detallados de la actividad..." style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 4px; font-family: inherit; font-size: 0.9rem; line-height: 1.4;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="background: #08855b; border: none; padding: 0.65rem 1.25rem; font-weight: 600; cursor: pointer; border-radius: 6px; color: #fff;">
                <i class="ri-add-line"></i> Guardar Actividad
            </button>
        </form>
    </div>

    <!-- SECCIÓN 2: LISTADO Y EDICIÓN DE ACTIVIDADES -->
    <div class="table-container">
        <h3 style="margin: 0 0 1rem 0; color: #1e293b; font-size: 1.1rem;">Actividades Configurada(s)</h3>
        
        <?php if (!empty($actividades)): ?>
            <?php foreach ($actividades as $index => $act): ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
                    
                    <form action="editar_prueba.php?id=<?= $pruebaId ?>&categoriaId=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST">
                        <input type="hidden" name="action_type" value="actualizar_actividad">
                        <input type="hidden" name="actividad_id" value="<?= (int)$act->id ?>">

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 4px;">
                                Actividad #<?= $index + 1 ?>
                            </label>
                            <textarea name="descripcion" class="auto-expand" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-family: inherit; font-size: 0.88rem; line-height: 1.4;"><?= htmlspecialchars($act->descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                            <button type="submit" class="btn btn-primary" style="background: #0284c7; border: none; padding: 0.45rem 1rem; font-size: 0.85rem; border-radius: 4px; cursor: pointer; color: #fff; font-weight: 600;">
                                <i class="ri-save-line"></i> Guardar Cambios
                            </button>
                    </form>

                    <!-- ACCIÓN INDEPENDIENTE PARA ELIMINAR -->
                    <form action="editar_prueba.php?id=<?= $pruebaId ?>&categoriaId=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST" onsubmit="return confirm('¿Está seguro de eliminar esta actividad?');" style="margin: 0;">
                        <input type="hidden" name="action_type" value="eliminar_actividad">
                        <input type="hidden" name="actividad_id" value="<?= (int)$act->id ?>">
                        <button type="submit" style="background: #dc2626; color: white; border: none; padding: 0.45rem 0.8rem; font-size: 0.85rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            <i class="ri-delete-bin-line"></i> Eliminar
                        </button>
                    </form>

                        </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; color: #64748b; padding: 2.5rem; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                No hay actividades asociadas a esta prueba. Agrega una nueva arriba.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const autoExpandTextarea = (element) => {
        element.style.height = 'auto';
        element.style.height = `${element.scrollHeight}px`;
    };

    const dynamicTextareas = document.querySelectorAll('textarea.auto-expand');

    dynamicTextareas.forEach((textarea) => {
        // Redimensionar al cargar la vista con el texto guardado
        autoExpandTextarea(textarea);

        // Redimensionar al escribir/pegar
        textarea.addEventListener('input', () => {
            autoExpandTextarea(textarea);
        });
    });
});
</script>

<?php
include '../main/layout_footer.php';
include '../main/footer.php';
?>