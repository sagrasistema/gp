<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Configuración y Conexión Backend
include '../main/config.php';
include 'conect-proyecto2.php';

$pageTitle = "Presupuesto de Horas y Selección de Pruebas";
include '../main/h.php';

// Capturar parámetros requeridos
$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) ?? 0;
$etapaFiltro = filter_input(INPUT_GET, 'etapa', FILTER_VALIDATE_INT) ?? 1; // Default: Etapa 1 Planificación

// Consultar lista de etapas disponibles
$stmtEtapas = $pdo->prepare("SELECT * FROM audit_etapas ORDER BY id ASC");
$stmtEtapas->execute();
$etapasList = $stmtEtapas->fetchAll(PDO::FETCH_OBJ);
?>

<link rel="stylesheet" href="../main/layout.css">
<style>
    /* Estilos globales compactos */
    .view-container { padding: 0.5rem; }
    .prueba-row-container { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.75rem; border-bottom: 1px solid var(--border-color); background: #ffffff; gap: 0.5rem; }
    .prueba-title { font-size: 0.8rem; font-weight: 600; color: #334155; flex-grow: 1; }
    .prueba-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end; }
    .status-select { padding: 0.25rem; border-radius: 4px; font-size: 0.75rem; border: 1px solid #cbd5e1; font-weight: 600; }
    .badge-hours { font-size: 0.7rem; background: #e0f2fe; color: #0369a1; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 700; white-space: nowrap; border: 1px solid #bae6fd; }

    /* Barra de Navegación por Etapas */
    .project-stages-bar { display: flex; gap: 6px; margin: 8px 0; flex-wrap: wrap; }
    .stage-btn { flex: 1; min-width: 130px; padding: 6px 12px; background-color: #1e3a5f; border: 1px solid #2b4c7e; border-radius: 6px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 11px; letter-spacing: 0.3px; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s ease-in-out; text-transform: uppercase; }
    .stage-btn i { font-size: 13px; color: #00bcd4; }
    .stage-btn:hover { background-color: #2b4c7e; border-color: #00bcd4; }
    .stage-btn.active { background-color: #0f1c2e; border: 1.5px solid #00bcd4; color: #ffffff; box-shadow: 0 2px 8px rgba(0, 188, 212, 0.2); }
    .stage-btn.active i { color: #00bcd4; }

    /* Indicadores y Controles */
    .cat-indicators-container { display: flex; align-items: center; gap: 0.25rem; margin-left: auto; margin-right: 0.75rem; }
    .input-horas { width: 70px; padding: 0.2rem 0.4rem; font-size: 0.75rem; font-weight: 700; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px; color: #0f172a; }
    .input-horas:focus { border-color: #00bcd4; outline: none; box-shadow: 0 0 0 2px rgba(0, 188, 212, 0.2); }
</style>

<?php include '../main/layout_header.php'; ?>

<div class="view-container">
    <form id="formHorasPruebas" action="guardar_horas.php" method="POST">
        <input type="hidden" name="proyectoId" value="<?= $proyectoId ?>">
        <input type="hidden" name="etapaId" value="<?= $etapaFiltro ?>">

        <!-- Barra de Navegación Rápida por Etapas -->
        <div class="project-stages-bar">
            <?php foreach ($etapasList as $etapa): 
                $isActive = ($etapa->id == $etapaFiltro) ? 'active' : '';
            ?>
                <a href="carta_seleccion_horas.php?proyectoId=<?= $proyectoId ?>&etapa=<?= $etapa->id ?>" class="stage-btn <?= $isActive ?>">
                    <i class="ri-checkbox-multiple-line"></i><?= htmlspecialchars($etapa->id . '. ' . $etapa->nombre, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Cabecera de Resumen Metodológico del Proyecto -->
        <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.75rem; padding: 0.6rem 0.8rem; border-radius: 8px; background: #ffffff; border: 1px solid var(--border-color);">
            <div style="display: flex; flex-direction: column; gap: 0.3rem; border-right: 1px solid #e2e8f0; padding-right: 0.5rem; font-size: 0.8rem;">
                <div>
                    <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Cliente / Empresa</span><br>
                    <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.3rem; border-right: 1px solid #e2e8f0; padding-right: 0.5rem; padding-left: 0.25rem; font-size: 0.8rem;">
                <div>
                    <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Frecuencia Acordada</span><br>
                    <strong style="color: #0284c7;"><?= htmlspecialchars($projectData->frecuencia ?? '1', ENT_QUOTES, 'UTF-8') ?> Revisión(es) al año</strong>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 0.3rem; padding-left: 0.25rem; font-size: 0.8rem;">
                <div>
                    <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Total Horas Presupuestadas</span><br>
                    <strong id="totalHorasGlobal" style="color: #059669; font-size: 0.95rem;"><?= number_format($totalHorasEtapa ?? 0, 2) ?> hrs</strong>
                </div>
            </div>
        </div>

        <!-- Barra de Título y Controles -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
            <h1 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.35rem;">
                <i class="ri-time-line" style="color: var(--accent);"></i>Selección de Pruebas y Asignación de Horas
            </h1>

            <div style="display: flex; align-items: center; gap: 0.25rem; margin-left: auto;">
                <button type="submit" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; background: #0284c7; border: none; color: white; border-radius: 4px; cursor: pointer; font-weight: 600;">
                    <i class="ri-save-3-line"></i> Guardar Presupuesto
                </button>
            </div>
        </div>

        <!-- Acordeones por Categoría de la Etapa Seleccionada -->
        <div class="accordion-container">
            <?php
            // Consultar categorías pertencientes a la etapa activa
            $stmtCat = $pdo->prepare("SELECT * FROM audit_categorias WHERE etapa_id = :etapaId ORDER BY orden ASC");
            $stmtCat->execute([':etapaId' => $etapaFiltro]);
            $categories = $stmtCat->fetchAll(PDO::FETCH_OBJ);

            $catIndex = 0;
            $pruebaIndex = 1;

            foreach ($categories as $cat):
                $letraCat = chr(65 + ($catIndex % 26));
                $catIndex++;

                // Consultar pruebas asignables
                $stmtP = $pdo->prepare("SELECT * FROM audit_pruebas WHERE categoria_id = :catId ORDER BY orden ASC");
                $stmtP->execute([':catId' => $cat->id]);
                $pruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);
            ?>
                <div class="accordion-item" style="margin-bottom: 0.4rem; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
                    <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 0.5rem 0.75rem; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                        <span><?= $letraCat ?>. <?= htmlspecialchars($cat->nombre, ENT_QUOTES, 'UTF-8') ?></span>

                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="badge-hours" id="subtotal-cat-<?= $cat->id ?>">0.00 hrs</span>
                            <i class="ri-arrow-down-s-line"></i>
                        </div>
                    </div>

                    <div class="accordion-content" style="display: none; background: #fff;">
                        <?php foreach ($pruebas as $pr): 
                            $pruebaConfig = $pruebasAsignadas[$pr->id] ?? null;
                            $isSelected = !empty($pruebaConfig['seleccionada']);
                            $horasAsignadas = $pruebaConfig['horas'] ?? 0;
                        ?>
                            <div class="prueba-row-container">
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-grow: 1;">
                                    <input type="checkbox" name="pruebas[<?= $pr->id ?>][selected]" value="1" <?= $isSelected ? 'checked' : '' ?> onchange="calcularSubtotales()" style="cursor: pointer; width: 16px; height: 16px;">
                                    <div class="prueba-title">
                                        <?= $pruebaIndex ?>. <?= htmlspecialchars($pr->nombre, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>

                                <div class="prueba-actions">
                                    <label style="font-size: 0.72rem; font-weight: 600; color: #475569;">Horas Estimadas:</label>
                                    <input type="number" step="0.5" min="0" name="pruebas[<?= $pr->id ?>][horas]" value="<?= $horasAsignadas ?>" class="input-horas" oninput="calcularSubtotales()">
                                </div>
                            </div>
                        <?php 
                            $pruebaIndex++; 
                        endforeach; 
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

<script>
function toggleAccordion(element) {
    const content = element.nextElementSibling;
    const icon = element.querySelector('.ri-arrow-down-s-line, .ri-arrow-up-s-line');
    
    if (content.style.display === "none" || content.style.display === "") {
        content.style.display = "block";
        if(icon) {
            icon.classList.remove('ri-arrow-down-s-line');
            icon.classList.add('ri-arrow-up-s-line');
        }
    } else {
        content.style.display = "none";
        if(icon) {
            icon.classList.remove('ri-arrow-up-s-line');
            icon.classList.add('ri-arrow-down-s-line');
        }
    }
}

function calcularSubtotales() {
    let totalGeneral = 0;
    
    document.querySelectorAll('.accordion-item').forEach(item => {
        let subtotalCat = 0;
        item.querySelectorAll('.prueba-row-container').forEach(row => {
            const chk = row.querySelector('input[type="checkbox"]');
            const inputHoras = row.querySelector('.input-horas');
            
            if (chk && chk.checked && inputHoras) {
                const hrs = parseFloat(inputHoras.value) || 0;
                subtotalCat += hrs;
            }
        });
        
        const badgeCat = item.querySelector('.badge-hours');
        if (badgeCat) {
            badgeCat.textContent = subtotalCat.toFixed(2) + ' hrs';
        }
        
        totalGeneral += subtotalCat;
    });
    
    const labelTotal = document.getElementById('totalHorasGlobal');
    if (labelTotal) {
        labelTotal.textContent = totalGeneral.toFixed(2) + ' hrs';
    }
}

document.addEventListener('DOMContentLoaded', calcularSubtotales);
</script>

<?php 
include 'js-proyectos.php';
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>