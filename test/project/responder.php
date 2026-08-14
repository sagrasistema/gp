<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// v/proyectos/responder.php
require_once '../main/config.php';
require_once 'conect-proyecto.php';

$pageTitle = "Panel de Control de Auditoría";

// 1. CAPTURA Y SANITIZACIÓN RIGUROSA DE ENTRADA ($_GET)
$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) ?: 0;

// 2. INICIALIZACIÓN PREVENTIVA DE VARIABLES Y METADATOS
$projectData = $projectData ?? (object)[
    'clientName' => 'N/D',
    'socioLider' => 'N/D',
    'nombre' => 'N/D',
    'socioCalidad' => 'N/D',
    'fechaRemision' => '',
    'gerente' => 'N/D'
];

$porcentajeProgreso = $porcentajeProgreso ?? 0;
$porcentajeProgresoo = $porcentajeProgresoo ?? 0;
$pruebasList = $pruebasList ?? [];
$pruebasEjecutadas = $pruebasEjecutadas ?? [];
$progresoActividades = $progresoActividades ?? [];

// 3. CONSULTA DE INDICADORES AGREGADOS POR CATEGORÍA
$indicadoresPorCategoria = [];

if ($proyectoId > 0 && isset($pdo) && $pdo instanceof PDO) {
    try {
        $sqlIndCat = "
            SELECT 
                p.categoria_id,
                MAX(CASE WHEN pe.indicador_ci = 1 THEN 1 ELSE 0 END) AS has_ci,
                MAX(CASE WHEN pe.indicador_cg = 1 THEN 1 ELSE 0 END) AS has_cg,
                MAX(CASE WHEN pe.indicador_sc = 1 THEN 1 ELSE 0 END) AS has_sc,
                MAX(CASE WHEN pe.indicador_aa = 1 THEN 1 ELSE 0 END) AS has_aa
            FROM audit_pruebas p
            LEFT JOIN proyecto_pruebas_ejecucion pe 
                   ON pe.prueba_id = p.id 
                  AND pe.proyecto_id = :proyectoId
            WHERE p.categoria_id IS NOT NULL
            GROUP BY p.categoria_id
        ";
        
        $stmtIndCat = $pdo->prepare($sqlIndCat);
        $stmtIndCat->execute([':proyectoId' => $proyectoId]);
        
        while ($row = $stmtIndCat->fetch(PDO::FETCH_ASSOC)) {
            $indicadoresPorCategoria[(int)$row['categoria_id']] = [
                'ci' => (int)$row['has_ci'] === 1,
                'cg' => (int)$row['has_cg'] === 1,
                'sc' => (int)$row['has_sc'] === 1,
                'aa' => (int)$row['has_aa'] === 1,
            ];
        }
    } catch (PDOException $e) {
        error_log('[Error PDO Indicadores Categoría] ' . $e->getMessage());
    }
}

