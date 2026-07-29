<?php
declare(strict_types=1);

/**
 * Componente Modular: Vista del Modelo 6 (Pruebas Especiales)
 * Compatible con PHP 8.x y PSR-12
 * 
 * NOTA ARQUITECTÓNICA: 
 * Este archivo actúa EXCLUSIVAMENTE como vista (Template HTML).
 * Las etiquetas <form> fueron removidas porque se anida en actividades.php.
 * La lógica POST y GET es manejada por el controlador padre.
 */

// Rescatamos los identificadores desde el contexto padre de forma segura
$currentPruebaId   = $pruebaId ?? filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT) ?? 0;
$currentProyectoId = $proyectoId ?? filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) ?? 0;

// Si $formData no fue inyectado por actividades.php (prevención de errores), definimos defaults
if (!isset($formData)) {
    $formData = [
        'partida_estado_financiero' => '',
        'fecha_periodo_prueba' => '',
        'importancia_relativa_general' => '',
        'importancia_relativa_planificacion' => '',
        'nivel_registro_sud' => '',
        'aser_c' => 0, 'aser_a' => 0, 'aser_eo' => 0, 'aser_co' => 0,
        'aser_ro' => 0, 'aser_va' => 0, 'aser_pd' => 0,
        'desarrollar_expectativa' => '',
        'definicion_diferencia_umbral' => '',
        'determinacion_diferencias' => '',
        'evaluacion_resultados' => ''
    ];
}
?>

<div class="card-modelo-6" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 2rem;">
    
    <!-- Cabecera del Módulo -->
    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.25rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; justify-content: space-between;">
            <span>Modelo 6 - Ejecución de Auditoría Especial </span>
            <span style="font-size: 0.8rem; background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.75rem; border-radius: 20px; font-weight: 600;">
                Prueba ID: <?php echo htmlspecialchars((string)$currentPruebaId, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </h3>
        <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
            Procedimientos integrales, lineamientos y evaluación analítica de la prueba:
        </p>
    </div>

    <!-- IMPORTANTE: Campos ocultos para enrutar la petición en actividades.php -->
    <input type="hidden" name="action_type" value="save_modelo_6">
    <input type="hidden" name="proyecto_id" value="<?php echo htmlspecialchars((string)$currentProyectoId, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="prueba_id" value="<?php echo htmlspecialchars((string)$currentPruebaId, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- 1. Información de la Cuenta y Objetivo -->
    <div style="margin-bottom: 1.75rem;">
        <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
            1. Información de la Cuenta y Objetivo de la Prueba
        </h4>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Partida del estado Financiero</label>
                <input type="text" name="partida_estado_financiero" value="<?php echo htmlspecialchars($formData['partida_estado_financiero'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej. Efectivo" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Fecha y período de la prueba</label>
                <input type="text" name="fecha_periodo_prueba" value="<?php echo htmlspecialchars($formData['fecha_periodo_prueba'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="DD-MM-AA" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Importancia relativa General</label>
                <input type="text" name="importancia_relativa_general" value="<?php echo htmlspecialchars((string)($formData['importancia_relativa_general'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Importancia relativa Planificación</label>
                <input type="text" name="importancia_relativa_planificacion" value="<?php echo htmlspecialchars((string)($formData['importancia_relativa_planificacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Nivel de registro SUD</label>
                <input type="text" name="nivel_registro_sud" value="<?php echo htmlspecialchars((string)($formData['nivel_registro_sud'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
            </div>
        </div>
    </div>

    <!-- 2. Aseveraciones a los Estados Financieros -->
    <div style="margin-bottom: 1.75rem;">
        <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
            2. Aseveraciones a los Estados Financieros
        </h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem; background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
            <?php 
            $aseveraciones = [
                'aser_c'  => ['sigla' => 'C', 'desc' => 'Completitud'],
                'aser_a'  => ['sigla' => 'A', 'desc' => 'Exactitud'],
                'aser_eo' => ['sigla' => 'E/O', 'desc' => 'Existencia / Ocurrencia'],
                'aser_co' => ['sigla' => 'CO', 'desc' => 'Corte'],
                'aser_ro' => ['sigla' => 'ORO', 'desc' => 'Derechos y Obligaciones'],
                'aser_va' => ['sigla' => 'VA', 'desc' => 'Valoración'],
                'aser_pd' => ['sigla' => 'PD', 'desc' => 'Presentación y Desglose']
            ];
            
            foreach ($aseveraciones as $key => $data): 
                $isChecked = !empty($formData[$key]) ? 'checked' : '';
            ?>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #334155; cursor: pointer;">
                    <input type="checkbox" name="<?php echo $key; ?>" value="1" <?php echo $isChecked; ?> style="width: 16px; height: 16px; accent-color: #2563eb;">
                    <span><strong><?php echo $data['sigla']; ?></strong> - <?php echo $data['desc']; ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 3. Desarrollar una Expectativa -->
    <div style="margin-bottom: 1.75rem;">
        <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
            3. Desarrollar una Expectativa
        </h4>
        <textarea name="desarrollar_expectativa" rows="4" placeholder="Describa analíticamente la expectativa..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc; resize: vertical;"><?php echo htmlspecialchars($formData['desarrollar_expectativa'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <!-- 4. Definir la Diferencia Tolerable y Umbrales -->
    <div style="margin-bottom: 1.75rem;">
        <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
            4. Definir la Diferencia Tolerable
        </h4>
        <textarea name="definicion_diferencia_umbral" rows="3" placeholder="Defina los criterios del umbral..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc; resize: vertical;"><?php echo htmlspecialchars($formData['definicion_diferencia_umbral'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <!-- 5. Ejecución y Registro de Resultados -->
    <div style="margin-bottom: 1.75rem;">
        <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
            5. Registro de Resultados y Desviaciones Obtenidas
        </h4>
        <textarea name="determinacion_diferencias" rows="4" placeholder="Indique los valores reales obtenidos..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc; resize: vertical;"><?php echo htmlspecialchars($formData['determinacion_diferencias'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <!-- 6. Conclusión y Evaluación del Hallazgo -->
    <div style="margin-bottom: 1.75rem;">
        <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
            6. Conclusión y Evaluación de Resultados
        </h4>
        <textarea name="evaluacion_resultados" rows="3" placeholder="Redacte la conclusión técnica..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc; resize: vertical;"><?php echo htmlspecialchars($formData['evaluacion_resultados'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>

    <!-- Botón de Envío: Desencadena el envío del formulario PADRE en actividades.php -->
    <div style="text-align: right; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
        <button type="submit" style="background: #2563eb; color: white; border: none; padding: 0.65rem 1.75rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem; cursor: pointer; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">
            Guardar Avance Completo (Modelo 6)
        </button>
    </div>
</div>