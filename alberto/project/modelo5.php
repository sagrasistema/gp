<?php
declare(strict_types=1);

/**
 * Componente Modular: Vista del Modelo 5
 * (Sin etiquetas <form>, gestionado por actividades.php)
 */

$currentPruebaId   = $pruebaId ?? filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT) ?? 0;
$currentProyectoId = $proyectoId ?? filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) ?? 0;

$data = $formData5 ?? []; // Inyectado desde el controlador
?>

<div style="background: #ffffff; border: 1px solid #cbd5e1; padding: 1.5rem; margin-bottom: 2rem;">
    
    <div style="background: #e2e8f0; padding: 0.75rem; margin-bottom: 1rem; color: #475569; font-size: 0.9rem;">
        Procedimientos que seran realizados, lineamientos y vinculos:
    </div>

    <!-- Inputs Controladores -->
    <input type="hidden" name="action_type" value="save_modelo_5">
    <input type="hidden" name="proyecto_id" value="<?php echo htmlspecialchars((string)$currentProyectoId, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="prueba_id" value="<?php echo htmlspecialchars((string)$currentPruebaId, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- SECCIÓN 1 Y 2 (Lado a Lado) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- 1. Información de la cuenta -->
        <div>
            <div style="background: #476a7d; color: white; padding: 0.5rem 1rem; margin-bottom: 1rem; font-weight: bold;">1. Información de la Cuenta y Objetivo de la Prueba</div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Partida del estado financiero</label>
                    <input type="text" name="partida_estado_financiero" value="<?php echo htmlspecialchars($data['partida_estado_financiero'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Fecha y período de la prueba</label>
                    <input type="text" name="fecha_periodo_prueba" value="<?php echo htmlspecialchars($data['fecha_periodo_prueba'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0; text-align: right;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Importancia relativa General</label>
                    <input type="text" name="importancia_relativa_general" value="<?php echo htmlspecialchars((string)($data['importancia_relativa_general'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0; text-align: right;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Importancia relativa Planificacion</label>
                    <input type="text" name="importancia_relativa_planificacion" value="<?php echo htmlspecialchars((string)($data['importancia_relativa_planificacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0; text-align: right;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Nivel de registro SUD</label>
                    <input type="text" name="nivel_registro_sud" value="<?php echo htmlspecialchars((string)($data['nivel_registro_sud'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0; text-align: right;">
                </div>
            </div>
        </div>

        <!-- 2. Aserciones -->
        <div>
            <div style="background: #476a7d; color: white; padding: 0.5rem 1rem; margin-bottom: 1rem; font-weight: bold;">2. Aserciones a los Estados Financieros</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <?php 
                $aserciones = [
                    'aser_c' => 'C (Completitud / Completeness)', 'aser_ro' => 'RO  (Derechos y Obligaciones / Rights and Obligations)', 
                    'aser_a' => 'A (Exactitud / Accuracy)', 'aser_va' => 'VA (Valuación y Asignación / Valuation & Allocation)', 
                    'aser_eo' => 'E/O (Existencia y Ocurrencia / Existence & Occurrence)', 'aser_pd' => 'PD (Presentación y Revelación / Presentation & Disclosure):', 
                    'aser_co' => 'CO (Corte / Cut-off)'
                ];
                foreach ($aserciones as $key => $label): 
                    $checked = !empty($data[$key]) ? 'checked' : '';
                ?>
                <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="<?php echo $key; ?>" value="1" <?php echo $checked; ?>> 
                        <span><?php echo $label; ?></span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 3 -->
    <div style="margin-bottom: 2rem;">
        <div style="background: #476a7d; color: white; padding: 0.5rem 1rem; margin-bottom: 1rem; font-weight: bold;">3. Definir Población</div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr 1.5fr 0.5fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            <div>
                <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Definir la Población (informe utilizado)</label>
                <input type="text" name="poblacion_informe" value="<?php echo htmlspecialchars($data['poblacion_informe'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0;">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Fecha y período de la prueba</label>
                <input type="text" name="poblacion_fecha" value="<?php echo htmlspecialchars($data['poblacion_fecha'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0; text-align: right;">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Valor total de la cuenta (población)</label>
                <input type="text" name="poblacion_valor_total" value="<?php echo htmlspecialchars((string)($data['poblacion_valor_total'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0; text-align: right;">
            </div>
            <div>
                <label style="display: block; font-size: 0.75rem; color: #94a3b8; margin-bottom: 0.2rem;">Nº de partidas</label>
                <input type="number" name="poblacion_n_partidas" value="<?php echo htmlspecialchars((string)($data['poblacion_n_partidas'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; padding: 0.25rem 0; text-align: right;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div style="border: 1px solid #e2e8f0;">
                <div style="background: #f8fafc; padding: 0.5rem; border-bottom: 1px solid #e2e8f0; font-weight: bold; font-size: 0.85rem;">✓ Procedimiento Realizado</div>
                <textarea name="procedimiento_realizado" class="editor-wysiwyg" rows="6" style="width: 100%; border: none; padding: 0.5rem; resize: vertical;"><?php echo htmlspecialchars($data['procedimiento_realizado'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div style="border: 1px solid #e2e8f0;">
                <div style="background: #f8fafc; padding: 0.5rem; border-bottom: 1px solid #e2e8f0; font-weight: bold; font-size: 0.85rem; color: #16a34a;">📋 Documentar excepciones</div>
                <textarea name="documentar_excepciones" class="editor-wysiwyg" rows="6" style="width: 100%; border: none; padding: 0.5rem; resize: vertical;"><?php echo htmlspecialchars($data['documentar_excepciones'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 4 Y 5 (Lado a Lado) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        <!-- 4. Definición de Error -->
        <div>
            <div style="background: #476a7d; color: white; padding: 0.5rem 1rem; margin-bottom: 1rem; font-weight: bold;">4. Definición de Error, expectativa</div>
            <div style="border: 1px solid #e2e8f0; height: calc(100% - 2.5rem);">
                <div style="background: #f8fafc; padding: 0.5rem; border-bottom: 1px solid #e2e8f0; font-weight: bold; font-size: 0.85rem; color: #dc2626;">⚠ Definición de Error, expectativa</div>
                <textarea name="definicion_error" class="editor-wysiwyg" rows="10" style="width: 100%; height: 85%; border: none; padding: 0.5rem; resize: none;"><?php echo htmlspecialchars($data['definicion_error'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        
        <!-- 5. Método de Selección -->
        <div>
            <div style="background: #476a7d; color: white; padding: 0.5rem 1rem; margin-bottom: 1rem; font-weight: bold;">5. Método de Selección</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #e2e8f0; color: #64748b;">
                        <th style="padding: 0.5rem; text-align: left;">Base de Selección</th>
                        <th style="padding: 0.5rem; text-align: center;">Nº Partidas</th>
                        <th style="padding: 0.5rem; text-align: right;">Monto</th>
                        <th style="padding: 0.5rem; text-align: right;">%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $filas = [
                        ['label' => 'Por Cobertura', 'pref' => 'cobertura'],
                        ['label' => 'Por riesgo:', 'pref' => 'riesgo'],
                        ['label' => 'Por procedimientos impredecibles:', 'pref' => 'impredecibles'],
                        ['label' => 'Monto probado (total muestra):', 'pref' => 'probado'],
                        ['label' => 'Monto no probado:', 'pref' => 'no_probado']
                    ];
                    foreach ($filas as $f): 
                        $pref = $f['pref'];
                    ?>
                    <tr>
                        <td style="padding: 0.75rem 0.5rem; border-bottom: 1px solid #e2e8f0;"><?php echo $f['label']; ?></td>
                        <td style="padding: 0.75rem 0.5rem; border-bottom: 1px solid #e2e8f0;">
                            <input type="number" name="<?php echo $pref; ?>_n" value="<?php echo htmlspecialchars((string)($data[$pref.'_n'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; text-align: center;">
                        </td>
                        <td style="padding: 0.75rem 0.5rem; border-bottom: 1px solid #e2e8f0;">
                            <input type="text" name="<?php echo $pref; ?>_monto" value="<?php echo htmlspecialchars((string)($data[$pref.'_monto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; text-align: right;">
                        </td>
                        <td style="padding: 0.75rem 0.5rem; border-bottom: 1px solid #e2e8f0;">
                            <input type="text" name="<?php echo $pref; ?>_porcentaje" value="<?php echo htmlspecialchars((string)($data[$pref.'_porcentaje'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; border: none; border-bottom: 1px solid #cbd5e1; outline: none; text-align: right;">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECCIÓN 6 Y 7 (Lado a Lado) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
        <div>
            <div style="background: #476a7d; color: white; padding: 0.5rem 1rem; margin-bottom: 1rem; font-weight: bold;">6. Documentar el Resultado de la Prueba realizada</div>
            <div style="border: 1px solid #e2e8f0;">
                <div style="background: #f8fafc; padding: 0.5rem; border-bottom: 1px solid #e2e8f0; font-weight: bold; font-size: 0.85rem; color: #dc2626;">📋 Documentar el Resultado de la Prueba realizada</div>
                <textarea name="documentar_resultado" class="editor-wysiwyg" rows="6" style="width: 100%; border: none; padding: 0.5rem; resize: vertical;"><?php echo htmlspecialchars($data['documentar_resultado'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
        <div>
            <div style="background: #476a7d; color: white; padding: 0.5rem 1rem; margin-bottom: 1rem; font-weight: bold;">7. Evaluación de los Resultados</div>
            <div style="border: 1px solid #e2e8f0;">
                <div style="background: #f8fafc; padding: 0.5rem; border-bottom: 1px solid #e2e8f0; font-weight: bold; font-size: 0.85rem; color: #dc2626;">📋 Evaluación de los Resultados</div>
                <textarea name="evaluacion_resultados" class="editor-wysiwyg" rows="6" style="width: 100%; border: none; padding: 0.5rem; resize: vertical;"><?php echo htmlspecialchars($data['evaluacion_resultados'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
    </div>

    <!-- Botón Submit Principal -->
    <div style="text-align: right; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
        <button type="submit" style="background: #2563eb; color: white; border: none; padding: 0.65rem 1.75rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
            Guardar Avance Completo (Modelo 5)
        </button>
    </div>
</div>