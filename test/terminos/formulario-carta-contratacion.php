<?php
require_once 'f-carta.php';

$pageTitle = "Carta de Contratación y Presupuestación";
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
    .table-custom {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
    }
    .table-custom th, .table-custom td {
        border: 1px solid #cbd5e1;
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
    }
    .table-custom th {
        background: #f1f5f9;
        color: #334155;
        font-weight: 600;
    }
</style>

<div class="view-container">
    <!-- CABECERA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0;">
                Carta de Contratación y Presupuestación de Horas
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

        <!-- 1. DEFINICIÓN DE PARÁMETROS: FRECUENCIA Y PERIODOS -->
        <div class="card-panel">
            <div class="card-panel-header"><i class="ri-calendar-2-line"></i> 1. Definición de Parámetros de Frecuencia</div>
            <div class="card-panel-body">
                <div style="display: grid; grid-template-columns: 200px 1fr; gap: 1.5rem; align-items: start;">
                    <div>
                        <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.35rem; font-weight: 600;">
                            Frecuencia (1 a 12):
                        </label>
                        <select name="frecuencia_cantidad" id="frecuencia_cantidad" class="form-control-line" onchange="renderTablaPeriodos(); recalcularTotales();" style="font-weight: 700; color: #2563eb;">
                            <?php for ($f = 1; $f <= 12; $f++): ?>
                                <option value="<?= $f ?>" <?= $f === $frecuenciaCantidadVal ? 'selected' : '' ?>><?= $f ?> <?= $f === 1 ? 'Revisión' : 'Revisiones' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.35rem; font-weight: 600;">
                            Periodos de Revisión:
                        </label>
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Frecuencia</th>
                                    <th>Periodo Exacto / Descripción</th>
                                </tr>
                            </thead>
                            <tbody id="container_periodos">
                                <!-- Render JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. CARGA DE BALANCE PRELIMINAR -->
        <div class="card-panel">
            <div class="card-panel-header"><i class="ri-file-excel-2-line"></i> 2. Carga de Balance Preliminar</div>
            <div class="card-panel-body">
                <div class="file-input-wrapper" style="border-bottom: none; margin-bottom: 0;">
                    <label for="input_balance_preliminar" class="btn-attach" title="Cargar Balance Preliminar">
                        <i class="ri-upload-2-line" style="font-size: 1.2rem;"></i>
                    </label>
                    <input type="file" id="input_balance_preliminar" name="balance_preliminar" accept=".xls,.xlsx,.pdf" style="display: none;" onchange="updateFileName(this, 'label_balance_preliminar')">
                    <span id="label_balance_preliminar" class="file-custom-label" onclick="document.getElementById('input_balance_preliminar').click();">
                        <?= !empty($balancePreliminarVal) ? '📊 ' . basename($balancePreliminarVal) : 'Adjuntar Balance Preliminar para detección de Rubros (.xlsx, .xls, .pdf)' ?>
                    </span>
                    <?php if (!empty($balancePreliminarVal)): ?>
                        <a href="../<?= htmlspecialchars($balancePreliminarVal, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-action-view">
                            <i class="ri-external-link-line"></i> Ver Archivo
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3. SELECCIÓN DE PRUEBAS Y CÁLCULO DE HORAS -->
        <div class="card-panel">
            <div class="card-panel-header"><i class="ri-list-check"></i> 3. Selección de Pruebas y Presupuestación de Horas</div>
            <div class="card-panel-body">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">Sel.</th>
                            <th style="width: 200px;">Rubro Detectado</th>
                            <th>Prueba Metodológica</th>
                            <th style="width: 130px; text-align: right;">Horas Base</th>
                            <th style="width: 140px; text-align: right;">Horas x Frecuencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($catalogoPruebas)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: #64748b;">No se han detectado rubros. Cargue el balance preliminar.</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            foreach ($catalogoPruebas as $prueba): 
                                $pId = (int)$prueba['id'];
                                $configGuardada = array_filter($pruebasConfiguradasVal, fn($i) => (int)$i['prueba_id'] === $pId);
                                $isSelected = !empty($configGuardada);
                                $itemSaved = reset($configGuardada);
                                $hrsBase = $isSelected ? (float)$itemSaved['horas_base'] : (float)($prueba['horas_base'] ?? 0);
                            ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" name="pruebas_seleccionadas[]" value="<?= $pId ?>" <?= $isSelected ? 'checked' : '' ?> onchange="recalcularTotales();">
                                    </td>
                                    <td><strong><?= htmlspecialchars($prueba['rubro'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= htmlspecialchars($prueba['codigo_prueba'] . ' - ' . $prueba['nombre_prueba'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <input type="text" name="horas_prueba[<?= $pId ?>]" class="form-control-line hora-base-input" data-pid="<?= $pId ?>" value="<?= formatMontoVe($hrsBase) ?>" style="text-align: right;" onblur="formatInputVe(this); recalcularTotales();">
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #1e293b;" id="total_frecuencia_<?= $pId ?>">
                                        0,00
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. SECCIÓN CARTA DE CONTRATACIÓN Y PRESUPUESTO PROYECTO -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <!-- COLUMNA IZQUIERDA: CARTA DE CONTRATACIÓN -->
            <div class="card-panel">
                <div class="card-panel-header">Carta de Contratación</div>
                <div class="card-panel-body">
                    <div class="file-input-wrapper">
                        <label for="input_carta" class="btn-attach" title="Adjuntar Archivo PDF">
                            <i class="ri-attachment-line" style="font-size: 1.2rem;"></i>
                        </label>
                        <input type="file" id="input_carta" name="archivo_carta" accept=".pdf,application/pdf" style="display: none;" onchange="updateFileName(this, 'label_carta')">
                        <span id="label_carta" class="file-custom-label" onclick="document.getElementById('input_carta').click();">
                            <?= !empty($archivoCartaVal) ? '📄 ' . basename($archivoCartaVal) : 'Adjuntar o cambiar Carta de Contratación (Sólo PDF)' ?>
                        </span>

                        <?php if (!empty($archivoCartaVal)): ?>
                            <a href="../<?= htmlspecialchars($archivoCartaVal, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-action-view">
                                <i class="ri-external-link-line"></i> Ver PDF
                            </a>
                        <?php endif; ?>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.25rem;">Fecha Solicitud de la Carta:</label>
                            <input type="date" name="fecha_solicitud" class="form-control-line" value="<?= htmlspecialchars($fechaSolicitudVal, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; color: #475569; margin-bottom: 0.25rem;">Recibida:</label>
                            <input type="date" name="fecha_recibida" class="form-control-line" value="<?= htmlspecialchars($fechaRecibidaVal, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <span style="font-size: 0.875rem; color: #334155; font-weight: 500;">¿Los términos del contrato fueron aprobados?</span>
                        <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem; align-items: center;">
                            <label style="font-size: 0.875rem; color: #475569; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                <input type="radio" name="terminos_aprobados" value="no" <?= $terminosAprobadosVal === 'no' ? 'checked' : '' ?>> No
                            </label>
                            <label style="font-size: 0.875rem; color: #475569; display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                <input type="radio" name="terminos_aprobados" value="si" <?= $terminosAprobadosVal === 'si' ? 'checked' : '' ?>> Sí
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: PRESUPUESTO Y TOTALIZACIÓN -->
            <div class="card-panel">
                <div class="card-panel-header">Presupuesto del Proyecto</div>
                <div class="card-panel-body">
                    <div class="file-input-wrapper">
                        <label for="input_presupuesto" class="btn-attach" title="Adjuntar Excel de Presupuesto">
                            <i class="ri-attachment-line" style="font-size: 1.2rem;"></i>
                        </label>
                        <input type="file" id="input_presupuesto" name="archivo_presupuesto" accept=".xls,.xlsx" style="display: none;" onchange="updateFileName(this, 'label_presupuesto')">
                        <span id="label_presupuesto" class="file-custom-label" onclick="document.getElementById('input_presupuesto').click();">
                            <?= !empty($archivoPresupuestoVal) ? '📊 ' . basename($archivoPresupuestoVal) : 'Adjuntar Presupuesto del Proyecto (Sólo Excel)' ?>
                        </span>

                        <?php if (!empty($archivoPresupuestoVal)): ?>
                            <a href="../<?= htmlspecialchars($archivoPresupuestoVal, ENT_QUOTES, 'UTF-8') ?>" download class="btn-action-view">
                                <i class="ri-download-line"></i> Descargar Excel
                            </a>
                        <?php endif; ?>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #475569; margin-bottom: 0.25rem;">Total Horas Presupuestadas</label>
                            <input type="text" id="horas_contempladas" name="horas_contempladas" class="form-control-line" style="text-align: right; font-weight: 700; color: #2563eb;" value="<?= htmlspecialchars(formatMontoVe($horasContempladasVal), ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #475569; margin-bottom: 0.25rem;">Monto Total Propuesta</label>
                            <input type="text" name="monto_propuesta" class="form-control-line" style="text-align: right;" value="<?= htmlspecialchars(formatMontoVe($montoPropuestaVal), ENT_QUOTES, 'UTF-8') ?>" placeholder="0,00" onblur="formatInputVe(this)">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.75rem; color: #475569; margin-bottom: 0.25rem;">Moneda</label>
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

        <!-- OBSERVACIONES -->
        <div class="card-panel">
            <div class="card-panel-header" style="background: #f1f5f9; color: #334155; border-bottom: 1px solid #cbd5e1;">
                <i class="ri-list-check-2" style="color: #16a34a;"></i> Observaciones durante la negociación
            </div>
            <div class="card-panel-body" style="padding: 0;">
                <textarea name="observaciones" rows="3" placeholder="Ingrese las observaciones..." style="width: 100%; border: none; padding: 1rem; font-size: 0.9rem; outline: none; background: #fafafa;"><?= htmlspecialchars($observacionesVal, ENT_QUOTES, 'UTF-8') ?></textarea>
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
const periodosGuardados = <?= json_encode($periodosVal, JSON_UNESCAPED_UNICODE) ?>;

function renderTablaPeriodos() {
    const cantidad = parseInt(document.getElementById('frecuencia_cantidad').value) || 1;
    const container = document.getElementById('container_periodos');
    container.innerHTML = '';

    for (let i = 1; i <= cantidad; i++) {
        const val = periodosGuardados[i] ? periodosGuardados[i] : '';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-weight: 600; color: #475569;">Frecuencia ${i}</td>
            <td>
                <input type="text" name="periodos[${i}]" value="${val}" placeholder="Ej: Q${i} 2026 / Mes ${i}" required class="form-control-line">
            </td>
        `;
        container.appendChild(tr);
    }
}

function parseMontoJs(str) {
    if (!str) return 0;
    return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
}

function formatMontoJs(num) {
    return num.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalcularTotales() {
    const frecuencia = parseInt(document.getElementById('frecuencia_cantidad').value) || 1;
    let totalHorasProyecto = 0;

    const inputs = document.querySelectorAll('.hora-base-input');
    inputs.forEach(input => {
        const pId = input.getAttribute('data-pid');
        const checkbox = document.querySelector(`input[name="pruebas_seleccionadas[]"][value="${pId}"]`);
        const totalTd = document.getElementById(`total_frecuencia_${pId}`);

        if (checkbox && checkbox.checked) {
            const hBase = parseMontoJs(input.value);
            const subtotal = hBase * frecuencia;
            totalHorasProyecto += subtotal;
            if (totalTd) totalTd.textContent = formatMontoJs(subtotal);
        } else {
            if (totalTd) totalTd.textContent = '0,00';
        }
    });

    document.getElementById('horas_contempladas').value = formatMontoJs(totalHorasProyecto);
}

function updateFileName(input, labelId) {
    const label = document.getElementById(labelId);
    if (input.files && input.files.length > 0) {
        label.textContent = "📄 " + input.files[0].name;
        label.style.color = "#0f172a";
        label.style.fontWeight = "600";
    }
}

function formatInputVe(input) {
    let val = input.value.trim();
    if (!val) return;
    if (val.includes('.') && !val.includes(',')) val = val.replace('.', ',');
    let parts = val.split(',');
    let integerPart = parts[0].replace(/\D/g, '');
    let decimalPart = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '00';
    if (decimalPart.length === 1) decimalPart += '0';
    if (integerPart === '') integerPart = '0';
    integerPart = parseInt(integerPart, 10).toLocaleString('de-DE');
    input.value = integerPart + ',' + decimalPart;
}

document.addEventListener('DOMContentLoaded', () => {
    renderTablaPeriodos();
    recalcularTotales();
});
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>