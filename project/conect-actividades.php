<?php
// v/proyectos/actividades.php
declare(strict_types=1);

include '../main/config.php';

$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT);
$pruebaId = filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT);

if (!$proyectoId || !$pruebaId) {
    die("Error: Parámetros relacionales faltantes.");
}

/**
 * Convierte un número en formato venezolano (ej. "5.000.000,00") a un float estándar de PHP.
 */
function parseVenezuelanNumber(?string $value): float {
    if ($value === null || trim($value) === '') {
        return 0.00;
    }
    // Eliminar puntos de miles y espacios, luego reemplazar la coma decimal por punto
    $clean = str_replace(['.', ' '], ['', ''], $value);
    $clean = str_replace(',', '.', $clean);
    
    return filter_var($clean, FILTER_VALIDATE_FLOAT) !== false ? (float)$clean : 0.00;
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
        
        // Procesamiento específico para la Prueba 16 (Materialidad) en el Guardado General
        if ((int)$pruebaId === 16 && isset($_POST['materialidad']) && is_array($_POST['materialidad'])) {
            $mat = $_POST['materialidad'];
            
            // Sanitización y conversión correcta del formato venezolano a flotante
            $ben_m   = parseVenezuelanNumber($mat['beneficios_monto'] ?? null);
            $tram_p  = parseVenezuelanNumber($mat['tramo_porc'] ?? null);
            $tram_m  = parseVenezuelanNumber($mat['tramo_monto'] ?? null);
            $imp_ini = parseVenezuelanNumber($mat['importancia_inicial_monto'] ?? null);
            $rec_p   = parseVenezuelanNumber($mat['recorte_porc'] ?? null);
            $rec_m   = parseVenezuelanNumber($mat['recorte_monto'] ?? null);
            $imp_aju = parseVenezuelanNumber($mat['importancia_ajustada_monto'] ?? null);
            $min_p   = parseVenezuelanNumber($mat['minimis_porc'] ?? null);
            $min_m   = parseVenezuelanNumber($mat['minimis_monto'] ?? null);
            $min_s   = parseVenezuelanNumber($mat['minimis_secundario_monto'] ?? null);

            $stmtMatSave = $pdo->prepare("
                INSERT INTO proyecto_materialidad 
                (proyecto_id, prueba_id, beneficios_monto, tramo_porc, tramo_monto, importancia_inicial_monto, recorte_porc, recorte_monto, importancia_ajustada_monto, minimis_porc, minimis_monto, minimis_secundario_monto)
                VALUES (:proj, :pr, :ben_m, :tram_p, :tram_m, :imp_ini, :rec_p, :rec_m, :imp_aju, :min_p, :min_m, :min_s)
                ON DUPLICATE KEY UPDATE 
                    beneficios_monto = :ben_m_u, 
                    tramo_porc = :tram_p_u, 
                    tramo_monto = :tram_m_u, 
                    importancia_inicial_monto = :imp_ini_u, 
                    recorte_porc = :rec_p_u, 
                    recorte_monto = :rec_m_u, 
                    importancia_ajustada_monto = :imp_aju_u, 
                    minimis_porc = :min_p_u, 
                    minimis_monto = :min_m_u, 
                    minimis_secundario_monto = :min_s_u
            ");

            $dataMat = [
                ':proj'      => $proyectoId,
                ':pr'        => $pruebaId,
                ':ben_m'     => $ben_m,
                ':tram_p'    => $tram_p,
                ':tram_m'    => $tram_m,
                ':imp_ini'   => $imp_ini,
                ':rec_p'     => $rec_p,
                ':rec_m'     => $rec_m,
                ':imp_aju'   => $imp_aju,
                ':min_p'     => $min_p,
                ':min_m'     => $min_m,
                ':min_s'     => $min_s,
                
                ':ben_m_u'   => $ben_m,
                ':tram_p_u'  => $tram_p,
                ':tram_m_u'  => $tram_m,
                ':imp_ini_u' => $imp_ini,
                ':rec_p_u'   => $rec_p,
                ':rec_m_u'   => $rec_m,
                ':imp_aju_u' => $imp_aju,
                ':min_p_u'   => $min_p,
                ':min_m_u'   => $min_m,
                ':min_s_u'   => $min_s,
            ];

            $stmtMatSave->execute($dataMat);
        }

        // Cargar los datos de materialidad para la vista si es la prueba 16
        $materialidadData = null;
        if ((int)$pruebaId === 16) {
            $stmtMatGet = $pdo->prepare("SELECT * FROM proyecto_materialidad WHERE proyecto_id = :proj AND prueba_id = :pr");
            $stmtMatGet->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
            $materialidadData = $stmtMatGet->fetch(PDO::FETCH_OBJ);
        }

        if ($action === 'add_indicador_detalle') {
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

        $nuevoEstadoPrueba = trim($_POST['estado_prueba'] ?? $estadoActualPrueba);

        // VALIDACIÓN DE NEGOCIO: Si intenta colocar el estado como 'completado', verificar que todas las actividades estén finalizadas
        if ($nuevoEstadoPrueba === 'completado') {
            $stmtCheckAct = $pdo->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM audit_actividades WHERE prueba_id = :prId1) AS total_actividades,
                    (SELECT COUNT(*) FROM proyecto_actividades_ejecucion ae 
                     INNER JOIN audit_actividades a ON ae.actividad_id = a.id 
                     WHERE ae.proyecto_id = :projId1 AND a.prueba_id = :prId2 AND ae.completado = 1) AS completadas
            ");
            $stmtCheckAct->execute([
                ':prId1'   => $pruebaId,
                ':projId1' => $proyectoId,
                ':prId2'   => $pruebaId
            ]);
            $resAct = $stmtCheckAct->fetch(PDO::FETCH_OBJ);

            if ($resAct && (int)$resAct->total_actividades > 0 && (int)$resAct->completadas < (int)$resAct->total_actividades) {
                throw new Exception("Acción no permitida: No se puede cambiar el estado a 'Completado' porque existen actividades pendientes de finalizar.");
            }
        }

        // SINCRONIZACIÓN AUTOMÁTICA: Verificar existencia de registros en detalles para cada indicador
        $hasCI = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='CI'")->fetchColumn() > 0 ? 1 : 0;
        $hasCG = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='CG'")->fetchColumn() > 0 ? 1 : 0;
        $hasSC = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='SC'")->fetchColumn() > 0 ? 1 : 0;
        $hasAA = $pdo->query("SELECT COUNT(*) FROM proyecto_indicador_detalles WHERE proyecto_id=$proyectoId AND prueba_id=$pruebaId AND tipo_indicador='AA'")->fetchColumn() > 0 ? 1 : 0;

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
    } catch (Exception $e) {
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

$pageTitle = "Formulario de Actividades y Hallazgos";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>