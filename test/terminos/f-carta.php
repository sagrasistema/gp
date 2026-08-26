<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../main/config.php';

/** @var PDO $pdo */

// -------------------------------------------------------------------------
// HELPERS DE FORMATO Y SANITIZACIÓN NUMÉRICA
// -------------------------------------------------------------------------

/**
 * Convierte una cadena con formato numérico venezolano (ej. "1.234,56") 
 * a un float estándar de PHP/SQL (1234.56).
 */
function parseMontoVe(?string $valor): float
{
    if ($valor === null || trim($valor) === '') {
        return 0.00;
    }
    $limpio = str_replace('.', '', trim($valor));
    $limpio = str_replace(',', '.', $limpio);

    return (float)$limpio;
}

/**
 * Formatea un float/cadena a formato numérico venezolano (ej. 1234.56 -> "1.234,56").
 */
function formatMontoVe(float|string $valor): string
{
    $num = (float)$valor;
    return number_format($num, 2, ',', '.');
}

// -------------------------------------------------------------------------
// 2. SANITIZACIÓN Y VALIDACIÓN DE PARÁMETROS ENTRANTES
// -------------------------------------------------------------------------
$terminoId = filter_input(INPUT_GET, 'terminoId', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'termino_id', FILTER_VALIDATE_INT);

if (!$terminoId || $terminoId <= 0) {
    http_response_code(400);
    die("Error: Identificador de Términos y Condiciones no especificado o inválido.");
}

$itemKey = 'carta_contratacion';
$uploadBaseDir = __DIR__ . '/../uploads/terminos/';

