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

// Archivos de configuración e infraestructura de BD
require_once '../main/config.php';
require_once 'conect-proyecto3.php';

// 1. CAPTURA Y SANITIZACIÓN RIGUROSA DE ENTRADAS ($_GET)
$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) ?: 0;
$frecuenciaNum = filter_input(INPUT_GET, 'frecuencia', FILTER_VALIDATE_INT) ?: 1;

// 2. INICIALIZACIÓN PREVENTIVA DE VARIABLES
$pageTitle = "Panel de Control de Auditoría";
$projectData = $projectData ?? (object)[
    'clientName' => 'N/D',
    'socioLider' => 'N/D',
    'nombre' => 'N/D',
    'socioCalidad' => 'N/D',
    'fechaRemision' => '',
    'gerente' => 'N/D'
];

$isClosed = $isClosed ?? false;
$totalFrecuencias = $totalFrecuencias ?? 1;
$porcentajeProgreso = $porcentajeProgreso ?? 0;
$porcentajeProgresoo = $porcentajeProgresoo ?? 0;
$pruebasList = $pruebasList ?? [];
$pruebasEjecutadas = $pruebasEjecutadas ?? [];
$progresoActividades = $progresoActividades ?? [];
$asercionesPruebas = [];
$categoriesWithTests = [];

// Lista estándar de las 7 aserciones
$todasAserciones = ['C', 'A', 'E/O', 'CO', 'RO', 'VA', 'PD'];

