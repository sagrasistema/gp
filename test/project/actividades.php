<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
include 'conect-actividades.php';
$pruebaId = filter_var($pruebaId ?? 0, FILTER_VALIDATE_INT);

$categoriaId = null;
$etapaId = null;

if ($pruebaId > 0 && isset($pdo)) {
    try {
        // Consulta optimizada: Trae categoria_id de audit_pruebas y etapa_id de audit_categorias en un solo paso
        $stmt = $pdo->prepare("
            SELECT 
                p.categoria_id, 
                c.etapa_id 
            FROM audit_pruebas p
            INNER JOIN audit_categorias c ON p.categoria_id = c.id
            WHERE p.id = :prueba_id
            LIMIT 1
        ");
        
        $stmt->execute([':prueba_id' => $pruebaId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $categoriaId = (int)$resultado['categoria_id'];
            $etapaId = (int)$resultado['etapa_id'];
        }

    } catch (PDOException $e) {
        // Registrar el error de forma segura en el servidor sin exponer detalles al usuario final
        error_log("Error crítico al obtener categoría y etapa para la prueba ID {$pruebaId}: " . $e->getMessage());
    }
}
// 2. Cargar mapeo de estados y banderas de indicadores de las pruebas desde la ejecución
try {
    $stmtPruebas = $pdo->prepare("
        SELECT prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado 
        FROM proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :proyecto_id
    ");
    $stmtPruebas->execute([':proyecto_id' => $proyectoId]);
    $pruebasEjecutadas = $stmtPruebas->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar ejecución de pruebas: " . $e->getMessage());
    $pruebasEjecutadas = [];
}
// 3. Cargar lista completa de pruebas para la Fase de Planificación (Etapa 1) con sus categorías
try {
    $etapaId = (int)($etapaId ?? 0);
    $stmtList = $pdo->prepare("
        SELECT p.id, p.nombre, p.orden, p.texto_inadecuado, p.texto_inadecuado2, c.nombre as categoria_nombre 
        FROM audit_pruebas p
        INNER JOIN audit_categorias c ON p.categoria_id = c.id
        WHERE c.etapa_id = :etapa_id
        ORDER BY p.id ASC
    ");
    // Pasamos el valor de forma segura en el array de ejecución
    $stmtList->execute([':etapa_id' => $etapaId]);
    $pruebasList = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar listado de pruebas: " . $e->getMessage());
    $pruebasList = [];
}

// 4. Cargar métricas de progreso de actividades por prueba para este proyecto
try {
    // Aseguramos que la etapa sea un entero antes de procesar
    $etapaId = (int)($etapaId ?? 0);
    $stmtActProgress = $pdo->prepare("
        SELECT 
            p.id AS prueba_id,
            COUNT(a.id) AS total_actividades,
            SUM(CASE WHEN ae.completado = 1 THEN 1 ELSE 0 END) AS actividades_completadas
        FROM audit_pruebas p
        INNER JOIN audit_categorias c ON p.categoria_id = c.id
        LEFT JOIN audit_actividades a ON a.prueba_id = p.id
        LEFT JOIN proyecto_actividades_ejecucion ae ON ae.actividad_id = a.id AND ae.proyecto_id = :proyecto_id
        WHERE c.etapa_id = :etapa_id
        GROUP BY p.id
    ");
    // Ejecutamos pasando ambos parámetros de forma segura
    $stmtActProgress->execute([
        ':proyecto_id' => $proyectoId,
        ':etapa_id'    => $etapaId
    ]);

    // Indexamos por prueba_id para acceso O(1) en la vista
    $progresoActividades = $stmtActProgress->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al calcular progreso de actividades: " . $e->getMessage());
    $progresoActividades = [];
}

// 5. Calcular el porcentaje global de avance de la fase
$totalPruebasCount = count($pruebasList);
$completadasCount = 0;

foreach ($pruebasList as $pruebaItem) {
    $pIdCheck = $pruebaItem['id'];
    $estadoActual = strtolower($pruebasEjecutadas[$pIdCheck]['estado'] ?? 'en_proceso');
    if ($estadoActual === 'completado' || $estadoActual === 'cerrado') {
        $completadasCount++;
    }
}

$porcentajeProgreso = $totalPruebasCount > 0 ? round(($completadasCount / $totalPruebasCount) * 100) : 0;
// 6. Determinar el orden (ordinal) de la prueba actual dentro de la etapa
$ordinalPruebaEnEtapa = 0;

if (!empty($pruebasList)) {
    // Buscamos el ID en el array de pruebas de la etapa
    foreach ($pruebasList as $index => $pruebaItem) {
        if ((int)$pruebaItem['id'] === (int)$pruebaId) {
            // El índice comienza en 0, sumamos 1 para obtener la posición humana (1ª, 2ª, etc.)
            $ordinalPruebaEnEtapa = $index + 1;
            break; // Detenemos el bucle al encontrarla
        }
    }
}
$categoriaId = null;
$etapaId = null;
$categoriaNombre = ''; // Variable para almacenar el nombre de la categoría

if ($pruebaId > 0 && isset($pdo)) {
    try {
        // Consulta optimizada: Trae categoria_id, etapa_id y el nombre de la categoría en un solo paso
        $stmt = $pdo->prepare("
            SELECT 
                p.categoria_id, 
                c.etapa_id,
                c.nombre AS categoria_nombre 
            FROM audit_pruebas p
            INNER JOIN audit_categorias c ON p.categoria_id = c.id
            WHERE p.id = :prueba_id
            LIMIT 1
        ");
        
        $stmt->execute([':prueba_id' => $pruebaId]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resultado) {
            $categoriaId     = (int)$resultado['categoria_id'];
            $etapaId         = (int)$resultado['etapa_id'];
            $categoriaNombre = (string)$resultado['categoria_nombre'];
        }

    } catch (PDOException $e) {
        error_log("Error crítico al obtener categoría y etapa para la prueba ID {$pruebaId}: " . $e->getMessage());
    }
}
if ($etapaId == 3) {
$frecuenciaNum = filter_input(INPUT_GET, 'frecuencia', FILTER_VALIDATE_INT) ?: 1;
if ($frecuenciaNum <= 0) {
    $frecuenciaNum = 1;
    try {
    $stmtPruebas = $pdo->prepare("
        SELECT prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado 
        FROM proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :proyecto_id AND frecuencia_num = :frecuencia_num
    ");
    $stmtPruebas->execute([
        ':proyecto_id'   => $proyectoId,
        ':frecuencia_num' => $frecuenciaNum
    ]);
    // Indexamos por prueba_id para acceso O(1) en la vista
    $pruebasEjecutadas = $stmtPruebas->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar ejecución de pruebas por frecuencia: " . $e->getMessage());
    $pruebasEjecutadas = [];
}
}
// -------------------------------------------------------------------------
// 2. CARGAR MAPEO DE ESTADOS E INDICADORES DE LA FRECUENCIA ACTIVA
// -------------------------------------------------------------------------
    try {
        $stmtPruebas = $pdo->prepare("
            SELECT prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado 
            FROM proyecto_pruebas_ejecucion 
            WHERE proyecto_id = :proyecto_id AND frecuencia_num = :frecuencia_num
        ");
        $stmtPruebas->execute([
            ':proyecto_id'   => $proyectoId,
            ':frecuencia_num' => $frecuenciaNum
        ]);
        // Indexamos por prueba_id para acceso O(1) en la vista
        $pruebasEjecutadas = $stmtPruebas->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al cargar ejecución de pruebas por frecuencia: " . $e->getMessage());
        $pruebasEjecutadas = [];
    }

    // -------------------------------------------------------------------------
    // 3. CARGAR LISTA DE PRUEBAS SELECCIONADAS DE LA FRECUENCIA ACTIVA (ETAPA 3)sss
    // -------------------------------------------------------------------------
    try {
        $stmtList = $pdo->prepare("
            SELECT p.id, p.nombre, p.orden,  p.texto_inadecuado, p.texto_inadecuado2, c.nombre as categoria_nombre 
            FROM audit_pruebas p
            INNER JOIN audit_categorias c ON p.categoria_id = c.id
            INNER JOIN proyecto_pruebas_ejecucion pe ON pe.prueba_id = p.id
            WHERE c.etapa_id = 3 
            AND pe.proyecto_id = :proyecto_id 
            AND pe.frecuencia_num = :frecuencia_num
            ORDER BY p.id ASC
        ");
        $stmtList->execute([
            ':proyecto_id'   => $proyectoId,
            ':frecuencia_num' => $frecuenciaNum
        ]);
        $pruebasList = $stmtList->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al cargar listado de pruebas seleccionadas por frecuencia: " . $e->getMessage());
        $pruebasList = [];
    }

    // -------------------------------------------------------------------------
    // 4. CARGAR MÉTRICAS DE PROGRESO DE ACTIVIDADES DE LA FRECUENCIA ACTIVA
    // -------------------------------------------------------------------------
    try {
        $stmtActProgress = $pdo->prepare("
            SELECT 
                p.id AS prueba_id,
                COUNT(a.id) AS total_actividades,
                SUM(CASE WHEN ae.completado = 1 THEN 1 ELSE 0 END) AS actividades_completadas
            FROM audit_pruebas p
            INNER JOIN audit_categorias c ON p.categoria_id = c.id
            LEFT JOIN audit_actividades a ON a.prueba_id = p.id
            LEFT JOIN proyecto_actividades_ejecucion ae 
                ON ae.actividad_id = a.id 
            AND ae.proyecto_id = :proyecto_id 
            AND (ae.frecuencia_num = :frecuencia_num OR ae.frecuencia_num IS NULL)
            WHERE c.etapa_id = 3
            GROUP BY p.id
        ");
        $stmtActProgress->execute([
            ':proyecto_id'   => $proyectoId,
            ':frecuencia_num' => $frecuenciaNum
        ]);
        // Indexamos por prueba_id para acceso O(1) en la vista
        $progresoActividades = $stmtActProgress->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al calcular progreso de actividades por frecuencia: " . $e->getMessage());
        $progresoActividades = [];
    }

    // -------------------------------------------------------------------------
    // 5. CALCULAR EL PORCENTAJE GLOBAL DE AVANCE DE LA FRECUENCIA ACTIVA
    // -------------------------------------------------------------------------
    $totalPruebasCount = count($pruebasList);
    $completadasCount = 0;

    foreach ($pruebasList as $pruebaItem) {
        $pIdCheck = $pruebaItem['id'];
        $estadoActual = strtolower($pruebasEjecutadas[$pIdCheck]['estado'] ?? 'en_proceso');
        if ($estadoActual === 'completado' || $estadoActual === 'cerrado') {
            $completadasCount++;
        }
    }

    $porcentajeProgreso = $totalPruebasCount > 0 ? round(($completadasCount / $totalPruebasCount) * 100) : 0;
    if (isset($pdo, $proyectoId) && $proyectoId > 0) {
    try {
        // 1. Consultar las pruebas de ejecución del proyecto en su orden secuencial exacto
        $stmtEjecucion = $pdo->prepare("
            SELECT prueba_id 
            FROM proyecto_pruebas_ejecucion 
            WHERE proyecto_id = :proyecto_id 
            ORDER BY id ASC
        ");
        
        $stmtEjecucion->execute([':proyecto_id' => $proyectoId]);
        $pruebasEjecucionList = $stmtEjecucion->fetchAll(PDO::FETCH_ASSOC);

        // 2. Buscar la posición ordinal (índice 1-based) de la prueba actual dentro de la ejecución
        $contador = 1;
        if (!empty($pruebasEjecucionList)) {
            foreach ($pruebasEjecucionList as $row) {
                // Comparamos el prueba_id de la tabla con el parámetro actual de la prueba
                if (isset($pruebaId) && (int)$row['prueba_id'] === (int)$pruebaId) {
                    $ordinalPruebaEnEtapa = $contador;
                    break; // Detenemos el bucle al encontrar la coincidencia exacta
                }
                $contador++;
            }
        }

    } catch (PDOException $e) {
        // Control robusto de errores mediante registro interno y fallback seguro
        error_log("Error al calcular el ordinal de ejecución para el proyecto {$proyectoId}: " . $e->getMessage());
        $ordinalPruebaEnEtapa = 1;
    }
}

}
$letraCategoria = chr(64 + $categoriaId);
?>
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
    background-color: #1e3a5f; 
    border: 1px solid #2b4c7e;
    border-radius: 8px;
    color: #ffffff;
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
    color: #00bcd4;
}

.stage-btn:hover {
    background-color: #2b4c7e;
    border-color: #00bcd4;
    transform: translateY(-2px);
}

.stage-btn.active {
    background-color: #0f1c2e;
    border: 2px solid #00bcd4;
    color: #ffffff;
    box-shadow: 0 4px 15px rgba(0, 188, 212, 0.25);
}

.stage-btn.active i {
    color: #00bcd4;
}

/* Rediseño de Tablas y Metadatos */
.meta-summary-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 1.5rem;
}

.meta-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.meta-field-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: #64748b;
    font-weight: 700;
    letter-spacing: 0.05em;
}

.meta-field-value {
    color: #0f172a;
    font-size: 0.875rem;
    font-weight: 600;
}

.activity-textarea:focus {
    outline: none;
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}
</style>

<div class="view-container">
    <!-- Barra de Navegación Rápida por Etapas del Proyecto -->
    <div class="project-stages-bar">
        <a href="responder.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn">
            <i class="ri-calendar-check-line"></i> 1. Planificación
        </a>
        <a href="responder2.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn">
            <i class="ri-compass-3-line"></i> 2. Estrategia
        </a>
        <a href="responder3.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn">
            <i class="ri-play-circle-line"></i> 3. Ejecución
        </a>
        <a href="responder4.php?proyectoId=<?php echo $proyectoId; ?>" class="stage-btn">
            <i class="ri-flag-line"></i> 4. Conclusión
        </a>
    </div>

    <!-- Barra de Controles y Herramientas -->
    <div class="table-actions-container" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
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
        <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="btn btn-primary" data-tooltip="Cancelar (Atrás)" style="margin-left: auto;">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>

    <!-- Cabecera de Metadatos del Proyecto - Fila 1 -->
    <div class="meta-summary-card" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem;">
        <div class="meta-field-group" style="border-right: 1px solid #f1f5f9; padding-right: 1rem;">
            <div class="meta-field-group">
                <span class="meta-field-label">Cliente / Empresa</span>
                <span class="meta-field-value"><?= htmlspecialchars($projectData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="meta-field-group" style="border-top: 1px dashed #e2e8f0; padding-top: 0.6rem; margin-top: 0.6rem;">
                <span class="meta-field-label">Socio Líder</span>
                <span class="meta-field-value"><?= htmlspecialchars($projectData->socioLider ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <div class="meta-field-group" style="border-right: 1px solid #f1f5f9; padding-right: 1rem;">
            <div class="meta-field-group">
                <span class="meta-field-label">Proyecto / Alcance</span>
                <span class="meta-field-value"><?= htmlspecialchars($projectData->nombre ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="meta-field-group" style="border-top: 1px dashed #e2e8f0; padding-top: 0.6rem; margin-top: 0.6rem;">
                <span class="meta-field-label">Socio de Calidad</span>
                <span class="meta-field-value"><?= htmlspecialchars($projectData->socioCalidad ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <div class="meta-field-group">
            <div class="meta-field-group">
                <?php
                $fechaRemisionFormateada = 'N/D';
                if (!empty($projectData->fechaRemision)) {
                    try {
                        $dateObj = new DateTime($projectData->fechaRemision);
                        $fechaRemisionFormateada = $dateObj->format('d/m/Y');
                    } catch (Exception $e) {
                        $fechaRemisionFormateada = htmlspecialchars($projectData->fechaRemision, ENT_QUOTES, 'UTF-8');
                    }
                }
                ?>
                <span class="meta-field-label">Fecha de Revisión</span>
                <span class="meta-field-value"><?= htmlspecialchars($fechaRemisionFormateada, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="meta-field-group" style="border-top: 1px dashed #e2e8f0; padding-top: 0.6rem; margin-top: 0.6rem;">
                <span class="meta-field-label">Gerente Encargado</span>
                <span class="meta-field-value"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <!-- Cabecera de Metadatos del Proyecto - Fila 2 -->
    <div class="meta-summary-card" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem;">
        <div class="meta-field-group" style="border-right: 1px solid #f1f5f9; padding-right: 0.75rem;">
            <span class="meta-field-label">Realizado por:</span>
            <span class="meta-field-value"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="meta-field-group" style="border-right: 1px solid #f1f5f9; padding-right: 0.75rem;">
            <?php
            $fechacompletadoFormateada = 'N/D';
            if (!empty($projectData->completado)) {
                try {
                    $dateObj = new DateTime($projectData->completado);
                    $fechacompletadoFormateada = $dateObj->format('d/m/Y');
                } catch (Exception $e) {
                    $fechacompletadoFormateada = htmlspecialchars($projectData->completado, ENT_QUOTES, 'UTF-8');
                }
            }
            ?>
            <span class="meta-field-label">Fecha</span>
            <span class="meta-field-value"><?= htmlspecialchars($fechacompletadoFormateada, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="meta-field-group" style="border-right: 1px solid #f1f5f9; padding-right: 0.75rem;">
            <span class="meta-field-label">Revisado</span>
            <span class="meta-field-value"><?= htmlspecialchars($projectData->socioLider ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="meta-field-group" style="border-right: 1px solid #f1f5f9; padding-right: 0.75rem;">
            <?php
            $fecharevisadoFormateada = 'N/D';
            if (!empty($projectData->revisado)) {
                try {
                    $dateObj = new DateTime($projectData->revisado);
                    $fecharevisadoFormateada = $dateObj->format('d/m/Y');
                } catch (Exception $e) {
                    $fecharevisadoFormateada = htmlspecialchars($projectData->revisado, ENT_QUOTES, 'UTF-8');
                }
            }
            ?>
            <span class="meta-field-label">Fecha</span>
            <span class="meta-field-value"><?= htmlspecialchars($fecharevisadoFormateada, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="meta-field-group">
            <span class="meta-field-label">Estatus</span>
            <span class="meta-field-value" style="text-transform: capitalize; color: #0284c7;">
                <?= str_replace('_', ' ', $estadoActualPrueba) ?>
            </span>
        </div>
    </div>

    <!-- Bloque de Progreso General de Pruebas -->
    <div class="pruebas-progress-container" style="margin-bottom: 1.5rem; background: #ffffff; padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem;">
            <h4 style="margin: 0; font-size: 0.875rem; color: #334155; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                Listado Secuencial de Pruebas
            </h4>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
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
                    $safeCat = htmlspecialchars($prueba['categoria_nombre'] ?? '', ENT_QUOTES, 'UTF-8');
                    $safeNombrePrueba = htmlspecialchars($prueba['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>
                    <a href="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $safeId ?>" 
                       title="Nº <?= $globalIndex ?>: <?= $safeNombrePrueba ?> | Categoría: <?= $safeCat ?> | Estado: <?= ucfirst($estadoPrueba) ?>"
                       style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; background-color: <?= $bgColor ?>; color: #ffffff; font-weight: 700; border-radius: 6px; font-size: 0.85rem; text-decoration: none; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
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
    </div>

    <!-- Cabecera de la Prueba Actual -->
    <div style="background: linear-gradient(135deg, #1e3a5f 0%, #0f1c2e 100%); padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);">
        <span style="font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.2rem;">
            Etapa <?= htmlspecialchars($etapaId, ENT_QUOTES, 'UTF-8') ?>
        </span>
        <span style="font-size: 0.8rem; font-weight: 700; color: #00bcd4; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.5rem;">
            <?= htmlspecialchars($metaPrueba->catNombre, ENT_QUOTES, 'UTF-8') ?>
        </span>
        <?php
        $pruebaId = (int) ($pruebaId ?? 0);
        $nombrePrueba = $metaPrueba->nombre ?? '';

        $prefijoOrdinal = match ($pruebaId) {
            113 => '20',
            123 => '21',
            default => (string) ($ordinalPruebaEnEtapa ?? ''),
        };
        ?>

        <h2 style="margin: 0 0 1.25rem 0; font-size: 1.25rem; color: #ffffff; font-weight: 600; line-height: 1.4;">
            <?php if ($prefijoOrdinal !== ''): ?>
                <?= htmlspecialchars($prefijoOrdinal, ENT_QUOTES, 'UTF-8') ?>. 
            <?php endif; ?>
            <?= htmlspecialchars($nombrePrueba, ENT_QUOTES, 'UTF-8') ?>
        </h2>
        
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" onclick="openNormaModal()" style="background: #0284c7; color: #ffffff; border: none; font-size: 0.8rem; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: background 0.2s ease;">
                <i class="ri-book-line"></i> Norma 
            </button>
            <button type="button" onclick="openNormaModal2()" style="background: #0369a1; color: #ffffff; border: none; font-size: 0.8rem; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; transition: background 0.2s ease;">
                <i class="ri-book-open-line"></i> Instrucciones
            </button>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="padding: 0.85rem 1rem; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="ri-checkbox-circle-fill" style="font-size: 1.1rem; color: #10b981;"></i> Operación ejecutada con éxito.
        </div>
    <?php endif; ?>

    <!-- FORMULARIO PRINCIPAL DE ACTIVIDADES -->
    <form action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action_type" value="save_all">
        
        <?php foreach ($listaActividades as $act): ?>
            <div class="card-actividad activity-row" style="background: #ffffff; border: 1px solid #e2e8f0; padding: 1.25rem; border-radius: 8px; margin-bottom: 1rem; box-shadow: 0 1px 2px rgba(0,0,0,0.01);">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.85rem; gap: 1rem; align-items: flex-start;">
                    <div style="font-size: 0.925rem; color: #1e293b; line-height: 1.5; font-weight: 500;">
                        <strong style="color: #0f172a;">Actividad <?= $act->orden ?>:</strong> <?= $act->descripcion; ?>
                    </div>
                    <div class="activity-checkbox-container">
                        <label style="font-weight: 600; font-size: 0.825rem; color: #475569; display: flex; align-items: center; gap: 0.35rem; cursor: pointer; background: #f8fafc; padding: 0.3rem 0.6rem; border-radius: 4px; border: 1px solid #cbd5e1;">
                            <input type="checkbox" name="actividades_data[<?= $act->id ?>][completado]" value="1" <?= $act->is_ok ? 'checked' : '' ?> style="accent-color: #0284c7;"> Realizado
                        </label>
                    </div>
                </div>
                <textarea class="comment-input auto-expand activity-textarea" name="actividades_data[<?= $act->id ?>][contenido]" placeholder="Escriba aquí los hallazgos, papeles de trabajo o evidencias analizadas..." rows="3" style="width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1; font-family: inherit; resize: vertical; font-size: 0.875rem; color: #334155; transition: border-color 0.2s ease, box-shadow 0.2s ease;"><?= htmlspecialchars($act->respuesta, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        <?php endforeach; ?>

        <!-- SCRIPT PARA CONTROLAR LA VISIBILIDAD DEL CHECKBOX SEGÚN EL TEXTAREA -->
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const textareas = document.querySelectorAll('.activity-textarea');

            textareas.forEach(textarea => {
                const row = textarea.closest('.activity-row');
                const checkboxContainer = row ? row.querySelector('.activity-checkbox-container') : null;
                const checkbox = checkboxContainer ? checkboxContainer.querySelector('input[type="checkbox"]') : null;

                function updateCheckboxVisibility() {
                    if (!checkboxContainer) return;

                    if (textarea.value.trim() === '') {
                        checkboxContainer.style.display = 'none';
                        if (checkbox) checkbox.checked = false;
                    } else {
                        checkboxContainer.style.display = 'block';
                    }
                }

                updateCheckboxVisibility();
                textarea.addEventListener('input', updateCheckboxVisibility);
            });
        });
        </script>

        <!-- INCLUSIÓN AUTOMÁTICA DE LA REVISIÓN ANALÍTICA -->
        <?php 
            if ((int)$modeloPrueba === 6) {
                include 'modelo6.php';
            }
            if ((int)$modeloPrueba === 5) {
                include 'modelo5.php';
            }
            if ((int)$pruebaId === 11) {
                include 'prueba11.php';
            } elseif ((int)$pruebaId === 16) {
                include 'prueba16.php';
            } elseif ((int)$pruebaId === 23) {
                include 'prueba23.php';
            }
        ?>

        <!-- SECCIÓN DE ACORDEONES POR CADA INDICADOR (CI, CG, SC, AA) -->
        <div style="margin: 2rem 0 1.5rem 0;">
            <h3 style="font-size: 1rem; color: #0f172a; font-weight: 700; margin-bottom: 0.85rem; text-transform: uppercase; letter-spacing: 0.025em;">
                Indicadores y Puntos de Control
            </h3>
            
            <?php 
            $indicadoresMeta = [
                'CI' => ['nombre' => 'Debilidades de Control Interno (CI)', 'color' => '#ca8a04'],
                'CG' => ['nombre' => 'Carta de Gerencia (CG)', 'color' => '#ea580c'],
                'SC' => ['nombre' => 'Situaciones Críticas (SC)', 'color' => '#dc2626'],
                'AA' => ['nombre' => 'Asuntos de Auditoría (AA)', 'color' => '#2563eb']
            ];

            foreach ($indicadoresMeta as $key => $meta):
                $items = $detallesPorTipo[$key] ?? [];
            ?>
                <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background: #ffffff;">
                    <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f8fafc; padding: 0.85rem 1.1rem; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid <?= $meta['color'] ?>;">
                        <span style="color: #1e293b; display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem;">
                            <i class="ri-file-list-3-line" style="color: <?= $meta['color'] ?>; font-size: 1.1rem;"></i> <?= $meta['nombre'] ?> 
                            <span style="font-size: 0.725rem; background: #e2e8f0; color: #334155; padding: 0.1rem 0.5rem; border-radius: 9999px; font-weight: 700;"><?= count($items) ?></span>
                        </span>
                        <i class="ri-arrow-down-s-line" style="color: #64748b;"></i>
                    </div>

                    <div class="accordion-content" style="display: none; padding: 1.25rem; background: #ffffff; border-top: 1px solid #f1f5f9;">
                        <div style="margin-bottom: 1rem;">
                            <button type="button" class="btn btn-primary" onclick="openIndicatorModal('<?= $key ?>')" style="padding: 0.4rem 0.85rem; font-size: 0.8rem; background: <?= $meta['color'] ?>; border-color: <?= $meta['color'] ?>; border-radius: 6px;">
                                <i class="ri-add-line"></i> Punto de control
                            </button>
                        </div>

                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                                <thead>
                                    <tr style="background: #f1f5f9; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                        <th style="padding: 0.6rem 0.75rem;">Rubro</th>
                                        <th style="padding: 0.6rem 0.75rem;">Título</th>
                                        <th style="padding: 0.6rem 0.75rem;">Descripción</th>
                                        <th style="padding: 0.6rem 0.75rem;">Recomendación del Asunto</th>
                                        <th style="padding: 0.6rem 0.75rem; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr style="border-bottom: 1px solid #f8fafc;">
                                                <td style="padding: 0.75rem; font-weight: 600; color: #334155;"><?= htmlspecialchars($item->rubro ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td style="padding: 0.75rem; font-weight: 600; color: #0f172a;"><?= htmlspecialchars($item->titulo, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td style="padding: 0.75rem; color: #475569;"><?= nl2br(htmlspecialchars($item->descripcion, ENT_QUOTES, 'UTF-8')) ?></td>
                                                <td style="padding: 0.75rem; color: #475569;"><?= nl2br(htmlspecialchars($item->recomendacion ?? '-', ENT_QUOTES, 'UTF-8')) ?></td>
                                                <td style="padding: 0.75rem; text-align: center;">
                                                    <button type="submit" form="deleteForm_<?= $item->id ?>" class="btn" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;" title="Eliminar">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="padding: 1rem; text-align: center; color: #94a3b8; font-style: italic;">No hay registros agregados en este indicador.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CAJA DE COMENTARIOS SOCIOS -->
        <div class="accordion-socios" style="margin-top: 1.5rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.01);">
            <div class="accordion-header" onclick="toggleAcordeonSocios(this)" style="padding: 0.85rem 1.1rem; background: #f8fafc; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-radius: 8px 8px 0 0; font-weight: 600; color: #0f172a; font-size: 0.875rem;">
                <span><i class="ri-shield-user-line" style="margin-right: 6px; color: #0284c7;"></i> Observaciones de Socios (Líder y Calidad)</span>
                <i class="ri-arrow-down-s-line" style="transition: transform 0.3s ease; color: #64748b;"></i>
            </div>
            
            <div class="accordion-body" style="display: none; padding: 1.25rem; border-top: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 280px;">
                        <label for="observacion_socio_lider" style="display: block; font-weight: 600; font-size: 0.825rem; color: #475569; margin-bottom: 0.4rem;">
                            Observaciones del Socio Líder
                        </label>
                        <textarea name="observacion_socio_lider" id="observacion_socio_lider" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; resize: vertical; color: #334155;"><?= htmlspecialchars($obsSocioLider ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div style="flex: 1; min-width: 280px;">
                        <label for="observacion_socio_calidad" style="display: block; font-weight: 600; font-size: 0.825rem; color: #475569; margin-bottom: 0.4rem;">
                            Observaciones del Socio de Calidad
                        </label>
                        <textarea name="observacion_socio_calidad" id="observacion_socio_calidad" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; resize: vertical; color: #334155;"><?= htmlspecialchars($obsSocioCalidad ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN ADJUNTO ÚNICO DE LA PRUEBA (UI REDISEÑADA) -->
        <div style="margin-top: 1.5rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.25rem; box-shadow: 0 1px 2px rgba(0,0,0,0.01);">
            <label style="display: flex; align-items: center; gap: 0.4rem; font-weight: 700; font-size: 0.875rem; color: #0f172a; margin-bottom: 0.75rem;">
                <i class="ri-attachment-2" style="color: #0284c7; font-size: 1.1rem;"></i> Documento Adjunto de la Prueba
            </label>
            
            <div style="display: flex; align-items: center; gap: 0.85rem; background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 0.85rem 1.1rem; border-radius: 8px; flex-wrap: wrap;">
                <label for="documento_prueba" 
                       style="cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; background-color: #0284c7; color: #ffffff; font-weight: 600; font-size: 0.8rem; padding: 0.45rem 1rem; border-radius: 6px; margin: 0; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: background-color 0.2s ease;">
                    <i class="ri-upload-cloud-2-line" style="font-size: 1rem;"></i> Seleccionar archivo
                </label>
                
                <input type="file" 
                       name="documento_prueba" 
                       id="documento_prueba" 
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip,.rar"
                       style="display: none;" 
                       onchange="actualizarNombreArchivo(this)">
                
                <span id="texto_archivo_nuevo" style="font-size: 0.825rem; color: #64748b; font-style: italic; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 320px;">
                    Ningún archivo nuevo seleccionado
                </span>
            </div>

            <small style="display: block; color: #94a3b8; font-size: 0.75rem; margin-top: 0.4rem; margin-left: 0.2rem;">
                <i class="ri-information-line"></i> Formatos permitidos: PDF, Word, Excel, Imágenes, ZIP, RAR (Tamaño máximo: 10MB).
            </small>

            <?php if (!empty($archivoRutaPrueba)): ?>
                <div style="margin-top: 0.85rem; padding: 0.6rem 0.85rem; background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                    <span style="font-size: 0.825rem; color: #0369a1; display: flex; align-items: center; gap: 0.4rem;">
                        <i class="ri-file-text-line" style="font-size: 1rem; color: #0284c7;"></i>
                        <strong>Documento actual:</strong> 
                        <span style="color: #0c4a6e; font-weight: 600;"><?= htmlspecialchars((string)$archivoNombrePrueba, ENT_QUOTES, 'UTF-8') ?></span>
                        <small style="color: #0369a1;">(<?= round(((int)($archivoPesoPrueba ?? 0)) / 1024, 1) ?> KB)</small>
                    </span>
                    
                    <a href="../<?= htmlspecialchars((string)$archivoRutaPrueba, ENT_QUOTES, 'UTF-8') ?>" 
                       target="_blank" 
                       class="btn btn-sm" 
                       style="background-color: #e0f2fe; color: #0369a1; font-weight: 600; font-size: 0.775rem; padding: 0.3rem 0.75rem; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.35rem; transition: background-color 0.2s ease;">
                        <i class="ri-download-2-line"></i> Descargar Documento
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <script>
        function actualizarNombreArchivo(input) {
            const labelTexto = document.getElementById('texto_archivo_nuevo');
            if (input.files && input.files.length > 0) {
                labelTexto.textContent = input.files[0].name;
                labelTexto.style.color = '#0f172a';
                labelTexto.style.fontWeight = '600';
                labelTexto.style.fontStyle = 'normal';
            } else {
                labelTexto.textContent = 'Ningún archivo nuevo seleccionado';
                labelTexto.style.color = '#64748b';
                labelTexto.style.fontWeight = '400';
                labelTexto.style.fontStyle = 'italic';
            }
        }

        function toggleAcordeonSocios(header) {
            const body = header.nextElementSibling;
            const icon = header.querySelector('.ri-arrow-down-s-line');
            if (body.style.display === 'none' || body.style.display === '') {
                body.style.display = 'block';
                icon.style.transform = 'rotate(180deg)';
            } else {
                body.style.display = 'none';
                icon.style.transform = 'rotate(0deg)';
            }
        }
        </script>

        <!-- CAJA DE ESTATUS GENERAL DE LA PRUEBA Y BOTONES ACCIÓN -->
        <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 1.25rem; border-radius: 10px; margin-top: 1.5rem; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div>
                <h4 style="margin: 0 0 0.2rem 0; font-size: 0.925rem; color: #0f172a; font-weight: 700;">Estatus General de la Prueba</h4>
                <p style="margin: 0; font-size: 0.8rem; color: #64748b;">El estatus se actualizará al guardar todo el formulario.</p>
            </div>

            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <select name="estado_prueba" class="status-select" style="padding: 0.55rem 0.85rem; border-radius: 6px; font-size: 0.85rem; border: 1px solid #cbd5e1; font-weight: 600; background: #f8fafc; color: #0f172a;">
                    <option value="en_proceso" <?= $estadoActualPrueba === 'en_proceso' ? 'selected' : '' ?>>⏳ En proceso</option>
                    <option value="completado" <?= $estadoActualPrueba === 'completado' ? 'selected' : '' ?>>✅ Completado</option>
                    <option value="por_corregir_lider" <?= $estadoActualPrueba === 'por_corregir_lider' ? 'selected' : '' ?>>⚠️ Por Corregir Lider</option>
                    <option value="por_corregir_riesgo" <?= $estadoActualPrueba === 'por_corregir_riesgo' ? 'selected' : '' ?>>🚨 Por Corregir Riesgo</option>
                    <option value="revisado" <?= $estadoActualPrueba === 'revisado' ? 'selected' : '' ?>>🔹 Revisado</option>
                    <option value="cerrado" <?= $estadoActualPrueba === 'cerrado' ? 'selected' : '' ?>>🔒 Cerrado</option>
                </select>

                <div style="display: flex; gap: 0.5rem;">
                    <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary" style="padding: 0.55rem 1.25rem; font-size: 0.85rem; border-radius: 6px;">Volver al Panel</a>
                    <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1.75rem; font-size: 0.85rem; border-radius: 6px; font-weight: 600;"><i class="ri-save-3-line"></i> Guardar Todo</button>
                </div>
            </div>
        </div>
    </form>

    <!-- Formularios ocultos individuales para eliminar registros de indicadores -->
    <?php foreach ($allDetalles as $det): ?>
        <form id="deleteForm_<?= $det->id ?>" action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST" style="display:none;">
            <input type="hidden" name="action_type" value="delete_indicador_detalle">
            <input type="hidden" name="detalle_id" value="<?= $det->id ?>">
        </form>
    <?php endforeach; ?>
</div>

<!-- MODAL PARA AGREGAR PUNTO DE CONTROL DE INDICADOR -->
<div id="indicatorModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1100; align-items:center; justify-content:center;">
    <div style="background:#ffffff; padding:1.75rem; border-radius:10px; max-width:600px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid #e2e8f0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
            <h3 id="modalIndicatorTitle" style="margin:0; color:#0f172a; font-size:1.1rem; display:flex; align-items:center; gap:0.5rem; font-weight: 700;">
                <i class="ri-add-box-line" style="color:var(--accent);"></i> Nuevo Punto de Control
            </h3>
            <button type="button" onclick="closeIndicatorModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#64748b;">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <form action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST">
            <input type="hidden" name="action_type" value="add_indicador_detalle">
            <input type="hidden" id="modalTipoIndicador" name="tipo_indicador" value="">

            <div style="margin-bottom: 0.85rem;">
                <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Rubro</label>
                <input type="text" name="rubro" placeholder="Ej. Activo Corriente, Cuentas por Cobrar..." style="width: 100%; padding: 0.55rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.875rem;">
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Título del Asunto / Hallazgo *</label>
                <input type="text" name="titulo" required placeholder="Título resumido..." style="width: 100%; padding: 0.55rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.875rem;">
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Descripción *</label>
                <textarea name="descripcion" required rows="3" placeholder="Descripción detallada de la debilidad o hallazgo..." style="width: 100%; padding: 0.55rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.875rem; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Recomendación del Asunto</label>
                <textarea name="recomendacion" rows="3" placeholder="Recomendación sugerida..." style="width: 100%; padding: 0.55rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.875rem; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="text-align:right; border-top:1px solid #e2e8f0; padding-top:0.85rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeIndicatorModal()" style="padding: 0.45rem 1.1rem; font-size: 0.85rem; border-radius: 6px;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="padding: 0.45rem 1.25rem; font-size: 0.85rem; border-radius: 6px; font-weight: 600;">Guardar Registro</button>
            </div>
        </form>
    </div>
</div>

<?php if ((int)$pruebaId === 11): ?>
    <!-- Formularios ocultos para eliminar filas analíticas -->
    <?php foreach (array_merge($analiticaItems['activo'], $analiticaItems['pasivo'], $analiticaItems['patrimonio']) as $it): ?>
        <form id="delAnalitica_<?= (int)$it->id ?>" action="guardar_analitica.php?proyectoId=<?= (int)$proyectoId ?>&pruebaId=11" method="POST" style="display:none;">
            <input type="hidden" name="action_type" value="delete_analitica_item">
            <input type="hidden" name="item_id" value="<?= (int)$it->id ?>">
        </form>
    <?php endforeach; ?>

    <!-- Modal para Agregar Partida Analítica -->
    <div id="analiticaModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1150; align-items: center; justify-content: center;">
        <div style="background: #ffffff; padding: 1.75rem; border-radius: 10px; max-width: 500px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
                <h3 id="modalAnaliticaTitle" style="margin: 0; color: #0f172a; font-size: 1.05rem; font-weight: 700;">Agregar Partida</h3>
                <button type="button" onclick="closeAnaliticaModal()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #64748b;"><i class="ri-close-line"></i></button>
            </div>
            
            <form action="guardar_analitica.php?proyectoId=<?= (int)$proyectoId ?>&pruebaId=11" method="POST">
                <input type="hidden" name="action_type" value="add_analitica_item">
                <input type="hidden" name="tipo" id="modal_tipo_input">

                <div style="margin-bottom: 0.85rem;">
                    <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Tipo / Rubro (Ej. Efectivo, Cuentas por Cobrar...)</label>
                    <input type="text" name="tipo_rubro" required style="width: 100%; padding: 0.55rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.875rem;">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; margin-bottom: 0.85rem;">
                    <div>
                        <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Saldo Actual</label>
                        <input type="number" step="0.01" name="saldo_actual" value="0.00" required style="width: 100%; padding: 0.55rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.875rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Saldo Anterior</label>
                        <input type="number" step="0.01" name="saldo_anterior" value="0.00" required style="width: 100%; padding: 0.55rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.875rem;">
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.825rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Observaciones (ID de referencia)</label>
                    <input type="text" name="observaciones" placeholder="Ej. 6, 7..." style="width: 100%; padding: 0.55rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.875rem;">
                </div>

                <div style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem; border-top: 1px solid #e2e8f0; padding-top: 0.85rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeAnaliticaModal()" style="padding: 0.45rem 1rem; font-size: 0.85rem; border-radius: 6px;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.85rem; border-radius: 6px; font-weight: 600;">Guardar Partida</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAnaliticaModal(tipo) {
        const modal = document.getElementById('analiticaModal');
        const tipoInput = document.getElementById('modal_tipo_input');
        const titleEl = document.getElementById('modalAnaliticaTitle');
        
        if (modal && tipoInput && titleEl) {
            tipoInput.value = tipo;
            const tipoFormateado = tipo.charAt(0).toUpperCase() + tipo.slice(1);
            titleEl.textContent = 'Agregar Partida de ' + tipoFormateado;
            modal.style.display = 'flex';
        }
    }

    function closeAnaliticaModal() {
        const modal = document.getElementById('analiticaModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('analiticaModal');
        if (event.target === modal) {
            closeAnaliticaModal();
        }
    });
    </script>
<?php endif; ?>

<!-- MODAL RIESGO -->
<div id="modalRiesgo" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background-color: #ffffff; border: 1px solid #cbd5e1; width: 95%; max-width: 1100px; border-radius: 10px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        
        <div style="background-color: #1e3a5f; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: #ffffff; font-size: 0.95rem; margin: 0; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                <i class="ri-add-line" style="color: #00bcd4;"></i> Agregar Riesgo
            </h3>
            <button type="button" onclick="closeModalRiesgo()" style="background: transparent; border: none; color: #ffffff; font-size: 1.25rem; cursor: pointer;"><i class="ri-close-line"></i></button>
        </div>

        <form id="formRiesgo23" style="padding: 20px; max-height: 80vh; overflow-y: auto;">
            <input type="hidden" name="proyecto_id" value="<?php echo htmlspecialchars((string)$proyectoId, ENT_QUOTES, 'UTF-8'); ?>">

            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin-bottom: 18px;">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                    <?php foreach ($catalogosRiesgo as $nameKey => $opciones): ?>
                        <div class="form-group" style="display: flex; flex-direction: column;">
                            <label style="font-size: 0.7rem; text-transform: uppercase; color: #475569; margin-bottom: 6px; font-weight: 700; letter-spacing: 0.05em;">
                                <?php echo ucwords(str_replace('_', ' ', $nameKey)); ?>
                            </label>
                            <select name="<?php echo $nameKey; ?>" class="form-control" style="width: 100%; background-color: #ffffff; border: 1px solid #cbd5e1; color: #0f172a; padding: 8px 10px; border-radius: 6px; font-size: 0.825rem;" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($opciones as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" id="acuerdo_informacion" name="acuerdo_informacion" value="1" required style="width: 16px; height: 16px; accent-color: #1e3a5f; cursor: pointer;">
                    <label for="acuerdo_informacion" style="color: #334155; font-size: 0.825rem; font-weight: 600; cursor: pointer;">Estoy de acuerdo con la información suministrada!</label>
                </div>

                <button type="button" id="btnGuardarRiesgo23" class="btn" style="background-color: #1e3a5f; color: #ffffff; border: none; padding: 8px 20px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">
                    <i class="ri-save-line"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
$textoInadecuado2 = (bool)($metaPrueba->texto_inadecuado2 ?? false);
?>

<!-- Modal de Normas (normaModal) -->
<div id="normaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#ffffff; padding:1.75rem; border-radius:10px; max-width:850px; width:92%; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid #e2e8f0; margin:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
            <h3 style="margin:0; color:#0f172a; font-size:1.05rem; display:flex; align-items:center; gap:0.5rem; font-weight: 700;">
                <i class="ri-scales-3-line" style="color:#059669;"></i> Normas y Regulaciones
            </h3>
            <button type="button" onclick="closeNormaModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#64748b;">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <div class="audit-container" style="max-height:60vh; overflow-y:auto; padding-right:0.5rem; font-size:0.875rem; color:#334155; line-height:1.6;">
            <?= ($metaPrueba->normas ?? $metaPrueba->norma ?? null) ?>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.25rem; border-top:1px solid #e2e8f0; padding-top:0.85rem;">
            <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.825rem; color:#475569; cursor:pointer; user-select:none;">
                <input 
                    type="checkbox" 
                    id="chkTextoInadecuado2" 
                    value="1" 
                    <?= $textoInadecuado2 ? 'checked' : '' ?>
                    style="width:1rem; height:1rem; accent-color:#ef4444; cursor:pointer;"
                >
                <span style="font-weight: 500;">No me parece correcta esta redacción / contenido</span>
            </label>

            <div style="display:flex; gap:0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeNormaModal()" style="padding: 0.45rem 1rem; font-size: 0.825rem; background:#cbd5e1; color:#334155; border:none; border-radius:6px; cursor:pointer;">
                    Cancelar
                </button>
                <button type="button" id="btnGuardarFeedbackNorma" onclick="procesarGuardadoFeedbackNorma(<?= $pruebaId ?>)" class="btn btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.825rem; background:#0284c7; color:#ffffff; border:none; border-radius:6px; cursor:pointer; font-weight: 600;">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function procesarGuardadoFeedbackNorma(pruebaId) {
    if (!pruebaId || pruebaId <= 0) {
        alert('Error: El identificador de la prueba no es válido.');
        return;
    }

    const checkbox = document.getElementById('chkTextoInadecuado2');
    const btnGuardar = document.getElementById('btnGuardarFeedbackNorma');

    if (!checkbox) {
        console.error('No se encontró el elemento checkbox en el DOM.');
        return;
    }

    btnGuardar.disabled = true;
    btnGuardar.innerText = 'Guardando...';

    const formData = new FormData();
    formData.append('prueba_id', pruebaId);
    formData.append('texto_inadecuado2', checkbox.checked ? '1' : '0');

    fetch('guardar_feedback_norma.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error en el servidor: HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeNormaModal();
        } else {
            alert('Atención: ' + (data.error || 'No se pudo guardar la evaluación.'));
        }
    })
    .catch(error => {
        console.error('Error durante la persistencia del feedback:', error);
        alert('Error de conexión al guardar el registro.');
    })
    .finally(() => {
        btnGuardar.disabled = false;
        btnGuardar.innerText = 'Guardar';
    });
}
</script>

<!-- Modal de Instrucciones -->
<?php 
include 'AuditTextRenderer.php';
$textoInadecuado = (bool)($metaPrueba->texto_inadecuado ?? false);
?>

<div id="normaModal2" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#ffffff; padding:1.75rem; border-radius:10px; max-width:850px; width:92%; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid #e2e8f0; margin:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
            <h3 style="margin:0; color:#0f172a; font-size:1.05rem; display:flex; align-items:center; gap:0.5rem; font-weight: 700;">
                <i class="ri-book-2-line" style="color:#0284c7;"></i> Instrucciones
            </h3>
            <button type="button" onclick="closeNormaModal2()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#64748b;">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <div class="audit-container" style="max-height:60vh; overflow-y:auto; padding-right:0.5rem; font-size:0.875rem; color:#334155; line-height:1.6;">
            <?= AuditTextRenderer::render($metaPrueba->informacion ?? null) ?>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.25rem; border-top:1px solid #e2e8f0; padding-top:0.85rem;">
            <label style="display:flex; align-items:center; gap:0.4rem; font-size:0.825rem; color:#475569; cursor:pointer; user-select:none;">
                <input 
                    type="checkbox" 
                    id="chkTextoInadecuado" 
                    value="1" 
                    <?= $textoInadecuado ? 'checked' : '' ?>
                    style="width:1rem; height:1rem; accent-color:#ef4444; cursor:pointer;"
                >
                <span style="font-weight: 500;">No me parece correcta esta redacción / contenido</span>
            </label>

            <div style="display:flex; gap:0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeNormaModal2()" style="padding: 0.45rem 1rem; font-size: 0.825rem; background:#cbd5e1; color:#334155; border:none; border-radius:6px; cursor:pointer;">
                    Cancelar
                </button>
                <button type="button" id="btnGuardarFeedback" onclick="procesarGuardadoFeedback(<?= $pruebaId ?>)" class="btn btn-primary" style="padding: 0.45rem 1.15rem; font-size: 0.825rem; background:#0284c7; color:#ffffff; border:none; border-radius:6px; cursor:pointer; font-weight: 600;">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function procesarGuardadoFeedback(pruebaId) {
    if (!pruebaId || pruebaId <= 0) {
        alert('Error: El identificador de la prueba no es válido.');
        return;
    }

    const checkbox = document.getElementById('chkTextoInadecuado');
    const btnGuardar = document.getElementById('btnGuardarFeedback');

    if (!checkbox) {
        console.error('No se encontró el elemento checkbox en el DOM.');
        return;
    }

    btnGuardar.disabled = true;
    btnGuardar.innerText = 'Guardando...';

    const formData = new FormData();
    formData.append('prueba_id', pruebaId);
    formData.append('texto_inadecuado', checkbox.checked ? '1' : '0');

    fetch('update_feedback.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error en el servidor: HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            closeNormaModal2();
        } else {
            alert('Atención: ' + (data.error || 'No se pudo guardar la evaluación.'));
        }
    })
    .catch(error => {
        console.error('Error durante la persistencia del feedback:', error);
        alert('Error de conexión al guardar el registro.');
    })
    .finally(() => {
        btnGuardar.disabled = false;
        btnGuardar.innerText = 'Guardar';
    });
}
</script>

<?php 
include 'js-actividades.php'; 
?>