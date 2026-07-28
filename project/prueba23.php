<?php
/**
 * Módulo: Prueba 23 - Matriz de Riesgo 
 * Arquitectura Backend: PHP 8.x + PDO (Seguro contra Inyección SQL)
 */

if (!isset($pdo) || !$pdo instanceof PDO) {
    exit('Error de inicialización de base de datos.');
}

// Obtener ID del proyecto de forma robusta (priorizando POST, luego GET)
$proyectoId = filter_input(INPUT_POST, 'proyecto_id', FILTER_VALIDATE_INT) 
    ?? filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) 
    ?? 0;

$mensajeRiesgo = '';
$tipoAlertaRiesgo = '';

// Catálogos estandarizados de selección única para la matriz de riesgo
$catalogosRiesgo = [
    'origen_riesgo' => ['Estratégico', 'Operativo', 'Financiero', 'Cumplimiento', 'Tecnológico'],
    'objetivos_negocio' => ['Crecimiento de ingresos', 'Optimización de costos', 'Continuidad operativa', 'Integridad de reportes', 'Cumplimiento regulatorio'],
    'riesgo_negocio' => ['Pérdida de mercado', 'Fraude interno/externo', 'Error en estados financieros', 'Interrupción de sistemas', 'Sanciones legales'],
    'riesgo_clave' => ['Alto impacto financiero', 'Deficiencia de control interno significativa', 'Riesgo de incorrección material', 'Incumplimiento crítico'],
    'respuesta_controles' => ['Mitigación mediante supervisión', 'Controles automáticos de sistema', 'Conciliaciones periódicas', 'Segregación de funciones', 'Monitoreo gerencial'],
    'area_asercion' => ['Existencia / Ocurrencia', 'Integralidad', 'Valoración y asignación', 'Derechos y obligaciones', 'Presentación y revelación'],
    'enfoque_auditoria' => ['Pruebas sustantivas detalladas', 'Pruebas de controles', 'Revisión analítica sustantiva', 'Procedimientos duales'],
    'emision_informe' => ['Carta de recomendaciones', 'Salvedad en opinión', 'Párrafo de énfasis', 'Informe estándar limpio']
];

// Procesamiento seguro del formulario POST para inserción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_riesgo']) && $_POST['accion_riesgo'] === 'guardar_riesgo') {
    try {
        $origen = trim($_POST['origen_riesgo'] ?? '');
        $objetivos = trim($_POST['objetivos_negocio'] ?? '');
        $riesgoNeg = trim($_POST['riesgo_negocio'] ?? '');
        $riesgoClave = trim($_POST['riesgo_clave'] ?? '');
        $respControles = trim($_POST['respuesta_controles'] ?? '');
        $areaAsercion = trim($_POST['area_asercion'] ?? '');
        $enfoque = trim($_POST['enfoque_auditoria'] ?? '');
        $emision = trim($_POST['emision_informe'] ?? '');
        $acuerdo = isset($_POST['acuerdo_informacion']) ? 1 : 0;

        if ($proyectoId > 0 && $acuerdo === 1 && !empty($origen)) {
            $stmt = $pdo->prepare('
                INSERT INTO prueba_23_riesgos 
                (proyecto_id, origen_riesgo, objetivos_negocio, riesgo_negocio, riesgo_clave, respuesta_controles, area_asercion, enfoque_auditoria, emision_informe) 
                VALUES (:pid, :origen, :objetivos, :r_neg, :r_clave, :resp, :area, :enf, :emi)
            ');
            
            $stmt->execute([
                'pid' => $proyectoId,
                'origen' => $origen,
                'objetivos' => $objetivos,
                'r_neg' => $riesgoNeg,
                'r_clave' => $riesgoClave,
                'resp' => $respControles,
                'area' => $areaAsercion,
                'enf' => $enfoque,
                'emi' => $emision
            ]);

            $mensajeRiesgo = 'Registro de matriz de riesgo guardado exitosamente.';
            $tipoAlertaRiesgo = 'success';
        } else {
            $mensajeRiesgo = 'Debe seleccionar un proyecto válido, completar los campos obligatorios y aceptar los términos.';
            $tipoAlertaRiesgo = 'error';
        }
    } catch (PDOException $e) {
        error_log('Error al guardar riesgo (Prueba 23): ' . $e->getMessage());
        $mensajeRiesgo = 'Ocurrió un error en la base de datos al procesar la solicitud.';
        $tipoAlertaRiesgo = 'error';
    }
}

// Consultar registros existentes para este proyecto de forma segura
$listaRiesgos = [];
if ($proyectoId > 0) {
    try {
        $stmtRiesgos = $pdo->prepare('SELECT * FROM prueba_23_riesgos WHERE proyecto_id = :pid ORDER BY id DESC');
        $stmtRiesgos->execute(['pid' => $proyectoId]);
        $listaRiesgos = $stmtRiesgos->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $listaRiesgos = [];
    }
}
?>

