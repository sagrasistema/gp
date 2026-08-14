<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../main/config.php';
require_once 'conect-proyecto2.php';

// Sanitarización y Validación de Entrada
$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) ?: 0;

// Inicialización defensiva de datos heredados desde conect-proyecto2.php o contexto
$projectData = $projectData ?? new stdClass();
$pruebasList = is_array($pruebasList ?? null) ? $pruebasList : [];
$pruebasEjecutadas = is_array($pruebasEjecutadas ?? null) ? $pruebasEjecutadas : [];
$progresoActividades = is_array($progresoActividades ?? null) ? $progresoActividades : [];

$porcentajeProgreso = (float)($porcentajeProgreso ?? 0);
$porcentajeRevisado = (float)($porcentajeProgresoo ?? 0); // Corrección de typo $porcentajeProgresoo

// Carga optimizada de Categorías y Pruebas (Reducción de N+1 Queries)
$categories = [];
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        // Query de Categorías (Etapa 2 - Estrategia)
        $stmtCat = $pdo->prepare("
            SELECT id, nombre, orden 
            FROM audit_categorias 
            WHERE etapa_id = :etapaId 
            ORDER BY orden ASC
        ");
        $stmtCat->execute([':etapaId' => 2]);
        $categories = $stmtCat->fetchAll(PDO::FETCH_OBJ);

        // Pre-carga masiva de pruebas asociadas a la etapa 2 para optimizar memoria y tiempo de respuesta
        if (!empty($categories)) {
            $catIds = array_map(fn($c) => (int)$c->id, $categories);
            $inClause = implode(',', array_fill(0, count($catIds), '?'));
            
            $stmtP = $pdo->prepare("
                SELECT id, categoria_id, nombre, orden 
                FROM audit_pruebas 
                WHERE categoria_id IN ($inClause) 
                ORDER BY orden ASC
            ");
            $stmtP->execute($catIds);
            $allPruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);

            // Agrupar pruebas por categoria_id
            $pruebasPorCategoria = [];
            foreach ($allPruebas as $pr) {
                $pruebasPorCategoria[$pr->categoria_id][] = $pr;
            }
        }
    }
} catch (PDOException $e) {
    // Evitar exponer mensajes internos del servidor en producción
    error_log("Error de base de datos en responder.php: " . $e->getMessage());
    $categories = [];
}

