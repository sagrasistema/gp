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
?>

<!-- 3. Interfaz Visual (HTML / Formulario Unificado) -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">
        <strong>Modelo 6 - Ejecución de Auditoría Especial (Prueba ID: <?= htmlspecialchars((string)$currentPruebaId) ?>)</strong>
    </div>
    <div class="card-body">
        
        <?php if ($mensajeRespuesta): ?>
            <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensajeRespuesta) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="needs-validation" novalidate>
            <input type="hidden" name="proyecto_id" value="<?= htmlspecialchars((string)$currentProyectoId) ?>">
            <input type="hidden" name="prueba_id" value="<?= htmlspecialchars((string)$currentPruebaId) ?>">
            <input type="hidden" name="guardar_modelo_6" value="1">

            <p class="text-muted">Procedimientos que serán realizados, lineamientos y vínculos:</p>

            <!-- Sección 1 y 2 -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card p-3 border">
                        <h6 class="bg-light p-2 text-dark">1. Información de la Cuenta y Objetivo de la Prueba</h6>
                        <div class="mb-3">
                            <label class="form-label">Partida del estado Financiero</label>
                            <input type="text" class="form-control" name="partida_estado_financiero" value="<?= htmlspecialchars($formData['partida_estado_financiero']) ?>" placeholder="Ej. Efectivo">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha y período de la prueba</label>
                            <input type="text" class="form-control" name="fecha_periodo_prueba" value="<?= htmlspecialchars($formData['fecha_periodo_prueba']) ?>" placeholder="DD-MM-AA">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small">Importancia relativa General</label>
                                <input type="number" step="0.01" class="form-control" name="importancia_relativa_general" value="<?= htmlspecialchars((string)$formData['importancia_relativa_general']) ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small">Importancia relativa Planificación</label>
                                <input type="number" step="0.01" class="form-control" name="importancia_relativa_planificacion" value="<?= htmlspecialchars((string)$formData['importancia_relativa_planificacion']) ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small">Nivel de registro SUD</label>
                                <input type="number" step="0.01" class="form-control" name="nivel_registro_sud" value="<?= htmlspecialchars((string)$formData['nivel_registro_sud']) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3 border">
                        <h6 class="bg-light p-2 text-dark">2. Aserciones a los Estados Financieros</h6>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="aser_c" value="1" <?= $formData['aser_c'] ? 'checked' : '' ?>><label class="form-check-label">C</label></div>
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="aser_a" value="1" <?= $formData['aser_a'] ? 'checked' : '' ?>><label class="form-check-label">A</label></div>
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="aser_eo" value="1" <?= $formData['aser_eo'] ? 'checked' : '' ?>><label class="form-check-label">E/O</label></div>
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="aser_co" value="1" <?= $formData['aser_co'] ? 'checked' : '' ?>><label class="form-check-label">CO</label></div>
                            </div>
                            <div class="col-6">
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="aser_ro" value="1" <?= $formData['aser_ro'] ? 'checked' : '' ?>><label class="form-check-label">RO</label></div>
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="aser_va" value="1" <?= $formData['aser_va'] ? 'checked' : '' ?>><label class="form-check-label">VA</label></div>
                                <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="aser_pd" value="1" <?= $formData['aser_pd'] ? 'checked' : '' ?>><label class="form-check-label">PD</label></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secciones 3, 4, 5 y 6 (Editores de Texto Enriquecido) -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card p-3 border">
                        <h6 class="bg-light p-2 text-dark">3. Desarrollar una Expectativa</h6>
                        <textarea class="form-control editor-html" name="desarrollar_expectativa" rows="5"><?= htmlspecialchars($formData['desarrollar_expectativa']) ?></textarea>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card p-3 border">
                        <h6 class="bg-light p-2 text-dark">4. Definición de una Diferencia Significativa o Umbral</h6>
                        <textarea class="form-control editor-html" name="definicion_diferencia_umbral" rows="5"><?= htmlspecialchars($formData['definicion_diferencia_umbral']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card p-3 border">
                        <h6 class="bg-light p-2 text-dark">5. Determinación de Diferencias</h6>
                        <textarea class="form-control editor-html" name="determinacion_diferencias" rows="5"><?= htmlspecialchars($formData['determinacion_diferencias']) ?></textarea>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card p-3 border">
                        <h6 class="bg-light p-2 text-dark">6. Evaluación de los Resultados</h6>
                        <textarea class="form-control editor-html" name="evaluacion_resultados" rows="5"><?= htmlspecialchars($formData['evaluacion_resultados']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Botón de guardado unificado -->
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg px-5">Guardar Modelo 6</button>
            </div>
        </form>
    </div>
</div>