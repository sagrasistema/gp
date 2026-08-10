<?php

declare(strict_types=1);

// v/proyectos/conect-proyecto3.php

// -------------------------------------------------------------------------
// 0. CAPTURA Y SANITIZACIÓN DE PARÁMETROS ENTRANTES
// -------------------------------------------------------------------------
$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);
$frecuenciaNum = filter_input(INPUT_GET, 'frecuencia', FILTER_VALIDATE_INT) ?: 1;

if (!$proyectoId || $proyectoId <= 0) {
    die("Error: Proyecto no especificado o ID inválido.");
}
// 1. Verificar estado del registro maestro
$stmtStatus = $pdo->prepare("SELECT statusId FROM proyectos WHERE id = :id");
$stmtStatus->execute([':id' => $proyectoId]);
$isClosed = ((int)$stmtStatus->fetchColumn() === 2);

if ($frecuenciaNum <= 0) {
    $frecuenciaNum = 1;
}

// -------------------------------------------------------------------------
// 1. CARGAR CABECERA DEL PROYECTO Y DATOS DEL CLIENTE
// -------------------------------------------------------------------------
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            c.name AS clientName, 
            c.rif AS clientRif
        FROM proyectos p 
        INNER JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $proyectoId]);
    $projectData = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$projectData) {
        die("Error: El proyecto solicitado no existe.");
    }

    // Determinar la cantidad total de frecuencias configuradas en el proyecto
    $totalFrecuencias = max(1, (int)($projectData->frecuencia_cantidad ?? 1));

    // Ajustar si se consulta una frecuencia mayor a la configurada
    if ($frecuenciaNum > $totalFrecuencias) {
        $frecuenciaNum = $totalFrecuencias;
    }

} catch (PDOException $e) {
    error_log("Error crítico en cabecera de proyecto: " . $e->getMessage());
    die("Error crítico de base de datos al cargar el proyecto.");
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
// 3. CARGAR LISTA DE PRUEBAS SELECCIONADAS DE LA FRECUENCIA ACTIVA (ETAPA 3)
// -------------------------------------------------------------------------
try {
    $stmtList = $pdo->prepare("
        SELECT p.id, p.nombre, p.orden, c.nombre as categoria_nombre 
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
    if ($estadoActual === 'completado' || $estadoActual === 'cerrado' || $estadoActual === 'revisado') {
        $completadasCount++;
    }
    if ($estadoActual === 'revisado' || $estadoActual === 'cerrado') {
        $completadasCountt++;
    }
}

$porcentajeProgreso = $totalPruebasCount > 0 ? round(($completadasCount / $totalPruebasCount) * 100) : 0;
$porcentajeProgresoo = $totalPruebasCount > 0 ? round(($completadasCountt / $totalPruebasCount) * 100) : 0;