<?php

declare(strict_types=1);

/**
 * Componente Modular: Modelo 6 para Pruebas Especiales de Auditoría
 * Compatible con PHP 8.x y PSR-12
 */

// 1. Procesamiento Backend de Guardado (si se envía el formulario)
$mensajeRespuesta = null;
$tipoAlerta = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_modelo_6'])) {
    try {
        // Validación de identificadores críticos
        $proyectoId = filter_var($_POST['proyecto_id'] ?? 0, FILTER_VALIDATE_INT);
        $pruebaId   = filter_var($_POST['prueba_id'] ?? 0, FILTER_VALIDATE_INT);

        if ($proyectoId <= 0 || $pruebaId <= 0) {
            throw new Exception('Los identificadores de proyecto o prueba son inválidos.');
        }

        // Sanitización de datos estructurados
        $partidaFinanciera = trim((string) ($_POST['partida_estado_financiero'] ?? ''));
        $fechaPeriodo      = trim((string) ($_POST['fecha_periodo_prueba'] ?? ''));
        $impGeneral        = filter_var($_POST['importancia_relativa_general'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        $impPlanificacion  = filter_var($_POST['importancia_relativa_planificacion'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;
        $nivelSud          = filter_var($_POST['nivel_registro_sud'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0.00;

        // Aserciones (Checkboxes booleanos)
        $aserC  = isset($_POST['aser_c']) ? 1 : 0;
        $aserA  = isset($_POST['aser_a']) ? 1 : 0;
        $aserEo = isset($_POST['aser_eo']) ? 1 : 0;
        $aserCo = isset($_POST['aser_co']) ? 1 : 0;
        $aserRo = isset($_POST['aser_ro']) ? 1 : 0;
        $aserVa = isset($_POST['aser_va']) ? 1 : 0;
        $aserPd = isset($_POST['aser_pd']) ? 1 : 0;

        // Editores de Texto Enriquecido (HTML)
        $desarrollarExpectativa     = trim((string) ($_POST['desarrollar_expectativa'] ?? ''));
        $definicionDiferenciaUmbral = trim((string) ($_POST['definicion_diferencia_umbral'] ?? ''));
        $determinacionDiferencias   = trim((string) ($_POST['determinacion_diferencias'] ?? ''));
        $evaluacionResultados       = trim((string) ($_POST['evaluacion_resultados'] ?? ''));

        // Sentencia SQL UPSERT segura con PDO
        $sql = "INSERT INTO audit_modelo_6_detalles (
                    proyecto_id, prueba_id, partida_estado_financiero, fecha_periodo_prueba, 
                    importancia_relativa_general, importancia_relativa_planificacion, nivel_registro_sud, 
                    aser_c, aser_a, aser_eo, aser_co, aser_ro, aser_va, aser_pd, 
                    desarrollar_expectativa, definicion_diferencia_umbral, determinacion_diferencias, evaluacion_resultados
                ) VALUES (
                    :proyecto_id, :prueba_id, :partida_estado_financiero, :fecha_periodo_prueba, 
                    :imp_general, :imp_planificacion, :nivel_sud, 
                    :aser_c, :aser_a, :aser_eo, :aser_co, :aser_ro, :aser_va, :aser_pd, 
                    :desarrollar_expectativa, :definicion_diferencia_umbral, :determinacion_diferencias, :evaluacion_resultados
                ) ON DUPLICATE KEY UPDATE 
                    partida_estado_financiero = VALUES(partida_estado_financiero),
                    fecha_periodo_prueba = VALUES(fecha_periodo_prueba),
                    importancia_relativa_general = VALUES(importancia_relativa_general),
                    importancia_relativa_planificacion = VALUES(importancia_relativa_planificacion),
                    nivel_registro_sud = VALUES(nivel_registro_sud),
                    aser_c = VALUES(aser_c),
                    aser_a = VALUES(aser_a),
                    aser_eo = VALUES(aser_eo),
                    aser_co = VALUES(aser_co),
                    aser_ro = VALUES(aser_ro),
                    aser_va = VALUES(aser_va),
                    aser_pd = VALUES(aser_pd),
                    desarrollar_expectativa = VALUES(desarrollar_expectativa),
                    definicion_diferencia_umbral = VALUES(definicion_diferencia_umbral),
                    determinacion_diferencias = VALUES(determinacion_diferencias),
                    evaluacion_resultados = VALUES(evaluacion_resultados)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'proyecto_id'                  => $proyectoId,
            'prueba_id'                    => $pruebaId,
            'partida_estado_financiero'    => $partidaFinanciera,
            'fecha_periodo_prueba'         => $fechaPeriodo,
            'imp_general'                  => $impGeneral,
            'imp_planificacion'            => $impPlanificacion,
            'nivel_sud'                    => $nivelSud,
            'aser_c'                       => $aserC,
            'aser_a'                       => $aserA,
            'aser_eo'                      => $aserEo,
            'aser_co'                      => $aserCo,
            'aser_ro'                      => $aserRo,
            'aser_va'                      => $aserVa,
            'aser_pd'                      => $aserPd,
            'desarrollar_expectativa'      => $desarrollarExpectativa,
            'definicion_diferencia_umbral' => $definicionDiferenciaUmbral,
            'determinacion_diferencias'    => $determinacionDiferencias,
            'evaluacion_resultados'        => $evaluacionResultados,
        ]);

        $mensajeRespuesta = "Los datos del Modelo 6 se guardaron exitosamente.";
        $tipoAlerta = "success";
    } catch (Exception $e) {
        error_log('[Error Modelo 6]: ' . $e->getMessage());
        $mensajeRespuesta = "Error al procesar la solicitud: " . $e->getMessage();
        $tipoAlerta = "danger";
    }
}

// 2. Consulta de Datos Existentes para la Vista (Precarga por Proyecto y Prueba)
$currentProyectoId = $proyecto_id ?? $_GET['proyecto_id'] ?? 1;
$currentPruebaId   = $prueba_id ?? $_GET['prueba_id'] ?? 0;

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

if ($currentPruebaId > 0) {
    try {
        $stmtSelect = $pdo->prepare("SELECT * FROM audit_modelo_6_detalles WHERE proyecto_id = :proyecto_id AND prueba_id = :prueba_id LIMIT 1");
        $stmtSelect->execute(['proyecto_id' => $currentProyectoId, 'prueba_id' => $currentPruebaId]);
        $row = $stmtSelect->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $formData = $row;
        }
    } catch (PDOException $e) {
        error_log('[Error Fetch Modelo 6]: ' . $e->getMessage());
    }
}
/**
 * Módulo del Modelo 6 - Ejecución de Auditoría Especial (Completo del 1 al 6)
 * Compatible con PHP 8.x / Estándar PSR-12
 */

// Rescatamos los identificadores de forma segura desde el contexto padre o URL
$currentPruebaId = $pruebaId ?? filter_input(INPUT_GET, 'pruebaId', FILTER_VALIDATE_INT) ?? 0;
$currentProyectoId = $proyectoId ?? filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) ?? 0;
?>

