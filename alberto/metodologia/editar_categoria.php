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
$categoriaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$serviceId   = filter_input(INPUT_GET, 'serviceId', FILTER_VALIDATE_INT);
$etapaActiva = filter_input(INPUT_GET, 'etapa', FILTER_VALIDATE_INT) ?: 1;

if (!$categoriaId) {
    header('Location: index.php');
    exit;
}

$mensajeError = '';
$mensajeExito = '';

// 2. PROCESAR ACCIONES POST (Antes de imprimir HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? '';

    // A. Actualizar nombre de la categoría
    if ($action === 'actualizar_categoria') {
        $nuevoNombre = trim($_POST['nombre_categoria'] ?? '');

        if (empty($nuevoNombre)) {
            $mensajeError = 'El nombre de la categoría no puede estar vacío.';
        } else {
            try {
                $stmtUp = $pdo->prepare("UPDATE audit_categorias SET nombre = :nombre WHERE id = :id");
                $stmtUp->execute([':nombre' => $nuevoNombre, ':id' => $categoriaId]);
                $mensajeExito = 'Nombre de la categoría actualizado correctamente.';
            } catch (PDOException $e) {
                error_log("Error al actualizar categoría: " . $e->getMessage());
                $mensajeError = 'No se pudo actualizar el nombre de la categoría.';
            }
        }
    }

    // B. Agregar nueva prueba
    if ($action === 'crear_prueba') {
        $nombrePrueba = trim($_POST['nombre_prueba'] ?? '');
        $informacion  = trim($_POST['informacion'] ?? '');
        $norma        = trim($_POST['norma'] ?? '');

        if (empty($nombrePrueba)) {
            $mensajeError = 'El nombre de la prueba es obligatorio.';
        } else {
            try {
                $stmtInsPru = $pdo->prepare("
                    INSERT INTO audit_pruebas (categoria_id, nombre, informacion, norma) 
                    VALUES (:cat_id, :nombre, :info, :norma)
                ");
                $stmtInsPru->execute([
                    ':cat_id' => $categoriaId,
                    ':nombre' => $nombrePrueba,
                    ':info'   => $informacion,
                    ':norma'  => $norma
                ]);
                $mensajeExito = 'Prueba de auditoría registrada con éxito.';
            } catch (PDOException $e) {
                error_log("Error al crear prueba: " . $e->getMessage());
                $mensajeError = 'Error al guardar la prueba en la base de datos.';
            }
        }
    }

    // C. Editar / Actualizar textos de una Prueba
    if ($action === 'actualizar_prueba') {
        $pruebaId     = filter_input(INPUT_POST, 'prueba_id', FILTER_VALIDATE_INT);
        $nombrePrueba = trim($_POST['nombre_prueba'] ?? '');
        $informacion  = trim($_POST['informacion'] ?? '');
        $norma        = trim($_POST['norma'] ?? '');

        if (!$pruebaId || empty($nombrePrueba)) {
            $mensajeError = 'Datos insuficientes para actualizar la prueba.';
        } else {
            try {
                $stmtUpPru = $pdo->prepare("
                    UPDATE audit_pruebas 
                    SET nombre = :nombre, informacion = :info, norma = :norma 
                    WHERE id = :id AND categoria_id = :cat_id
                ");
                $stmtUpPru->execute([
                    ':nombre' => $nombrePrueba,
                    ':info'   => $informacion,
                    ':norma'  => $norma,
                    ':id'     => $pruebaId,
                    ':cat_id' => $categoriaId
                ]);
                $mensajeExito = 'Datos de la prueba actualizados correctamente.';
            } catch (PDOException $e) {
                error_log("Error al actualizar prueba: " . $e->getMessage());
                $mensajeError = 'No se pudieron actualizar los datos de la prueba.';
            }
        }
    }

    // D. Eliminar Prueba
    if ($action === 'eliminar_prueba') {
        $pruebaId = filter_input(INPUT_POST, 'prueba_id', FILTER_VALIDATE_INT);

        if ($pruebaId) {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM audit_pruebas WHERE id = :id AND categoria_id = :cat_id");
                $stmtDel->execute([':id' => $pruebaId, ':cat_id' => $categoriaId]);
                $mensajeExito = 'Prueba eliminada con éxito.';
            } catch (PDOException $e) {
                error_log("Error al eliminar prueba: " . $e->getMessage());
                $mensajeError = 'No se pudo eliminar la prueba seleccionada.';
            }
        }
    }
}

// 3. CONSULTAR DATOS (Categoría + Pruebas)
try {
    $stmtCat = $pdo->prepare("SELECT * FROM audit_categorias WHERE id = :id");
    $stmtCat->execute([':id' => $categoriaId]);
    $categoria = $stmtCat->fetch(PDO::FETCH_OBJ);

    if (!$categoria) {
        header('Location: index.php');
        exit;
    }

    $stmtPru = $pdo->prepare("SELECT * FROM audit_pruebas WHERE categoria_id = :cat_id ORDER BY id ASC");
    $stmtPru->execute([':cat_id' => $categoriaId]);
    $pruebas = $stmtPru->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al consultar datos de categoría/pruebas: " . $e->getMessage());
    $categoria = null;
    $pruebas   = [];
}

// 4. Cargar interfaz visual
include '../main/h.php';
?>

<link rel="stylesheet" href="../main/layout.css">

