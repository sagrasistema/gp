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
        // Manejo de errores silencioso para producción o registro en log
        error_log("Error al recuperar materialidad: " . $e->getMessage());
    }
}

// Si $materialidadData existe, lo asignamos; de lo contrario creamos un objeto vacío con ceros
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
    
    <div style="background: #e2e8f0; padding: 1rem 1.25rem; font-weight: 700; color: #1e293b; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color);">
        <span>Materialidad</span>
        <i class="ri-edit-box-line" style="color: #475569;"></i>
    </div>

    <div style="padding: 1.5rem;">
        
        <!-- Beneficios -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
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
                    <input type="number" step="0.01" name="materialidad[beneficios_monto]" value="<?= htmlspecialchars(number_format((float)($m->beneficios_monto ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

        <!-- Tramo -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 0.75rem; font-size: 0.85rem; color: #64748b; font-weight: 600;">
                <div>Escoger medición empírica:</div>
                <div style="text-align: center;">Cálculo de la importancia relativa</div>
                <div>Observaciones</div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; align-items: center;">
                <div style="font-size: 0.9rem; font-weight: 600; color: #1e293b;">Tramo 5% - 10%*:</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: center;">%</span>
                        <input type="number" step="0.01" name="materialidad[tramo_porc]" value="<?= htmlspecialchars(number_format((float)($m->tramo_porc ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    </div>
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: right;">Monto</span>
                        <input type="number" step="0.01" name="materialidad[tramo_monto]" value="<?= htmlspecialchars(number_format((float)($m->tramo_monto ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;">
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: #64748b; line-height: 1.4;">
                    Ingrese el porcentaje apropiado para aplicar el punto de referencia
                </div>
            </div>
        </div>

        <!-- Importancia relativa inicial -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #334155; font-weight: 600;">Importancia relativa seleccionada*:</label>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="number" step="0.01" name="materialidad[importancia_inicial_monto]" value="<?= htmlspecialchars(number_format((float)($m->importancia_inicial_monto ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

        <!-- Recorte / Ajuste Global -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem; background: #f8fafc; padding: 1rem; border-radius: 8px;">
            <div style="margin-bottom: 1rem;">
                <strong style="font-size: 0.9rem; color: #1e293b; display: block; margin-bottom: 0.25rem;">Ajustar la importancia relativa global *</strong>
            </div>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="font-size: 0.85rem; color: #334155; font-weight: 600;">Recorte</label>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: center;">%</span>
                        <input type="number" step="0.01" name="materialidad[recorte_porc]" value="<?= htmlspecialchars(number_format((float)($m->recorte_porc ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    </div>
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: right;">Monto</span>
                        <input type="number" step="0.01" name="materialidad[recorte_monto]" value="<?= htmlspecialchars(number_format((float)($m->recorte_monto ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Importancia Ajustada -->
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1rem;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="display: block; font-size: 0.85rem; color: #334155; font-weight: 600;">Importancia relativa seleccionada (ajustada):</label>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="number" step="0.01" name="materialidad[importancia_ajustada_monto]" value="<?= htmlspecialchars(number_format((float)($m->importancia_ajustada_monto ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

        <!-- Nivel Minimis -->
        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px;">
            <div style="margin-bottom: 1rem;">
                <strong style="font-size: 0.9rem; color: #1e293b; display: block; margin-bottom: 0.25rem;">Nivel de mínimo registro de incorrecciones (opcional) *</strong>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center; margin-bottom: 1rem;">
                <div>
                    <label style="font-size: 0.85rem; color: #334155; font-weight: 600;">Nivel minimis registro de incorrecciones</label>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: center;">%</span>
                        <input type="number" step="0.01" name="materialidad[minimis_porc]" value="<?= htmlspecialchars(number_format((float)($m->minimis_porc ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center;">
                    </div>
                    <div>
                        <span style="font-size: 0.70rem; color: #64748b; display: block; text-align: right;">Monto</span>
                        <input type="number" step="0.01" name="materialidad[minimis_monto]" value="<?= htmlspecialchars(number_format((float)($m->minimis_monto ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right;">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: center;">
                <div>
                    <label style="font-size: 0.85rem; color: #334155; font-weight: 600;">Nivel minimis registro de incorrecciones (Secundario):</label>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: #64748b; display: block; text-align: right; margin-bottom: 0.2rem;">Monto</span>
                    <input type="number" step="0.01" name="materialidad[minimis_secundario_monto]" value="<?= htmlspecialchars(number_format((float)($m->minimis_secundario_monto ?? 0), 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; text-align: right; font-weight: 600;">
                </div>
            </div>
        </div>

    </div>
</div>
<?php endif; ?>