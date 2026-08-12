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

// 2. PROCESAR ACCIONES POST (Antes de imprimir cualquier HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? '';

    // Actualizar nombre de la categoría
    if ($action === 'actualizar_categoria') {
        $nuevoNombre = trim($_POST['nombre_categoria'] ?? '');

        if (empty($nuevoNombre)) {
            $mensajeError = 'El nombre de la categoría no puede estar vacío.';
        } else {
            try {
                $stmtUp = $pdo->prepare("UPDATE audit_categorias SET nombre = :nombre WHERE id = :id");
                $stmtUp->execute([':nombre' => $nuevoNombre, ':id' => $categoriaId]);
                $mensajeExito = 'Categoría actualizada correctamente.';
            } catch (PDOException $e) {
                error_log("Error al actualizar categoría: " . $e->getMessage());
                $mensajeError = 'No se pudo actualizar el nombre de la categoría.';
            }
        }
    }

    // Agregar nueva prueba a esta categoría
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
                $mensajeExito = 'Prueba de auditoría agregada con éxito.';
            } catch (PDOException $e) {
                error_log("Error al crear prueba: " . $e->getMessage());
                $mensajeError = 'Error al guardar la prueba en la base de datos.';
            }
        }
    }
}

// 3. CONSULTAR DATOS (Categoría + Lista de Pruebas)
try {
    // Obtener datos de la categoría
    $stmtCat = $pdo->prepare("SELECT * FROM audit_categorias WHERE id = :id");
    $stmtCat->execute([':id' => $categoriaId]);
    $categoria = $stmtCat->fetch(PDO::FETCH_OBJ);

    if (!$categoria) {
        header('Location: index.php');
        exit;
    }

    // Obtener pruebas vinculadas a esta categoría
    $stmtPru = $pdo->prepare("SELECT * FROM audit_pruebas WHERE categoria_id = :cat_id ORDER BY id ASC");
    $stmtPru->execute([':cat_id' => $categoriaId]);
    $pruebas = $stmtPru->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al consultar datos de la categoría/pruebas: " . $e->getMessage());
    $categoria = null;
    $pruebas   = [];
}

// 4. Cargar interfaz visual
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

    <!-- SECCIÓN 2: AGREGAR NUEVA PRUEBA -->
    <div style="background: #ffffff; padding: 1.5rem; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 1.5rem;">
        <h3 style="margin-top: 0; color: #1e293b; font-size: 1.1rem; margin-bottom: 1rem;">+ Registrar Nueva Prueba de Auditoría</h3>
        <form action="editar_categoria.php?id=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" method="POST">
            <input type="hidden" name="action_type" value="crear_prueba">
            
            <div style="display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 10px; margin-bottom: 10px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Nombre de la Prueba *</label>
                    <input type="text" name="nombre_prueba" required placeholder="Ej. Verificación de Arqueo de Caja" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Información / Instrucciones</label>
                    <input type="text" name="informacion" placeholder="Detalles u objetivos..." style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px;">Norma / Referencia</label>
                    <input type="text" name="norma" placeholder="Ej. NIA 500" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="background: #08855b; border: none; padding: 0.6rem 1.25rem; font-weight: 600; cursor: pointer; border-radius: 6px; color: #fff;">
                <i class="ri-add-line"></i> Guardar Prueba
            </button>
        </form>
    </div>

    <!-- SECCIÓN 3: TABLA CONSULTAR PRUEBAS -->
    <div class="table-container">
        <h3 style="margin: 0 0 1rem 0; color: #1e293b; font-size: 1.1rem;">Pruebas Registradas</h3>
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 10%;">ID</th>
                    <th style="width: 30%;">Nombre de la Prueba</th>
                    <th style="width: 35%;">Información / Instrucciones</th>
                    <th style="width: 15%;">Norma</th>
                    <th style="width: 10%; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pruebas)): ?>
                    <?php foreach ($pruebas as $pru): ?>
                        <tr>
                            <td><strong>#<?= (int)$pru->id ?></strong></td>
                            <td><strong><?= htmlspecialchars($pru->nombre, ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($pru->informacion ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; border: 1px solid #cbd5e1;"><?= htmlspecialchars($pru->norma ?? 'General', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td style="text-align: center;">
                                <a href="editar_prueba.php?id=<?= (int)$pru->id ?>&categoriaId=<?= $categoriaId ?>&serviceId=<?= $serviceId ?>&etapa=<?= $etapaActiva ?>" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; background: #08855b; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 0.8rem; font-weight: 600;" data-tooltip="Gestionar Actividades">
                                    <i class="ri-list-check"></i> Actividades
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #64748b; padding: 2.5rem;">
                            No hay pruebas asignadas a esta categoría. Registra una nueva arriba.
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