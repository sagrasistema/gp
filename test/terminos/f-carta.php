<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../main/config.php';

/** @var PDO $pdo */

// -------------------------------------------------------------------------
// HELPERS NUMÉRICOS Y FORMATO VENEZOLANO
// -------------------------------------------------------------------------
function parseMontoVe(?string $valor): float
{
    if ($valor === null || trim($valor) === '') {
        return 0.00;
    }
    $limpio = str_replace('.', '', trim($valor));
    $limpio = str_replace(',', '.', $limpio);
    return (float)$limpio;
}

function formatMontoVe(float|string $valor): string
{
    return number_format((float)$valor, 2, ',', '.');
}

// -------------------------------------------------------------------------
// 1. VALIDACIÓN DE PARÁMETROS MAESTROS
// -------------------------------------------------------------------------
$terminoId = filter_input(INPUT_GET, 'terminoId', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'termino_id', FILTER_VALIDATE_INT);

if (!$terminoId || $terminoId <= 0) {
    http_response_code(400);
    die("Error: Identificador de Términos y Condiciones no especificado.");
}

$itemKey = 'carta_contratacion';
$uploadBaseDir = __DIR__ . '/../uploads/terminos/';

// Verificar estado máster
$stmtStatus = $pdo->prepare("SELECT statusId FROM terminos_condiciones WHERE id = :id");
$stmtStatus->execute([':id' => $terminoId]);
$isClosed = ((int)$stmtStatus->fetchColumn() === 2);

