<?php

declare(strict_types=1);

// v/proyectos/conect-proyecto.php

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);

if (!$proyectoId) {
    die("Error: Proyecto no especificado o ID inválido.");
}

// 1. Cargar Cabecera del Proyecto y Datos del Cliente como ARRAY asociativo para evitar errores de tipo stdClass
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
    $projectData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$projectData) {
        die("Error: El proyecto solicitado no existe.");
    }
} catch (PDOException $e) {
    error_log("Error crítico en cabecera de proyecto: " . $e->getMessage());
    die("Error crítico de base de datos al cargar el proyecto.");
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
    $stmtList = $pdo->prepare("
        SELECT p.id, p.nombre, p.orden, c.nombre as categoria_nombre 
        FROM audit_pruebas p
        INNER JOIN audit_categorias c ON p.categoria_id = c.id
        WHERE c.etapa_id = 4
        ORDER BY p.id ASC
    ");
    $stmtList->execute();
    $pruebasList = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar listado de pruebas: " . $e->getMessage());
    $pruebasList = [];
}

// 4. Cargar métricas de progreso de actividades por prueba para este proyecto
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
        WHERE c.etapa_id = 4
        GROUP BY p.id
    ");
    $stmtActProgress->execute([':proyecto_id' => $proyectoId]);
    $progresoActividades = $stmtActProgress->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al calcular progreso de actividades: " . $e->getMessage());
    $progresoActividades = [];
}

// 5. Inicializar variables auxiliares para evitar warnings en vistas (como $catalogosRiesgo)
$catalogosRiesgo = $catalogosRiesgo ?? [];

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
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// 7. PROCESAR CAMBIO DE ESTADO DEL PROYECTO (Solo usuarios 1 y 2)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statusId'])) {
    if ($currentUserId === 1 || $currentUserId === 2) {
        $newStatusId = (int)$_POST['statusId'];
        $targetProyectoId = filter_input(INPUT_POST, 'proyecto_id', FILTER_VALIDATE_INT) ?: ($proyectoId ?? 0);
        
        if (in_array($newStatusId, [1, 2], true) && $targetProyectoId > 0) {
            try {
                $stmtUpdateStatus = $pdo->prepare("
                    UPDATE proyectos 
                    SET statusId = :statusId, updated_at = NOW() 
                    WHERE id = :proyecto_id
                ");
                $stmtUpdateStatus->execute([
                    ':statusId'    => $newStatusId,
                    ':proyecto_id' => $targetProyectoId
                ]);

                header("Location: actividades.php?proyectoId={$targetProyectoId}&success=project_updated");
                exit();
            } catch (PDOException $e) {
                error_log("Error al actualizar el estado del proyecto: " . $e->getMessage());
                $errorMessage = "Error interno al intentar actualizar el estado del proyecto.";
            }
        }
    } else {
        http_response_code(403);
        die("Acceso denegado: No tienes permisos para modificar el estado de este proyecto.");
    }
}