<style>
    /* Estilos para el Auto-Expand de los Textareas */
    textarea.auto-expand {
        overflow-y: hidden;
        resize: none;
        box-sizing: border-box;
        transition: height 0.05s ease-out;
        min-height: 80px;
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
            <i class="ri-edit-box-line"></i> Categoría: <?= htmlspecialchars($categoria->nombre ?? '', ENT_QUOTES, 'UTF-8') ?>
        </h1>
    </div>

    <div class="table-actions-container">
        <a href="servicio_etapas.php?serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" class="btn btn-primary" data-tooltip="Volver a Etapas">
            <i class="ri-arrow-go-back-line"></i> Volver a Etapas
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

    <!-- SECCIÓN 1: EDITAR NOMBRE DE LA CATEGORÍA -->
    <div class="table-container" style="padding: 1.5rem; background: #ffffff; border-radius: 8px; margin-bottom: 2rem;">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; margin-bottom: 1rem;">Detalles de la Categoría</h3>
        <form action="editar_categoria.php?id=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST" style="display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="action_type" value="actualizar_categoria">
            <input 
                type="text" 
                name="nombre_categoria" 
                value="<?= htmlspecialchars($categoria->nombre ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required 
                style="flex: 1; padding: 0.65rem 1rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;"
            >
            <button type="submit" class="btn btn-primary" style="background-color: #0284c7; border: none; padding: 0.65rem 1.25rem; font-weight: 600; cursor: pointer; border-radius: 6px; color: #fff;">
                <i class="ri-save-line"></i> Actualizar Categoría
            </button>
        </form>
    </div>

    <!-- SECCIÓN 2: REGISTRAR NUEVA PRUEBA -->
    <div style="background: #ffffff; padding: 1.5rem; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem;">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; margin-bottom: 1rem;">+ Registrar Nueva Prueba</h3>
        <form action="editar_categoria.php?id=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST">
            <input type="hidden" name="action_type" value="crear_prueba">
            
            <div style="display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #334155;">Nombre de la Prueba *</label>
                    <input type="text" name="nombre_prueba" required placeholder="Ej. Verificación de Arqueo de Caja" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #334155;">Información / Instrucciones Extensas</label>
                        <textarea name="informacion" class="auto-expand" placeholder="Escriba las instrucciones detalladas..." style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-family: inherit; font-size: 0.9rem;"></textarea>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #334155;">Norma / Referencia Extensa</label>
                        <textarea name="norma" class="auto-expand" placeholder="Ej. NIA 500 - Evidencia de Auditoría..." style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-family: inherit; font-size: 0.9rem;"></textarea>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="background: #08855b; border: none; padding: 0.65rem 1.25rem; font-weight: 600; cursor: pointer; border-radius: 6px; color: #fff;">
                <i class="ri-add-line"></i> Guardar Nueva Prueba
            </button>
        </form>
    </div>

    <!-- SECCIÓN 3: GESTIÓN Y EDICIÓN DE PRUEBAS EXISTENTES -->
    <div class="table-container">
        <h3 style="margin: 0 0 1rem 0; color: #1e293b; font-size: 1.1rem;">Pruebas Configurada(s)</h3>
        
        <?php if (!empty($pruebas)): ?>
            <?php foreach ($pruebas as $pru): ?>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1.25rem;">
                    
                    <form action="editar_categoria.php?id=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST">
                        <input type="hidden" name="action_type" value="actualizar_prueba">
                        <input type="hidden" name="prueba_id" value="<?= (int)$pru->id ?>">

                        <div style="display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 4px;">Nombre de la Prueba</label>
                                <input type="text" name="nombre_prueba" value="<?= htmlspecialchars($pru->nombre, ENT_QUOTES, 'UTF-8') ?>" required style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; font-weight: 600; box-sizing: border-box;">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 4px;">Instrucciones / Texto de Información</label>
                                    <textarea name="informacion" class="auto-expand" style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; font-family: inherit; font-size: 0.88rem; line-height: 1.4;"><?= htmlspecialchars($pru->informacion ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 4px;">Norma APLICABLE</label>
                                    <textarea name="norma" class="auto-expand" style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 4px; font-family: inherit; font-size: 0.88rem; line-height: 1.4;"><?= htmlspecialchars($pru->norma ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 12px;">
                            <div style="display: flex; gap: 8px;">
                                <button type="submit" class="btn btn-primary" style="background: #0284c7; border: none; padding: 0.5rem 1.1rem; font-size: 0.85rem; border-radius: 4px; cursor: pointer; color: #fff; font-weight: 600;">
                                    <i class="ri-save-line"></i> Guardar Cambios
                                </button>
                                
                                <a href="editar_prueba.php?id=<?= (int)$pru->id ?>&categoriaId=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" class="btn" style="background: #08855b; color: #fff; padding: 0.5rem 1.1rem; font-size: 0.85rem; border-radius: 4px; text-decoration: none; font-weight: 600;">
                                    <i class="ri-list-check"></i> Actividades
                                </a>
                            </div>
                    </form>

                    <!-- ACCIÓN DE ELIMINAR -->
                    <form action="editar_categoria.php?id=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST" onsubmit="return confirm('¿Está seguro de eliminar esta prueba y sus actividades asociadas?');" style="margin: 0;">
                        <input type="hidden" name="action_type" value="eliminar_prueba">
                        <input type="hidden" name="prueba_id" value="<?= (int)$pru->id ?>">
                        <button type="submit" style="background: #dc2626; color: white; border: none; padding: 0.5rem 0.9rem; font-size: 0.85rem; border-radius: 4px; cursor: pointer; font-weight: 600;">
                            <i class="ri-delete-bin-line"></i> Eliminar
                        </button>
                    </form>

                        </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; color: #64748b; padding: 2.5rem; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0;">
                No hay pruebas asignadas a esta categoría. Registra una nueva prueba arriba para comenzar.
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
        // Ajuste al cargar la pantalla
        autoExpandTextarea(textarea);

        // Ajuste en tiempo real al escribir/pegar
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