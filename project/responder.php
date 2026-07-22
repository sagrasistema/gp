<?php
// v/proyectos/responder.php
include '../main/config.php';
include 'conect-proyecto.php';

$pageTitle = "Panel de Control de Auditoría";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<style>
    .prueba-row-container { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color); background: #ffffff; gap: 1rem; }
    .prueba-title { font-size: 0.95rem; font-weight: 600; color: #334155; flex-grow: 1; }
    .prueba-actions { display: flex; align-items: center; gap: 0.75rem; }
    .indicator-chk { display: flex; align-items: center; gap: 0.25rem; font-size: 0.8rem; font-weight: 700; border: 1px solid #cbd5e1; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; }
    .status-select { padding: 0.4rem; border-radius: 6px; font-size: 0.85rem; border: 1px solid #cbd5e1; font-weight: 600; }
</style>

<?php include '../main/layout_header.php'; ?>

<div class="view-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">
            <i class="ri-dashboard-line" style="color: var(--accent);"></i> Panel de Ejecución - Etapa 1 Planificación
        </h1>
        <a href="index.php" class="btn btn-secondary"><i class="ri-arrow-left-line"></i> Volver</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px; margin-bottom:1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Parámetros e indicadores de prueba sincronizados correctamente.
        </div>
    <?php endif; ?>
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px;">
        
        <!-- Columna 1: Cliente y Socio Líder -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Cliente / Empresa</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio Líder</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioLider ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <!-- Columna 2: Proyecto y Socio de Calidad -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; padding-left: 0.5rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Proyecto / Alcance</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->nombre ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio de Calidad</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioCalidad ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <!-- Columna 3: Fecha de Remisión y Gerente -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem; padding-left: 0.5rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Remisión</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->fechaRemision ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Gerente Encargado</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

    </div>


    <!-- SISTEMA DE ACORDEONES (CATEGORÍAS -> PRUEBAS) -->
    <div class="accordion-container">
        <?php
        $categories = $pdo->query("SELECT * FROM audit_categorias WHERE etapa_id = 1 ORDER BY orden ASC")->fetchAll(PDO::FETCH_OBJ);
        foreach ($categories as $cat):
            $stmtP = $pdo->prepare("SELECT * FROM audit_pruebas WHERE categoria_id = :catId ORDER BY orden ASC");
            $stmtP->execute([':catId' => $cat->id]);
            $pruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);
        ?>
            <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <span><?= htmlspecialchars($cat->nombre, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                
                <div class="accordion-content" style="display: none; background: #fff;">
                    <?php foreach ($pruebas as $pr): 
                        $saved = $pruebasEjecutadas[$pr->id] ?? null;
                        $savedStatus = $saved['estado'] ?? 'en_proceso';
                    ?>
                        <form action="responder.php?proyectoId=<?= $proyectoId ?>" method="POST" class="prueba-row-container">
                            <input type="hidden" name="action_type" value="update_prueba">
                            <input type="hidden" name="prueba_id" value="<?= $pr->id ?>">
                            
                            <div class="prueba-title"><?= htmlspecialchars($pr->nombre, ENT_QUOTES, 'UTF-8') ?></div>
                            
                            <div class="prueba-actions">
                                <!-- Checkbox de Indicadores Estilo Botón Corto -->
                                <label class="indicator-chk" style="color: #16a34a;">
                                    <input type="checkbox" name="ci" value="1" <?= (!empty($saved['indicador_ci'])) ? 'checked' : '' ?> onchange="this.form.submit()"> CI
                                </label>
                                <label class="indicator-chk" style="color: #2563eb;">
                                    <input type="checkbox" name="cg" value="1" <?= (!empty($saved['indicador_cg'])) ? 'checked' : '' ?> onchange="this.form.submit()"> CG
                                </label>
                                <label class="indicator-chk" style="color: #dc2626;">
                                    <input type="checkbox" name="sc" value="1" <?= (!empty($saved['indicador_sc'])) ? 'checked' : '' ?> onchange="this.form.submit()"> SC
                                </label>
                                <label class="indicator-chk" style="color: #9333ea;">
                                    <input type="checkbox" name="aa" value="1" <?= (!empty($saved['indicador_aa'])) ? 'checked' : '' ?> onchange="this.form.submit()"> AA
                                </label>

                                <!-- Selector de Estados del Flujo de Trabajo -->
                                <select name="estado" class="status-select" onchange="this.form.submit()">
                                    <option value="en_proceso" <?= $savedStatus === 'en_proceso' ? 'selected' : '' ?>>⏳ En proceso</option>
                                    <option value="completado" <?= $savedStatus === 'completado' ? 'selected' : '' ?>>✅ Completado</option>
                                    <option value="por_corregir_lider" <?= $savedStatus === 'por_corregir_lider' ? 'selected' : '' ?>>⚠️ Por Corregir Lider</option>
                                    <option value="por_corregir_riesgo" <?= $savedStatus === 'por_corregir_riesgo' ? 'selected' : '' ?>>🚨 Por Corregir Riesgo</option>
                                    <option value="revisado" <?= $savedStatus === 'revisado' ? 'selected' : '' ?>>🔹 Revisado</option>
                                    <option value="cerrado" <?= $savedStatus === 'cerrado' ? 'selected' : '' ?>>🔒 Cerrado</option>
                                </select>

                                <!-- LINK A LA OTRA PANTALLA EXCLUSIVA DE ACTIVIDADES -->
                                <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pr->id ?>" class="btn btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;" data-tooltip="Llenar Cuestionario de Actividades">
                                    <i class="ri-survey-line"></i> Actividades
                                </a>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php 
include 'js-proyectos.php';
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>