// -------------------------------------------------------------------------
// 2. CONSULTAR PRUEBAS DISPONIBLES EN BASE DE DATOS (Adaptado a pruebas_metodologicas)
// -------------------------------------------------------------------------
function obtenerPruebasPorRubros(PDO $pdo, array $rubrosDetectados): array
{
    // Mapeo adaptado a las columnas reales de la tabla 'pruebas_metodologicas':
    // - id: Identificador único de la prueba
    // - categoria_id: Representa la categoría/rubro
    // - nombre: Nombre de la prueba metodológica
    // - norma: Norma aplicable (opcional)
    // - informacion: Detalle/procedimiento de la prueba
    
    if (empty($rubrosDetectados)) {
        $stmt = $pdo->query("
            SELECT 
                id, 
                categoria_id AS rubro, 
                id AS codigo_prueba, 
                nombre AS nombre_prueba, 
                informacion 
            FROM pruebas_metodologicas 
            ORDER BY orden ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $inQuery = implode(',', array_fill(0, count($rubrosDetectados), '?'));
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            categoria_id AS rubro, 
            id AS codigo_prueba, 
            nombre AS nombre_prueba, 
            informacion 
        FROM pruebas_metodologicas 
        WHERE categoria_id IN ($inQuery) 
        ORDER BY orden ASC
    ");
    $stmt->execute($rubrosDetectados);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// -------------------------------------------------------------------------
// 3. PROCESAR GUARDADO DEL FORMULARIO (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_carta'])) {

    $frecuenciaCantidad = filter_input(INPUT_POST, 'frecuencia_cantidad', FILTER_VALIDATE_INT) ?: 1;
    $fechaSolicitud      = filter_input(INPUT_POST, 'fecha_solicitud', FILTER_DEFAULT) ?? '';
    $fechaRecibida       = filter_input(INPUT_POST, 'fecha_recibida', FILTER_DEFAULT) ?? '';
    $terminosAprobados   = filter_input(INPUT_POST, 'terminos_aprobados', FILTER_DEFAULT) ?? 'no';
    
    $rawHoras            = filter_input(INPUT_POST, 'horas_contempladas', FILTER_DEFAULT);
    $rawMonto            = filter_input(INPUT_POST, 'monto_propuesta', FILTER_DEFAULT);
    $horasContempladas   = parseMontoVe(is_string($rawHoras) ? $rawHoras : '0');
    $montoPropuesta      = parseMontoVe(is_string($rawMonto) ? $rawMonto : '0');

    $moneda              = filter_input(INPUT_POST, 'moneda', FILTER_DEFAULT) ?? 'USD';
    $observaciones       = filter_input(INPUT_POST, 'observaciones', FILTER_DEFAULT) ?? '';
    $situacionImportante = isset($_POST['situacion_importante']) ? 1 : 0;

    // Periodos por frecuencia
    $rawPeriodos = $_POST['periodos'] ?? [];
    $periodos    = [];
    if (is_array($rawPeriodos)) {
        foreach ($rawPeriodos as $idx => $val) {
            $periodos[(int)$idx] = htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
        }
    }

    // Pruebas Seleccionadas y sus Horas asignadas
    $pruebasSeleccionadas = $_POST['pruebas_seleccionadas'] ?? [];
    $horasPorPrueba       = $_POST['horas_prueba'] ?? [];
    $pruebasConfiguradas  = [];

    if (is_array($pruebasSeleccionadas)) {
        foreach ($pruebasSeleccionadas as $pruebaId) {
            $pId = (int)$pruebaId;
            $hrs = isset($horasPorPrueba[$pId]) ? parseMontoVe((string)$horasPorPrueba[$pId]) : 0.0;
            $pruebasConfiguradas[] = [
                'prueba_id'  => $pId,
                'horas_base' => $hrs,
                'horas_totales_frecuencia' => $hrs * $frecuenciaCantidad
            ];
        }
    }

    // Cargar datos previos para mantener archivos
    $stmtPrev = $pdo->prepare("SELECT datos_json FROM terminos_condiciones_items WHERE termino_id = :termino_id AND item_key = :item_key");
    $stmtPrev->execute([':termino_id' => $terminoId, ':item_key' => $itemKey]);
    $prevJson = $stmtPrev->fetchColumn();
    $prevData = $prevJson ? json_decode($prevJson, true) : [];

    $archivoCartaPath       = $prevData['archivo_carta'] ?? '';
    $archivoPresupuestoPath = $prevData['archivo_presupuesto'] ?? '';
    $balancePreliminarPath  = $prevData['balance_preliminar'] ?? '';
    $rubrosDetectados       = $prevData['rubros_detectados'] ?? [];

    if (!is_dir($uploadBaseDir) && !mkdir($uploadBaseDir, 0755, true) && !is_dir($uploadBaseDir)) {
        $errorMessage = "No se pudo crear el directorio de almacenamiento.";
    }

    // A) CARTA DE CONTRATACIÓN (PDF)
    if (!isset($errorMessage) && isset($_FILES['archivo_carta']) && $_FILES['archivo_carta']['error'] === UPLOAD_ERR_OK) {
        $newFileName = 'carta_' . $terminoId . '_' . bin2hex(random_bytes(8)) . '.pdf';
        if (move_uploaded_file($_FILES['archivo_carta']['tmp_name'], $uploadBaseDir . $newFileName)) {
            $archivoCartaPath = 'uploads/terminos/' . $newFileName;
        }
    }

    // B) PRESUPUESTO EXCEL
    if (!isset($errorMessage) && isset($_FILES['archivo_presupuesto']) && $_FILES['archivo_presupuesto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['archivo_presupuesto']['name'], PATHINFO_EXTENSION));
        $newFileName = 'presupuesto_' . $terminoId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (move_uploaded_file($_FILES['archivo_presupuesto']['tmp_name'], $uploadBaseDir . $newFileName)) {
            $archivoPresupuestoPath = 'uploads/terminos/' . $newFileName;
        }
    }

    // C) BALANCE PRELIMINAR (EXCEL / PDF)
    if (!isset($errorMessage) && isset($_FILES['balance_preliminar']) && $_FILES['balance_preliminar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['balance_preliminar']['name'], PATHINFO_EXTENSION));
        $newFileName = 'bal_preliminar_' . $terminoId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (move_uploaded_file($_FILES['balance_preliminar']['tmp_name'], $uploadBaseDir . $newFileName)) {
            $balancePreliminarPath = 'uploads/terminos/' . $newFileName;
            $rubrosDetectados = [1, 2, 3, 4, 5]; // Mapeo dinámico de IDs de categorías
        }
    }

    // PERSISTENCIA EN BD
    if (!isset($errorMessage)) {
        try {
            $pdo->beginTransaction();

            $payloadJson = json_encode([
                'frecuencia_cantidad'  => $frecuenciaCantidad,
                'periodos'             => $periodos,
                'balance_preliminar'   => $balancePreliminarPath,
                'rubros_detectados'    => $rubrosDetectados,
                'pruebas_configuradas' => $pruebasConfiguradas,
                'archivo_carta'        => $archivoCartaPath,
                'fecha_solicitud'      => trim((string)$fechaSolicitud),
                'fecha_recibida'       => trim((string)$fechaRecibida),
                'terminos_aprobados'   => trim((string)$terminosAprobados),
                'archivo_presupuesto'  => $archivoPresupuestoPath,
                'horas_contempladas'   => number_format($horasContempladas, 2, '.', ''),
                'monto_propuesta'      => number_format($montoPropuesta, 2, '.', ''),
                'moneda'               => trim((string)$moneda),
                'observaciones'        => trim((string)$observaciones),
                'situacion_importante' => $situacionImportante,
                'updated_at'           => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $stmtUpdateItem = $pdo->prepare("
                UPDATE terminos_condiciones_items 
                SET datos_json = :datos_json, estado = 'completado'
                WHERE termino_id = :termino_id AND item_key = :item_key
            ");
            $stmtUpdateItem->execute([
                ':datos_json' => $payloadJson,
                ':termino_id'  => $terminoId,
                ':item_key'    => $itemKey
            ]);

            $stmtCheckPending = $pdo->prepare("SELECT COUNT(*) FROM terminos_condiciones_items WHERE termino_id = :termino_id AND estado != 'completado'");
            $stmtCheckPending->execute([':termino_id' => $terminoId]);
            $pendingCount = (int)$stmtCheckPending->fetchColumn();

            $stmtUpdateMaster = $pdo->prepare("UPDATE terminos_condiciones SET estado = :estado WHERE id = :id");
            $stmtUpdateMaster->execute([
                ':estado' => ($pendingCount === 0) ? 'completado' : 'en_proceso',
                ':id'     => $terminoId
            ]);

            $pdo->commit();

            header("Location: responder-terminos.php?id={$terminoId}&success=carta_saved");
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al guardar carta de contratación: " . $e->getMessage());
            $errorMessage = "Error interno al procesar los datos.";
        }
    }
}

// -------------------------------------------------------------------------
// 4. PREPARACIÓN DE DATOS PARA LA VISTA
// -------------------------------------------------------------------------
$stmtHeader = $pdo->prepare("SELECT tc.*, c.name AS clientName FROM terminos_condiciones tc INNER JOIN clientes c ON tc.cliente_id = c.id WHERE tc.id = :id");
$stmtHeader->execute([':id' => $terminoId]);
$headerData = $stmtHeader->fetch(PDO::FETCH_OBJ);

$stmtItem = $pdo->prepare("SELECT * FROM terminos_condiciones_items WHERE termino_id = :termino_id AND item_key = :item_key");
$stmtItem->execute([':termino_id' => $terminoId, ':item_key' => $itemKey]);
$itemData = $stmtItem->fetch(PDO::FETCH_OBJ);

$savedData = ($itemData && !empty($itemData->datos_json)) ? json_decode($itemData->datos_json, true) : [];

$frecuenciaCantidadVal  = (int)($savedData['frecuencia_cantidad'] ?? 1);
$periodosVal            = $savedData['periodos'] ?? [];
$balancePreliminarVal   = (string)($savedData['balance_preliminar'] ?? '');
$rubrosDetectadosVal    = $savedData['rubros_detectados'] ?? [];
$pruebasConfiguradasVal = $savedData['pruebas_configuradas'] ?? [];

$archivoCartaVal        = (string)($savedData['archivo_carta'] ?? '');
$fechaSolicitudVal       = (string)($savedData['fecha_solicitud'] ?? '');
$fechaRecibidaVal        = (string)($savedData['fecha_recibida'] ?? '');
$terminosAprobadosVal    = (string)($savedData['terminos_aprobados'] ?? 'no');
$archivoPresupuestoVal  = (string)($savedData['archivo_presupuesto'] ?? '');
$horasContempladasVal    = (string)($savedData['horas_contempladas'] ?? '0.00');
$montoPropuestaVal       = (string)($savedData['monto_propuesta'] ?? '0.00');
$monedaVal               = (string)($savedData['moneda'] ?? 'USD');
$observacionesVal        = (string)($savedData['observaciones'] ?? '');
$situacionImportanteVal  = (int)($savedData['situacion_importante'] ?? 0);

$monedasSoportadas = ['USD', 'BS', 'EUR'];

// Cargar catálogo de pruebas según la nueva estructura de datos
$catalogoPruebas = obtenerPruebasPorRubros($pdo, $rubrosDetectadosVal);