// -------------------------------------------------------------------------
// 3. CARGAR DATOS EXISTENTES DE LA BASE DE DATOS
// -------------------------------------------------------------------------
try {
    $stmtStatus = $pdo->prepare("SELECT statusId FROM terminos_condiciones WHERE id = :id");
    $stmtStatus->execute([':id' => $terminoId]);
    $isClosed = ((int)$stmtStatus->fetchColumn() === 2);

    $stmtHeader = $pdo->prepare("
        SELECT tc.*, c.name AS clientName 
        FROM terminos_condiciones tc 
        INNER JOIN clientes c ON tc.cliente_id = c.id 
        WHERE tc.id = :id
    ");
    $stmtHeader->execute([':id' => $terminoId]);
    $headerData = $stmtHeader->fetch(PDO::FETCH_OBJ);

    if (!$headerData) {
        http_response_code(404);
        die("Error: El registro de Términos y Condiciones no existe.");
    }

    $stmtItem = $pdo->prepare("
        SELECT * 
        FROM terminos_condiciones_items 
        WHERE termino_id = :termino_id AND item_key = :item_key
    ");
    $stmtItem->execute([
        ':termino_id' => $terminoId,
        ':item_key'   => $itemKey
    ]);
    $itemData = $stmtItem->fetch(PDO::FETCH_OBJ);

    $savedData = [];
    if ($itemData && !empty($itemData->datos_json)) {
        $savedData = json_decode($itemData->datos_json, true) ?: [];
    }

} catch (PDOException $e) {
    error_log("Error al cargar carta de contratación: " . $e->getMessage());
    die("Error crítico al consultar la base de datos.");
}

// -------------------------------------------------------------------------
// 4. PROCESAR GUARDADO DEL FORMULARIO (POST)
// -------------------------------------------------------------------------
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_carta'])) {
    
    $fechaSolicitud      = filter_input(INPUT_POST, 'fecha_solicitud', FILTER_DEFAULT) ?? '';
    $fechaRecibida       = filter_input(INPUT_POST, 'fecha_recibida', FILTER_DEFAULT) ?? '';
    $terminosAprobados   = filter_input(INPUT_POST, 'terminos_aprobados', FILTER_DEFAULT) ?? 'no';
    
    $rawHoras            = filter_input(INPUT_POST, 'horas_contempladas', FILTER_DEFAULT);
    $rawMonto            = filter_input(INPUT_POST, 'monto_propuesta', FILTER_DEFAULT);
    $horasContempladas   = parseMontoVe(is_string($rawHoras) ? $rawHoras : '');
    $montoPropuesta      = parseMontoVe(is_string($rawMonto) ? $rawMonto : '');

    $moneda              = filter_input(INPUT_POST, 'moneda', FILTER_DEFAULT) ?? 'USD';
    $observaciones       = filter_input(INPUT_POST, 'observaciones', FILTER_DEFAULT) ?? '';
    $situacionImportante = isset($_POST['situacion_importante']) ? 1 : 0;

    $archivoCartaPath       = $savedData['archivo_carta'] ?? '';
    $archivoPresupuestoPath = $savedData['archivo_presupuesto'] ?? '';

    if (!is_dir($uploadBaseDir) && !mkdir($uploadBaseDir, 0755, true) && !is_dir($uploadBaseDir)) {
        $errorMessage = "No se pudo crear el directorio de almacenamiento.";
    }

    // A) PROCESAR CARTA DE CONTRATACIÓN (PDF)
    if (!$errorMessage && isset($_FILES['archivo_carta']) && $_FILES['archivo_carta']['error'] === UPLOAD_ERR_OK) {
        $fileTmp   = $_FILES['archivo_carta']['tmp_name'];
        $fileName  = $_FILES['archivo_carta']['name'];
        $fileExt   = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType  = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        if ($fileExt !== 'pdf' || $mimeType !== 'application/pdf') {
            $errorMessage = "La Carta de Contratación debe ser un archivo PDF válido.";
        } else {
            $newFileName = 'carta_' . $terminoId . '_' . bin2hex(random_bytes(8)) . '.pdf';
            $targetPath  = $uploadBaseDir . $newFileName;
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $archivoCartaPath = 'uploads/terminos/' . $newFileName;
            } else {
                $errorMessage = "Error al guardar el archivo PDF en el servidor.";
            }
        }
    }

    // B) PROCESAR PRESUPUESTO DEL PROYECTO (EXCEL)
    if (!$errorMessage && isset($_FILES['archivo_presupuesto']) && $_FILES['archivo_presupuesto']['error'] === UPLOAD_ERR_OK) {
        $fileTmp   = $_FILES['archivo_presupuesto']['tmp_name'];
        $fileName  = $_FILES['archivo_presupuesto']['name'];
        $fileExt   = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $finfo     = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType  = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        $allowedExcelMimes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream'
        ];

        if (!in_array($fileExt, ['xls', 'xlsx'], true) || !in_array($mimeType, $allowedExcelMimes, true)) {
            $errorMessage = "El presupuesto debe ser un archivo de Excel (.xls o .xlsx) válido.";
        } else {
            $newFileName = 'presupuesto_' . $terminoId . '_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
            $targetPath  = $uploadBaseDir . $newFileName;
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $archivoPresupuestoPath = 'uploads/terminos/' . $newFileName;
            } else {
                $errorMessage = "Error al guardar el archivo de Excel en el servidor.";
            }
        }
    }

    // ACTUALIZACIÓN DE BD CON TRANSACCIÓN
    if (!$errorMessage) {
        try {
            $pdo->beginTransaction();

            $payloadJson = json_encode([
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
                SET datos_json = :datos_json,
                    estado = 'completado'
                WHERE termino_id = :termino_id AND item_key = :item_key
            ");
            $stmtUpdateItem->execute([
                ':datos_json' => $payloadJson,
                ':termino_id'  => $terminoId,
                ':item_key'    => $itemKey
            ]);

            $stmtCheckPending = $pdo->prepare("
                SELECT COUNT(*) 
                FROM terminos_condiciones_items 
                WHERE termino_id = :termino_id AND estado != 'completado'
            ");
            $stmtCheckPending->execute([':termino_id' => $terminoId]);
            $pendingCount = (int)$stmtCheckPending->fetchColumn();

            $nuevoEstadoGlobal = ($pendingCount === 0) ? 'completado' : 'en_proceso';

            $stmtUpdateMaster = $pdo->prepare("
                UPDATE terminos_condiciones 
                SET estado = :estado 
                WHERE id = :id
            ");
            $stmtUpdateMaster->execute([
                ':estado' => $nuevoEstadoGlobal,
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
            $errorMessage = "Error interno al procesar el formulario.";
        }
    }
}

// Prepara las variables necesarias para ser consumidas por la vista
$archivoCartaVal       = (string)($savedData['archivo_carta'] ?? '');
$fechaSolicitudVal      = (string)($savedData['fecha_solicitud'] ?? '');
$fechaRecibidaVal       = (string)($savedData['fecha_recibida'] ?? '');
$terminosAprobadosVal   = (string)($savedData['terminos_aprobados'] ?? 'no');
$archivoPresupuestoVal = (string)($savedData['archivo_presupuesto'] ?? '');
$horasContempladasVal   = (string)($savedData['horas_contempladas'] ?? '0.00');
$montoPropuestaVal      = (string)($savedData['monto_propuesta'] ?? '0.00');
$monedaVal              = (string)($savedData['moneda'] ?? 'USD');
$observacionesVal       = (string)($savedData['observaciones'] ?? '');
$situacionImportanteVal = (int)($savedData['situacion_importante'] ?? 0);

$monedasSoportadas = ['USD', 'BS', 'EUR'];