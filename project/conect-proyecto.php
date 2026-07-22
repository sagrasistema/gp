<?php

declare(strict_types=1);

// v/proyectos/conect-proyecto.php

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);

if (!$proyectoId) {
    die("Error: Proyecto no especificado.");
}

// 1. Cargar Cabecera del Proyecto con los campos exactos y prefijo audit_
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            c.name AS clientName, 
            c.rif AS clientRif,
            p.socio_lider AS socioLider,
            p.socio_calidad AS socioCalidad,
            p.fecha_remision AS fechaRemision,
            p.gerente AS gerente
        FROM audit_proyectos p 
        INNER JOIN audit_clientes c ON p.cliente_id = c.id 
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $proyectoId]);
    $projectData = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$projectData) {
        die("Error: El proyecto no existe.");
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
                INSERT INTO audit_proyecto_pruebas_ejecucion 
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
                ':proyecto_id' => $proyectoId, 
                ':prueba_id' => $pruebaId,
                ':ci' => $ci, 
                ':cg' => $cg, 
                ':sc' => $sc, 
                ':aa' => $aa, 
                ':estado' => $estado,
                ':ci_u' => $ci, 
                ':cg_u' => $cg, 
                ':sc_u' => $sc, 
                ':aa_u' => $aa, 
                ':estado_u' => $estado
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

// 3. Cargar mapeo de estados guardados de las pruebas de forma segura (Sentencia Preparada)
try {
    $stmtPruebas = $pdo->prepare("
        SELECT prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado 
        FROM audit_proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :proyecto_id
    ");
    $stmtPruebas->execute([':proyecto_id' => $proyectoId]);
    $pruebasEjecutadas = $stmtPruebas->fetchAll(PDO::FETCH_UNIQUE);
} catch (PDOException $e) {
    error_log("Error al cargar ejecución de pruebas: " . $e->getMessage());
    $pruebasEjecutadas = [];
}