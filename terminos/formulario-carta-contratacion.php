<?php

declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// v/terminos/formulario-carta-contratacion.php
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
    // Elimina puntos de millar y reemplaza coma decimal por punto
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
// 1. SANITIZACIÓN Y VALIDACIÓN DE PARÁMETROS ENTRANTES
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
// 2. CARGAR DATOS EXISTENTES
// -------------------------------------------------------------------------
try {
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
// 3. PROCESAR GUARDADO DEL FORMULARIO (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_carta'])) {
    
    $fechaSolicitud      = filter_input(INPUT_POST, 'fecha_solicitud', FILTER_DEFAULT) ?? '';
    $fechaRecibida       = filter_input(INPUT_POST, 'fecha_recibida', FILTER_DEFAULT) ?? '';
    $terminosAprobados   = filter_input(INPUT_POST, 'terminos_aprobados', FILTER_DEFAULT) ?? 'no';
    
    // Parsear montos formateados en estilo venezolano (ej. "1.500,00" -> 1500.00)
    $rawHoras            = filter_input(INPUT_POST, 'horas_contempladas', FILTER_DEFAULT);
    $rawMonto            = filter_input(INPUT_POST, 'monto_propuesta', FILTER_DEFAULT);
    $horasContempladas   = parseMontoVe(is_string($rawHoras) ? $rawHoras : '');
    $montoPropuesta      = parseMontoVe(is_string($rawMonto) ? $rawMonto : '');

    $moneda              = filter_input(INPUT_POST, 'moneda', FILTER_DEFAULT) ?? 'USD';
    $observaciones       = filter_input(INPUT_POST, 'observaciones', FILTER_DEFAULT) ?? '';
    $situacionImportante = isset($_POST['situacion_importante']) ? 1 : 0;

    // Preservar rutas de archivos anteriores si no se vuelven a subir
    $archivoCartaPath       = $savedData['archivo_carta'] ?? '';
    $archivoPresupuestoPath = $savedData['archivo_presupuesto'] ?? '';

    // Crear directorio de archivos si no existe
    if (!is_dir($uploadBaseDir) && !mkdir($uploadBaseDir, 0755, true) && !is_dir($uploadBaseDir)) {
        $errorMessage = "No se pudo crear el directorio de almacenamiento.";
    }

    // A) PROCESAR CARTA DE CONTRATACIÓN (PDF)
    if (!isset($errorMessage) && isset($_FILES['archivo_carta']) && $_FILES['archivo_carta']['error'] === UPLOAD_ERR_OK) {
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
    if (!isset($errorMessage) && isset($_FILES['archivo_presupuesto']) && $_FILES['archivo_presupuesto']['error'] === UPLOAD_ERR_OK) {
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

    // ACTUALIZACIÓN DE BD
    if (!isset($errorMessage)) {
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

            // Evaluar estado global
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

// Cargar variables formateadas para la vista
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

$pageTitle = "Carta de Contratación - Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<style>
    .card-panel {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .card-panel-header {
        background: #5b7083;
        color: #ffffff;
        padding: 0.65rem 1rem;
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .card-panel-body {
        padding: 1.25rem;
    }
    .file-input-wrapper {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-bottom: 1px solid #cbd5e1;
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
    }
    .btn-attach {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #f8fafc;
        color: #2563eb;
        cursor: pointer;
    }
    .btn-action-view {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: #2563eb;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 4px;
        text-decoration: none;
        transition: background 0.2s;
    }
    .btn-action-view:hover {
        background: #dbeafe;
    }
    .file-custom-label {
        font-size: 0.875rem;
        color: #64748b;
        cursor: pointer;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .form-control-line {
        width: 100%;
        border: none;
        border-bottom: 1px solid #cbd5e1;
        padding: 0.4rem 0;
        font-size: 0.9rem;
        outline: none;
        background: transparent;
    }
    .form-control-line:focus {
        border-bottom-color: #2563eb;
    }
</style>

<!--<div class="view-container" style="padding: 2rem; max-width: 1050px; margin: 0 auto;">-->
    <div class="view-container" >
    <!-- CABECERA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0;">
                Carta de Contratación
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Cliente: <strong><?= htmlspecialchars($headerData->clientName, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
        <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; text-decoration: none; background: #e2e8f0; color: #334155; border-radius: 6px; font-weight: 600;">
            <i class="ri-arrow-left-line"></i> Volver a Actividades
        </a>
    </div>

    <?php if (isset($errorMessage)): ?>
        <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 1.5rem;">
            <i class="ri-error-warning-fill"></i> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="formulario-carta-contratacion.php?terminoId=<?= $terminoId ?>" enctype="multipart/form-data">
        <input type="hidden" name="action_save_carta" value="1">
        <input type="hidden" name="termino_id" value="<?= $terminoId ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">

            <!-- COLUMNA IZQUIERDA: CARTA DE CONTRATACIÓN -->
            <div class="card-panel">
                <div class="card-panel-header">Carta de Contratación</div>
                <div class="card-panel-body">
                    
                    <!-- ADJUNTAR CARTA Y BOTÓN DESCARGAR/VER -->
                    <div class="file-input-wrapper">
                        <label for="input_carta" class="btn-attach" title="Adjuntar Archivo PDF">
                            <i class="ri-attachment-line" style="font-size: 1.2rem;"></i>
                        </label>
                        <input type="file" id="input_carta" name="archivo_carta" accept=".pdf,application/pdf" style="display: none;" onchange="updateFileName(this, 'label_carta')">
                        <span id="label_carta" class="file-custom-label" onclick="document.getElementById('input_carta').click();">
                            <?= !empty($archivoCartaVal) ? '📄 ' . basename($archivoCartaVal) : 'Adjuntar o cambiar Carta de Contratación (Sólo PDF)' ?>
                        </span>

                        <!-- BOTÓN DESCARGAR/VER PDF -->
                        <?php if (!empty($archivoCartaVal)): ?>
                            <a href="../<?= htmlspecialchars($archivoCartaVal, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-action-view" title="Ver / Descargar Carta de Contratación">
                                <i class="ri-external-link-line"></i> Ver PDF
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- FECHAS -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.25rem;">Fecha Solicitud de la Carta :</label>
                            <input type="date" name="fecha_solicitud" class="form-control-line" value="<?= htmlspecialchars($fechaSolicitudVal, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.25rem;">Recibida:</label>
                            <input type="date" name="fecha_recibida" class="form-control-line" value="<?= htmlspecialchars($fechaRecibidaVal, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <!-- APROBACIÓN TÉRMINOS -->
                    <div style="margin-top: 2rem;">
                        <span style="font-size: 0.875rem; color: #334155; font-weight: 500;">Los términos del contrato fueron aprobados?</span>
                        <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem; align-items: center;">
                            <label style="font-size: 0.875rem; color: #475569; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                <input type="radio" name="terminos_aprobados" value="no" <?= $terminosAprobadosVal === 'no' ? 'checked' : '' ?>> No
                            </label>
                            <label style="font-size: 0.875rem; color: #475569; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                <input type="radio" name="terminos_aprobados" value="si" <?= $terminosAprobadosVal === 'si' ? 'checked' : '' ?>> Si
                            </label>
                        </div>
                    </div>

                </div>
            </div>

            <!-- COLUMNA DERECHA: PRESUPUESTO DEL PROYECTO -->
            <div class="card-panel">
                <div class="card-panel-header">Presupuesto del Proyecto</div>
                <div class="card-panel-body">

                    <!-- ADJUNTAR PRESUPUESTO Y BOTÓN DESCARGAR EXCEL -->
                    <div class="file-input-wrapper">
                        <label for="input_presupuesto" class="btn-attach" title="Adjuntar Excel de Presupuesto">
                            <i class="ri-attachment-line" style="font-size: 1.2rem;"></i>
                        </label>
                        <input type="file" id="input_presupuesto" name="archivo_presupuesto" accept=".xls,.xlsx" style="display: none;" onchange="updateFileName(this, 'label_presupuesto')">
                        <span id="label_presupuesto" class="file-custom-label" onclick="document.getElementById('input_presupuesto').click();">
                            <?= !empty($archivoPresupuestoVal) ? '📊 ' . basename($archivoPresupuestoVal) : 'Adjuntar o Cambiar Presupuesto del Proyecto (Sólo Excel)' ?>
                        </span>

                        <!-- BOTÓN DESCARGAR EXCEL -->
                        <?php if (!empty($archivoPresupuestoVal)): ?>
                            <a href="../<?= htmlspecialchars($archivoPresupuestoVal, ENT_QUOTES, 'UTF-8') ?>" download class="btn-action-view" title="Descargar Presupuesto Excel">
                                <i class="ri-download-line"></i> Descargar Excel
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- METRICAS DE PRESUPUESTO CON FORMATO VENEZOLANO -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #475569; margin-bottom: 0.25rem;">Horas contempladas</label>
                            <input type="text" name="horas_contempladas" class="form-control-line" style="text-align: right;" value="<?= htmlspecialchars(formatMontoVe($horasContempladasVal), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" onblur="formatInputVe(this)">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #475569; margin-bottom: 0.25rem;">Monto Total de la Propuesta</label>
                            <input type="text" name="monto_propuesta" class="form-control-line" style="text-align: right;" value="<?= htmlspecialchars(formatMontoVe($montoPropuestaVal), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" onblur="formatInputVe(this)">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #475569; margin-bottom: 0.25rem;">Moneda asociada</label>
                            <select name="moneda" class="form-control-line">
                                <?php foreach ($monedasSoportadas as $m): ?>
                                    <option value="<?= $m ?>" <?= $m === $monedaVal ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- OBSERVACIONES DE LA NEGOCIACIÓN -->
        <div class="card-panel">
            <div class="card-panel-header" style="background: #f1f5f9; color: #334155; border-bottom: 1px solid #cbd5e1;">
                <i class="ri-list-check-2" style="color: #16a34a;"></i> Observaciones durante la negociación
            </div>
            <div class="card-panel-body" style="padding: 0;">
                <textarea name="observaciones" rows="4" placeholder="Ingrese las observaciones o notas relevantes durante el proceso de negociación..." style="width: 100%; border: none; padding: 1rem; font-size: 0.9rem; resize: vertical; outline: none; background: #fafafa;"><?= htmlspecialchars($observacionesVal, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>

        <!-- SITUACIÓN IMPORTANTE -->
        <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" id="situacion_importante" name="situacion_importante" value="1" <?= $situacionImportanteVal === 1 ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
            <label for="situacion_importante" style="font-size: 0.875rem; color: #334155; cursor: pointer;">
                Haga click en el recuadro si considera haber hallado una <strong>"Situación Importante"</strong>
            </label>
        </div>

        <!-- BOTONES -->
        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
            <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.6rem 1.25rem; background: #e2e8f0; color: #334155; border-radius: 6px; text-decoration: none; font-weight: 600;">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ri-save-line"></i> Guardar Cambios
            </button>
        </div>

    </form>
</div>

<script>
function updateFileName(input, labelId) {
    const label = document.getElementById(labelId);
    if (input.files && input.files.length > 0) {
        label.textContent = "📄 " + input.files[0].name;
        label.style.color = "#0f172a";
        label.style.fontWeight = "600";
    }
}

// Formateador dinámico para mejorar la UX del usuario al escribir montos
function formatInputVe(input) {
    let val = input.value.trim();
    if (!val) return;

    // Normalizar si introducen punto como decimal
    if (val.includes('.') && !val.includes(',')) {
        val = val.replace('.', ',');
    }

    let parts = val.split(',');
    let integerPart = parts[0].replace(/\D/g, ''); // Solo dígitos
    let decimalPart = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '00';

    if (decimalPart.length === 1) decimalPart += '0';

    if (integerPart === '') integerPart = '0';

    // Separador de miles con punto
    integerPart = parseInt(integerPart, 10).toLocaleString('de-DE');

    input.value = integerPart + ',' + decimalPart;
}
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>