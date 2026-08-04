<?php
// v/proyectos/responder.php
include '../main/config.php';
include 'conect-proyecto3.php';

$pageTitle = "Panel de Control de Auditoría";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<style>
    .prueba-row-container { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color); background: #ffffff; gap: 1rem; }
    .prueba-title { font-size: 0.95rem; font-weight: 600; color: #334155; flex-grow: 1; }
    .prueba-actions { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; justify-content: flex-end; }
    .indicator-chk { display: flex; align-items: center; gap: 0.25rem; font-size: 0.8rem; font-weight: 700; border: 1px solid #cbd5e1; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; }
    .status-select { padding: 0.4rem; border-radius: 6px; font-size: 0.85rem; border: 1px solid #cbd5e1; font-weight: 600; }
    .badge-progress { font-size: 0.75rem; background: #f1f5f9; color: #475569; padding: 0.25rem 0.5rem; border-radius: 6px; font-weight: 600; white-space: nowrap; }
</style>

<?php include '../main/layout_header.php'; ?>

<div class="view-container">
<?php
// Capturar y validar el ID del proyecto desde la URL de forma segura
$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) ?? 0;
?>

<!-- Barra de Navegación Rápida por Etapas del Proyecto -->
<div class="project-stages-bar">
    <a href="responder.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn ">
        <i class="ri-calendar-check-line"></i> Planificación
    </a>
    <a href="responder2.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn ">
        <i class="ri-compass-3-line"></i> Estrategia
    </a>
    <a href="responder3.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn active">
        <i class="ri-play-circle-line"></i> Ejecución
    </a>
    <a href="responder4.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn ">
        <i class="ri-flag-line"></i> Conclusión
    </a>
</div>

<style>
.project-stages-bar {
    display: flex;
    gap: 12px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.stage-btn {
    flex: 1;
    min-width: 180px;
    padding: 14px 20px;
    /* Azul corporativo base (coincidente con el navbar superior) */
    background-color: #1e3a5f; 
    border: 1px solid #2b4c7e;
    border-radius: 8px;
    color: #ffffff; /* Texto blanco garantizado */
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.25s ease-in-out;
    text-transform: uppercase;
}

.stage-btn i {
    font-size: 16px;
    color: #00bcd4; /* Icono en acento cian corporativo */
}

.stage-btn:hover {
    background-color: #2b4c7e;
    border-color: #00bcd4;
    transform: translateY(-2px);
}

/* Estado Activo: Un tono de azul más oscuro, elegante y con borde de contraste */
.stage-btn.active {
    background-color: #0f1c2e; /* Azul significativamente más oscuro */
    border: 2px solid #00bcd4;
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(0, 188, 212, 0.25);
}

.stage-btn.active i {
    color: #00bcd4;
}
</style>


    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">
            <i class="ri-dashboard-line" style="color: var(--accent);"></i> Panel de Ejecución - Etapa 3 Ejecución 
        </h1>
        
    </div>
    <div class="table-actions-container">
        <!-- Botón para ir al Selector de Pruebas de la Etapa 3 ss-->
   
        <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;">
            <i class="ri-arrow-go-back-line"></i> 
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;">
            <i class="ri-screenshot-2-line"></i>
        </a>

        <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;">
            <i class="ri-book-open-line"></i> 
        </a>

        <a href="nuevo.php" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;">
            <i class="ri-add-line"></i>
        </a>

        <a href="../projet/index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px; margin-bottom:1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Parámetros e indicadores de prueba sincronizados correctamente.
        </div>
    <?php endif; ?>

    <!-- Cabecera de Metadatos del Proyecto -->
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px; background: #ffffff; border: 1px solid var(--border-color);">
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

        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; padding-left: 0.5rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Proyecto / Alcance</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->nombre ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio de Calidad</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioCalidad ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; padding-left: 0.5rem; font-size: 0.9rem;">
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
    <div class="table-actions-container" style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-bottom: 1rem;">
        <!-- Botón 1: Definir Frecuencia / Periodicidad -->
        <a href="configurar-frecuencia3.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">
            <i class="ri-calendar-event-line"></i> Frecuencias (<?= $totalFrecuencias ?>)
        </a>

        <!-- Botón 2: Seleccionar Pruebas de la Frecuencia Activa -->
        <a href="seleccionar-pruebas3.php?proyectoId=<?= $proyectoId ?>&frecuencia=<?= $frecuenciaNum ?>" class="btn btn-primary" style="background: #2563eb; color: #ffffff;">
            <i class="ri-checkbox-multiple-line"></i> Seleccionar Pruebas
        </a>
    </div>
    <!-- SISTEMA DE PESTAÑAS (TABS) DE FRECUENCIAS -->
<div style="background: #ffffff; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
    <span style="font-size: 0.85rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.5rem; text-transform: uppercase;">
        Selecciona la Frecuencia a Ejecutar:
    </span>
    <div style="display: flex; gap: 0.5rem; overflow-x: auto;">
        <?php for ($f = 1; $f <= $totalFrecuencias; $f++): ?>
            <a href="responder3.php?proyectoId=<?= $proyectoId ?>&frecuencia=<?= $f ?>" 
               style="padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; <?= $f === $frecuenciaNum ? 'background: #2563eb; color: #ffffff;' : 'background: #f8fafc; color: #475569; border: 1px solid #cbd5e1;' ?>">
               <i class="ri-time-line"></i> Frecuencia <?= $f ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
    <div class="table-actions-container">
    <!-- 1. Icono de Edición / Notas -->
    <a href="#" class="btn btn-primary" data-tooltip="Reporte de avance" onclick="return false;">
        <i class="ri-file-edit-line"></i> 
    </a>

    <!-- 2. Icono de Bandera / Hitos -->
    <a href="#" class="btn btn-primary" data-tooltip="Reporte de debilidades" onclick="return false;">
        <i class="ri-flag-line"></i>
    </a>

    <!-- 3. Icono de Tiempo / Historial -->
    <a href="#" class="btn btn-primary" data-tooltip="Reporte de Horas" onclick="return false;">
        <i class="ri-time-line"></i> 
    </a>

    <!-- 4. Icono Financiero / Moneda -->
    <a href="#" class="btn btn-primary" data-tooltip="Reporte de asientos" onclick="return false;">
        <i class="ri-money-dollar-circle-line"></i>
    </a>

    <!-- 5. Icono de Cuadrícula / Panel de Control (Activo) -->
    <a href="#" class="btn btn-primary" data-tooltip="Reporte general">
        <i class="ri-layout-grid-line"></i> 
    </a>
</div>

    <!-- Bloque de Progreso General de Pruebas (Numerado Dinámico) -->
<!-- Bloque de Progreso General de Pruebas (Numerado Consecutivo Global 1-19) -->
<div class="pruebas-progress-container" style="margin-bottom: 2rem; background: #ffffff; padding: 1.25rem; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.0rem;">
        <h4 style="margin: 0; font-size: 0.95rem; color: #1e293b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em;">
            Progreso General de Pruebas (Fase de Ejecución)
        </h4>
        <span style="font-size: 0.75rem; background-color: #f1f5f9; color: #475569; padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 600;">
             Actividades / Pruebas
        </span>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
        <?php if (!empty($pruebasList)): ?>
            <?php 
            $globalIndex = 1;
            foreach ($pruebasList as $prueba): 
                $pId = $prueba['id'];
                $ejecucion = $pruebasEjecutadas[$pId] ?? null;
                $estadoPrueba = strtolower($ejecucion['estado'] ?? 'en_proceso');
                
                // Asignación de colores según estado de la prueba
                if ($estadoPrueba === 'completado' || $estadoPrueba === 'cerrado') {
                    $bgColor = '#10b981'; // Verde esmeralda
                } elseif ($estadoPrueba === 'revisado') {
                    $bgColor = '#3b82f6'; // Azul
                } elseif (str_contains($estadoPrueba, 'corregir')) {
                    $bgColor = '#ef4444'; // Rojo de alerta
                } else {
                    $bgColor = '#64748b'; // Gris pizarra (En proceso)
                }
                
                $safeId = htmlspecialchars((string)$pId, ENT_QUOTES, 'UTF-8');
                $safeCat = htmlspecialchars($prueba['categoria_nombre'] ?? '', ENT_QUOTES, 'UTF-8');
                $safeNombrePrueba = htmlspecialchars($prueba['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
            ?>
                <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $safeId ?>" 
                   title="Nº <?= $globalIndex ?>: <?= $safeNombrePrueba ?> | Categoría: <?= $safeCat ?> | Estado: <?= ucfirst($estadoPrueba) ?>"
                   style="display: flex; align-items: center; justify-content: center; width: 42px; height: 42px; background-color: <?= $bgColor ?>; color: #ffffff; font-weight: 700; border-radius: 8px; font-size: 0.875rem; text-decoration: none; transition: transform 0.15s ease, opacity 0.15s ease;"
                   onmouseover="this.style.opacity='0.9'; this.style.transform='translateY(-2px)';"
                   onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';">
                    <?= $globalIndex ?>
                </a>
            <?php 
                $globalIndex++;
            endforeach; 
            ?>
        <?php else: ?>
            <p style="color: #64748b; font-size: 0.875rem; margin: 0; font-style: italic;">
                No hay pruebas configuradas en la fase de planificación.
            </p>
        <?php endif; ?>
    </div>
    <!-- Barra de Carga de Progreso del Formulario -->
    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #475569;">
            <span>Progreso del Formulario</span>
            <span style="color: #0f172a; font-weight: 700;"><?= $porcentajeProgreso ?>%</span>
        </div>
        <div style="width: 100%; background-color: #e2e8f0; height: 10px; border-radius: 9999px; overflow: hidden;">
            <div style="width: <?= $porcentajeProgreso ?>%; background-color: #10b981; height: 100%; border-radius: 9999px; transition: width 0.4s ease;"></div>
        </div>
    </div>
</div>


    <!-- SISTEMA DE ACORDEONES (CATEGORÍAS -> PRUEBAS Y ACTIVIDADES) -->
    <!-- SISTEMA DE ACORDEONES (CATEGORÍAS -> PRUEBAS Y ACTIVIDADES) -->
<!-- SISTEMA DE ACORDEONES (CATEGORÍAS -> PRUEBAS SELECCIONADAS) -->
<!-- SISTEMA DE ACORDEONES FILTRADOS POR FRECUENCIA -->
    <div class="accordion-container">
        <?php
        try {
            $stmtCat = $pdo->prepare("SELECT * FROM audit_categorias WHERE etapa_id = 3 ORDER BY orden ASC");
            $stmtCat->execute();
            $categories = $stmtCat->fetchAll(PDO::FETCH_OBJ);

            $hayPruebasVisibles = false;

            foreach ($categories as $cat):
                $stmtP = $pdo->prepare("
                    SELECT p.* 
                    FROM audit_pruebas p
                    INNER JOIN proyecto_pruebas_ejecucion pe ON pe.prueba_id = p.id
                    WHERE p.categoria_id = :catId AND pe.proyecto_id = :proyecto_id AND pe.frecuencia_num = :frecuencia_num
                    ORDER BY p.id ASC
                ");
                $stmtP->execute([
                    ':catId'          => $cat->id,
                    ':proyecto_id'    => $proyectoId,
                    ':frecuencia_num' => $frecuenciaNum
                ]);
                $pruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);

                if (empty($pruebas)) {
                    continue;
                }
                $hayPruebasVisibles = true;
        ?>
            <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <span>
                        <i class="ri-folder-3-line" style="margin-right: 0.5rem; color: #2563eb;"></i>
                        <?= htmlspecialchars($cat->nombre, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                
                <div class="accordion-content" style="display: none; background: #ffffff;">
                    <?php foreach ($pruebas as $pr): 
                        $saved = $pruebasEjecutadas[$pr->id] ?? null;
                        $savedStatus = $saved['estado'] ?? 'en_proceso';
                    ?>
                        <div class="prueba-row-container" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color); gap: 1rem;">
                            <div class="prueba-title">
                                <?= htmlspecialchars($pr->nombre, ENT_QUOTES, 'UTF-8') ?>
                                <span style="margin-left: 0.5rem; font-size: 0.75rem; background: #dbeafe; color: #1e40af; padding: 0.15rem 0.5rem; border-radius: 4px; font-weight: 600;">
                                    Frecuencia <?= $frecuenciaNum ?>
                                </span>
                            </div>
                            
                            <div class="prueba-actions" style="display: flex; align-items: center; gap: 0.75rem;">
                                <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pr->id ?>&frecuencia=<?= $frecuenciaNum ?>" class="btn btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                                    <i class="ri-survey-line"></i> Actividades (Frecuencia <?= $frecuenciaNum ?>)
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php 
            endforeach;

            if (!$hayPruebasVisibles):
        ?>
            <div style="padding: 2.5rem 1rem; text-align: center; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 12px;">
                <i class="ri-inbox-archive-line" style="font-size: 3rem; color: #94a3b8; display: block;"></i>
                <h3 style="margin: 0.5rem 0 0 0; color: #334155;">No hay pruebas asignadas a la Frecuencia <?= $frecuenciaNum ?></h3>
                <p style="color: #64748b; font-size: 0.875rem;">Selecciona las pruebas que aplican para esta frecuencia específica.</p>
                <a href="seleccionar-pruebas3.php?proyectoId=<?= $proyectoId ?>&frecuencia=<?= $frecuenciaNum ?>" class="btn btn-primary" style="background: #2563eb; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none;">
                    <i class="ri-checkbox-multiple-line"></i> Seleccionar Pruebas para Frecuencia <?= $frecuenciaNum ?>
                </a>
            </div>
        <?php 
            endif;
        } catch (PDOException $e) {
            error_log("Error al renderizar pruebas: " . $e->getMessage());
        }
        ?>
    </div>
</div>

<?php 
include 'js-proyectos.php';
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>