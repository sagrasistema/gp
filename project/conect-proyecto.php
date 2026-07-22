<?php

declare(strict_types=1);

// v/proyectos/conect-proyecto.php

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);

if (!$proyectoId) {
    die("Error: Proyecto no especificado o ID inválido.");
}

// 1. Cargar Cabecera del Proyecto y Datos del Cliente
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
} catch (PDOException $e) {
    error_log("Error crítico en cabecera de proyecto: " . $e->getMessage());
    die("Error crítico de base de datos al cargar el proyecto.");
}
// 2. Procesar Actualización de Indicadores y Estados de la Prueba (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_prueba') {
    $pruebaId = filter_input(INPUT_POST, 'prueba_id', FILTER_VALIDATE_INT);
    $estado = trim($_POST['estado'] ?? 'en_proceso');
    
    $ci = isset($_POST['ci']) ? 1 : 0;
    $cg = isset($_POST['cg']) ? 1 : 0;
    $sc = isset($_POST['sc']) ? 1 : 0;
    $aa = isset($_POST['aa']) ? 1 : 0;

    if ($pruebaId) {
        try {
            $pdo->beginTransaction();
            
            $stmtUpdate = $pdo->prepare("
                INSERT INTO proyecto_pruebas_ejecucion 
                (proyecto_id, prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado)
                VALUES (:proyecto_id, :prueba_id, :ci, :cg, :sc, :aa, :estado)
                ON DUPLICATE KEY UPDATE 
                    indicador_ci = :ci_u, 
                    indicador_cg = :cg_u, 
                    indicador_sc = :sc_u, 
                    indicador_aa = :aa_u, 
                    estado = :estado_u
            ");
            
            $stmtUpdate->execute([
                ':proyecto_id' => $proyectoId, ':prueba_id' => $pruebaId,
                ':ci' => $ci, ':cg' => $cg, ':sc' => $sc, ':aa' => $aa, ':estado' => $estado,
                ':ci_u' => $ci, ':cg_u' => $cg, ':sc_u' => $sc, ':aa_u' => $aa, ':estado_u' => $estado
            ]);
            
            $pdo->commit();
            
            header("Location: responder.php?proyectoId=" . $proyectoId . "&success=1");
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al actualizar prueba: " . $e->getMessage());
            die("Error al guardar estado de la prueba.");
        }
    }
}

// 3. Cargar mapeo de estados guardados de las pruebas
try {
    $stmtPruebas = $pdo->prepare("
        SELECT prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado 
        FROM proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :proyecto_id
    ");
    $stmtPruebas->execute([':proyecto_id' => $proyectoId]);
    $pruebasEjecutadas = $stmtPruebas->fetchAll(PDO::FETCH_UNIQUE);
} catch (PDOException $e) {
    error_log("Error al cargar ejecución de pruebas: " . $e->getMessage());
    $pruebasEjecutadas = [];
}

// 4. Cargar lista completa de pruebas para la Fase de Planificación (Etapa 1) con sus categorías
try {
    $stmtList = $pdo->prepare("
        SELECT p.id, p.nombre, p.orden, c.nombre as categoria_nombre 
        FROM audit_pruebas p
        INNER JOIN audit_categorias c ON p.categoria_id = c.id
        WHERE c.etapa_id = 1
        ORDER BY p.id ASC
    ");
    $stmtList->execute();
    $pruebasList = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar listado de pruebas: " . $e->getMessage());
    $pruebasList = [];
}

// 5. Cargar métricas de progreso de actividades por prueba para este proyecto
try {
    $stmtActProgress = $pdo->prepare("
        SELECT 
            p.id AS prueba_id,
            COUNT(a.id) AS total_actividades,
            SUM(CASE WHEN ae.completado = 1 THEN 1 ELSE 0 END) AS actividades_completadas
        FROM audit_pruebas p
        INNER JOIN audit_categorias c ON p.categoria_id = c.id
        LEFT JOIN audit_actividades a ON a.prueba_id = p.id
        LEFT JOIN proyecto_actividades_ejecucion ae ON ae.actividad_id = a.id AND ae.proyecto_id = :proyecto_id
        WHERE c.etapa_id = 1
        GROUP BY p.id
    ");
    $stmtActProgress->execute([':proyecto_id' => $proyectoId]);
    // Indexamos por prueba_id para acceso O(1) en la vista
    $progresoActividades = $stmtActProgress->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al calcular progreso de actividades: " . $e->getMessage());
    $progresoActividades = [];
}
// 6. Calcular el porcentaje global de avance de la fase
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