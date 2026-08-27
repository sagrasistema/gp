<?php
require_once 'f-carta.php';

$pageTitle = "Carta de Contratación y Presupuestación";
include '../main/h.php';
// Validar ID de Términos
$terminoId = filter_input(INPUT_GET, 'terminoId', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'termino_id', FILTER_VALIDATE_INT);

if (!$terminoId || $terminoId <= 0) {
    http_response_code(400);
    die("Error: Identificador de Términos y Condiciones no especificado.");
}

// Cargar datos previos
$stmtItem = $pdo->prepare("SELECT datos_json FROM terminos_condiciones_items WHERE termino_id = :termino_id AND item_key = 'carta_contratacion'");
$stmtItem->execute([':termino_id' => $terminoId]);
$itemData = $stmtItem->fetch(PDO::FETCH_OBJ);
$savedData = ($itemData && !empty($itemData->datos_json)) ? json_decode($itemData->datos_json, true) : [];

// Mapeo de pruebas guardadas previamente [prueba_id => horas_base]
$pruebasConfiguradasVal = $savedData['pruebas_configuradas'] ?? [];
$horasAsignadasMap = [];
if (is_array($pruebasConfiguradasVal)) {
    foreach ($pruebasConfiguradasVal as $pConf) {
        $horasAsignadasMap[$pConf['prueba_id']] = $pConf['horas_base'];
    }
}

