<?php
// v/proyectos/actividades.php
include '../main/config.php';

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);
$pruebaId = filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT);

if (!$proyectoId || !$pruebaId) {
    die("Error: Parámetros relacionales faltantes.");
}

// 1. Cargar Cabecera del Proyecto y Datos del Cliente
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS clientName, c.rif AS clientRif
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

// 2. Cargar metadatos de la Prueba y su Estatus Actual
try {
    $stmtPrueba = $pdo->prepare("
        SELECT p.nombre, p.norma, c.nombre AS catNombre 
        FROM audit_pruebas p 
        INNER JOIN audit_categorias c ON p.categoria_id = c.id 
        WHERE p.id = :pId
    ");
    $stmtPrueba->execute([':pId' => $pruebaId]);
    $metaPrueba = $stmtPrueba->fetch(PDO::FETCH_OBJ);

    if (!$metaPrueba) {
        die("Error: La prueba especificada no existe.");
    }

    $stmtStatus = $pdo->prepare("
        SELECT estado FROM proyecto_pruebas_ejecucion 
        WHERE proyecto_id = :projId AND prueba_id = :prId
    ");
    $stmtStatus->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
    $estadoActualPrueba = $stmtStatus->fetchColumn() ?: 'en_proceso';

} catch (PDOException $e) {
    die("Error al cargar metadatos: " . $e->getMessage());
}

// 3. Procesamiento POST (Guardado de actividades, estatus y sincronización de indicadores)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? 'save_all';

    try {
        $pdo->beginTransaction();
        if ((int)$pruebaId === 11) {
            if ($action === 'add_analitica_item') {
                $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                $tipoRubro = trim($_POST['tipo_rubro'] ?? '');
                $saldoActual = filter_input(INPUT_POST, 'saldo_actual', FILTER_VALIDATE_FLOAT) ?: 0.00;
                $saldoAnterior = filter_input(INPUT_POST, 'saldo_anterior', FILTER_VALIDATE_FLOAT) ?: 0.00;
                $observaciones = trim($_POST['observaciones'] ?? '');

                if (in_array($tipo, ['activo', 'pasivo', 'patrimonio'], true) && !empty($tipoRubro)) {
                    $stmtIns = $pdo->prepare("
                        INSERT INTO proyecto_revision_analitica 
                        (proyecto_id, prueba_id, tipo, tipo_rubro, saldo_actual, saldo_anterior, observaciones)
                        VALUES (:proj, :pr, :tipo, :rubro, :actual, :anterior, :obs)
                    ");
                    $stmtIns->execute([
                        ':proj'     => $proyectoId,
                        ':pr'       => $pruebaId,
                        ':tipo'     => $tipo,
                        ':rubro'    => $tipoRubro,
                        ':actual'   => $saldoActual,
                        ':anterior' => $saldoAnterior,
                        ':obs'      => $observaciones
                    ]);
                }
            } elseif ($action === 'delete_analitica_item') {
                $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
                if ($itemId) {
                    $stmtDel = $pdo->prepare("DELETE FROM proyecto_revision_analitica WHERE id = :id AND proyecto_id = :proj AND prueba_id = :pr");
                    $stmtDel->execute([':id' => $itemId, ':proj' => $proyectoId, ':pr' => $pruebaId]);
                }
            }
        }

        if ($action === 'add_indicador_detalle') {
            // Guardar un punto de control / detalle para un indicador específico
            $tipoInd = filter_input(INPUT_POST, 'tipo_indicador', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $rubro = trim($_POST['rubro'] ?? '');
            $titulo = trim($_POST['titulo'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $recomendacion = trim($_POST['recomendacion'] ?? '');

            if (in_array($tipoInd, ['CI', 'CG', 'SC', 'AA']) && !empty($titulo)) {
                $stmtIns = $pdo->prepare("
                    INSERT INTO proyecto_indicador_detalles (proyecto_id, prueba_id, tipo_indicador, rubro, titulo, descripcion, recomendacion)
                    VALUES (:proj, :pr, :tipo, :rubro, :titulo, :desc, :rec)
                ");
                $stmtIns->execute([
                    ':proj' => $proyectoId, ':pr' => $pruebaId, ':tipo' => $tipoInd,
                    ':rubro' => $rubro, ':titulo' => $titulo, ':desc' => $descripcion, ':rec' => $recomendacion
                ]);
            }
        } elseif ($action === 'delete_indicador_detalle') {
            // Eliminar un detalle de indicador
            $detalleId = filter_input(INPUT_POST, 'detalle_id', FILTER_VALIDATE_INT);
            if ($detalleId) {
                $stmtDel = $pdo->prepare("DELETE FROM proyecto_indicador_detalles WHERE id = :id AND proyecto_id = :proj AND prueba_id = :pr");
                $stmtDel->execute([':id' => $detalleId, ':proj' => $proyectoId, ':pr' => $pruebaId]);
            }
        } else {
            // Guardado General (Actividades + Estatus de Prueba)
            if (isset($_POST['actividades_data']) && is_array($_POST['actividades_data'])) {
                $stmtSave = $pdo->prepare("
                    INSERT INTO proyecto_actividades_ejecucion (proyecto_id, actividad_id, contenido_llenado, completado)
                    VALUES (:proyecto_id, :actividad_id, :contenido, :completado)
                    ON DUPLICATE KEY UPDATE contenido_llenado = :contenido_u, completado = :completado_u
                ");

                foreach ($_POST['actividades_data'] as $actId => $v) {
                    $contenido = trim($v['contenido'] ?? '');
                    $completado = isset($v['completado']) ? 1 : 0;

                    $stmtSave->execute([
                        ':proyecto_id'  => $proyectoId,
                        ':actividad_id' => $actId,
                        ':contenido'    => $contenido !== '' ? $contenido : null,
                        ':completado'   => $completado,
                        ':contenido_u'  => $contenido !== '' ? $contenido : null,
                        ':completado_u' => $completado
                    ]);
                }
            }
        }

        // SINCRONIZACIÓN AUTOMÁTICA: Verificar existencia de registros en detalles para cada indicador
        $hasCI = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='CI'")->fetchColumn() > 0 ? 1 : 0;
        $hasCG = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='CG'")->fetchColumn() > 0 ? 1 : 0;
        $hasSC = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='SC'")->fetchColumn() > 0 ? 1 : 0;
        $hasAA = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='AA'")->fetchColumn() > 0 ? 1 : 0;

        $nuevoEstadoPrueba = trim($_POST['estado_prueba'] ?? $estadoActualPrueba);

        // Actualizar o insertar en proyecto_pruebas_ejecucion
        $stmtTestSave = $pdo->prepare("
            INSERT INTO proyecto_pruebas_ejecucion 
            (proyecto_id, prueba_id, indicador_ci, indicador_cg, indicador_sc, indicador_aa, estado)
            VALUES (:proyecto_id, :prueba_id, :ci, :cg, :sc, :aa, :estado)
            ON DUPLICATE KEY UPDATE 
                indicador_ci = :ci_u, indicador_cg = :cg_u, indicador_sc = :sc_u, indicador_aa = :aa_u, estado = :estado_u
        ");
        $stmtTestSave->execute([
            ':proyecto_id' => $proyectoId, ':prueba_id' => $pruebaId,
            ':ci' => $hasCI, ':cg' => $hasCG, ':sc' => $hasSC, ':aa' => $hasAA, ':estado' => $nuevoEstadoPrueba,
            ':ci_u' => $hasCI, ':cg_u' => $hasCG, ':sc_u' => $hasSC, ':aa_u' => $hasAA, ':estado_u' => $nuevoEstadoPrueba
        ]);

        $pdo->commit();
        header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&success=1");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error al procesar la operación: " . $e->getMessage());
    }
}
// 4. Recuperar Catálogo de Actividades y Detalles de los Indicadores
$sqlActividades = "
    SELECT a.id, a.descripcion, a.orden, COALESCE(ae.contenido_llenado, '') AS respuesta, COALESCE(ae.completado, 0) AS is_ok
    FROM audit_actividades a
    LEFT JOIN proyecto_actividades_ejecucion ae ON ae.actividad_id = a.id AND ae.proyecto_id = :projId
    WHERE a.prueba_id = :prId ORDER BY a.orden ASC";
$stmtA = $pdo->prepare($sqlActividades);
$stmtA->execute([':projId' => $proyectoId, ':prId' => $pruebaId]);
$listaActividades = $stmtA->fetchAll(PDO::FETCH_OBJ);

// Cargar detalles de indicadores agrupados por tipo
$stmtIndDetalles = $pdo->prepare("SELECT * FROM proyecto_indicador_detalles WHERE proyecto_id = :proj AND prueba_id = :pr ORDER BY id DESC");
$stmtIndDetalles->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
$allDetalles = $stmtIndDetalles->fetchAll(PDO::FETCH_OBJ);

$detallesPorTipo = ['CI' => [], 'CG' => [], 'SC' => [], 'AA' => []];
foreach ($allDetalles as $det) {
    $detallesPorTipo[$det->tipo_indicador][] = $det;
}
// Cargar partidas analíticas agrupadas para la vista (Prueba 11)
$analiticaItems = ['activo' => [], 'pasivo' => [], 'patrimonio' => []];
if ((int)$pruebaId === 11) {
    try {
        $stmtAnalitica = $pdo->prepare("
            SELECT * FROM proyecto_revision_analitica 
            WHERE proyecto_id = :proj AND prueba_id = :pr 
            ORDER BY id ASC
        ");
        $stmtAnalitica->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
        while ($row = $stmtAnalitica->fetch(PDO::FETCH_OBJ)) {
            $analiticaItems[$row->tipo][] = $row;
        }
    } catch (PDOException $e) {
        error_log("Error al cargar partidas analíticas: " . $e->getMessage());
    }
}

$pageTitle = "Formulario de Actividades y Hallazgos";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>