<div class="card-modelo-6" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 2rem;">
    
    <!-- Cabecera del Módulo -->
    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem; margin-bottom: 1.5rem;">
        <h3 style="margin: 0 0 0.25rem 0; font-size: 1.25rem; color: #1e293b; font-weight: 700; display: flex; align-items: center; justify-content: space-between;">
            <span>Modelo 6 - Ejecución de Auditoría Especial (Completo)</span>
            <span style="font-size: 0.8rem; background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.75rem; border-radius: 20px; font-weight: 600;">
                Prueba ID: <?php echo htmlspecialchars((string)$currentPruebaId, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </h3>
        <p style="margin: 0; color: #64748b; font-size: 0.9rem;">
            Procedimientos integrales, lineamientos y evaluación analítica de la prueba:
        </p>
    </div>

    <!-- Formulario de Captura -->
    <form method="POST" action="actividades.php?proyectoId=<?php echo $currentProyectoId; ?>&pruebaId=<?php echo $currentPruebaId; ?>">
        <input type="hidden" name="action_type" value="save_modelo_6">
        <input type="hidden" name="prueba_id" value="<?php echo htmlspecialchars((string)$currentPruebaId, ENT_QUOTES, 'UTF-8'); ?>">

        <!-- 1. Información de la Cuenta y Objetivo -->
        <div style="margin-bottom: 1.75rem;">
            <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
                1. Información de la Cuenta y Objetivo de la Prueba
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Partida del estado Financiero</label>
                    <input type="text" name="modelo6[partida]" placeholder="Ej. Efectivo" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Fecha y período de la prueba</label>
                    <input type="text" name="modelo6[periodo]" placeholder="DD-MM-AA" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Importancia relativa General</label>
                    <input type="text" name="modelo6[imp_general]" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Importancia relativa Planificación</label>
                    <input type="text" name="modelo6[imp_planificacion]" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Nivel de registro SUD</label>
                    <input type="text" name="modelo6[sud]" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
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
                    'C'   => 'Completitud',
                    'A'   => 'Exactitud',
                    'E/O' => 'Existencia / Ocurrencia',
                    'CO'  => 'Corte',
                    'ORO' => 'Derechos y Obligaciones',
                    'VA'  => 'Valoración',
                    'PD'  => 'Presentación y Desglose'
                ];
                foreach ($aseveraciones as $sigla => $descripcion): 
                ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #334155; cursor: pointer;">
                        <input type="checkbox" name="modelo6[aseveraciones][]" value="<?php echo $sigla; ?>" style="width: 16px; height: 16px; accent-color: #2563eb;">
                        <span><strong><?php echo $sigla; ?></strong> - <?php echo $descripcion; ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 3. Desarrollar una Expectativa -->
        <div style="margin-bottom: 1.75rem;">
            <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
                3. Desarrollar una Expectativa
            </h4>
            <textarea name="modelo6[expectativa]" rows="4" placeholder="Describa analíticamente la expectativa..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc; resize: vertical;"></textarea>
        </div>

        <!-- 4. Definir la Diferencia Tolerable y Umbrales -->
        <div style="margin-bottom: 1.75rem;">
            <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
                4. Definir la Diferencia Tolerable
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Umbral de Variación Permitida (%)</label>
                    <input type="text" name="modelo6[umbral_porcentaje]" placeholder="Ej. 5%" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Monto Límite Tolerable (Moneda Base)</label>
                    <input type="text" name="modelo6[monto_tolerable]" placeholder="Ej. 1000.00" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
                </div>
            </div>
        </div>

        <!-- 5. Ejecución y Registro de Resultados -->
        <div style="margin-bottom: 1.75rem;">
            <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
                5. Registro de Resultados y Desviaciones Obtenidas
            </h4>
            <textarea name="modelo6[resultados]" rows="4" placeholder="Indique los valores reales obtenidos en la ejecución y las variaciones detectadas frente a la expectativa..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc; resize: vertical;"></textarea>
        </div>

        <!-- 6. Conclusión y Evaluación del Hallazgo -->
        <div style="margin-bottom: 1.75rem;">
            <h4 style="font-size: 0.95rem; color: #334155; margin-bottom: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; border-left: 3px solid #2563eb; padding-left: 0.5rem;">
                6. Conclusión del Procedimiento
            </h4>
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">¿La variación supera el límite tolerable?</label>
                <select name="modelo6[supera_limite]" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc;">
                    <option value="no">No (Expectativa cumplida satisfactoriamente)</option>
                    <option value="si">Sí (Requiere evaluación de hallazgo o ajuste)</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Conclusión detallada del auditor</label>
                <textarea name="modelo6[conclusion]" rows="3" placeholder="Redacte la conclusión técnica..." style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: #f8fafc; resize: vertical;"></textarea>
            </div>
        </div>

        <!-- Botón de Envío -->
        <div style="text-align: right; border-top: 1px solid #e2e8f0; padding-top: 1.25rem;">
            <button type="submit" style="background: #2563eb; color: white; border: none; padding: 0.65rem 1.75rem; border-radius: 6px; font-weight: 600; font-size: 0.9rem; cursor: pointer; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);">
                Guardar Avance Completo (Modelo 6)
            </button>
        </div>
    </form>
</div>