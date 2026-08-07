<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
// v/proyectos/responder.php
include '../main/config.php';
include 'conect-proyecto2.php';

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
        <i class="ri-calendar-check-line"></i>1. Planificación
    </a>
    <a href="responder2.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn active">
        <i class="ri-compass-3-line"></i>2. Estrategia
    </a>
    <a href="responder3.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn">
        <i class="ri-play-circle-line"></i>3. Ejecución
    </a>
    <a href="responder4.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn">
        <i class="ri-flag-line"></i>4. Conclusión
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
                 <?php
// Procesar y formatear la fecha de manera segura si existe
                    $fechaRemisionFormateada = 'N/D';
                    if (!empty($projectData->fechaRemision)) {
                        try {
                            $dateObj = new DateTime($projectData->fechaRemision);
                            $fechaRemisionFormateada = $dateObj->format('d/m/Y'); // Cambia a 'd-m-Y' si prefieres guiones
                        } catch (Exception $e) {
                            // Fallback seguro por si el string de la fecha está corrupto
                            $fechaRemisionFormateada = htmlspecialchars($projectData->fechaRemision, ENT_QUOTES, 'UTF-8');
                        }
                    }
                    ?>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Revisión</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($fechaRemisionFormateada ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Gerente Encargado</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>
    </div>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <!-- 1. Título (Alineado a la izquierda) -->
    <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <i class="ri-dashboard-line" style="color: var(--accent);"></i>Etapa 2 Estrategia 
    </h1>

    <!-- 2. Grupo de Botones (Agrupados y Alineados a la derecha) -->
    <div style="display: flex; align-items: center; gap: 0.35rem; margin-left: auto;">
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

        <a href="../project/index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>

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

        <!-- 5. Icono de Cuadrícula / Panel de Control -->
        <a href="#" class="btn btn-primary" data-tooltip="Reporte general">
            <i class="ri-layout-grid-line"></i> 
        </a>
    </div>
</div>

    <!-- Bloque de Progreso General de Pruebas (Numerado Dinámico) ss-->
<!-- Bloque de Progreso General de Pruebas (Numerado Consecutivo Global 1-19) -->
<div class="pruebas-progress-container" style="margin-bottom: 2rem; background: #ffffff; padding: 1.25rem; border: 1px solid #cbd5e1; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.0rem;">
        <h4 style="margin: 0; font-size: 0.95rem; color: #1e293b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em;">
            Progreso General de Pruebas (Fase de Estrategia)
        </h4>
        <span style="font-size: 0.75rem; background-color: #f1f5f9; color: #475569; padding: 0.25rem 0.75rem; border-radius: 9999px; font-weight: 600;">
            Total:  <?= count($pruebasList ?? []) ?> Actividades / Pruebas
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
    <div class="accordion-container">
        <?php
        $categories = $pdo->query("SELECT * FROM audit_categorias WHERE etapa_id = 2 ORDER BY orden ASC")->fetchAll(PDO::FETCH_OBJ);
        $catIndex = 0;
        $pruebaIndex = 1;

        foreach ($categories as $cat):
            // Generar letra MAYÚSCULA (A, B, C, D...) usando ASCII 65 ('A')
            $letraCat = chr(65 + ($catIndex % 26));
            $catIndex++;

            $stmtP = $pdo->prepare("SELECT * FROM audit_pruebas WHERE categoria_id = :catId ORDER BY orden ASC");
            $stmtP->execute([':catId' => $cat->id]);
            $pruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);
        ?>
            <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <span><?= $letraCat ?>. <?= htmlspecialchars($cat->nombre, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                
                <div class="accordion-content" style="display: none; background: #fff;">
                    <?php foreach ($pruebas as $pr): 
                        $saved = $pruebasEjecutadas[$pr->id] ?? null;
                        $savedStatus = $saved['estado'] ?? 'en_proceso';
                        
                        $statusLabels = [
                            'en_proceso' => '⏳ En proceso',
                            'completado' => '✅ Completado',
                            'por_corregir_lider' => '⚠️ Por Corregir Lider',
                            'por_corregir_riesgo' => '🚨 Por Corregir Riesgo',
                            'revisado' => '🔹 Revisado',
                            'cerrado' => '🔒 Cerrado'
                        ];
                        $statusText = $statusLabels[$savedStatus] ?? '⏳ En proceso';
                        
                        // Métricas de actividades
                        $metricaAct = $progresoActividades[$pr->id] ?? ['total_actividades' => 0, 'actividades_completadas' => 0];
                        $totalAct = (int)$metricaAct['total_actividades'];
                        $completadasAct = (int)$metricaAct['actividades_completadas'];
                    ?>
                        <div class="prueba-row-container" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color); background: #ffffff; gap: 1rem;">
                            
                            <div class="prueba-title">
                                <?= $pruebaIndex ?>.  <?= htmlspecialchars($pr->nombre, ENT_QUOTES, 'UTF-8') ?>
                                <div style="margin-top: 0.25rem;">
                                    <span class="badge-progress">
                                        <i class="ri-checkbox-circle-line"></i> Actividades: <?= $completadasAct ?> / <?= $totalAct ?> completadas
                                    </span>
                                </div>
                            </div>
                            
                            <div class="prueba-actions" style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; justify-content: flex-end;">
                                <!-- Indicadores de solo lectura (Disabled) cc-->
                         <!-- Indicadores visuales de solo lectura con colores personalizados -->
                            <!-- Indicadores visuales de solo lectura con escala de riesgo por color -->
                            <div style="display: flex; align-items: center; gap: 0.35rem;">
                                <?php 
                                $hasCI = !empty($saved['indicador_ci']);
                                $hasCG = !empty($saved['indicador_cg']);
                                $hasSC = !empty($saved['indicador_sc']);
                                $hasAA = !empty($saved['indicador_aa']);
                                ?>
                                <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid <?= $hasCI ? '#ca8a04' : '#cbd5e1' ?>; background: <?= $hasCI ? '#fef9c3' : '#f8fafc' ?>; color: <?= $hasCI ? '#ca8a04' : '#94a3b8' ?>;" title="Debilidades de Control Interno (Amarillo)">
                                    CI
                                </span>
                                <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid <?= $hasCG ? '#ea580c' : '#cbd5e1' ?>; background: <?= $hasCG ? '#ffedd5' : '#f8fafc' ?>; color: <?= $hasCG ? '#ea580c' : '#94a3b8' ?>;" title="Carta de Gerencia (Naranja)">
                                    CG
                                </span>
                                <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid <?= $hasSC ? '#dc2626' : '#cbd5e1' ?>; background: <?= $hasSC ? '#fee2e2' : '#f8fafc' ?>; color: <?= $hasSC ? '#dc2626' : '#94a3b8' ?>;" title="Situaciones Críticas (Rojo)">
                                    SC
                                </span>
                                <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid <?= $hasAA ? '#2563eb' : '#cbd5e1' ?>; background: <?= $hasAA ? '#dbeafe' : '#f8fafc' ?>; color: <?= $hasAA ? '#2563eb' : '#94a3b8' ?>;" title="Asuntos de Auditoría (Azul)">
                                    AA
                                </span>
                            </div>
                                <!-- Estatus de solo lectura -->
                                <span style="font-size: 0.8rem; font-weight: 600; padding: 0.35rem 0.75rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; color: #334155;">
                                    <?= $statusText ?>
                                </span>

                                <!-- Enlace a la pantalla de actividades para edición -->
                                <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pr->id ?>" class="btn btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;" data-tooltip="Llenar Cuestionario y Gestionar Estatus">
                                    <i class="ri-survey-line"></i> Actividades
                                </a>
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
</div>

<?php 
include 'js-proyectos.php';
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>