<?php

declare(strict_types=1);

// v/terminos/formulario-aceptacion-continuidad.php
require_once '../main/config.php';

/** @var PDO $pdo */

// -------------------------------------------------------------------------
// HELPERS DE SANITIZACIÓN Y VALIDACIÓN
// -------------------------------------------------------------------------

/**
 * Sanitiza entradas de texto para prevenir XSS
 */
function sanitizeInput(?string $value): string
{
    return trim(htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
}

/**
 * Valida niveles de riesgo permitidos
 */
function sanitizeNivelRiesgo(string $nivel): string
{
    $permitidos = ['Alto', 'Medio', 'Bajo'];
    return in_array($nivel, $permitidos, true) ? $nivel : 'Bajo';
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

$itemKey = 'aceptacion_continuidad';

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
    error_log("Error al consultar Aceptación y Continuidad: " . $e->getMessage());
    die("Error crítico al consultar la base de datos.");
}

// Extracto de datos persistidos
$pruebasACData = (array)($savedData['pruebas_ac'] ?? []);
$matrizRiesgosData = (array)($savedData['matriz_riesgos'] ?? []);

// -------------------------------------------------------------------------
// 3. PROCESAR GUARDADO (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_ac'])) {

    // Processing de las 30 Pruebas
    $postedPruebas = $_POST['prueba'] ?? [];
    $sanitizedPruebas = [];
    if (is_array($postedPruebas)) {
        foreach ($postedPruebas as $key => $val) {
            $sanitizedPruebas[sanitizeInput((string)$key)] = sanitizeInput((string)$val);
        }
    }

    // Processing de la Matriz de Riesgos (Prueba Especial)
    $sanitizedMatriz = [];
    $mIds         = $_POST['matriz_id'] ?? [];
    $mCategorias  = $_POST['matriz_categoria'] ?? [];
    $mDesc        = $_POST['matriz_descripcion'] ?? [];
    $mCausas      = $_POST['matriz_causa'] ?? [];
    $mNiveles     = $_POST['matriz_nivel'] ?? [];

    if (is_array($mIds) && count($mIds) > 0) {
        foreach ($mIds as $idx => $idVal) {
            $sanitizedMatriz[] = [
                'id_riesgo'   => sanitizeInput((string)$idVal),
                'categoria'   => sanitizeInput((string)($mCategorias[$idx] ?? '')),
                'descripcion' => sanitizeInput((string)($mDesc[$idx] ?? '')),
                'causa_raiz'  => sanitizeInput((string)($mCausas[$idx] ?? '')),
                'nivel_riesgo'=> sanitizeNivelRiesgo((string)($mNiveles[$idx] ?? 'Bajo')),
            ];
        }
    }

    try {
        $pdo->beginTransaction();

        $payloadJson = json_encode([
            'pruebas_ac'     => $sanitizedPruebas,
            'matriz_riesgos' => $sanitizedMatriz,
            'updated_at'     => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $stmtUpdateItem = $pdo->prepare("
            UPDATE terminos_condiciones_items 
            SET datos_json = :datos_json,
                estado = 'completado'
            WHERE termino_id = :termino_id AND item_key = :item_key
        ");
        $stmtUpdateItem->execute([
            ':datos_json' => $payloadJson,
            ':termino_id' => $terminoId,
            ':item_key'   => $itemKey
        ]);

        $pdo->commit();

        header("Location: formulario-aceptacion-continuidad.php?terminoId={$terminoId}&success=1");
        exit();

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al guardar Aceptación y Continuidad: " . $e->getMessage());
        $errorMessage = "Error interno al guardar los cambios.";
    }
}

$pageTitle = "Aceptación y Continuidad - Auditoría";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<style>
    .panel-box {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
    }
    .header-bar-panel {
        background: #334155;
        color: #ffffff;
        padding: 0.75rem 1.25rem;
        font-size: 0.95rem;
        font-weight: 600;
        border-radius: 6px 6px 0 0;
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
        padding: 0.75rem 0.75rem;
        border-bottom: 2px solid #cbd5e1;
        border-right: 1px solid #e2e8f0;
    }
    .table-custom td {
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #f1f5f9;
        font-size: 0.875rem;
        vertical-align: middle;
    }
    /* Estilos del Modal */
    .modal-backdrop {
        position: fixed;
        top:0; left:0; width:100%; height:100%;
        background: rgba(15, 23, 42, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .modal-backdrop.active { display: flex; }
    .modal-content {
        background: #ffffff;
        width: 100%;
        max-width: 600px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        overflow: hidden;
    }
    .modal-header {
        background: #0f172a;
        color: #ffffff;
        padding: 1rem 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-body { padding: 1.25rem; }
    .modal-footer {
        padding: 1rem 1.25rem;
        background: #f8fafc;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        border-top: 1px solid #e2e8f0;
    }
    .badge-risk-Alto { background: #fee2e2; color: #991b1b; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700; }
    .badge-risk-Medio { background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 700; font-weight: 700; }
    .badge-risk-Bajo { background: #dcfce7; color: #166534; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700; }
</style>

<div class="view-container" style="padding: 2rem; max-width: 1100px; margin: 0 auto;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0;">
                Aceptación y Continuidad (AC)
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Cliente: <strong><?= htmlspecialchars($headerData->clientName, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
        <a href="responder-terminos.php?id=<?= $terminoId ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem; text-decoration: none; background: #e2e8f0; color: #334155; border-radius: 6px; font-weight: 600;">
            <i class="ri-arrow-left-line"></i> Volver
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="padding: 0.85rem 1rem; background: #dcfce7; color: #166534; border-radius: 6px; margin-bottom: 1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Evaluación de Aceptación y Continuidad guardada exitosamente.
        </div>
    <?php endif; ?>

    <form method="POST" action="formulario-aceptacion-continuidad.php?terminoId=<?= $terminoId ?>">
        <input type="hidden" name="action_save_ac" value="1">
        <input type="hidden" name="termino_id" value="<?= $terminoId ?>">

        <!-- BLOQUE 1: CUESTIONARIO DE 30 PRUEBAS -->
        <div class="panel-box">
            <div class="header-bar-panel">1. Evaluación de Pruebas de Aceptación (30 Criterios)</div>
            <div style="padding: 1.25rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                    <?php for ($i = 1; $i <= 30; $i++): ?>
                        <?php $valPrueba = $pruebasACData["p_{$i}"] ?? 'cumple'; ?>
                        <div style="padding: 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #334155; margin-bottom: 0.35rem;">
                                Prueba #<?= $i ?>: Criterio de Riesgo AC-<?= sprintf('%02d', $i) ?>
                            </label>
                            <select name="prueba[p_<?= $i ?>]" style="width: 100%; padding: 0.35rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                                <option value="cumple" <?= $valPrueba === 'cumple' ? 'selected' : '' ?>>Cumple / Satisfactorio</option>
                                <option value="no_cumple" <?= $valPrueba === 'no_cumple' ? 'selected' : '' ?>>No Cumple / Alerta</option>
                                <option value="no_aplica" <?= $valPrueba === 'no_aplica' ? 'selected' : '' ?>>No Aplica</option>
                            </select>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- BLOQUE 2: PRUEBA ESPECIAL - MATRIZ DE RIESGO -->
        <div class="panel-box">
            <div class="header-bar-panel" style="display: flex; justify-content: space-between; align-items: center;">
                <span>2. Prueba Especial: Matriz de Riesgo Identificado</span>
                <button type="button" onclick="abrirModalRiesgo()" style="background: #2563eb; color: #ffffff; border: none; padding: 0.4rem 0.85rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                    <i class="ri-add-line"></i> Agregar Riesgo
                </button>
            </div>
            <div style="padding: 1.25rem;">
                
                <table class="table-custom" id="tablaMatrizRiesgos">
                    <thead>
                        <tr>
                            <th style="width: 10%; text-align: center;">ID Riesgo</th>
                            <th style="width: 20%;">Categoría de Riesgo</th>
                            <th style="width: 30%;">Descripción del Riesgo Identificado</th>
                            <th style="width: 25%;">Factor / Causa Raíz (Según AC)</th>
                            <th style="width: 15%; text-align: center;">Nivel de Riesgo Inherente</th>
                            <th style="width: 5%; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyMatriz">
                        <?php if (empty($matrizRiesgosData)): ?>
                            <tr id="rowNoData">
                                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 1.5rem;">
                                    No se han registrado riesgos en la matriz. Haz clic en <strong>Agregar Riesgo</strong>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($matrizRiesgosData as $r): ?>
                                <tr>
                                    <td style="text-align: center; font-weight: 700; color: #0f172a;">
                                        <?= htmlspecialchars($r['id_riesgo'], ENT_QUOTES, 'UTF-8') ?>
                                        <input type="hidden" name="matriz_id[]" value="<?= htmlspecialchars($r['id_riesgo'], ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['categoria'], ENT_QUOTES, 'UTF-8') ?>
                                        <input type="hidden" name="matriz_categoria[]" value="<?= htmlspecialchars($r['categoria'], ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                                        <input type="hidden" name="matriz_descripcion[]" value="<?= htmlspecialchars($r['descripcion'], ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($r['causa_raiz'], ENT_QUOTES, 'UTF-8') ?>
                                        <input type="hidden" name="matriz_causa[]" value="<?= htmlspecialchars($r['causa_raiz'], ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-risk-<?= $r['nivel_riesgo'] ?>">
                                            <?= htmlspecialchars($r['nivel_riesgo'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <input type="hidden" name="matriz_nivel[]" value="<?= htmlspecialchars($r['nivel_riesgo'], ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" onclick="eliminarFilaRiesgo(this)" style="background: transparent; border: none; color: #ef4444; cursor: pointer;">
                                            <i class="ri-delete-bin-line" style="font-size: 1.1rem;"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-bottom: 2rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.65rem 2rem; background: #16a34a; color: #ffffff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.95rem;">
                <i class="ri-save-3-line"></i> Guardar Evaluación AC y Matriz
            </button>
        </div>
    </form>
</div>

<!-- MODAL PARA AGREGAR NUEVO RIESGO -->
<div class="modal-backdrop" id="modalRiesgo">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">Agregar Riesgo a la Matriz (AC)</h3>
            <button type="button" onclick="cerrarModalRiesgo()" style="background: transparent; border: none; color: #ffffff; cursor: pointer; font-size: 1.2rem;">&times;</button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 0.85rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #334155;">ID Riesgo:</label>
                <input type="text" id="m_id" placeholder="Ej: R-01" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;">
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #334155;">Categoría de Riesgo:</label>
                <input type="text" id="m_categoria" placeholder="Ej: Integridad de la Gerencia / Financiero" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;">
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #334155;">Descripción del Riesgo Identificado:</label>
                <textarea id="m_descripcion" rows="3" placeholder="Describa detalladamente el riesgo..." style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;"></textarea>
            </div>

            <div style="margin-bottom: 0.85rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #334155;">Factor / Causa Raíz (Según AC):</label>
                <textarea id="m_causa" rows="2" placeholder="Origen o causa raíz identificada..." style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;"></textarea>
            </div>

            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #334155;">Nivel de Riesgo Inherente:</label>
                <select id="m_nivel" style="width: 100%; padding: 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.875rem;">
                    <option value="Alto">Alto</option>
                    <option value="Medio">Medio</option>
                    <option value="Bajo" selected>Bajo</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="cerrarModalRiesgo()" style="padding: 0.4rem 1rem; background: #e2e8f0; color: #334155; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Cancelar</button>
            <button type="button" onclick="insertarRiesgo()" style="padding: 0.4rem 1rem; background: #2563eb; color: #ffffff; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">Insertar a la Tabla</button>
        </div>
    </div>
</div>

<script>
function abrirModalRiesgo() {
    document.getElementById('modalRiesgo').classList.add('active');
}

function cerrarModalRiesgo() {
    document.getElementById('modalRiesgo').classList.remove('active');
    // Limpiar modal inputs
    document.getElementById('m_id').value = '';
    document.getElementById('m_categoria').value = '';
    document.getElementById('m_descripcion').value = '';
    document.getElementById('m_causa').value = '';
    document.getElementById('m_nivel').value = 'Bajo';
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

function insertarRiesgo() {
    const id = document.getElementById('m_id').value.trim();
    const cat = document.getElementById('m_categoria').value.trim();
    const desc = document.getElementById('m_descripcion').value.trim();
    const causa = document.getElementById('m_causa').value.trim();
    const nivel = document.getElementById('m_nivel').value;

    if (!id || !desc) {
        alert('Por favor complete al menos el ID y la Descripción del Riesgo.');
        return;
    }

    const tbody = document.getElementById('tbodyMatriz');
    const rowNoData = document.getElementById('rowNoData');
    if (rowNoData) {
        rowNoData.remove();
    }

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="text-align: center; font-weight: 700; color: #0f172a;">
            ${escapeHtml(id)}
            <input type="hidden" name="matriz_id[]" value="${escapeHtml(id)}">
        </td>
        <td>
            ${escapeHtml(cat)}
            <input type="hidden" name="matriz_categoria[]" value="${escapeHtml(cat)}">
        </td>
        <td>
            ${escapeHtml(desc)}
            <input type="hidden" name="matriz_descripcion[]" value="${escapeHtml(desc)}">
        </td>
        <td>
            ${escapeHtml(causa)}
            <input type="hidden" name="matriz_causa[]" value="${escapeHtml(causa)}">
        </td>
        <td style="text-align: center;">
            <span class="badge-risk-${escapeHtml(nivel)}">${escapeHtml(nivel)}</span>
            <input type="hidden" name="matriz_nivel[]" value="${escapeHtml(nivel)}">
        </td>
        <td style="text-align: center;">
            <button type="button" onclick="eliminarFilaRiesgo(this)" style="background: transparent; border: none; color: #ef4444; cursor: pointer;">
                <i class="ri-delete-bin-line" style="font-size: 1.1rem;"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    cerrarModalRiesgo();
}

function eliminarFilaRiesgo(btn) {
    const row = btn.closest('tr');
    row.remove();

    const tbody = document.getElementById('tbodyMatriz');
    if (tbody.children.length === 0) {
        tbody.innerHTML = `
            <tr id="rowNoData">
                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 1.5rem;">
                    No se han registrado riesgos en la matriz. Haz clic en <strong>Agregar Riesgo</strong>.
                </td>
            </tr>`;
    }
}
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>