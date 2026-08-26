<?php
declare(strict_types=1);

// Incluir la lógica backend y obtención de variables
require_once __DIR__ . '/f-carta.php';

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

<div class="view-container">
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

    <?php if (isset($errorMessage) && $errorMessage !== null): ?>
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
            <?php if (isset($permisosModulo6['puede_editar']) && $permisosModulo6['puede_editar'] == 1): ?>
                <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="ri-save-line"></i> Guardar Cambios
                </button>
            <?php endif; ?>
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
</script>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>