// Cargar Etapas de Auditoría
$stmtEtapas = $pdo->query("SELECT * FROM audit_etapas ORDER BY id ASC");
$etapas = $stmtEtapas->fetchAll(PDO::FETCH_OBJ);
$etapaActiva = filter_input(INPUT_GET, 'etapa', FILTER_VALIDATE_INT) ?: ($etapas[0]->id ?? 1);
?>
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

    /* Estilos extraídos del módulo de Proyectos */
    .view-container { padding: 0.5rem; }
    .prueba-row-container { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0.75rem; border-bottom: 1px solid var(--border-color); background: #ffffff; gap: 0.5rem; }
    .prueba-title { font-size: 0.8rem; font-weight: 600; color: #334155; flex-grow: 1; display: flex; align-items: center; gap: 0.5rem; }
    .prueba-actions { display: flex; align-items: center; gap: 0.4rem; justify-content: flex-end; }
    
    /* Barra de Navegación por Etapas */
    .project-stages-bar { display: flex; gap: 6px; margin: 8px 0; flex-wrap: wrap; }
    .stage-btn { flex: 1; min-width: 130px; padding: 6px 12px; background-color: #1e3a5f; border: 1px solid #2b4c7e; border-radius: 6px; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 11px; letter-spacing: 0.3px; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s ease-in-out; text-transform: uppercase; cursor: pointer; }
    .stage-btn i { font-size: 13px; color: #00bcd4; }
    .stage-btn:hover { background-color: #2b4c7e; border-color: #00bcd4; }
    .stage-btn.active { background-color: #0f1c2e; border: 1.5px solid #00bcd4; color: #ffffff; box-shadow: 0 2px 8px rgba(0, 188, 212, 0.2); }
    .stage-btn.active i { color: #00bcd4; }

    .input-horas-prueba { width: 75px; padding: 0.2rem 0.4rem; font-size: 0.78rem; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; text-align: right; color: #0f172a; }
    .input-horas-prueba:focus { border-color: #00bcd4; outline: none; box-shadow: 0 0 0 2px rgba(0, 188, 212, 0.2); }
    .badge-subtotal-cat { font-size: 0.68rem; background: #e0f2fe; color: #0369a1; padding: 0.15rem 0.4rem; border-radius: 4px; font-weight: 700; border: 1px solid #bae6fd; }
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
<!-- CONTENEDOR DE 12 COLUMNAS EN UNA SOLA LÍNEA -->
<div class="row g-3 mb-3">

    <!-- 1. DEFINICIÓN DE PARÁMETROS: FRECUENCIA Y PERIODOS (6 COLUMNAS) -->
    <div class="col-md-6">
        <div class="card-panel" style="height: 100%;">
            <div class="card-panel-header"><i class="ri-calendar-2-line"></i> 1. Definición de Parámetros de Frecuencia</div>
            <div class="card-panel-body">
                <div style="display: grid; grid-template-columns: 160px 1fr; gap: 1rem; align-items: start;">
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
                                    <th style="width: 100px;">Frecuencia</th>
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
    </div>

    <!-- 2. CARGA DE BALANCE PRELIMINAR (6 COLUMNAS) -->
    <div class="col-md-6">
        <div class="card-panel" style="height: 100%;">
            <div class="card-panel-header"><i class="ri-file-excel-2-line"></i> 2. Carga de Balance Preliminar</div>
            <div class="card-panel-body" style="display: flex; align-items: center; min-height: 120px;">
                <div class="file-input-wrapper" style="border-bottom: none; margin-bottom: 0; width: 100%;">
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
    </div>

</div>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------>
<!-- ===================================================================== -->
        <!-- BLOQUE 2: VISTA SELECCIÓN DE PRUEBAS Y HORAS (VISUAL DE PROYECTO)     -->
        <!-- ===================================================================== -->
        <div class="card card-custom mb-3" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <h5 style="font-size: 0.9rem; font-weight: 700; color: #1e3a5f; margin: 0; display: flex; align-items: center; gap: 0.35rem;">
                    <i class="ri-list-check-2" style="color: #00bcd4;"></i> 3. Selección Metodológica de Pruebas y Estimación de Horas
                </h5>
                <div style="font-size: 0.8rem; font-weight: 700; background: #f0fdf4; color: #166534; padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid #bbf7d0;">
                    Total Horas Proyecto: <span id="lbl_total_horas_proyecto">0.00</span> hrs
                </div>
            </div>

            <!-- Navegación por Etapas de Auditoría (Filtro por JS) -->
            <div class="project-stages-bar">
                <?php foreach ($etapas as $index => $et): ?>
                    <button type="button" class="stage-btn <?= ($et->id == $etapaActiva) ? 'active' : '' ?>" onclick="switchEtapa(<?= $et->id ?>, this)">
                        <i class="ri-compass-3-line"></i><?= ($index + 1) ?>. <?= htmlspecialchars($et->nombre, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Contenedores de Acordeones por Etapa -->
            <?php foreach ($etapas as $et): ?>
                <div class="etapa-content-block" id="etapa_block_<?= $et->id ?>" style="display: <?= ($et->id == $etapaActiva) ? 'block' : 'none' ?>;">
                    <div class="accordion-container">
                        <?php
                        // Consultar Categorías pertenecientes a la etapa
                        $stmtCat = $pdo->prepare("SELECT * FROM audit_categorias WHERE etapa_id = :etapaId ORDER BY orden ASC");
                        $stmtCat->execute([':etapaId' => $et->id]);
                        $categories = $stmtCat->fetchAll(PDO::FETCH_OBJ);

                        $catIndex = 0;
                        foreach ($categories as $cat):
                            $letraCat = chr(65 + ($catIndex % 26));
                            $catIndex++;

                            // Consultar Pruebas de la categoría (usando la tabla máster audit_pruebas)
                            $stmtP = $pdo->prepare("SELECT * FROM audit_pruebas WHERE categoria_id = :catId ORDER BY orden ASC");
                            $stmtP->execute([':catId' => $cat->id]);
                            $pruebas = $stmtP->fetchAll(PDO::FETCH_OBJ);
                        ?>
                            <div class="accordion-item" style="margin-bottom: 0.4rem; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
                                <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 0.5rem 0.75rem; font-size: 0.82rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                    <span><?= $letraCat ?>. <?= htmlspecialchars($cat->nombre, ENT_QUOTES, 'UTF-8') ?></span>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span class="badge-subtotal-cat cat-subtotal-span" data-cat-id="<?= $cat->id ?>">0.00 hrs</span>
                                        <i class="ri-arrow-down-s-line"></i>
                                    </div>
                                </div>

                                <div class="accordion-content" style="display: none; background: #fff;">
                                    <?php 
                                    $pruebaNum = 1;
                                    foreach ($pruebas as $pr): 
                                        $isChecked = isset($horasAsignadasMap[$pr->id]);
                                        $horasVal = $horasAsignadasMap[$pr->id] ?? 0;
                                    ?>
                                        <div class="prueba-row-container">
                                            <div class="prueba-title">
                                                <input type="checkbox" name="pruebas_seleccionadas[]" value="<?= $pr->id ?>" class="chk-prueba" <?= $isChecked ? 'checked' : '' ?> onchange="recalcularTotalHorasGlobal()" style="cursor: pointer; width: 15px; height: 15px;">
                                                <span><?= $pruebaNum ?>. <?= htmlspecialchars($pr->nombre, ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>

                                            <div class="prueba-actions">
                                                <span style="font-size: 0.7rem; color: #64748b; font-weight: 600;">Horas Base:</span>
                                                <input type="number" step="0.5" min="0" name="horas_prueba[<?= $pr->id ?>]" value="<?= $horasVal ?>" class="input-horas-prueba" oninput="recalcularTotalHorasGlobal()">
                                            </div>
                                        </div>
                                    <?php 
                                        $pruebaNum++;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ===================================================================== -->
        <!-- BLOQUE 3: CARTA, PRESUPUESTO Y DETALLES ECONÓMICOS                     -->
        <!-- ===================================================================== -->
        <div class="card card-custom mb-3" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem;">
            <h5 style="font-size: 0.9rem; font-weight: 700; color: #1e3a5f; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem;">
                <i class="ri-money-dollar-circle-line" style="color: #2563eb;"></i> 3. Propuesta Económica y Firmas
            </h5>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem; font-size: 0.8rem; margin-bottom: 0.5rem;">
                <div>
                    <label style="font-weight: 600; color: #475569;">Horas Contempladas Propuesta</label>
                    <input type="number" step="0.01" name="horas_contempladas" id="horas_contempladas" class="form-control" value="<?= htmlspecialchars((string)($savedData['horas_contempladas'] ?? '0.00')) ?>" style="padding: 0.3rem; font-size: 0.8rem;">
                </div>
                <div>
                    <label style="font-weight: 600; color: #475569;">Monto Propuesta Comercial</label>
                    <input type="text" name="monto_propuesta" class="form-control" value="<?= htmlspecialchars((string)($savedData['monto_propuesta'] ?? '0,00')) ?>" style="padding: 0.3rem; font-size: 0.8rem;">
                </div>
                <div>
                    <label style="font-weight: 600; color: #475569;">Moneda</label>
                    <select name="moneda" class="form-control" style="padding: 0.3rem; font-size: 0.8rem;">
                        <option value="USD" <?= (($savedData['moneda'] ?? '') === 'USD') ? 'selected' : '' ?>>USD ($)</option>
                        <option value="BS" <?= (($savedData['moneda'] ?? '') === 'BS') ? 'selected' : '' ?>>BS (Bs.)</option>
                        <option value="EUR" <?= (($savedData['moneda'] ?? '') === 'EUR') ? 'selected' : '' ?>>EUR (€)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; font-size: 0.8rem;">
                <div>
                    <label style="font-weight: 600; color: #475569;">Adjuntar Carta de Contratación (PDF)</label>
                    <input type="file" name="archivo_carta" class="form-control-file border rounded p-1" accept=".pdf" style="font-size: 0.75rem; width: 100%;">
                </div>
                <div>
                    <label style="font-weight: 600; color: #475569;">Adjuntar Presupuesto (Excel/PDF)</label>
                    <input type="file" name="archivo_presupuesto" class="form-control-file border rounded p-1" accept=".xlsx, .xls, .pdf" style="font-size: 0.75rem; width: 100%;">
                </div>
            </div>
        </div>

        <!-- Botón Guardar -->
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.82rem; background: #00bcd4; border: none; font-weight: 700;">
                <i class="ri-save-3-line"></i> Guardar Carta de Contratación
            </button>
        </div>
    </form>
</div>
<script>
// Cambiar visualmente de Etapa (Navegación Rápida)
function switchEtapa(etapaId, btnElement) {
    document.querySelectorAll('.stage-btn').forEach(btn => btn.classList.remove('active'));
    btnElement.classList.add('active');

    document.querySelectorAll('.etapa-content-block').forEach(block => block.style.display = 'none');
    const activeBlock = document.getElementById('etapa_block_' + etapaId);
    if (activeBlock) {
        activeBlock.style.display = 'block';
    }
}

// Control del Acordeón por Categorías
function toggleAccordion(headerElement) {
    const content = headerElement.nextElementSibling;
    const icon = headerElement.querySelector('.ri-arrow-down-s-line, .ri-arrow-up-s-line');

    if (content.style.display === "none" || content.style.display === "") {
        content.style.display = "block";
        if (icon) {
            icon.classList.remove('ri-arrow-down-s-line');
            icon.classList.add('ri-arrow-up-s-line');
        }
    } else {
        content.style.display = "none";
        if (icon) {
            icon.classList.remove('ri-arrow-up-s-line');
            icon.classList.add('ri-arrow-down-s-line');
        }
    }
}

// Recalcular Subtotales de Categorías y Total General del Proyecto (Horas Base * Frecuencia)
function recalcularTotalHorasGlobal() {
    let horasBaseAcumuladas = 0;
    const frecInput = document.getElementById('frecuencia_cantidad');
    const frecuencia = parseFloat(frecInput ? frecInput.value : 1) || 1;

    document.querySelectorAll('.accordion-item').forEach(item => {
        let catSubtotal = 0;
        item.querySelectorAll('.prueba-row-container').forEach(row => {
            const chk = row.querySelector('.chk-prueba');
            const inputHrs = row.querySelector('.input-horas-prueba');

            if (chk && chk.checked && inputHrs) {
                const hrs = parseFloat(inputHrs.value) || 0;
                catSubtotal += hrs;
            }
        });

        // Actualizar Subtotal Categoría
        const badgeCat = item.querySelector('.cat-subtotal-span');
        if (badgeCat) {
            badgeCat.textContent = catSubtotal.toFixed(2) + ' hrs';
        }

        horasBaseAcumuladas += catSubtotal;
    });

    const totalCalculado = horasBaseAcumuladas * frecuencia;
    
    // Actualizar indicador superior y campo de propuesta
    document.getElementById('lbl_total_horas_proyecto').textContent = totalCalculado.toFixed(2);
    const inputHorasPropuesta = document.getElementById('horas_contempladas');
    if (inputHorasPropuesta && (!inputHorasPropuesta.value || inputHorasPropuesta.value == '0.00')) {
        inputHorasPropuesta.value = totalCalculado.toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', recalcularTotalHorasGlobal);
</script>
<!------------------------------------------------------------------------------------------------------------------------------------------------------------>
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