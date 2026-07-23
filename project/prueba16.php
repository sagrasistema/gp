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
    'tramo_porc'                 => '5.00', // Valor por defecto dentro del rango válido
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
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: right;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #334155;">Beneficios operaciones continuas antes de impuestos:</label>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="text" id="beneficios_monto" name="materialidad[beneficios_monto]" value="<?= htmlspecialchars(number_format((float)($m->beneficios_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

        <!-- Fila 2: Escoger medición empírica (Tramo 5% - 10%) -->
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
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: center;">% (5 - 10)</span>
                        <input type="text" id="tramo_porc" name="materialidad[tramo_porc]" min="5" max="10" value="<?= htmlspecialchars(number_format((float)($m->tramo_porc ?? 5), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    </div>
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: right;">Monto</span>
                        <input type="text" id="tramo_monto" name="materialidad[tramo_monto]" value="<?= htmlspecialchars(number_format((float)($m->tramo_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;" readonly>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: #64748b; line-height: 1.4;">
                    Debe ingresar un porcentaje entre 5% y 10%. Si sale del rango, se restablecerá a 0.
                </div>
            </div>
        </div>

        <!-- Fila 3: Importancia relativa seleccionada -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.25rem;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #334155; font-weight: 600;">Importancia relativa seleccionada*:</label>
                    <p style="font-size: 0.75rem; color: #64748b; margin: 0.25rem 0 0 0; line-height: 1.4;">
                        Seleccione la cifra de la importancia relativa o materialidad. Este importe habrá que ajustarlo para obtener la "materialidad ajustada".
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
                    Se trata de corregir la materialidad inicial teniendo en cuenta factores cualitativos de carácter general:<br>
                    - Riesgos inherentes derivados de la naturaleza de la actividad.<br>
                    - Evaluación global del control interno.<br>
                    Estas correcciones no deben suponer una reducción mayor al 25% ni aumentar la materialidad.
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

        <!-- Fila 6: Nivel de mínimo registro de incorrecciones (Norma NIA 450 / De Minimis) -->
        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
            <div style="margin-bottom: 1rem;">
                <strong style="font-size: 0.9rem; color: #1e293b; display: block; margin-bottom: 0.25rem;">Nivel de mínimo registro de incorrecciones (Norma NIA 450) *</strong>
                <p style="font-size: 0.8rem; color: #475569; margin: 0; line-height: 1.4;">
                    Umbral o nivel <em>De Minimis</em> conforme a la <strong>NIA 450</strong> (Evaluación de las incorrecciones identificadas durante la auditoría). Establece el límite por debajo del cual las diferencias acumuladas se consideran claramente insignificantes.
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center; margin-bottom: 1rem;">
                <div>
                    <label style="font-size: 0.85rem; color: #334155; font-weight: 600;">Nivel minimis registro de incorrecciones</label>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: center;">%</span>
                        <input type="text" id="minimis_porc" name="materialidad[minimis_porc]" value="<?= htmlspecialchars(number_format((float)($m->minimis_porc ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    </div>
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: right;">Monto</span>
                        <input type="text" id="minimis_monto" name="materialidad[minimis_monto]" value="<?= htmlspecialchars(number_format((float)($m->minimis_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="font-size: 0.85rem; color: #334155; font-weight: 600;">Nivel minimis registro de incorrecciones (Secundario):</label>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="text" id="minimis_secundario_monto" name="materialidad[minimis_secundario_monto]" value="<?= htmlspecialchars(number_format((float)($m->minimis_secundario_monto ?? 0), 2, ',', '.'), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Script con validación de rango (5% - 10%) y reseteo a 0 al perder el foco si no cumple -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inputBeneficios       = document.getElementById('beneficios_monto');
    const inputTramoPorc        = document.getElementById('tramo_porc');
    const inputTramoMonto       = document.getElementById('tramo_monto');
    const inputImportancia      = document.getElementById('importancia_inicial_monto');
    const inputRecortePorc      = document.getElementById('recorte_porc');
    const inputRecorteMonto     = document.getElementById('recorte_monto');
    const inputImportanciaAjust = document.getElementById('importancia_ajustada_monto');
    const inputMinimisPorc      = document.getElementById('minimis_porc');
    const inputMinimisMonto     = document.getElementById('minimis_monto');
    const inputMinimisSec       = document.getElementById('minimis_secundario_monto');

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
        let beneficios = parseVenezuelanNumber(inputBeneficios ? inputBeneficios.value : 0);
        let tramoPorc  = parseVenezuelanNumber(inputTramoPorc ? inputTramoPorc.value : 0);

        // Alerta visual en tiempo real si está fuera de rango mientras se escribe
        if (tramoPorc > 0 && (tramoPorc < 5 || tramoPorc > 10)) {
            inputTramoPorc.style.borderColor = '#ef4444'; 
        } else {
            inputTramoPorc.style.borderColor = '#cbd5e1';
        }

        let tramoMonto = beneficios * (tramoPorc / 100);
        if (inputTramoMonto) {
            inputTramoMonto.value = formatVenezuelanNumber(tramoMonto, 2);
        }

        let importanciaInicial = Math.round(tramoMonto);
        if (inputImportancia) {
            inputImportancia.value = formatVenezuelanNumber(importanciaInicial, 0);
        }

        let recortePorc = parseVenezuelanNumber(inputRecortePorc ? inputRecortePorc.value : 0);
        let recorteMonto = importanciaInicial * (recortePorc / 100);
        if (inputRecorteMonto) {
            inputRecorteMonto.value = formatVenezuelanNumber(recorteMonto, 2);
        }

        let importanciaAjustada = Math.round(importanciaInicial - recorteMonto);
        if (inputImportanciaAjust) {
            inputImportanciaAjust.value = formatVenezuelanNumber(importanciaAjustada, 0);
        }

        let minimisPorc = parseVenezuelanNumber(inputMinimisPorc ? inputMinimisPorc.value : 0);
        let minimisMonto = importanciaInicial * (minimisPorc / 100);
        if (inputMinimisMonto) {
            inputMinimisMonto.value = formatVenezuelanNumber(minimisMonto, 2);
        }
    }

    const inputsEscucha = [
        inputBeneficios, inputTramoPorc, inputRecortePorc, 
        inputImportancia, inputMinimisPorc, inputMinimisSec
    ];

    inputsEscucha.forEach(input => {
        if (input) {
            input.addEventListener('input', recalcularTodo);
        }
    });

    inputsEscucha.forEach(input => {
        if (input) {
            input.addEventListener('blur', function() {
                let val = parseVenezuelanNumber(this.value);
                let decimals = 2;
                
                if (this.id === 'importancia_inicial_monto' || this.id === 'importancia_ajustada_monto') {
                    decimals = 0;
                } else if (this.id === 'tramo_porc') {
                    decimals = 2;
                    // RESTRICCIÓN: Si al salir del campo no está entre 5 y 10, se fuerza a 0
                    if (val < 5 || val > 10) {
                        val = 0;
                        this.style.borderColor = '#cbd5e1'; // Limpiar borde de alerta
                    }
                } else if (this.id === 'recorte_porc' || this.id === 'minimis_porc') {
                    decimals = 2;
                }
                
                this.value = formatVenezuelanNumber(val, decimals);
                recalcularTodo();
            });
        }
    });
});
</script>
<?php endif; ?>