<?php
declare(strict_types=1);

// 1. Inicializar la variable por defecto
$materialidadData = null;

// 2. Verificar que estemos en la prueba 16 y que las variables de identificación existan
if (isset($pdo, $proyectoId, $pruebaId) && (int)$pruebaId === 16) {
    try {
        $stmtMatGet = $pdo->prepare("
            SELECT * FROM proyecto_materialidad 
            WHERE proyecto_id = :proj AND prueba_id = :pr 
            LIMIT 1
        ");
        $stmtMatGet->execute([
            ':proj' => (int)$proyectoId,
            ':pr'   => (int)$pruebaId
        ]);
        
        // Obtener como objeto para facilitar la lectura en la vista
        $materialidadData = $stmtMatGet->fetch(PDO::FETCH_OBJ);
        
    } catch (PDOException $e) {
        // Manejo de errores silencioso para registro en log
        error_log("Error al recuperar materialidad: " . $e->getMessage());
    }
}

// Asegurar valores por defecto si aún no existen registros guardados
$m = $materialidadData ?? (object)[
    'beneficios_monto'           => '0.00',
    'tramo_porc'                 => '0.00',
    'tramo_monto'                => '0.00',
    'importancia_inicial_monto'  => '0.00',
    'recorte_porc'               => '0.00',
    'recorte_monto'              => '0.00',
    'importancia_ajustada_monto' => '0.00',
    'minimis_porc'               => '0.00',
    'minimis_monto'              => '0.00',
    'minimis_secundario_monto'   => '0.00'
];

if ((int)$pruebaId === 16):
?>
<div style="margin-top: 2.5rem; margin-bottom: 1.5rem; background: #ffffff; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    
    <!-- Cabecera -->
    <div style="background: #e2e8f0; padding: 1rem 1.25rem; font-weight: 700; color: #1e293b; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color);">
        <span>Materialidad</span>
        <i class="ri-edit-box-line" style="color: #475569;"></i>
    </div>

    <div style="padding: 1.5rem;">
        
        <!-- Fila 1: Punto de referencia y Beneficios -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; font-size: 0.9rem;">
                <div><span style="color: #64748b; font-weight: 600;">Punto de referencia:</span></div>
                <div style="color: #1e293b; font-weight: 500;">Empresas con beneficios normales</div>
            </div>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #334155;">Beneficios operaciones continuas antes de impuestos:</label>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="text" id="beneficios_monto" name="materialidad[beneficios_monto]" value="<?= htmlspecialchars(number_format((float)($m->beneficios_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

        <!-- Fila 2: Escoger medición empírica (Tramo) -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 0.75rem; font-size: 0.85rem; color: #64748b; font-weight: 600;">
                <div>Escoger medición empírica:</div>
                <div style="text-align: center;">Cálculo de la importancia relativa</div>
                <div>Observaciones</div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; align-items: center;">
                <div style="font-size: 0.9rem; font-weight: 600; color: #1e293b;">
                    Tramo 5% - 10%*:
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: center;">%</span>
                        <input type="text" id="tramo_porc" name="materialidad[tramo_porc]" value="<?= htmlspecialchars(number_format((float)($m->tramo_porc ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    </div>
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: right;">Monto</span>
                        <input type="text" id="tramo_monto" name="materialidad[tramo_monto]" value="<?= htmlspecialchars(number_format((float)($m->tramo_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;" readonly>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: #64748b; line-height: 1.4;">
                    Ingrese el porcentaje apropiado (medición empírica) para aplicar el punto de referencia
                </div>
            </div>
        </div>

        <!-- Fila 3: Importancia relativa seleccionada -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #334155; font-weight: 600;">Importancia relativa seleccionada*:</label>
                    <p style="font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0 0; line-height: 1.4;">
                        Seleccione la cifra de la importancia relativa o materialidad. Este importe habrá que ajustarlo para obtener la "materialidad ajustada". Recuerde que el auditor es el responsable de decidir el importe de la importancia relativa o materialidad.
                    </p>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="text" id="importancia_inicial_monto" name="materialidad[importancia_inicial_monto]" value="<?= htmlspecialchars(number_format((float)($m->importancia_inicial_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

        <!-- Fila 4: Ajustar la importancia relativa global -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem; background: #f8fafc; padding: 1rem; border-radius: 8px;">
            <div style="margin-bottom: 1rem;">
                <strong style="font-size: 0.9rem; color: #1e293b; display: block; margin-bottom: 0.25rem;">Ajustar la importancia relativa global *</strong>
                <p style="font-size: 0.8rem; color: #475569; margin: 0; line-height: 1.4;">
                    Se trata de corregir la materialidad inicial teniendo en cuenta factores cualitativos de carácter general, como los siguientes:<br>
                    - Riesgos inherentes derivados de la "naturaleza de la actividad" de la entidad.<br>
                    - Evaluación global del "control interno".<br>
                    Estas correcciones no deben suponer una reducción en la materialidad inicial de más del 25% y en ningún caso deben aumentar la materialidad.
                </p>
            </div>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="font-size: 0.85rem; color: #334155; font-weight: 600;">Recorte</label>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: center;">%</span>
                        <input type="text" id="recorte_porc" name="materialidad[recorte_porc]" value="<?= htmlspecialchars(number_format((float)($m->recorte_porc ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    </div>
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: right;">Monto</span>
                        <input type="text" id="recorte_monto" name="materialidad[recorte_monto]" value="<?= htmlspecialchars(number_format((float)($m->recorte_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;" readonly>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 5: Importancia relativa seleccionada (ajustada) -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #334155; font-weight: 600;">Importancia relativa seleccionada (ajustada):</label>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="text" id="importancia_ajustada_monto" name="materialidad[importancia_ajustada_monto]" value="<?= htmlspecialchars(number_format((float)($m->importancia_ajustada_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;" readonly>
                </div>
            </div>
        </div>

        <!-- Fila 6: Nivel de mínimo registro de incorrecciones -->
        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
            <div style="margin-bottom: 1rem;">
                <strong style="font-size: 0.9rem; color: #1e293b; display: block; margin-bottom: 0.25rem;">Nivel de mínimo registro de incorrecciones (opcional) *</strong>
                <p style="font-size: 0.8rem; color: #475569; margin: 0;">Los factores que influirían al decidir la selección del umbral para el nivel de registro del resumen de ajustes no registrados.</p>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center; margin-bottom: 1rem;">
                <div>
                    <label style="font-size: 0.85rem; color: #334155; font-weight: 600;">Nivel minimis registro de incorrecciones</label>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: center;">%</span>
                        <input type="text" name="materialidad[minimis_porc]" value="<?= htmlspecialchars(number_format((float)($m->minimis_porc ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    </div>
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: right;">Monto</span>
                        <input type="text" name="materialidad[minimis_monto]" value="<?= htmlspecialchars(number_format((float)($m->minimis_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="font-size: 0.85rem; color: #334155; font-weight: 600;">Nivel minimis registro de incorrecciones (Secundario):</label>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="text" name="materialidad[minimis_secundario_monto]" value="<?= htmlspecialchars(number_format((float)($m->minimis_secundario_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Script en tiempo real para automatización de tramos, recortes y materialidad ajustada -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBeneficios       = document.getElementById('beneficios_monto');
    const inputTramoPorc        = document.getElementById('tramo_porc');
    const inputTramoMonto       = document.getElementById('tramo_monto');
    const inputImportancia      = document.getElementById('importancia_inicial_monto');
    const inputRecortePorc      = document.getElementById('recorte_porc');
    const inputRecorteMonto     = document.getElementById('recorte_monto');
    const inputImportanciaAjust = document.getElementById('importancia_ajustada_monto');

    function parseVenezuelanNumber(value) {
        if (!value) return 0;
        let clean = value.toString().replace(/\./g, '').replace(',', '.');
        let num = parseFloat(clean);
        return isNaN(num) ? 0 : num;
    }

    function formatVenezuelanNumber(value, decimals = 2) {
        return value.toLocaleString('es-VE', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function recalcularTodo() {
        // 1. Obtener valores base
        let beneficios = parseVenezuelanNumber(inputBeneficios.value);
        let tramoPorc  = parseVenezuelanNumber(inputTramoPorc.value);

        // 2. Calcular monto del tramo (con 2 decimales)
        let tramoMonto = beneficios * (tramoPorc / 100);
        if (inputTramoMonto) {
            inputTramoMonto.value = formatVenezuelanNumber(tramoMonto, 2);
        }

        // 3. Importancia relativa seleccionada: Monto del tramo redondeado sin decimales
        let importanciaInicial = Math.round(tramoMonto);
        if (inputImportancia) {
            inputImportancia.value = formatVenezuelanNumber(importanciaInicial, 0);
        }

        // 4. Calcular Recorte basado en la importancia inicial
        let recortePorc = parseVenezuelanNumber(inputRecortePorc.value);
        let recorteMonto = importanciaInicial * (recortePorc / 100);
        if (inputRecorteMonto) {
            inputRecorteMonto.value = formatVenezuelanNumber(recorteMonto, 2);
        }

        // 5. Importancia relativa ajustada: Inicial menos recorte, redondeado sin decimales
        let importanciaAjustada = Math.round(importanciaInicial - recorteMonto);
        if (inputImportanciaAjust) {
            inputImportanciaAjust.value = formatVenezuelanNumber(importanciaAjustada, 0);
        }
    }

    // Escuchar eventos de entrada en tiempo real
    if (inputBeneficios) inputBeneficios.addEventListener('input', recalcularTodo);
    if (inputTramoPorc) inputTramoPorc.addEventListener('input', recalcularTodo);
    if (inputRecortePorc) inputRecortePorc.addEventListener('input', recalcularTodo);
    if (inputImportancia) inputImportancia.addEventListener('input', recalcularTodo);

    // Formateo automático al salir de los campos (blur)
    const inputsFormato = [inputBeneficios, inputTramoPorc, inputImportancia, inputRecortePorc];
    inputsFormato.forEach(input => {
        if (input) {
            input.addEventListener('blur', function() {
                let val = parseVenezuelanNumber(this.value);
                let decimals = (this.id === 'importancia_inicial_monto' || this.id === 'tramo_porc') ? 0 : 2;
                if (this.id === 'tramo_porc' || this.id === 'recorte_porc') decimals = 2;
                this.value = formatVenezuelanNumber(val, decimals);
                recalcularTodo();
            });
        }
    });
});
// Añade estas variables y lógica dentro de tu función de recálculo existente:

const inputMinimisPorc  = document.getElementById('minimis_porc');
const inputMinimisMonto = document.getElementById('minimis_monto');
// inputMinimisSecundarioMonto si aplica por ID en tu HTML

function recalcularMinimis() {
    let importanciaInicial = parseVenezuelanNumber(inputImportancia.value);
    let minimisPorc = parseVenezuelanNumber(inputMinimisPorc.value);

    // Calcular el monto minimis basado en el porcentaje y la importancia inicial
    let minimisMonto = importanciaInicial * (minimisPorc / 100);
    
    if (inputMinimisMonto) {
        // Generalmente se muestra con 2 decimales o sin decimales según el estándar de la firma
        inputMinimisMonto.value = formatVenezuelanNumber(minimisMonto, 2);
    }
}

// Escuchar cambios en el porcentaje minimis
if (inputMinimisPorc) {
    inputMinimisPorc.addEventListener('input', recalcularMinimis);
}
</script>
<?php endif; ?>