// Configuración de interfaz
$pageTitle = "Panel de Control de Auditoría - Estrategia";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<style>
    .view-container { padding: 0.5rem; }
    .prueba-row-container { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.75rem; border-bottom: 1px solid var(--border-color, #e2e8f0); background: #ffffff; gap: 0.5rem; }
    .prueba-title { font-size: 0.8rem; font-weight: 600; color: #334155; flex-grow: 1; }
    .prueba-actions { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; justify-content: flex-end; }
    .badge-progress { font-size: 0.7rem; background: #f1f5f9; color: #475569; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 600; white-space: nowrap; }
    .project-stages-bar { display: flex; gap: 6px; margin: 8px 0; flex-wrap: wrap; }
    .stage-btn { flex: 1; min-width: 130px; padding: 6px 12px; background-color: #1e3a5f; border: 1px solid #2b4c7e; border-radius: 6px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 11px; letter-spacing: 0.3px; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s ease-in-out; text-transform: uppercase; }
    .stage-btn i { font-size: 13px; color: #00bcd4; }
    .stage-btn:hover { background-color: #2b4c7e; border-color: #00bcd4; }
    .stage-btn.active { background-color: #0f1c2e; border: 1.5px solid #00bcd4; color: #ffffff; box-shadow: 0 2px 8px rgba(0, 188, 212, 0.2); }
    .stage-btn.active i { color: #00bcd4; }
</style>

<?php include '../main/layout_header.php'; ?>

<div class="view-container">

    <!-- Navegación por Etapas -->
    <div class="project-stages-bar">
        <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-calendar-check-line"></i>1. Planificación
        </a>
        <a href="responder2.php?proyectoId=<?= $proyectoId ?>" class="stage-btn active">
            <i class="ri-compass-3-line"></i>2. Estrategia
        </a>
        <a href="responder3.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-play-circle-line"></i>3. Ejecución
        </a>
        <a href="responder4.php?proyectoId=<?= $proyectoId ?>" class="stage-btn">
            <i class="ri-flag-line"></i>4. Conclusión
        </a>
    </div>

    <?php if (filter_input(INPUT_GET, 'success') !== null): ?>
        <div class="alert-success" style="padding:0.5rem 0.75rem; background:#d1fae5; color:#065f46; border-radius:6px; margin-bottom:0.75rem; font-size:0.8rem;">
            <i class="ri-checkbox-circle-fill"></i> Parámetros e indicadores de prueba sincronizados correctamente.
        </div>
    <?php endif; ?>

    <!-- Cabecera Metadatos del Proyecto -->
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 0.75rem; padding: 0.6rem 0.8rem; border-radius: 8px; background: #ffffff; border: 1px solid var(--border-color, #e2e8f0);">
        <div style="display: flex; flex-direction: column; gap: 0.3rem; border-right: 1px solid #e2e8f0; padding-right: 0.5rem; font-size: 0.8rem;">
            <div>
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Cliente / Empresa</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.25rem;">
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio Líder</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioLider ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.3rem; border-right: 1px solid #e2e8f0; padding-right: 0.5rem; padding-left: 0.25rem; font-size: 0.8rem;">
            <div>
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Proyecto / Alcance</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->nombre ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.25rem;">
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio de Calidad</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioCalidad ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.3rem; padding-left: 0.25rem; font-size: 0.8rem;">
            <div>
                <?php
                $fechaRemisionFormateada = 'N/D';
                if (!empty($projectData->fechaRemision)) {
                    try {
                        $dateObj = new DateTimeImmutable((string)$projectData->fechaRemision);
                        $fechaRemisionFormateada = $dateObj->format('d/m/Y');
                    } catch (Exception) {
                        $fechaRemisionFormateada = htmlspecialchars((string)$projectData->fechaRemision, ENT_QUOTES, 'UTF-8');
                    }
                }
                ?>
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Revisión</span><br>
                <strong style="color: #1e293b;"><?= $fechaRemisionFormateada ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.25rem;">
                <span style="font-size: 0.68rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Gerente Encargado</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>
    </div>

    <!-- Título y Controles -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
        <h1 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.35rem;">
            <i class="ri-dashboard-line" style="color: var(--accent, #0284c7);"></i>Etapa 2 Estrategia
        </h1>

        <div style="display: flex; align-items: center; gap: 0.25rem; margin-left: auto;">
            <a href="../project/index.php" class="btn btn-primary" style="padding: 0.3rem 0.5rem; font-size: 0.8rem;" data-tooltip="Cancelar (Atrás)">
                <i class="ri-close-circle-line"></i> 
            </a>
        </div>
    </div>

    <!-- Indicador de Progreso General -->
    <div class="pruebas-progress-container" style="margin-bottom: 0.75rem; background: #ffffff; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 8px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
            <h4 style="margin: 0; font-size: 0.8rem; color: #1e293b; font-weight: 700; text-transform: uppercase;">
                Progreso General de Pruebas (Fase de Estrategia)
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
                    $pId = (int)($prueba['id'] ?? 0);
                    $ejecucion = $pruebasEjecutadas[$pId] ?? [];
                    $estadoPrueba = strtolower((string)($ejecucion['estado'] ?? 'en_proceso'));

                    $bgColor = match (true) {
                        in_array($estadoPrueba, ['completado', 'cerrado'], true) => '#10b981',
                        $estadoPrueba === 'revisado' => '#3b82f6',
                        str_contains($estadoPrueba, 'corregir') => '#ef4444',
                        ((int)($prueba['texto_inadecuado'] ?? 0) === 1 || (int)($prueba['texto_inadecuado2'] ?? 0) === 1) => '#ef4444',
                        default => '#64748b',
                    };

                    $safeId = $pId;
                    $safeCat = htmlspecialchars((string)($prueba['categoria_nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $safeNombrePrueba = htmlspecialchars((string)($prueba['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
                ?>
                    <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $safeId ?>" 
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
                    No hay pruebas configuradas en la fase de Estrategia.
                </p>
            <?php endif; ?>
        </div>

        <!-- Barras de Progreso Porcentual -->
        <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #e2e8f0; display: grid; grid-template-columns: repeat(12, 1fr); gap: 0.5rem;">
            <div style="grid-column: span 6; background: #ffffff; padding: 0.4rem; border-radius: 4px; border: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; font-size: 0.72rem; font-weight: 600; color: #475569;">
                    <span>Progreso Tareas Completadas</span>
                    <span style="color: #0f172a; font-weight: 700;"><?= number_format($porcentajeProgreso, 1) ?>%</span>
                </div>
                <div style="width: 100%; background-color: #e2e8f0; height: 6px; border-radius: 9999px; overflow: hidden;">
                    <div style="width: <?= min(100, max(0, $porcentajeProgreso)) ?>%; background-color: #10b981; height: 100%;"></div>
                </div>
            </div>

            <div style="grid-column: span 6; background: #ffffff; padding: 0.4rem; border-radius: 4px; border: 1px solid #f1f5f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; font-size: 0.72rem; font-weight: 600; color: #475569;">
                    <span>Progreso Tareas Revisadas</span>
                    <span style="color: #0f172a; font-weight: 700;"><?= number_format($porcentajeRevisado, 1) ?>%</span>
                </div>
                <div style="width: 100%; background-color: #e2e8f0; height: 6px; border-radius: 9999px; overflow: hidden;">
                    <div style="width: <?= min(100, max(0, $porcentajeRevisado)) ?>%; background-color: #3b82f6; height: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acordeones por Categorías -->
    <div class="accordion-container">
        <?php
        $catIndex = 0;
        $pruebaIndex = 1;

        $statusLabels = [
            'en_proceso' => '⏳ En proceso',
            'completado' => '✅ Completado',
            'por_corregir_lider' => '⚠️ Por Corregir Líder',
            'por_corregir_riesgo' => '🚨 Por Corregir Riesgo',
            'revisado' => '🔹 Revisado',
            'cerrado' => '🔒 Cerrado'
        ];

        foreach ($categories as $cat):
            $letraCat = chr(65 + ($catIndex % 26));
            $catIndex++;
            $pruebas = $pruebasPorCategoria[$cat->id] ?? [];
        ?>
            <div class="accordion-item" style="margin-bottom: 0.4rem; border: 1px solid var(--border-color, #cbd5e1); border-radius: 6px; overflow: hidden;">
                <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 0.5rem 0.75rem; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                    <span><?= $letraCat ?>. <?= htmlspecialchars((string)$cat->nombre, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                
                <div class="accordion-content" style="display: none; background: #fff;">
                    <?php foreach ($pruebas as $pr): 
                        $saved = $pruebasEjecutadas[$pr->id] ?? [];
                        $savedStatus = $saved['estado'] ?? 'en_proceso';
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
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasCI ? '#ca8a04' : '#cbd5e1' ?>; background: <?= $hasCI ? '#fef9c3' : '#f8fafc' ?>; color: <?= $hasCI ? '#ca8a04' : '#94a3b8' ?>;" title="Debilidades de Control Interno">CI</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasCG ? '#ea580c' : '#cbd5e1' ?>; background: <?= $hasCG ? '#ffedd5' : '#f8fafc' ?>; color: <?= $hasCG ? '#ea580c' : '#94a3b8' ?>;" title="Carta de Gerencia">CG</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasSC ? '#dc2626' : '#cbd5e1' ?>; background: <?= $hasSC ? '#fee2e2' : '#f8fafc' ?>; color: <?= $hasSC ? '#dc2626' : '#94a3b8' ?>;" title="Situaciones Críticas">SC</span>
                                    <span style="font-size: 0.68rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 3px; border: 1px solid <?= $hasAA ? '#2563eb' : '#cbd5e1' ?>; background: <?= $hasAA ? '#dbeafe' : '#f8fafc' ?>; color: <?= $hasAA ? '#2563eb' : '#94a3b8' ?>;" title="Asuntos de Auditoría">AA</span>
                                </div>

                                <span style="font-size: 0.72rem; font-weight: 600; padding: 0.2rem 0.5rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; color: #334155;">
                                    <?= $statusText ?>
                                </span>

                                <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= (int)$pr->id ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" data-tooltip="Llenar Cuestionario">
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