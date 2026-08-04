<?php

declare(strict_types=1);

// v/terminos/formulario-frecuencia.php
include '../main/config.php';

// -------------------------------------------------------------------------
// 1. SANITIZACIÓN Y VALIDACIÓN DE PARÁMETROS ENTRANTES
// -------------------------------------------------------------------------
$terminoId = filter_input(INPUT_GET, 'terminoId', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'termino_id', FILTER_VALIDATE_INT);

if (!$terminoId || $terminoId <= 0) {
    die("Error: Identificador de Términos y Condiciones no especificado o inválido.");
}

$itemKey = 'frecuencia';

// -------------------------------------------------------------------------
// 2. PROCESAR GUARDADO DEL FORMULARIO (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_frecuencia'])) {
    $frecuenciaCantidad = filter_input(INPUT_POST, 'frecuencia_cantidad', FILTER_VALIDATE_INT);
    $frecuenciaTipo     = filter_input(INPUT_POST, 'frecuencia_tipo', FILTER_SANITIZE_SPECIAL_CHARS);
    $observaciones      = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS);

    $frecuenciaTipo = $frecuenciaTipo ? trim($frecuenciaTipo) : 'Mensual';
    $observaciones  = $observaciones ? trim($observaciones) : '';

    if (!$frecuenciaCantidad || $frecuenciaCantidad < 1 || $frecuenciaCantidad > 12) {
        $errorMessage = "La cantidad de frecuencias debe ser un número entero entre 1 y 12.";
    } else {
        try {
            $pdo->beginTransaction();

            // Estructurar el JSON de datos
            $payloadJson = json_encode([
                'frecuencia_cantidad' => $frecuenciaCantidad,
                'frecuencia_tipo'     => $frecuenciaTipo,
                'observaciones'       => $observaciones,
                'updated_at'          => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            // Actualizar el item específico
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

            // Recalcular estado global de la cabecera (completado vs en_proceso)
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

            header("Location: responder-terminos.php?id={$terminoId}&success=frecuencia_saved");
            exit();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error al guardar frecuencia en términos: " . $e->getMessage());
            $errorMessage = "Error al almacenar los datos de frecuencia en la base de datos.";
        }
    }
}

// -------------------------------------------------------------------------
// 3. CARGAR DATOS DE CABECERA E ITEM EXISTENTE
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

    // Deserializar JSON actual si existe
    $savedData = [];
    if ($itemData && !empty($itemData->datos_json)) {
        $savedData = json_decode($itemData->datos_json, true) ?: [];
    }

} catch (PDOException $e) {
    error_log("Error al cargar formulario de frecuencia: " . $e->getMessage());
    die("Error crítico al cargar los datos.");
}

$frecuenciaCantidadVal = (int)($savedData['frecuencia_cantidad'] ?? 1);
$frecuenciaTipoVal     = $savedData['frecuencia_tipo'] ?? 'Mensual';
$observacionesVal      = $savedData['observaciones'] ?? '';

$pageTitle = "Configurar Frecuencia - Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container" style="padding: 2rem; max-width: 900px; margin: 0 auto;">

    <!-- CABECERA DE NAVEGACIÓN -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0;">
                <i class="ri-calendar-schedule-line" style="color: #2563eb;"></i> Actividad 2: Configurar Frecuencia
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
        <form method="POST" action="formulario-frecuencia.php?terminoId=<?= $terminoId ?>">
            <input type="hidden" name="action_save_frecuencia" value="1">
            <input type="hidden" name="termino_id" value="<?= $terminoId ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                
                <!-- CANTIDAD DE FRECUENCIAS -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Cantidad de Frecuencias (1 a 12) *
                    </label>
                    <select name="frecuencia_cantidad" required style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $i === $frecuenciaCantidadVal ? 'selected' : '' ?>>
                                <?= $i ?> <?= $i === 1 ? 'Frecuencia' : 'Frecuencias' ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <small style="color: #64748b; font-size: 0.78rem; margin-top: 0.25rem; display: block;">
                        Define cuántas entregas o iteraciones contempla este servicio.
                    </small>
                </div>

                <!-- TIPO DE PERIODICIDAD -->
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Tipo de Periodicidad *
                    </label>
                    <select name="frecuencia_tipo" required style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                        <?php 
                        $tipos = ['Única', 'Mensual', 'Bimestral', 'Trimestral', 'Semestral', 'Anual'];
                        foreach ($tipos as $tipo):
                        ?>
                            <option value="<?= $tipo ?>" <?= $tipo === $frecuenciaTipoVal ? 'selected' : '' ?>>
                                <?= $tipo ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- OBSERVACIONES / DETALLES -->
            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Observaciones o Notas Adicionales
                </label>
                <textarea name="observaciones" rows="4" placeholder="Escriba aquí detalles relevantes sobre el calendario de ejecución..." style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($observacionesVal, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
                <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.6rem 1.25rem; background: #e2e8f0; color: #334155; border-radius: 6px; text-decoration: none; font-weight: 600;">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="ri-save-line"></i> Guardar Frecuencia
                </button>
            </div>

        </form>
    </div>

</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>