include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<style>
    /* Estilos globales compactos */
    .view-container { padding: 0.5rem; }
    .prueba-row-container { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.75rem; border-bottom: 1px solid var(--border-color); background: #ffffff; gap: 0.5rem; }
    .prueba-title { font-size: 0.8rem; font-weight: 600; color: #334155; flex-grow: 1; }
    .prueba-actions { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; justify-content: flex-end; }
    .indicator-chk { display: flex; align-items: center; gap: 0.2rem; font-size: 0.7rem; font-weight: 700; border: 1px solid #cbd5e1; padding: 0.15rem 0.35rem; border-radius: 4px; cursor: pointer; }
    .status-select { padding: 0.25rem; border-radius: 4px; font-size: 0.75rem; border: 1px solid #cbd5e1; font-weight: 600; }
    .badge-progress { font-size: 0.7rem; background: #f1f5f9; color: #475569; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 600; white-space: nowrap; }
    
    /* Barra de Navegación Compacta */
    .project-stages-bar { display: flex; gap: 6px; margin: 8px 0; flex-wrap: wrap; }
    .stage-btn { flex: 1; min-width: 130px; padding: 6px 12px; background-color: #1e3a5f; border: 1px solid #2b4c7e; border-radius: 6px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 11px; letter-spacing: 0.3px; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s ease-in-out; text-transform: uppercase; }
    .stage-btn i { font-size: 13px; color: #00bcd4; }
    .stage-btn:hover { background-color: #2b4c7e; border-color: #00bcd4; }
    .stage-btn.active { background-color: #0f1c2e; border: 1.5px solid #00bcd4; color: #ffffff; box-shadow: 0 2px 8px rgba(0, 188, 212, 0.2); }
    .stage-btn.active i { color: #00bcd4; }

    /* Badges de Indicadores para Categorías y Pruebas */
    .ind-badge { font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 3px; border: 1px solid; }
    .ind-ci-active { background: #fef9c3; color: #ca8a04; border-color: #ca8a04; }
    .ind-cg-active { background: #ffedd5; color: #ea580c; border-color: #ea580c; }
    .ind-sc-active { background: #fee2e2; color: #dc2626; border-color: #dc2626; }
    .ind-aa-active { background: #dbeafe; color: #2563eb; border-color: #2563eb; }
    .ind-inactive { background: #ffffff; color: #cbd5e1; border-color: #cbd5e1; opacity: 0.7; }
</style>

<?php include '../main/layout_header.php'; ?>

<div class="view-container">

    <!-- Barra de Navegación Rápida por Etapas del Proyecto -->
    <div class="project-stages-bar">
        <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="stage-btn active">
            <i class="ri-calendar-check-line"></i>1. Planificación
        </a>
        <a href="responder2.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-compass-3-line"></i>2. Estrategia
        </a>
        <a href="responder3.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-play-circle-line"></i>3. Ejecución
        </a>
        <a href="responder4.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-flag-line"></i>4. Conclusión
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="padding:0.5rem 0.75rem; background:#d1fae5; color:#065f46; border-radius:6px; margin-bottom:0.75rem; font-size:0.8rem;">
            <i class="ri-checkbox-circle-fill"></i> Parámetros e indicadores de prueba sincronizados correctamente.
        </div>
    <?php endif; ?>

    <!-- Cabecera de Metadatos del Proyecto Compacta -->
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.75rem; padding: 0.6rem 0.8rem; border-radius: 8px; background: #ffffff; border: 1px solid var(--border-color);">
        <div style="display: flex; flex-direction: column; gap: 0.3rem; border-right: 1px solid #e2e8f0; padding-right: 0.5rem; font-size: 0.8rem;">
            <div>
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Cliente / Empresa</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars((string)($projectData->clientName ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.25rem;">
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio Líder</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars((string)($projectData->socioLider ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.3rem; border-right: 1px solid #e2e8f0; padding-right: 0.5rem; padding-left: 0.25rem; font-size: 0.8rem;">
            <div>
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Proyecto / Alcance</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars((string)($projectData->nombre ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.25rem;">
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio de Calidad</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars((string)($projectData->socioCalidad ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.3rem; padding-left: 0.25rem; font-size: 0.8rem;">
            <div>
                <?php
                $fechaRemisionFormateada = 'N/D';
                if (!empty($projectData->fechaRemision)) {
                    try {
                        $dateObj = new DateTime((string)$projectData->fechaRemision);
                        $fechaRemisionFormateada = $dateObj->format('d/m/Y');
                    } catch (Exception $e) {
                        $fechaRemisionFormateada = (string)$projectData->fechaRemision;
                    }
                }
                ?>
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Revisión</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($fechaRemisionFormateada, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.25rem;">
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Gerente Encargado</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars((string)($projectData->gerente ?? 'N/D'), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>
    </div>

    <!-- Barra de Título y Controles Compacta -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
        <h1 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.35rem;">
            <i class="ri-dashboard-line" style="color: var(--accent);"></i>Etapa 1 Planificación
        </h1>

        <div style="display: flex; align-items: center; gap: 0.25rem; margin-left: auto;">
            <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;">
                <i class="ri-arrow-go-back-line"></i> 
            </a>
            <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;">
                <i class="ri-screenshot-2-line"></i>
            </a>
            <a href="nuevo.php" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;">
                <i class="ri-add-line"></i>
            </a>
            <a href="../project/index.php" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" data-tooltip="Cancelar (Atrás)">
                <i class="ri-close-circle-line"></i> 
            </a>
            <a href="#" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" data-tooltip="Reporte de avance" onclick="return false;">
                <i class="ri-file-edit-line"></i> 
            </a>
            <a href="#" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" data-tooltip="Reporte de debilidades" onclick="return false;">
                <i class="ri-flag-line"></i>
            </a>
            <a href="#" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" data-tooltip="Reporte de Horas" onclick="return false;">
                <i class="ri-time-line"></i> 
            </a>
            <a href="#" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" data-tooltip="Reporte de asientos" onclick="return false;">
                <i class="ri-money-dollar-circle-line"></i>
            </a>
            <a href="#" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" data-tooltip="Reporte general">
                <i class="ri-layout-grid-line"></i> 
            </a>
        </div>
    </div>

    <!-- Bloque de Progreso General de Pruebas Compacto -->
    <div class="pruebas-progress-container" style="margin-bottom: 0.75rem; background: #ffffff; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
            <h4 style="margin: 0; font-size: 0.8rem; color: #1e293b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em;">
                Progreso General de Pruebas (Fase de Planificación)
            </h4>
            <span style="font-size: 0.68rem; background-color: #f1f5f9; color: #475569; padding: 0.15rem 0.5rem; border-radius: 9999px; font-weight: 600;">
                Total: <?= count($pruebasList) ?> Actividades / Pruebas
            </span>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
            <?php if (!empty($pruebasList)): ?>
                <?php 
                $globalIndex = 1;
                foreach ($pruebasList as $prueba): 
                    $pId = $prueba['id'];
                    $ejecucion = $pruebasEjecutadas[$pId] ?? null;
                    $estadoPrueba = strtolower($ejecucion['estado'] ?? 'en_proceso');

                    if ($estadoPrueba === 'completado' || $estadoPrueba === 'cerrado') {
                        $bgColor = '#10b981';
                    } elseif ($estadoPrueba === 'revisado') {
                        $bgColor = '#3b82f6';
                    } elseif (str_contains($estadoPrueba, 'corregir')) {
                        $bgColor = '#ef4444';
                    } else {
                        $bgColor = '#64748b';
                    }

                    if ((int)($prueba['texto_inadecuado'] ?? 0) === 1 || (int)($prueba['texto_inadecuado2'] ?? 0) === 1) {
                        $bgColor = '#ef4444';
                    }

                    $safeId = htmlspecialchars((string)$pId, ENT_QUOTES, 'UTF-8');
                    $safeCat = htmlspecialchars((string)($prueba['categoria_nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $safeNombrePrueba = htmlspecialchars((string)($prueba['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
                ?>
                    <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $safeId ?>" 
                       title="Nº <?= $globalIndex ?>: <?= $safeNombrePrueba ?> | Categoría: <?= $safeCat ?> | Estado: <?= ucfirst($estadoPrueba) ?>"
                       style="display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; background-color: <?= $bgColor ?>; color: #ffffff; font-weight: 700; border-radius: 6px; font-size: 0.75rem; text-decoration: none; transition: transform 0.15s ease, opacity 0.15s ease;"
                       onmouseover="this.style.opacity='0.9'; this.style.transform='translateY(-1px)';"
                       onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';">
                        <?= $globalIndex ?>
                    </a>
                <?php 
                    $globalIndex++;
                endforeach; 
                ?>
            <?php else: ?>
                <p style="color: #64748b; font-size: 0.75rem; margin: 0; font-style: italic;">
                    No hay pruebas configuradas en la fase de planificación.
                </p>
            <?php endif; ?>
        </div>

        <!-- Contenedor en Fila de Barras de Progreso -->
        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(12, 1fr); gap: 0.5rem;">
            <!-- Columna 1: Progreso Completado -->
            <div style="grid-column: span 6; background: #ffffff; padding: 0.4rem; border-radius: 4px; border: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; font-size: 0.72rem; font-weight: 600; color: #475569;">
                    <span>Progreso Tareas Completadas</span>
                    <span style="color: #0f172a; font-weight: 700;"><?= (int)$porcentajeProgreso ?>%</span>
                </div>
                <div style="width: 100%; background-color: #e2e8f0; height: 6px; border-radius: 9999px; overflow: hidden;">
                    <div style="width: <?= (int)$porcentajeProgreso ?>%; background-color: #10b981; height: 100%; border-radius: 9999px; transition: width 0.4s ease;"></div>
                </div>
            </div>

            <!-- Columna 2: Progreso Revisado -->
            <div style="grid-column: span 6; background: #ffffff; padding: 0.4rem; border-radius: 4px; border: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; font-size: 0.72rem; font-weight: 600; color: #475569;">
                    <span>Progreso Tareas Revisadas</span>
                    <span style="color: #0f172a; font-weight: 700;"><?= (int)$porcentajeProgresoo ?>%</span>
                </div>
                <div style="width: 100%; background-color: #e2e8f0; height: 6px; border-radius: 9999px; overflow: hidden;">
                    <div style="width: <?= (int)$porcentajeProgresoo ?>%; background-color: #3b82f6; height: 100%; border-radius: 9999px; transition: width 0.4s ease;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acordeones Compactos -->
    <div class="accordion-container">
        <?php
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmtCat = $pdo->prepare("SELECT * FROM audit_categorias WHERE etapa_id = 1 ORDER BY orden ASC");
            $stmtCat->execute();
            $categories = $stmtCat->fetchAll(PDO::FETCH_OBJ);
        } else {
            $categories = [];
        }

        $catIndex = 0;
        $pruebaIndex = 1;

        foreach ($categories as $cat):
            $letraCat = chr(65 + ($catIndex % 26));
            $catIndex++;

            $catInd = $indicadoresPorCategoria[(int)$cat->id] ?? ['ci' => false, 'cg' => false, 'sc' => false, 'aa' => false];

            $stmtP = $pdo->prepare("SELECT * FROM audit_pruebas WHERE categoria_id = :catId ORDER BY orden ASC");
            $stmtP->execute([':catId' => $cat->id]);
            $pruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);
        ?>
            <div class="accordion-item" style="margin-bottom: 0.4rem; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
                
                <!-- CINTILLO DE CATEGORÍA CON INDICADORES A LA DERECHA -->
                <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 0.5rem 0.75rem; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    <span><?= $letraCat ?>. <?= htmlspecialchars((string)$cat->nombre, ENT_QUOTES, 'UTF-8') ?></span>
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem;" onclick="event.stopPropagation();">
                        <!-- Indicadores Agregados en Categoría -->
                        <div style="display: flex; align-items: center; gap: 0.18rem;">
                            <span style="font-size: 0.65rem; color: #475569; font-weight: 600; margin-right: 0.2rem;">Indicadores:</span>
                            <span class="ind-badge <?= $catInd['ci'] ? 'ind-ci-active' : 'ind-inactive' ?>" title="Debilidades de Control Interno">CI</span>
                            <span class="ind-badge <?= $catInd['cg'] ? 'ind-cg-active' : 'ind-inactive' ?>" title="Carta de Gerencia">CG</span>
                            <span class="ind-badge <?= $catInd['sc'] ? 'ind-sc-active' : 'ind-inactive' ?>" title="Situaciones Críticas">SC</span>
                            <span class="ind-badge <?= $catInd['aa'] ? 'ind-aa-active' : 'ind-inactive' ?>" title="Asuntos de Auditoría">AA</span>
                        </div>

                        <i class="ri-arrow-down-s-line" style="margin-left: 0.2rem; font-size: 1rem; color: #64748b;"></i>
                    </div>
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
                        
                        $metricaAct = $progresoActividades[$pr->id] ?? ['total_actividades' => 0, 'actividades_completadas' => 0];
                        $totalAct = (int)$metricaAct['total_actividades'];
                        $completadasAct = (int)$metricaAct['actividades_completadas'];
                    ?>
                        <div class="prueba-row-container">
                            <div class="prueba-title">
                                <?= $pruebaIndex ?>. <?= htmlspecialchars((string)$pr->nombre, ENT_QUOTES, 'UTF-8') ?>
                                <div style="margin-top: 0.15rem;">
                                    <span class="badge-progress">
                                        <i class="ri-checkbox-circle-line"></i> Actividades: <?= $completadasAct ?> / <?= $totalAct ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="prueba-actions">
                                <div style="display: flex; align-items: center; gap: 0.2rem;">
                                    <?php 
                                    $hasCI = !empty($saved['indicador_ci']);
                                    $hasCG = !empty($saved['indicador_cg']);
                                    $hasSC = !empty($saved['indicador_sc']);
                                    $hasAA = !empty($saved['indicador_aa']);
                                    ?>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasCI ? '#ca8a04' : '#cbd5e1' ?>; background: <?= $hasCI ? '#fef9c3' : '#f8fafc' ?>; color: <?= $hasCI ? '#ca8a04' : '#94a3b8' ?>;" title="Debilidades de Control Interno (Amarillo)">CI</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasCG ? '#ea580c' : '#cbd5e1' ?>; background: <?= $hasCG ? '#ffedd5' : '#f8fafc' ?>; color: <?= $hasCG ? '#ea580c' : '#94a3b8' ?>;" title="Carta de Gerencia (Naranja)">CG</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasSC ? '#dc2626' : '#cbd5e1' ?>; background: <?= $hasSC ? '#fee2e2' : '#f8fafc' ?>; color: <?= $hasSC ? '#dc2626' : '#94a3b8' ?>;" title="Situaciones Críticas (Rojo)">SC</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasAA ? '#2563eb' : '#cbd5e1' ?>; background: <?= $hasAA ? '#dbeafe' : '#f8fafc' ?>; color: <?= $hasAA ? '#2563eb' : '#94a3b8' ?>;" title="Asuntos de Auditoría (Azul)">AA</span>
                                </div>

                                <span style="font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.5rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; color: #334155;">
                                    <?= $statusText ?>
                                </span>

                                <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pr->id ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" data-tooltip="Llenar Cuestionario y Gestionar Estatus">
                                    <i class="ri-pencil-fill"></i> Consultar
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