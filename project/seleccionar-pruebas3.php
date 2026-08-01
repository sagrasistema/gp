<?php

declare(strict_types=1);

// v/proyectos/seleccionar-pruebas3.php
include '../main/config.php';

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) 
    ?? filter_input(INPUT_POST, 'proyecto_id', FILTER_VALIDATE_INT);

if (!$proyectoId) {
    die("Error: Proyecto no especificado o ID inválido.");
}

// -------------------------------------------------------------------------
// 1. PROCESAR GUARDADO DE SELECCIÓN (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_selection'])) {
    $selectedPruebas = isset($_POST['pruebas']) && is_array($_POST['pruebas'])
        ? array_map('intval', $_POST['pruebas'])
        : [];

    try {
        $pdo->beginTransaction();

        // Obtener IDs de todas las pruebas que pertenecen a la Etapa 3
        $stmtEtapa = $pdo->prepare("
            SELECT p.id 
            FROM audit_pruebas p
            INNER JOIN audit_categorias c ON p.categoria_id = c.id
            WHERE c.etapa_id = 3
        ");
        $stmtEtapa->execute();
        $etapa3PruebaIds = $stmtEtapa->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($etapa3PruebaIds)) {
            // Identificar pruebas a desmarcar/eliminar
            $toDelete = array_diff($etapa3PruebaIds, $selectedPruebas);

            if (!empty($toDelete)) {
                $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
                $stmtDelete = $pdo->prepare("
                    DELETE FROM proyecto_pruebas_ejecucion 
                    WHERE proyecto_id = ? AND prueba_id IN ($placeholders)
                ");
                $stmtDelete->execute(array_merge([$proyectoId], array_values($toDelete)));
            }

            // Insertar o mantener las pruebas seleccionadas
            if (!empty($selectedPruebas)) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO proyecto_pruebas_ejecucion (proyecto_id, prueba_id, estado)
                    VALUES (?, ?, 'en_proceso')
                    ON DUPLICATE KEY UPDATE proyecto_id = VALUES(proyecto_id)
                ");
                foreach ($selectedPruebas as $pId) {
                    if (in_array($pId, $etapa3PruebaIds, true)) {
                        $stmtInsert->execute([$proyectoId, $pId]);
                    }
                }
            }
        }

        $pdo->commit();
        header("Location: responder3.php?proyectoId={$proyectoId}&success=1");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error al guardar selección de pruebas: " . $e->getMessage());
        $errorMessage = "Error de base de datos al guardar la configuración.";
    }
}

// -------------------------------------------------------------------------
// 2. CARGAR DATOS PARA LA VISTA
// -------------------------------------------------------------------------
try {
    // Cabecera del Proyecto
    $stmtProj = $pdo->prepare("
        SELECT p.*, c.name AS clientName 
        FROM proyectos p 
        INNER JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = :id
    ");
    $stmtProj->execute([':id' => $proyectoId]);
    $projectData = $stmtProj->fetch(PDO::FETCH_OBJ);

    if (!$projectData) {
        die("Error: El proyecto no existe.");
    }

    // Pruebas actualmente seleccionadas
    $stmtSelected = $pdo->prepare("
        SELECT prueba_id 
        FROM proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :proyecto_id
    ");
    $stmtSelected->execute([':proyecto_id' => $proyectoId]);
    $currentlySelected = $stmtSelected->fetchAll(PDO::FETCH_COLUMN);

    // Categorías y Pruebas de la Etapa 3
    $stmtCategories = $pdo->prepare("SELECT * FROM audit_categorias WHERE etapa_id = 3 ORDER BY orden ASC");
    $stmtCategories->execute();
    $categories = $stmtCategories->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al cargar selector de pruebas: " . $e->getMessage());
    die("Error al cargar la configuración de pruebas.");
}

$pageTitle = "Seleccionar Pruebas de Ejecución";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container" style="padding: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0;">
                <i class="ri-checkbox-multiple-line" style="color: #2563eb;"></i> Configuración de Pruebas (Etapa 3)
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Cliente: <strong><?= htmlspecialchars($projectData->clientName, ENT_QUOTES, 'UTF-8') ?></strong> | 
                Proyecto: <strong><?= htmlspecialchars($projectData->nombre, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
        <a href="responder3.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
            <i class="ri-arrow-left-line"></i> Volver a Ejecución
        </a>
    </div>

    <?php if (isset($errorMessage)): ?>
        <div style="padding:1rem; background:#fee2e2; color:#991b1b; border-radius:8px; margin-bottom:1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="seleccionar-pruebas3.php">
        <input type="hidden" name="action_save_selection" value="1">
        <input type="hidden" name="proyecto_id" value="<?= $proyectoId ?>">

        <div class="accordion-container" style="margin-bottom: 2rem;">
            <?php foreach ($categories as $cat): 
                $stmtP = $pdo->prepare("SELECT * FROM audit_pruebas WHERE categoria_id = :catId ORDER BY orden ASC");
                $stmtP->execute([':catId' => $cat->id]);
                $pruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);
            ?>
                <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: #ffffff;">
                    <div class="accordion-header" onclick="toggleCatAccordion(this)" style="background: #f8fafc; padding: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0;">
                        <span>
                            <i class="ri-folder-3-line" style="color: #475569; margin-right: 0.5rem;"></i>
                            <?= htmlspecialchars($cat->nombre, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <div style="display: flex; align-items: center; gap: 1rem;" onclick="event.stopPropagation();">
                            <label style="font-size: 0.75rem; color: #2563eb; cursor: pointer; font-weight: 600;">
                                <input type="checkbox" onchange="toggleCategoryCheckboxes(this, 'cat_group_<?= $cat->id ?>')"> Seleccionar todas
                            </label>
                            <i class="ri-arrow-down-s-line"></i>
                        </div>
                    </div>

                    <div class="accordion-content cat_group_<?= $cat->id ?>" style="display: block; padding: 0.5rem 1rem;">
                        <?php if (!empty($pruebas)): ?>
                            <?php foreach ($pruebas as $pr): 
                                $isChecked = in_array($pr->id, $currentlySelected, true) ? 'checked' : '';
                            ?>
                                <label style="display: flex; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; cursor: pointer; gap: 0.75rem;">
                                    <input type="checkbox" name="pruebas[]" value="<?= $pr->id ?>" <?= $isChecked ?> style="width: 18px; height: 18px; accent-color: #2563eb;">
                                    <span style="font-size: 0.9rem; color: #334155; font-weight: 500;">
                                        <?= htmlspecialchars($pr->nombre, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #94a3b8; font-size: 0.85rem; padding: 0.5rem 0;">No hay pruebas disponibles en esta categoría.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="position: sticky; bottom: 1rem; background: #ffffff; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: right;">
            <a href="responder3.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary" style="margin-right: 0.5rem;">Cancelar</a>
            <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                <i class="ri-save-line"></i> Guardar Pruebas Seleccionadas
            </button>
        </div>
    </form>
</div>

<script>
function toggleCatAccordion(header) {
    const content = header.nextElementSibling;
    content.style.display = (content.style.display === 'none' || content.style.display === '') ? 'block' : 'none';
}

function toggleCategoryCheckboxes(master, groupClass) {
    const checkboxes = document.querySelectorAll('.' + groupClass + ' input[type="checkbox"]');
    checkboxes.forEach(cb => cb.checked = master.checked);
}
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>