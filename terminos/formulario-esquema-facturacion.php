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
// v/terminos/formulario-esquema-facturacion.php
require_once '../main/config.php';

/** @var PDO $pdo */

// -------------------------------------------------------------------------
// HELPERS DE FORMATO Y SANITIZACIÓN NUMÉRICA
// -------------------------------------------------------------------------

/**
 * Convierte cadena en formato venezolano ("4.000,00") a float (4000.00)
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
 * Formatea un float/string a formato venezolano (4000.00 -> "4.000,00")
 */
function formatMontoVe(float|string $valor): string
{
    return number_format((float)$valor, 2, ',', '.');
}

/**
 * Divide el monto total equitativamente entre N cuotas ajustando centavos
 */
function calcularCuotas(float $montoTotal, int $cantidad): array
{
    if ($cantidad <= 0) {
        return [];
    }

    $montoBase = floor(($montoTotal / $cantidad) * 100) / 100;
    $diferencia = round($montoTotal - ($montoBase * $cantidad), 2);

    $cuotas = [];
    for ($i = 1; $i <= $cantidad; $i++) {
        $montoCuota = $montoBase;
        if ($i === $cantidad) {
            $montoCuota += $diferencia;
        }
        $cuotas[] = $montoCuota;
    }

    return $cuotas;
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

$itemKey = 'esquema_facturacion';

// -------------------------------------------------------------------------
// 2. CARGAR DATOS DE CABECERA Y CARTA DE CONTRATACIÓN
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

    // Obtener Monto Propuesta desde Carta de Contratación
    $stmtCarta = $pdo->prepare("
        SELECT datos_json 
        FROM terminos_condiciones_items 
        WHERE termino_id = :termino_id AND item_key = 'carta_contratacion'
    ");
    $stmtCarta->execute([':termino_id' => $terminoId]);
    $cartaRaw = $stmtCarta->fetchColumn();
    $cartaJson = $cartaRaw ? (json_decode($cartaRaw, true) ?: []) : [];

    $montoPropuestaCarta = (float)($cartaJson['monto_propuesta'] ?? 0.00);
    $monedaCarta          = (string)($cartaJson['moneda'] ?? 'USD');

    // Cargar item actual de Esquema de Facturación
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
    error_log("Error al cargar esquema de facturación: " . $e->getMessage());
    die("Error crítico al consultar la base de datos.");
}

// -------------------------------------------------------------------------
// 3. PROCESAR ACCIONES (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_esquema'])) {

    $fechaEstimadaBase = filter_input(INPUT_POST, 'fecha_estimada_base', FILTER_DEFAULT) ?: date('Y-m-d');
    $cantidadFacturas  = filter_input(INPUT_POST, 'cantidad_facturas', FILTER_VALIDATE_INT) ?: 1;
    $rawMontoFactura   = filter_input(INPUT_POST, 'monto_factura', FILTER_DEFAULT);
    $montoFacturaTotal = parseMontoVe(is_string($rawMontoFactura) ? $rawMontoFactura : '');
    $moneda            = filter_input(INPUT_POST, 'moneda', FILTER_DEFAULT) ?? $monedaCarta;

    $facturasList = [];

    if (isset($_POST['btn_generar_cuotas'])) {
        $cuotasCalculadas = calcularCuotas($montoFacturaTotal, $cantidadFacturas);
        
        foreach ($cuotasCalculadas as $idx => $montoCuota) {
            $fechaCuota = date('Y-m-d', strtotime("+$idx month", strtotime($fechaEstimadaBase)));
            $facturasList[] = [
                'numero' => $idx + 1,
                'fecha'  => $fechaCuota,
                'monto'  => number_format($montoCuota, 2, '.', '')
            ];
        }
    } else {
        $fechasPOST  = $_POST['factura_fecha'] ?? [];
        $montosPOST  = $_POST['factura_monto'] ?? [];

        if (is_array($fechasPOST) && count($fechasPOST) > 0) {
            foreach ($fechasPOST as $index => $fFecha) {
                $fMontoRaw = $montosPOST[$index] ?? '0,00';
                $facturasList[] = [
                    'numero' => $index + 1,
                    'fecha'  => trim((string)$fFecha),
                    'monto'  => number_format(parseMontoVe((string)$fMontoRaw), 2, '.', '')
                ];
            }
        }
    }

    try {
        $pdo->beginTransaction();

        $payloadJson = json_encode([
            'fecha_estimada_base' => $fechaEstimadaBase,
            'cantidad_facturas'   => $cantidadFacturas,
            'monto_factura'       => number_format($montoFacturaTotal, 2, '.', ''),
            'moneda'              => $moneda,
            'facturas'            => $facturasList,
            'updated_at'          => date('Y-m-d H:i:s')
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

        header("Location: formulario-esquema-facturacion.php?terminoId={$terminoId}&success=1");
        exit();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al guardar esquema de facturación: " . $e->getMessage());
        $errorMessage = "Error interno al guardar los cambios.";
    }
}

// -------------------------------------------------------------------------
// 4. PREPARAR DATOS PARA LA VISTA
// -------------------------------------------------------------------------
$fechaEstimadaBaseVal = (string)($savedData['fecha_estimada_base'] ?? date('Y-m-d'));
$cantidadFacturasVal  = (int)($savedData['cantidad_facturas'] ?? 1);
$montoFacturaVal      = (string)($savedData['monto_factura'] ?? '0.00');
$monedaVal            = (string)($savedData['moneda'] ?? $monedaCarta);
$facturasVal          = (array)($savedData['facturas'] ?? []);

$sumaTotalEsquema = 0.00;
foreach ($facturasVal as $f) {
    $sumaTotalEsquema += (float)($f['monto'] ?? 0);
}

$diferenciaConPropuesta = round($sumaTotalEsquema - $montoPropuestaCarta, 2);
$esIgualPropuesta       = (abs($diferenciaConPropuesta) < 0.01);

$monedasSoportadas = ['USD', 'BS', 'EUR'];

$pageTitle = "Esquema de Facturación - Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<style>
    .header-bar-panel {
        background: #475569;
        color: #ffffff;
        padding: 0.65rem 1.25rem;
        font-size: 0.95rem;
        font-weight: 600;
        border-radius: 4px 4px 0 0;
    }
    .panel-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
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
    .btn-blue-icon {
        background: #2563eb;
        color: #ffffff;
        border: none;
        width: 38px;
        height: 38px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-blue-icon:hover {
        background: #1d4ed8;
    }
    .table-custom {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    .table-custom th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.8rem;
        text-transform: uppercase;
        padding: 0.65rem 1rem;
        border-bottom: 2px solid #e2e8f0;
        text-align: left;
    }
    .table-custom td {
        padding: 0.65rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.9rem;
    }
    .table-custom tfoot td {
        background: #f1f5f9;
        border-top: 2px solid #cbd5e1;
        border-bottom: none;
    }
</style>

<div class="view-container">

    <!-- CABECERA SUPERIOR -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0;">
                Esquema de Facturación
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Cliente: <strong><?= htmlspecialchars($headerData->clientName, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
        <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; text-decoration: none; background: #e2e8f0; color: #334155; border-radius: 6px; font-weight: 600;">
            <i class="ri-arrow-left-line"></i> Volver a Actividades
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="padding: 0.85rem 1rem; background: #dcfce7; color: #166534; border-radius: 6px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Esquema de facturación actualizado correctamente.
        </div>
    <?php endif; ?>

    <!-- PANEL PRINCIPAL -->
    <div class="panel-box">
        <div class="header-bar-panel">Esquema de Facturación</div>
        <div style="padding: 1.5rem;">
            
            <form method="POST" action="formulario-esquema-facturacion.php?terminoId=<?= $terminoId ?>">
                <input type="hidden" name="action_save_esquema" value="1">
                <input type="hidden" name="termino_id" value="<?= $terminoId ?>">

                <div style="display: grid; grid-template-columns: 2fr 1.5fr 2fr 1.5fr 60px; gap: 1.5rem; align-items: end;">
                    <div>
                        <label style="display: block; font-size: 0.78rem; color: #475569; margin-bottom: 0.25rem;">Fecha Factura (Estimada):</label>
                        <input type="date" name="fecha_estimada_base" class="form-control-line" style="text-align: center;" value="<?= htmlspecialchars($fechaEstimadaBaseVal, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.78rem; color: #475569; margin-bottom: 0.25rem;">Cantidad de Facturas:</label>
                        <input type="number" min="1" max="60" name="cantidad_facturas" class="form-control-line" style="text-align: center;" value="<?= $cantidadFacturasVal ?>">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.78rem; color: #475569; margin-bottom: 0.25rem;">Monto Factura (Total):</label>
                        <input type="text" name="monto_factura" class="form-control-line" style="text-align: right;" value="<?= htmlspecialchars(formatMontoVe($montoFacturaVal), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" onblur="formatInputVe(this)">
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.78rem; color: #475569; margin-bottom: 0.25rem;">Moneda:</label>
                        <select name="moneda" class="form-control-line">
                            <?php foreach ($monedasSoportadas as $m): ?>
                                <option value="<?= $m ?>" <?= $m === $monedaVal ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <button type="submit" name="btn_generar_cuotas" value="1" class="btn-blue-icon" title="Generar / Re-calcular Cuotas">
                            <i class="ri-save-3-fill" style="font-size: 1.25rem;"></i>
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <!-- TABLA DE FACTURAS Y TOTALIZACIÓN -->
    <?php if (!empty($facturasVal)): ?>
        <?php $totalFacturasCount = count($facturasVal); ?>
        <form method="POST" action="formulario-esquema-facturacion.php?terminoId=<?= $terminoId ?>">
            <input type="hidden" name="action_save_esquema" value="1">
            <input type="hidden" name="termino_id" value="<?= $terminoId ?>">
            <input type="hidden" name="fecha_estimada_base" value="<?= htmlspecialchars($fechaEstimadaBaseVal, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="cantidad_facturas" value="<?= $totalFacturasCount ?>">
            <input type="hidden" name="monto_factura" value="<?= htmlspecialchars(formatMontoVe($montoFacturaVal), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="moneda" value="<?= htmlspecialchars($monedaVal, ENT_QUOTES, 'UTF-8') ?>">

            <div class="panel-box" style="padding: 1.25rem;">
                <h3 style="font-size: 1rem; color: #1e293b; margin: 0 0 1rem 0; font-weight: 600;">
                    <i class="ri-table-line" style="color: #2563eb;"></i> Desglose Detallado de Facturas
                </h3>

                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 12%; text-align: center;"># Cuota</th>
                            <th style="width: 38%; text-align: right;">Fecha Estimada de Emisión</th>
                            <th style="width: 35%; text-align: right;">Monto Cuota (<?= htmlspecialchars($monedaVal, ENT_QUOTES, 'UTF-8') ?>)</th>
                            <th style="width: 15%; text-align: center;">Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturasVal as $index => $f): ?>
                            <tr>
                                <!-- REQUERIMIENTO 1: NUMERACIÓN FORMATO (1/10, 2/10) -->
                                <td style="font-weight: 600; color: #334155; text-align: center;">
                                  Factura  <?= (int)($f['numero'] ?? ($index + 1)) ?>/<?= $totalFacturasCount ?>
                                </td>
                                <!-- REQUERIMIENTO 2: FECHA ALINEADA A LA DERECHA -->
                                <td>
                                    <input type="date" name="factura_fecha[]" class="form-control-line" style="text-align: right;" value="<?= htmlspecialchars((string)($f['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                </td>
                                <td>
                                    <input type="text" name="factura_monto[]" class="form-control-line js-monto-cuota" style="text-align: right; font-weight: 600;" value="<?= htmlspecialchars(formatMontoVe((float)($f['monto'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>" onblur="formatInputVe(this); recalcularTotales();" required>
                                </td>
                                <td style="text-align: center;">
                                    <span style="font-size: 0.75rem; background: #f1f5f9; color: #475569; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;">
                                        Programada
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    
                    <!-- REQUERIMIENTO 3: TOTALIZACIÓN SOLO NÚMERO Y MONEDA ABAJO DE ESTATUS -->
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align: right; font-weight: 700; color: #334155; font-size: 0.9rem;">
                                TOTAL ESQUEMA:
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 0.95rem;" id="tfoot_total_esquema">
                                <?= formatMontoVe($sumaTotalEsquema) ?>
                            </td>
                            <td style="text-align: center; font-weight: 700; color: #0f172a; font-size: 0.95rem;" id="tfoot_moneda">
                                <?= htmlspecialchars($monedaVal, ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!-- RESUMEN INFERIOR ALINEADO -->
                <div style="margin-top: 1.5rem; padding: 1.25rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    
                    <div style="display: grid; grid-template-columns: auto minmax(150px, auto); gap: 0.5rem 1.5rem; align-items: center; font-size: 0.9rem;">
                        <span style="color: #475569; font-weight: 500;">Total Esquema de Facturación:</span>
                        <span id="summary_total_esquema" style="font-size: 0.9rem; font-weight: 700; color: #0f172a; text-align: right;">
                            <?= formatMontoVe($sumaTotalEsquema) ?> <?= htmlspecialchars($monedaVal, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <span style="color: #475569; font-weight: 500;">Monto Propuesta (Carta Contratación):</span>
                        <span style="font-size: 0.9rem; font-weight: 700; color: #0f172a; text-align: right;">
                            <?= formatMontoVe($montoPropuestaCarta) ?> <?= htmlspecialchars($monedaCarta, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div id="status_badge_container">
                        <?php if ($esIgualPropuesta): ?>
                            <div style="padding: 0.5rem 1rem; background: #dcfce7; color: #15803d; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                                <i class="ri-checkbox-circle-fill"></i> Coincide con la Propuesta
                            </div>
                        <?php else: ?>
                            <div style="padding: 0.5rem 1rem; background: #fef3c7; color: #b45309; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                                <i class="ri-alert-fill"></i> Diferencia: <?= formatMontoVe($diferenciaConPropuesta) ?> <?= htmlspecialchars($monedaVal, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- BOTÓN GUARDAR -->
                <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ri-save-line"></i> Guardar Esquema Detallado
                    </button>
                </div>

            </div>
        </form>
    <?php endif; ?>

</div>

<script>
const MONEDA_ACTUAL = <?= json_encode($monedaVal, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const MONEDA_PROPUESTA = <?= json_encode($monedaCarta, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const MONTO_PROPUESTA_CARTA = <?= (float)$montoPropuestaCarta ?>;

function formatInputVe(input) {
    let val = input.value.trim();
    if (!val) return;

    if (val.includes('.') && !val.includes(',')) {
        val = val.replace('.', ',');
    }

    let parts = val.split(',');
    let integerPart = parts[0].replace(/\D/g, '');
    let decimalPart = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '00';

    if (decimalPart.length === 1) decimalPart += '0';
    if (integerPart === '') integerPart = '0';

    integerPart = parseInt(integerPart, 10).toLocaleString('de-DE');

    input.value = integerPart + ',' + decimalPart;
}

function parseVeToFloat(strVal) {
    if (!strVal) return 0.00;
    let limpio = strVal.replace(/\./g, '').replace(',', '.');
    let num = parseFloat(limpio);
    return isNaN(num) ? 0.00 : num;
}

function formatFloatToVe(num) {
    return num.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalcularTotales() {
    const inputs = document.querySelectorAll('.js-monto-cuota');
    let sumaTotal = 0.00;

    inputs.forEach(input => {
        sumaTotal += parseVeToFloat(input.value);
    });

    const strNumVe = formatFloatToVe(sumaTotal);

    // 1. Actualizar Pie de Tabla (SOLO NÚMERO)
    const elTfoot = document.getElementById('tfoot_total_esquema');
    if (elTfoot) elTfoot.textContent = strNumVe;

    // 2. Actualizar Resumen (NÚMERO + MONEDA)
    const elSummary = document.getElementById('summary_total_esquema');
    if (elSummary) elSummary.textContent = strNumVe + ' ' + MONEDA_ACTUAL;

    // 3. Recalcular Diferencia / Coincidencia
    const diff = Math.round((sumaTotal - MONTO_PROPUESTA_CARTA) * 100) / 100;
    const badgeContainer = document.getElementById('status_badge_container');

    if (badgeContainer) {
        if (Math.abs(diff) < 0.01) {
            badgeContainer.innerHTML = `
                <div style="padding: 0.5rem 1rem; background: #dcfce7; color: #15803d; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                    <i class="ri-checkbox-circle-fill"></i> Coincide con la Propuesta
                </div>`;
        } else {
            badgeContainer.innerHTML = `
                <div style="padding: 0.5rem 1rem; background: #fef3c7; color: #b45309; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 0.35rem;">
                    <i class="ri-alert-fill"></i> Diferencia: ${formatFloatToVe(diff)} ${MONEDA_ACTUAL}
                </div>`;
        }
    }
}
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>