// 3. CONSULTA Y PROCESAMIENTO PDO
if ($proyectoId > 0 && isset($pdo) && $pdo instanceof PDO) {
    try {
        // Cargar Aserciones del Modelo 6 por cada prueba
        $stmtAser = $pdo->prepare("
            SELECT 
                prueba_id,
                aser_c, aser_a, aser_eo, aser_co, aser_ro, aser_va, aser_pd
            FROM audit_modelo_6_detalles
            WHERE proyecto_id = :proyecto_id
        ");
        $stmtAser->execute([':proyecto_id' => $proyectoId]);

        while ($row = $stmtAser->fetch(PDO::FETCH_ASSOC)) {
            $asercionesPruebas[(int)$row['prueba_id']] = [
                'C'   => (int)($row['aser_c'] ?? 0) === 1,
                'A'   => (int)($row['aser_a'] ?? 0) === 1,
                'E/O' => (int)($row['aser_eo'] ?? 0) === 1,
                'CO'  => (int)($row['aser_co'] ?? 0) === 1,
                'RO'  => (int)($row['aser_ro'] ?? 0) === 1,
                'VA'  => (int)($row['aser_va'] ?? 0) === 1,
                'PD'  => (int)($row['aser_pd'] ?? 0) === 1,
            ];
        }

        // Cargar Categorías y sus Pruebas para la Frecuencia seleccionada
        $stmtCat = $pdo->prepare("SELECT * FROM audit_categorias WHERE etapa_id = 3 ORDER BY orden ASC");
        $stmtCat->execute();
        $categories = $stmtCat->fetchAll(PDO::FETCH_OBJ);

        foreach ($categories as $cat) {
            $stmtP = $pdo->prepare("
                SELECT p.* 
                FROM audit_pruebas p
                INNER JOIN proyecto_pruebas_ejecucion pe ON pe.prueba_id = p.id
                WHERE p.categoria_id = :catId 
                  AND pe.proyecto_id = :proyecto_id 
                  AND pe.frecuencia_num = :frecuencia_num
                ORDER BY p.id ASC
            ");
            $stmtP->execute([
                ':catId'          => $cat->id,
                ':proyecto_id'    => $proyectoId,
                ':frecuencia_num' => $frecuenciaNum
            ]);
            $pruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);

            if (!empty($pruebas)) {
                // Lógica de Agregación Booleana (OR) de Aserciones para la Categoría
                $catAserciones = array_fill_keys($todasAserciones, false);

                foreach ($pruebas as $p) {
                    $pId = (int)$p->id;
                    if (isset($asercionesPruebas[$pId])) {
                        foreach ($todasAserciones as $sigla) {
                            if (!empty($asercionesPruebas[$pId][$sigla])) {
                                $catAserciones[$sigla] = true;
                            }
                        }
                    }
                }

                $categoriesWithTests[] = [
                    'category'      => $cat,
                    'pruebas'       => $pruebas,
                    'catAserciones' => $catAserciones
                ];
            }
        }
    } catch (PDOException $e) {
        error_log('[Error PDO Audit System] ' . $e->getMessage());
    }
}

// Inclusión del Header principal
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<style>
    .view-container { padding: 0.5rem; }
    .prueba-row-container { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.75rem; border-bottom: 1px solid var(--border-color); background: #ffffff; gap: 0.5rem; }
    .prueba-title { font-size: 0.8rem; font-weight: 600; color: #334155; flex-grow: 1; }
    .prueba-actions { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; justify-content: flex-end; }
    .badge-progress { font-size: 0.7rem; background: #f1f5f9; color: #475569; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 600; white-space: nowrap; }
    .project-stages-bar { display: flex; gap: 6px; margin: 8px 0; flex-wrap: wrap; }
    .stage-btn { flex: 1; min-width: 130px; padding: 6px 12px; background-color: #1e3a5f; border: 1px solid #2b4c7e; border-radius: 6px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 11px; letter-spacing: 0.3px; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s ease-in-out; text-transform: uppercase; }
    .stage-btn i { font-size: 13px; color: #00bcd4; }
    .stage-btn:hover { background-color: #2b4c7e; border-color: #00bcd4; }
    .stage-btn.active { background-color: #0f1c2e; border: 1.5px solid #00bcd4; color: #ffffff; box-shadow: 0 2px 8px rgba(0, 188, 212, 0.2); }
    .stage-btn.active i { color: #00bcd4; }
    
    /* Badges Unificados de Aserciones (Categoría y Pruebas) */
    .aser-badge { font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.35rem; border-radius: 3px; border: 1px solid; }
    .aser-active { background: #0284c7; color: #ffffff; border-color: #0369a1; }
    .aser-inactive { background: #e2e8f0; color: #94a3b8; border-color: #cbd5e1; opacity: 0.65; }
</style>

<?php include '../main/layout_header.php'; ?>

<div class="view-container">

    <!-- Navegación de Etapas -->
    <div class="project-stages-bar">
        <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-calendar-check-line"></i>1. Planificación
        </a>
        <a href="responder2.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-compass-3-line"></i>2. Estrategia
        </a>
        <a href="responder3.php?proyectoId=<?= $proyectoId ?>" class="stage-btn active">
            <i class="ri-play-circle-line"></i>3. Ejecución
        </a>
        <a href="responder4.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-flag-line"></i>4. Conclusión
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="padding:0.5rem 0.75rem; background:#d1fae5; color:#065f46; border-radius:6px; margin-bottom:0.75rem; font-size:0.8rem;">
            <i class="ri-checkbox-circle-fill"></i> Operación realizada correctamente.
        </div>
    <?php endif; ?>

    <!-- Resumen de Metadatos del Proyecto -->
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

    <!-- Controles Superiores -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
        <h1 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.35rem;">
            <i class="ri-dashboard-line" style="color: var(--accent);"></i>Etapa 3 Ejecución
        </h1>

        <div style="display: flex; align-items: center; gap: 0.25rem; margin-left: auto;">
            <?php if (!$isClosed): ?>
                <a href="configurar-frecuencia3.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">
                    <i class="ri-calendar-event-line"></i> Frecuencias (<?= (int)$totalFrecuencias ?>)
                </a>

                <a href="seleccionar-pruebas3.php?proyectoId=<?= $proyectoId ?>&frecuencia=<?= $frecuenciaNum ?>" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; background: #2563eb; color: #ffffff;">
                    <i class="ri-checkbox-multiple-line"></i> Pruebas
                </a>
            <?php endif; ?>
            <a href="../project/index.php" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" data-tooltip="Cancelar (Atrás)">
                <i class="ri-close-circle-line"></i> 
            </a>
        </div>
    </div>

    <!-- Pestañas de Frecuencia -->
    <div style="background: #ffffff; padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 0.75rem;">
        <span style="font-size: 0.7rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 0.3rem; text-transform: uppercase;">
            Selecciona la Frecuencia a Ejecutar:
        </span>
        <div style="display: flex; gap: 0.38rem; overflow-x: auto;">
            <?php for ($f = 1; $f <= (int)$totalFrecuencias; $f++): ?>
                <a href="responder3.php?proyectoId=<?= $proyectoId ?>&frecuencia=<?= $f ?>" 
                   style="padding: 0.3rem 0.6rem; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 0.75rem; transition: all 0.2s; <?= $f === $frecuenciaNum ? 'background: #2563eb; color: #ffffff;' : 'background: #f8fafc; color: #475569; border: 1px solid #cbd5e1;' ?>">
                   <i class="ri-time-line"></i> Frecuencia <?= $f ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Panel de Progreso General -->
    <div class="pruebas-progress-container" style="margin-bottom: 0.75rem; background: #ffffff; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
            <h4 style="margin: 0; font-size: 0.8rem; color: #1e293b; font-weight: 700; text-transform: uppercase;">
                Progreso General de Pruebas
            </h4>
            <span style="font-size: 0.68rem; background-color: #f1f5f9; color: #475569; padding: 0.15rem 0.5rem; border-radius: 9999px; font-weight: 600;">
                Total: <?= count($pruebasList) ?> Actividades
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
                    <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $safeId ?>&frecuencia=<?= $frecuenciaNum ?>" 
                       title="Nº <?= $globalIndex ?>: <?= $safeNombrePrueba ?> | Categoría: <?= $safeCat ?> | Estado: <?= ucfirst($estadoPrueba) ?>"
                       style="display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; background-color: <?= $bgColor ?>; color: #ffffff; font-weight: 700; border-radius: 6px; font-size: 0.75rem; text-decoration: none;">
                        <?= $globalIndex ?>
                    </a>
                <?php 
                    $globalIndex++;
                endforeach; 
                ?>
            <?php else: ?>
                <p style="color: #64748b; font-size: 0.75rem; margin: 0; font-style: italic;">
                    No hay pruebas configuradas en la fase de ejecución.
                </p>
            <?php endif; ?>
        </div>

        <!-- Barras Estadísticas de Progreso -->
        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(12, 1fr); gap: 0.5rem;">
            <div style="grid-column: span 6; background: #ffffff; padding: 0.4rem; border-radius: 4px; border: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">
                    <span>Progreso Tareas Completadas</span>
                    <span style="color: #0f172a; font-weight: 700;"><?= (int)$porcentajeProgreso ?>%</span>
                </div>
                <div style="width: 100%; background-color: #e2e8f0; height: 6px; border-radius: 9999px; overflow: hidden;">
                    <div style="width: <?= (int)$porcentajeProgreso ?>%; background-color: #10b981; height: 100%;"></div>
                </div>
            </div>

            <div style="grid-column: span 6; background: #ffffff; padding: 0.4rem; border-radius: 4px; border: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; font-size: 0.72rem; font-weight: 600; color: #475569; margin-bottom: 0.25rem;">
                    <span>Progreso Tareas Revisadas</span>
                    <span style="color: #0f172a; font-weight: 700;"><?= (int)$porcentajeProgresoo ?>%</span>
                </div>
                <div style="width: 100%; background-color: #e2e8f0; height: 6px; border-radius: 9999px; overflow: hidden;">
                    <div style="width: <?= (int)$porcentajeProgresoo ?>%; background-color: #3b82f6; height: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acordeón de Categorías y Pruebas -->
    <div class="accordion-container">
        <?php
        $catIndex = 0;
        $pruebaIndex = 1;

        if (!empty($categoriesWithTests)):
            foreach ($categoriesWithTests as $item):
                $cat = $item['category'];
                $pruebas = $item['pruebas'];
                $catAserciones = $item['catAserciones'];
                $letraCat = chr(65 + ($catIndex % 26));
                $catIndex++;
        ?>
            <div class="accordion-item" style="margin-bottom: 0.4rem; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
                
                <!-- CINTILLO / ENCABEZADO DE LA CATEGORÍA CON LAS 7 ASERCIONES -->
                <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 0.5rem 0.75rem; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                    
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span><?= $letraCat ?>. <?= htmlspecialchars((string)$cat->nombre, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>

                    <!-- Renderizado de Aserciones Unificadas de la Categoría -->
                    <div style="display: flex; align-items: center; gap: 0.2rem;" onclick="event.stopPropagation();">
                        <span style="font-size: 0.65rem; color: #475569; font-weight: 600; margin-right: 0.2rem;">Aserciones:</span>
                        <?php foreach ($todasAserciones as $sigla): ?>
                            <?php $isActiva = !empty($catAserciones[$sigla]); ?>
                            <span class="aser-badge <?= $isActiva ? 'aser-active' : 'aser-inactive' ?>" 
                                  title="<?= $isActiva ? "Aserción $sigla marcada en pruebas de esta categoría" : "Aserción $sigla inactiva" ?>">
                                <?= $sigla ?>
                            </span>
                        <?php endforeach; ?>
                        <i class="ri-arrow-down-s-line" style="margin-left: 0.5rem; font-size: 1rem; color: #64748b;"></i>
                    </div>
                </div>
                
                <!-- LISTADO INTERNO DE PRUEBAS -->
                <div class="accordion-content" style="display: none; background: #ffffff;">
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

                        $aserList = $asercionesPruebas[(int)$pr->id] ?? [];
                    ?>
                        <div class="prueba-row-container">
                            <div class="prueba-title">
                                <?= $pruebaIndex ?>. <?= htmlspecialchars((string)$pr->nombre, ENT_QUOTES, 'UTF-8') ?>
                                <span style="margin-left: 0.3rem; font-size: 0.68rem; background: #dbeafe; color: #1e40af; padding: 0.1rem 0.35rem; border-radius: 3px; font-weight: 600;">
                                    Frecuencia <?= $frecuenciaNum ?>
                                </span>
                                <div style="margin-top: 0.15rem;">
                                    <span class="badge-progress">
                                        <i class="ri-checkbox-circle-line"></i> Actividades: <?= $completadasAct ?> / <?= $totalAct ?>
                                    </span>
                                </div>
                            </div>

                            <div class="prueba-actions">
                                <!-- Aserciones Específicas de la Prueba (Mismo diseño que la categoría, pegado a la izquierda de los indicadores) -->
                                <div style="display: flex; align-items: center; gap: 0.18rem; margin-right: 0.25rem;">
                                    <?php foreach ($todasAserciones as $sigla): ?>
                                        <?php $isActivaPrueba = !empty($aserList[$sigla]); ?>
                                        <span class="aser-badge <?= $isActivaPrueba ? 'aser-active' : 'aser-inactive' ?>" 
                                              title="<?= $isActivaPrueba ? "Aserción $sigla marcada en esta prueba" : "Aserción $sigla inactiva" ?>">
                                            <?= $sigla ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Indicadores de Control Interno, Carta de Gerencia, etc. -->
                                <div style="display: flex; align-items: center; gap: 0.2rem;">
                                    <?php 
                                    $hasCI = !empty($saved['indicador_ci']);
                                    $hasCG = !empty($saved['indicador_cg']);
                                    $hasSC = !empty($saved['indicador_sc']);
                                    $hasAA = !empty($saved['indicador_aa']);
                                    ?>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasCI ? '#ca8a04' : '#cbd5e1' ?>; background: <?= $hasCI ? '#fef9c3' : '#f8fafc' ?>; color: <?= $hasCI ? '#ca8a04' : '#94a3b8' ?>;" title="Debilidades de Control Interno">CI</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasCG ? '#ea580c' : '#cbd5e1' ?>; background: <?= $hasCG ? '#ffedd5' : '#f8fafc' ?>; color: <?= $hasCG ? '#ea580c' : '#94a3b8' ?>;" title="Carta de Gerencia">CG</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasSC ? '#dc2626' : '#cbd5e1' ?>; background: <?= $hasSC ? '#fee2e2' : '#f8fafc' ?>; color: <?= $hasSC ? '#dc2626' : '#94a3b8' ?>;" title="Situaciones Críticas">SC</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasAA ? '#2563eb' : '#cbd5e1' ?>; background: <?= $hasAA ? '#dbeafe' : '#f8fafc' ?>; color: <?= $hasAA ? '#2563eb' : '#94a3b8' ?>;" title="Asuntos de Auditoría">AA</span>
                                </div>

                                <span style="font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.5rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; color: #334155;">
                                    <?= $statusText ?>
                                </span>

                                <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pr->id ?>&frecuencia=<?= $frecuenciaNum ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
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
        <?php 
            endforeach;
        else:
        ?>
            <div style="padding: 1.5rem 1rem; text-align: center; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 8px;">
                <i class="ri-inbox-archive-line" style="font-size: 2rem; color: #94a3b8; display: block;"></i>
                <h3 style="margin: 0.4rem 0 0.2rem 0; color: #334155; font-size: 0.95rem;">No hay pruebas asignadas a la Frecuencia <?= $frecuenciaNum ?></h3>
                <p style="color: #64748b; font-size: 0.78rem; margin-bottom: 0.6rem;">Selecciona las pruebas que aplican para esta frecuencia específica.</p>
                <a href="seleccionar-pruebas3.php?proyectoId=<?= $proyectoId ?>&frecuencia=<?= $frecuenciaNum ?>" class="btn btn-primary" style="background: #2563eb; color: #fff; padding: 0.35rem 0.75rem; border-radius: 4px; text-decoration: none; font-size: 0.78rem;">
                    <i class="ri-checkbox-multiple-line"></i> Seleccionar Pruebas para Frecuencia <?= $frecuenciaNum ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
include 'js-proyectos.php';
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>