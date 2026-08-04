<?php

declare(strict_types=1);

// v/terminos/formulario-carta-contratacion.php
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

$itemKey = 'carta_contratacion';

// -------------------------------------------------------------------------
// 2. PROCESAR GUARDADO DEL FORMULARIO (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_carta'])) {
    $numeroCartaInput      = filter_input(INPUT_POST, 'numero_carta', FILTER_DEFAULT);
    $fechaEmisionInput     = filter_input(INPUT_POST, 'fecha_emision', FILTER_DEFAULT);
    $resumenPropuestaInput = filter_input(INPUT_POST, 'resumen_propuesta', FILTER_DEFAULT);
    $observacionesInput    = filter_input(INPUT_POST, 'observaciones', FILTER_DEFAULT);

    $numeroCarta      = is_string($numeroCartaInput) ? trim($numeroCartaInput) : '';
    $fechaEmision     = is_string($fechaEmisionInput) ? trim($fechaEmisionInput) : '';
    $resumenPropuesta = is_string($resumenPropuestaInput) ? trim($resumenPropuestaInput) : '';
    $observaciones    = is_string($observacionesInput) ? trim($observacionesInput) : '';

    // Validaciones de Lógica de Negocio
    if (empty($numeroCarta)) {
        $errorMessage = "El número o referencia de la carta de contratación es obligatorio.";
    } elseif (empty($fechaEmision) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaEmision)) {
        $errorMessage = "La fecha de emisión debe tener un formato válido (AAAA-MM-DD).";
    } else {
        try {
            $pdo->beginTransaction();

            // Guardar payload JSON en texto plano
            $payloadJson = json_encode([
                'numero_carta'      => $numeroCarta,
                'fecha_emision'     => $fechaEmision,
                'resumen_propuesta' => $resumenPropuesta,
                'observaciones'     => $observaciones,
                'updated_at'        => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            // Actualizar el item 'carta_contratacion'
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
            $errorMessage = "Error interno al guardar los datos de la carta de contratación.";
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
    error_log("Error al cargar carta de contratación: " . $e->getMessage());
    die("Error crítico al cargar los datos.");
}

$numeroCartaVal      = (string)($savedData['numero_carta'] ?? '');
$fechaEmisionVal     = (string)($savedData['fecha_emision'] ?? date('Y-m-d'));
$resumenPropuestaVal = (string)($savedData['resumen_propuesta'] ?? '');
$observacionesVal    = (string)($savedData['observaciones'] ?? '');

$pageTitle = "Carta de Contratación - Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container" style="padding: 2rem; max-width: 900px; margin: 0 auto;">

    <!-- CABECERA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0;">
                <i class="ri-file-text-line" style="color: #2563eb;"></i> Actividad 1: Carta de Contratación
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
        <form method="POST" action="formulario-carta-contratacion.php?terminoId=<?= $terminoId ?>">
            <input type="hidden" name="action_save_carta" value="1">
            <input type="hidden" name="termino_id" value="<?= $terminoId ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <!-- NÚMERO / REFERENCIA DE CARTA -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        N° de Carta o Referencia *
                    </label>
                    <input type="text" name="numero_carta" required value="<?= htmlspecialchars($numeroCartaVal, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: CC-2026-0042" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                </div>

                <!-- FECHA DE EMISIÓN -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Fecha de Emisión *
                    </label>
                    <input type="date" name="fecha_emision" required value="<?= htmlspecialchars($fechaEmisionVal, ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                </div>

            </div>

            <!-- RESUMEN / ALCANCE CONTRACTUAL -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Resumen del Alcance / Objeto del Contrato
                </label>
                <textarea name="resumen_propuesta" rows="3" placeholder="Sintetice los puntos principales de la propuesta aceptada..." style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($resumenPropuestaVal, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- OBSERVACIONES -->
            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Observaciones o Clausulado Adicional
                </label>
                <textarea name="observaciones" rows="3" placeholder="Indique cláusulas especiales o acuerdos específicos..." style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($observacionesVal, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- ACCIONES -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
                <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.6rem 1.25rem; background: #e2e8f0; color: #334155; border-radius: 6px; text-decoration: none; font-weight: 600;">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="ri-save-line"></i> Guardar Carta
                </button>
            </div>

        </form>
    </div>

</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>