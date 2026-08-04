<?php

declare(strict_types=1);

// v/terminos/formulario-esquema-facturacion.php
require_once '../main/config.php';

/** @var PDO $pdo */

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

// Lista blanca de monedas soportadas
$monedasPermitidas = ['USD', 'BS', 'EUR'];

// -------------------------------------------------------------------------
// 2. PROCESAR GUARDADO DEL FORMULARIO (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_facturacion'])) {
    $montoTotalInput       = filter_input(INPUT_POST, 'monto_total', FILTER_VALIDATE_FLOAT);
    $monedaInput           = filter_input(INPUT_POST, 'moneda', FILTER_DEFAULT);
    $formaPagoInput        = filter_input(INPUT_POST, 'forma_pago', FILTER_DEFAULT);
    $hitosFacturacionInput = filter_input(INPUT_POST, 'hitos_facturacion', FILTER_DEFAULT);
    $observacionesInput    = filter_input(INPUT_POST, 'observaciones', FILTER_DEFAULT);

    $moneda           = is_string($monedaInput) ? trim($monedaInput) : 'USD';
    $formaPago        = is_string($formaPagoInput) ? trim($formaPagoInput) : '';
    $hitosFacturacion = is_string($hitosFacturacionInput) ? trim($hitosFacturacionInput) : '';
    $observaciones    = is_string($observacionesInput) ? trim($observacionesInput) : '';

    // Validaciones de Lógica de Negocio
    if ($montoTotalInput === false || $montoTotalInput < 0) {
        $errorMessage = "Ingrese un monto total válido para el servicio.";
    } elseif (!in_array($moneda, $monedasPermitidas, true)) {
        $errorMessage = "La moneda seleccionada no es válida.";
    } elseif (empty($formaPago)) {
        $errorMessage = "Debe especificar la forma o condición de pago (ej: Contado, Crédito 30 días).";
    } else {
        try {
            $pdo->beginTransaction();

            $payloadJson = json_encode([
                'monto_total'       => number_format((float)$montoTotalInput, 2, '.', ''),
                'moneda'            => $moneda,
                'forma_pago'        => $formaPago,
                'hitos_facturacion' => $hitosFacturacion,
                'observaciones'     => $observaciones,
                'updated_at'        => date('Y-m-d H:i:s')
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

            // Reevaluar estado global de la cabecera
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

            header("Location: responder-terminos.php?id={$terminoId}&success=facturacion_saved");
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al guardar esquema de facturación: " . $e->getMessage());
            $errorMessage = "Error interno al guardar los datos de facturación.";
        }
    }
}

// -------------------------------------------------------------------------
// 3. CARGAR DATOS EXISTENTES
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
    error_log("Error al cargar esquema de facturación: " . $e->getMessage());
    die("Error crítico al cargar los datos.");
}

$montoTotalVal       = (string)($savedData['monto_total'] ?? '0.00');
$monedaVal           = (string)($savedData['moneda'] ?? 'USD');
$formaPagoVal        = (string)($savedData['forma_pago'] ?? '');
$hitosFacturacionVal = (string)($savedData['hitos_facturacion'] ?? '');
$observacionesVal    = (string)($savedData['observaciones'] ?? '');

$pageTitle = "Esquema de Facturación - Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container" style="padding: 2rem; max-width: 900px; margin: 0 auto;">

    <!-- CABECERA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0;">
                <i class="ri-money-dollar-circle-line" style="color: #2563eb;"></i> Actividad 4: Esquema de Facturación
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Cliente: <strong><?= htmlspecialchars($headerData->clientName, ENT_QUOTES, 'UTF-8') ?></strong> | 
                Servicio: <strong><?= htmlspecialchars($headerData->servicio, ENT_QUOTES, 'UTF-8') ?></strong>
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

    <!-- FORMULARIO -->
    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <form method="POST" action="formulario-esquema-facturacion.php?terminoId=<?= $terminoId ?>">
            <input type="hidden" name="action_save_facturacion" value="1">
            <input type="hidden" name="termino_id" value="<?= $terminoId ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <!-- MONTO TOTAL -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Monto Total Estimado *
                    </label>
                    <input type="number" step="0.01" min="0" name="monto_total" required value="<?= htmlspecialchars($montoTotalVal, ENT_QUOTES, 'UTF-8') ?>" placeholder="0.00" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                </div>

                <!-- MONEDA -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Moneda de Facturación *
                    </label>
                    <select name="moneda" required style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                        <?php foreach ($monedasPermitidas as $m): ?>
                            <option value="<?= $m ?>" <?= $m === $monedaVal ? 'selected' : '' ?>>
                                <?= $m ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- FORMA / CONDICIÓN DE PAGO -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Forma / Condición de Pago *
                </label>
                <input type="text" name="forma_pago" required value="<?= htmlspecialchars($formaPagoVal, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: 50% anticipado, 50% contra entrega de informe final" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
            </div>

            <!-- HITOS DE FACTURACIÓN -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Hitos / Fechas Estimadas de Entrega
                </label>
                <textarea name="hitos_facturacion" rows="3" placeholder="Desglose de entregables vinculados a pagos..." style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($hitosFacturacionVal, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- OBSERVACIONES -->
            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Observaciones o Consideraciones Fiscales
                </label>
                <textarea name="observaciones" rows="3" placeholder="Retenciones, impuestos aplicables, cuentas bancarias, etc." style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($observacionesVal, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- ACCIONES -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
                <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.6rem 1.25rem; background: #e2e8f0; color: #334155; border-radius: 6px; text-decoration: none; font-weight: 600;">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="ri-save-line"></i> Guardar Esquema
                </button>
            </div>

        </form>
    </div>

</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>