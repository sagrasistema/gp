<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include '../main/config.php';

// -------------------------------------------------------------------------
// 1. SANITIZACIÓN Y VALIDACIÓN DE PARÁMETROS ENTRANTES
// -------------------------------------------------------------------------
$terminoId = filter_input(INPUT_GET, 'terminoId', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'termino_id', FILTER_VALIDATE_INT);

if (!$terminoId || $terminoId <= 0) {
    die("Error: Identificador de Términos y Condiciones no especificado o inválido.");
}

$stmtStatus = $pdo->prepare("SELECT statusId FROM terminos_condiciones WHERE id = :id");
$stmtStatus->execute([':id' => $terminoId]);
$isClosed = ((int)$stmtStatus->fetchColumn() === 2);
$itemKey = 'frecuencia';

$uploadDir = __DIR__ . '/../uploads/balances/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    die('Error al crear el directorio de almacenamiento.');
}

// -------------------------------------------------------------------------
// 2. PROCESAR GUARDADO DEL FORMULARIO (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_frecuencia'])) {
    $frecuenciaCantidad = filter_input(INPUT_POST, 'frecuencia_cantidad', FILTER_VALIDATE_INT);
    $frecuenciaTipo     = filter_input(INPUT_POST, 'frecuencia_tipo', FILTER_SANITIZE_SPECIAL_CHARS);
    $auditorEeff        = filter_input(INPUT_POST, 'auditor_eeff', FILTER_SANITIZE_SPECIAL_CHARS);
    $observaciones      = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS);

    $frecuenciaTipo = $frecuenciaTipo ? trim($frecuenciaTipo) : 'Mensual';
    $auditorEeff    = $auditorEeff ? trim($auditorEeff) : 'tercero';
    $observaciones  = $observaciones ? trim($observaciones) : '';

    // Obtener array de periodos ingresados dinámicamente
    $rawPeriodos = $_POST['periodos'] ?? [];
    $periodos    = [];

    if (is_array($rawPeriodos)) {
        foreach ($rawPeriodos as $idx => $val) {
            $periodos[(int)$idx] = htmlspecialchars(trim((string)$val), ENT_QUOTES, 'UTF-8');
        }
    }

    if (!$frecuenciaCantidad || $frecuenciaCantidad < 1 || $frecuenciaCantidad > 12) {
        $errorMessage = "La cantidad de frecuencias debe ser un número entero entre 1 y 12.";
    } else {
        try {
            // Cargar datos guardados previamente para preservar archivos subidos previamente si no se reemplazan
            $stmtPrev = $pdo->prepare("SELECT datos_json FROM terminos_condiciones_items WHERE termino_id = :termino_id AND item_key = :item_key");
            $stmtPrev->execute([':termino_id' => $terminoId, ':item_key' => $itemKey]);
            $prevDataJson = $stmtPrev->fetchColumn();
            $prevData = $prevDataJson ? json_decode($prevDataJson, true) : [];
            $archivosBalances = $prevData['archivos_balances'] ?? [];

            // Procesar anexos si la opción seleccionada es SAGRAG
            if ($auditorEeff === 'sagrag' && isset($_FILES['balance_files'])) {
                $allowedExtensions = ['pdf', 'xlsx', 'xls', 'zip'];
                foreach ($_FILES['balance_files']['tmp_name'] as $mesNum => $tmpName) {
                    if ($_FILES['balance_files']['error'][$mesNum] === UPLOAD_ERR_OK) {
                        $originalName = $_FILES['balance_files']['name'][$mesNum];
                        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                        if (in_array($ext, $allowedExtensions, true)) {
                            $newName = "balance_t{$terminoId}_m{$mesNum}_" . bin2hex(random_bytes(8)) . ".{$ext}";
                            $destination = $uploadDir . $newName;

                            if (move_uploaded_file($tmpName, $destination)) {
                                $archivosBalances[$mesNum] = $newName;
                            }
                        }
                    }
                }
            }

            $pdo->beginTransaction();

            $payloadJson = json_encode([
                'frecuencia_cantidad' => $frecuenciaCantidad,
                'frecuencia_tipo'     => $frecuenciaTipo,
                'periodos'            => $periodos,
                'auditor_eeff'        => $auditorEeff,
                'archivos_balances'   => $archivosBalances,
                'observaciones'       => $observaciones,
                'updated_at'          => date('Y-m-d H:i:s')
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

            $stmtCheckPending = $pdo->prepare("
                SELECT COUNT(*) FROM terminos_condiciones_items 
                WHERE termino_id = :termino_id AND estado != 'completado'
            ");
            $stmtCheckPending->execute([':termino_id' => $terminoId]);
            $pendingCount = (int)$stmtCheckPending->fetchColumn();

            $nuevoEstadoGlobal = ($pendingCount === 0) ? 'completado' : 'en_proceso';

            $stmtUpdateMaster = $pdo->prepare("
                UPDATE terminos_condiciones SET estado = :estado WHERE id = :id
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
        die("Error: El registro de Términos y Condiciones no existe.");
    }

    $stmtItem = $pdo->prepare("
        SELECT * FROM terminos_condiciones_items 
        WHERE termino_id = :termino_id AND item_key = :item_key
    ");
    $stmtItem->execute([':termino_id' => $terminoId, ':item_key' => $itemKey]);
    $itemData = $stmtItem->fetch(PDO::FETCH_OBJ);

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
$auditorEeffVal        = $savedData['auditor_eeff'] ?? 'tercero';
$periodosVal           = $savedData['periodos'] ?? [];
$archivosBalancesVal   = $savedData['archivos_balances'] ?? [];
$observacionesVal      = $savedData['observaciones'] ?? '';

$pageTitle = "Configurar Frecuencia - Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container">
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

    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <form method="POST" action="formulario-frecuencia.php?terminoId=<?= $terminoId ?>" enctype="multipart/form-data">
            <input type="hidden" name="action_save_frecuencia" value="1">
            <input type="hidden" name="termino_id" value="<?= $terminoId ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Cantidad de Frecuencias (1 a 12) *
                    </label>
                    <select name="frecuencia_cantidad" id="frecuencia_cantidad" onchange="renderTablaPeriodos()" required style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?= $i ?>" <?= $i === $frecuenciaCantidadVal ? 'selected' : '' ?>>
                                <?= $i ?> <?= $i === 1 ? 'Frecuencia' : 'Frecuencias' ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                        Tipo de Periodicidad *
                    </label>
                    <select name="frecuencia_tipo" required style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; background: #f8fafc;">
                        <?php 
                        $tipos = ['Única', 'Mensual', 'Bimestral', 'Trimestral', 'Semestral', 'Anual'];
                        foreach ($tipos as $tipo):
                        ?>
                            <option value="<?= $tipo ?>" <?= $tipo === $frecuenciaTipoVal ? 'selected' : '' ?>><?= $tipo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- TABLA DINÁMICA DE PERIODOS -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Periodo de cada Frecuencia
                </label>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; border: 1px solid #cbd5e1;">
                        <thead>
                            <tr style="background: #f1f5f9; text-align: left;">
                                <th style="padding: 0.5rem 1rem; border: 1px solid #cbd5e1; width: 120px;">Frecuencia</th>
                                <th style="padding: 0.5rem 1rem; border: 1px solid #cbd5e1;">Periodo / Descripción</th>
                            </tr>
                        </thead>
                        <tbody id="container_periodos">
                            <!-- Inyección JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN AUDITOR EEFF -->
            <div style="margin-bottom: 1.5rem; background: #f8fafc; padding: 1.25rem; border-radius: 6px; border: 1px solid #e2e8f0;">
                <label style="display: block; font-size: 0.9rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem;">
                    Estados Financieros auditados por:
                </label>
                <div style="display: flex; gap: 2rem;">
                    <label style="font-weight: 600; cursor: pointer; color: #334155;">
                        <input type="radio" name="auditor_eeff" value="sagrag" onchange="toggleSeccionBalances()" <?= $auditorEeffVal === 'sagrag' ? 'checked' : '' ?>>
                        SAGRAG
                    </label>
                    <label style="font-weight: 600; cursor: pointer; color: #334155;">
                        <input type="radio" name="auditor_eeff" value="tercero" onchange="toggleSeccionBalances()" <?= $auditorEeffVal === 'tercero' ? 'checked' : '' ?>>
                        Tercero
                    </label>
                </div>
            </div>

            <!-- ANEXO DE 12 BALANCES (SAGRAG) -->
            <div id="seccion_balances" style="display: <?= $auditorEeffVal === 'sagrag' ? 'block' : 'none' ?>; margin-bottom: 1.5rem; background: #eff6ff; padding: 1.25rem; border-radius: 6px; border: 1px solid #bfdbfe;">
                <label style="display: block; font-size: 0.9rem; font-weight: 700; color: #1e3a8a; margin-bottom: 0.75rem;">
                    Anexar Balances (1 a 12)
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <div style="background: #ffffff; padding: 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 0.25rem;">
                                Balance Mes <?= $m ?>
                            </label>
                            <input type="file" name="balance_files[<?= $m ?>]" accept=".pdf,.xlsx,.xls,.zip" style="font-size: 0.8rem; width: 100%;">
                            <?php if (!empty($archivosBalancesVal[$m])): ?>
                                <small style="display: block; margin-top: 0.25rem; color: #166534; font-size: 0.75rem;">
                                    ✓ Adjunto: <?= htmlspecialchars($archivosBalancesVal[$m], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- OBSERVACIONES -->
            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem;">
                    Observaciones o Notas Adicionales
                </label>
                <textarea name="observaciones" rows="4" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; resize: vertical;"><?= htmlspecialchars($observacionesVal, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- BOTONES -->
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

<script>
const periodosGuardados = <?= json_encode($periodosVal, JSON_UNESCAPED_UNICODE) ?>;

function renderTablaPeriodos() {
    const cantidad = parseInt(document.getElementById('frecuencia_cantidad').value) || 1;
    const container = document.getElementById('container_periodos');
    container.innerHTML = '';

    for (let i = 1; i <= cantidad; i++) {
        const val = periodosGuardados[i] ? periodosGuardados[i] : '';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding: 0.5rem 1rem; border: 1px solid #cbd5e1; font-weight: 600; color: #475569;">Frecuencia ${i}</td>
            <td style="padding: 0.5rem 1rem; border: 1px solid #cbd5e1;">
                <input type="text" name="periodos[${i}]" value="${val}" placeholder="Ej: Enero 2026 / Trimestre 1" required style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem;">
            </td>
        `;
        container.appendChild(tr);
    }
}

function toggleSeccionBalances() {
    const opcionSagrag = document.querySelector('input[name="auditor_eeff"][value="sagrag"]').checked;
    const divBalances = document.getElementById('seccion_balances');
    divBalances.style.display = opcionSagrag ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    renderTablaPeriodos();
});
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>