<!-- 1. ACORDEÓN Y CONTENIDO VISUAL PRINCIPAL -->
<div class="card accordion-card" style="margin-bottom: 20px; background-color: var(--bg-form, #16161a); border: 1px solid var(--border-color, #2a2b2f); border-radius: 8px; overflow: hidden;">
    <div class="accordion-header" onclick="toggleAccordion('acordeonRiesgo')" style="background-color: #1e3a5f; color: #ffffff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
        <h3 style="font-size: 16px; margin: 0; font-weight: 600;">Matriz de riesgo</h3>
        <button type="button" class="btn-icon" style="background: transparent; border: none; color: #ffffff; font-size: 18px;"><i class="ri-edit-box-line"></i></button>
    </div>
    
    <div id="acordeonRiesgo" class="accordion-body" style="padding: 20px;">
        
        <?php if (!empty($mensajeRiesgo)): ?>
            <div class="alert alert-<?php echo $tipoAlertaRiesgo; ?>" style="padding: 10px 15px; margin-bottom: 15px; border-radius: 6px; font-size: 13px; background-color: <?php echo $tipoAlertaRiesgo === 'success' ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)'; ?>; color: <?php echo $tipoAlertaRiesgo === 'success' ? '#10b981' : '#ef4444'; ?>;">
                <?php echo htmlspecialchars($mensajeRiesgo, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- Botón para abrir el Modal -->
        <button type="button" class="btn" onclick="openModalRiesgo()" style="background-color: #1e3a5f; color: #ffffff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; margin-bottom: 15px;">
            <i class="ri-add-line"></i> Riesgo
        </button>

        <!-- Tabla de Contenido -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; color: #ffffff;">
                <thead>
                    <tr style="background-color: #2a2b2f; color: #9ca3af; text-align: left;">
                        <th style="padding: 12px;">Origen del riesgo</th>
                        <th style="padding: 12px;">Objetivos del negocio</th>
                        <th style="padding: 12px;">Riesgo del negocio</th>
                        <th style="padding: 12px;">Riesgo clave</th>
                        <th style="padding: 12px;">Respuesta controles</th>
                        <th style="padding: 12px;">Área y aserción</th>
                        <th style="padding: 12px;">Enfoque auditoría</th>
                        <th style="padding: 12px;">Emisión informe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listaRiesgos)): ?>
                        <tr>
                            <td colspan="8" style="padding: 15px; text-align: center; color: #6b7280;">No hay riesgos registrados en la matriz todavía.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listaRiesgos as $item): ?>
                            <tr style="border-bottom: 1px solid #2a2b2f;">
                                <td style="padding: 12px;"><?php echo htmlspecialchars($item['origen_riesgo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($item['objetivos_negocio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($item['riesgo_negocio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($item['riesgo_clave'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($item['respuesta_controles'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($item['area_asercion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($item['enfoque_auditoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="padding: 12px;"><?php echo htmlspecialchars($item['emision_informe'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. MODAL COLOCADO FUERA DEL FLUJO DE FORMULARIOS PADRES (AISLADO ABAJO) -->
<div id="modalRiesgo" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background-color: #ffffff; border: 1px solid #cbd5e1; width: 95%; max-width: 1200px; border-radius: 8px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        
        <!-- Cabecera del Modal (Azul Navbar) -->
        <div style="background-color: #1e3a5f; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: #ffffff; font-size: 16px; margin: 0; font-weight: 600;">Agregar &gt; Riesgo</h3>
            <button type="button" onclick="closeModalRiesgo()" style="background: transparent; border: none; color: #ffffff; font-size: 20px; cursor: pointer;"><i class="ri-close-line"></i></button>
        </div>

        <!-- Formulario Independiente del Modal -->
        <form action="" method="POST" style="padding: 30px; max-height: 80vh; overflow-y: auto;">
            <input type="hidden" name="accion_riesgo" value="guardar_riesgo">
            <input type="hidden" name="proyecto_id" value="<?php echo htmlspecialchars((string)$proyectoId, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Caja Interna Clara para Selects -->
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 25px;">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                    <?php foreach ($catalogosRiesgo as $nameKey => $opciones): ?>
                        <div class="form-group" style="display: flex; flex-direction: column;">
                            <label style="font-size: 11px; text-transform: uppercase; color: #334155; margin-bottom: 8px; font-weight: 700; letter-spacing: 0.5px;">
                                <?php echo ucwords(str_replace('_', ' ', $nameKey)); ?>
                            </label>
                            <select name="<?php echo $nameKey; ?>" class="form-control" style="width: 100%; background-color: #ffffff; border: 1px solid #cbd5e1; color: #0f172a; padding: 10px 12px; border-radius: 6px; font-size: 13px;" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($opciones as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Caja Contenedora Inferior (Checkbox y Botón Guardar) -->
            <div style="background-color: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <input type="checkbox" id="acuerdo_informacion" name="acuerdo_informacion" value="1" required style="width: 18px; height: 18px; accent-color: #1e3a5f; cursor: pointer;">
                    <label for="acuerdo_informacion" style="color: #334155; font-size: 13px; font-weight: 600; cursor: pointer;">Estoy de acuerdo con la información suministrada!</label>
                </div>

                <button type="submit" class="btn" style="background-color: #1e3a5f; color: #ffffff; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="ri-save-line" style="font-size: 16px;"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAccordion(id) {
    const el = document.getElementById(id);
    if (el.style.display === 'none') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}

function openModalRiesgo() {
    document.getElementById('modalRiesgo').style.display = 'flex';
}

function closeModalRiesgo() {
    document.getElementById('modalRiesgo').style.display = 'none';
}
</script>