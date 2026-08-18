<?php
// v/ac/responder.php
include '../main/config.php';
include '../ac/conect-responder.php';
// Validar que exista el ID de la evaluación a responder

// Evaluamos el estado actual del AC (1: En Proceso, 2: Cerrado)
$currentStatusId = (int)($acData->statusId ?? 1);
$isClosed = ($currentStatusId === 2);
$disabledAttr = $isClosed ? 'disabled' : '';
// 1. Verificar estado del registro maestro
?>
<div class="view-container">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <i class="ri-survey-line" style="color: var(--accent);"></i> Ejecutar Cuestionario
        </h1>
    </div>

    <div class="table-actions-container">
        <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;">
            <i class="ri-arrow-go-back-line"></i> 
        </a>
        <a href="exportar-word.php?acId=<?= urlencode((string)$acId) ?>" class="btn btn-primary" data-tooltip="Exportar Informe a Word" style="background-color: #2b579a; border-color: #2b579a;">
            <i class="ri-file-word-2-line"></i> Exportar a Word
        </a>
        <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;">
            <i class="ri-screenshot-2-line"></i>
        </a>
        <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;">
            <i class="ri-book-open-line"></i> 
        </a>
        <a href="nuevo.php" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;">
            <i class="ri-add-line"></i>
        </a>
        <a href="../ac/index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
            <i class="ri-checkbox-circle-fill"></i> Respuestas guardadas y nivel de riesgo recalculado de forma correcta.
        </div>
    <?php endif; ?>

<div class="meta-summary" style="display: grid; grid-template-columns: 17.5% 17.5% 17.5% 17.5% 30%; gap: 1rem; width: 100%; align-items: stretch;">
    
    <div class="meta-item">Client / Empresa <br><strong><?= htmlspecialchars($acData->clientName, ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="meta-item">Tipo Evaluación <br><strong><?= htmlspecialchars($acData->typeName, ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="meta-item">Naturaleza del Servicio <br><strong><?= htmlspecialchars($acData->serviceName, ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="meta-item">Período de la AC <br><strong><?php 
            if (!empty($acData->startDate) && !empty($acData->endDate)) {
                echo "Desde " . date('Y-m-d', strtotime($acData->startDate)) . " Hasta " . date('Y-m-d', strtotime($acData->endDate));
            } else {
                echo "SIN ASIGNAR";
            }
            ?></strong></div>

<?php
$score = isset($acData->riskScore) ? (float)$acData->riskScore : 0;
$clampedScore = max(0, min(105, $score));
$angle = -90 + (($clampedScore - 0) / (105 - 0)) * 180;
?>

<div class="meta-item-gauge">
    <div class="gauge-wrapper">
        <svg class="gauge-svg" viewBox="0 -12 200 122" width="100%" height="100%">
            <defs>
                <path id="path-bajo" d="M 20 100 A 80 80 0 0 1 35.3 53.0" fill="none" />
                <path id="path-bajo-mod" d="M 35.3 53.0 A 80 80 0 0 1 75.3 23.9" fill="none" />
                <path id="path-mod" d="M 75.3 23.9 A 80 80 0 0 1 124.7 23.9" fill="none" />
                <path id="path-mod-alto" d="M 124.7 23.9 A 80 80 0 0 1 164.7 53.0" fill="none" />
                <path id="path-alto" d="M 164.7 53.0 A 80 80 0 0 1 180 100" fill="none" />
            </defs>

            <path d="M 8 100 A 92 92 0 0 1 25.6 45.9 L 45.0 60.0 A 68 68 0 0 0 32 100 Z" fill="#22c55e" />
            <path d="M 25.6 45.9 A 92 92 0 0 1 71.6 12.5 L 79.0 35.3 A 68 68 0 0 0 45.0 60.0 Z" fill="#84cc16" />
            <path d="M 71.6 12.5 A 92 92 0 0 1 128.4 12.5 L 121.0 35.3 A 68 68 0 0 0 79.0 35.3 Z" fill="#eab308" />
            <path d="M 128.4 12.5 A 92 92 0 0 1 174.4 45.9 L 155.0 60.0 A 68 68 0 0 0 121.0 35.3 Z" fill="#f97316" />
            <path d="M 174.4 45.9 A 92 92 0 0 1 192 100 L 168 100 A 68 68 0 0 0 155.0 60.0 Z" fill="#ef4444" />

            <text font-size="6.2" fill="#ffffff" class="gauge-label-text">
                <textPath href="#path-bajo" startOffset="50%">BAJO</textPath>
            </text>
            <text font-size="5.4" fill="#1e293b" class="gauge-label-text">
                <textPath href="#path-bajo-mod" startOffset="50%">BAJO MODERADO</textPath>
            </text>
            <text font-size="6.2" fill="#1e293b" class="gauge-label-text">
                <textPath href="#path-mod" startOffset="50%">MODERADO</textPath>
            </text>
            <text font-size="5.4" fill="#ffffff" class="gauge-label-text">
                <textPath href="#path-mod-alto" startOffset="50%">MODERADO ALTO</textPath>
            </text>
            <text font-size="6.2" fill="#ffffff" class="gauge-label-text">
                <textPath href="#path-alto" startOffset="50%">ALTO</textPath>
            </text>

            <text x="2" y="112" class="gauge-text">0</text>
            <text x="21" y="34" class="gauge-text">21</text>
            <text x="64" y="2" class="gauge-text">42</text>
            <text x="136" y="2" class="gauge-text">63</text>
            <text x="179" y="34" class="gauge-text">84</text>
            <text x="185" y="112" class="gauge-text">105</text>

            <g transform="rotate(<?= $angle ?>, 100, 100)">
                <path d="M 97 100 L 99.3 10 L 100.7 10 L 103 100 Z" fill="#1e293b" />
            </g>

            <circle cx="100" cy="100" r="11" fill="#1e293b" stroke="#ffffff" stroke-width="2.5" />
            <circle cx="100" cy="100" r="4" fill="#94a3b8" />
        </svg>
    </div>
</div>

    <hr style="grid-column: span 4; margin: 0; border: 0; border-top: 1px solid var(--border-color, #e2e8f0); opacity: 0.6;">

    <div class="meta-item">Socio Líder de A&C <br><strong><?= htmlspecialchars($acData->partnerName ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="meta-item">Gerente de A&C <br><strong><?= htmlspecialchars($acData->managerName ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="meta-item">Socio de Riesgo <br><strong><?= htmlspecialchars($acData->riskUserId ?? '', ENT_QUOTES, 'UTF-8') ?></strong></div>
    
    <div class="meta-item" style="display: flex; flex-direction: column; justify-content: center; gap: 0.25rem;">
        <span style="font-size: 0.8rem; color: var(--text-muted, #64748b); font-weight: 500;">Riesgo Calculado Matriz</span>
        <?php
        $riskClass = 'risk-bajo';
        $riskIcon = 'ri-checkbox-circle-line';
        
        if ($acData->riskLevel === 'Moderado') { $riskClass = 'risk-moderado'; $riskIcon = 'ri-alert-line'; }
        elseif ($acData->riskLevel === 'Moderado-Alto') { $riskClass = 'risk-moderado-alto'; $riskIcon = 'ri-error-warning-line'; }
        elseif ($acData->riskLevel === 'Alto') { $riskClass = 'risk-alto'; $riskIcon = 'ri-close-circle-line'; }
        ?>
        <span id="live-risk-badge" class="badge-risk <?= $riskClass ?>" style="white-space: nowrap; width: fit-content;">
            <i class="<?= $riskIcon ?>"></i> <?= $acData->riskScore ?> Pts (<?= $acData->riskLevel ?>)
        </span>
    </div>

</div>

    <div class="activities-grid-card">
        <h3><i class="ri-grid-fill" style="color: var(--accent);"></i> Progreso General de Actividades (1-30)</h3>
<div class="activities-grid">
    <?php 
    for ($i = 1; $i <= 30; $i++): 
        $isCompleted = false;

        if ($i === 28) {
            $totalSubtests = 21;
            $answeredSubtests = 0;

            if (isset($q28Saved) && is_array($q28Saved)) {
                foreach ($q28Saved as $ans) {
                    if (isset($ans['score']) && (float)$ans['score'] > 0) {
                        $answeredSubtests++;
                    }
                }
            }
            if ($answeredSubtests >= $totalSubtests) {
                $isCompleted = true;
            }
        } else {
            $qId = $qNumberToIdMap[$i] ?? null;
            if ($qId && isset($answersSaved[$qId]) && $answersSaved[$qId] !== '') {
                $isCompleted = true;
            }
        }

        $statusClass = $isCompleted ? 'completed' : 'pending';
    ?>
        <a href="#question-<?= $i ?>" id="grid-box-<?= $i ?>" class="activity-box <?= $statusClass ?>" onclick="scrollToQuestion(<?= $i ?>, event)">
            <?= $i ?>
        </a>
    <?php endfor; ?>
</div>

        <div class="progress-bar-container" style="margin-top: 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; font-size: 0.85rem; font-weight: 600; color: #475569;">
                <span>Progreso del Formulario</span>
                <span id="progress-percentage-text">0%</span>
            </div>
            <div style="width: 100%; height: 8px; background-color: #e2e8f0; border-radius: 9999px; overflow: hidden; position: relative;">
                <div id="progress-bar-fill" style="width: 0%; height: 100%; background: linear-gradient(90deg, #10b981, #059669); border-radius: 9999px; transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);"></div>
            </div>
        </div>
    </div>

    <form action="responder.php?acId=<?= $acId ?>" method="POST">
        
        <?php
        $categories = $pdo->query("SELECT * FROM ac_categories ORDER BY orderNum ASC")->fetchAll(PDO::FETCH_OBJ);
        $qNumberToIdMap = [];
        // Inicializamos la letra de inicio para la secuencia alfabética
        $categoryLetter = 'A';
        foreach ($categories as $cat):
            $catId   = (int)$cat->categoryId;
            $catName = htmlspecialchars((string)$cat->categoryName, ENT_QUOTES, 'UTF-8');
            
            // Generamos el prefijo alfabético (Ej: "A.", "B.", etc.)
            $currentPrefix = $categoryLetter . '.';

            $stmtQ = $pdo->prepare("SELECT * FROM ac_questions WHERE categoryId = :catId ORDER BY questionNumber ASC");
            $stmtQ->execute([':catId' => $cat->categoryId]);
            $questions = $stmtQ->fetchAll(PDO::FETCH_OBJ);
        ?>
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(this)"  style="color: #ffffff;background: #1e293b">
                    <span><?= $currentPrefix ?> <?= htmlspecialchars($cat->categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                
                <div class="accordion-content">
                    <?php foreach ($questions as $q): 
                        $savedRes = $answersSaved[$q->questionId]['response'] ?? '';
                        $savedComment = $answersSaved[$q->questionId]['comment'] ?? '';
                        
                        $qNumberToIdMap[$q->questionNumber] = [
                            'id' => $q->questionId,
                            'completed' => (!empty($savedRes))
                        ];
                    ?>
                        <div class="question-row" id="question-<?= $q->questionNumber ?>">
                            <div class="question-text">
                                <strong><?= $q->questionNumber ?>.</strong> <?= htmlspecialchars($q->questionText, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            
                            <div class="question-inputs">
                               <div class="radio-group">
                                    <label class="radio-label">
                                        <input type="radio" 
                                            name="answers[<?= (int)$q->questionId ?>][response]" 
                                            value="Si" 
                                            class="q-radio" 
                                            data-qnum="<?= (int)$q->questionNumber ?>" 
                                            <?= $savedRes === 'Si' ? 'checked' : '' ?> 
                                            <?= $disabledAttr ?>> Sí
                                    </label>
                                    
                                    <label class="radio-label">
                                        <input type="radio" 
                                            name="answers[<?= (int)$q->questionId ?>][response]" 
                                            value="No" 
                                            class="q-radio" 
                                            data-qnum="<?= (int)$q->questionNumber ?>" 
                                            <?= $savedRes === 'No' ? 'checked' : '' ?> 
                                            <?= $disabledAttr ?>> No
                                    </label>

                                    <?php if ((int)$q->questionNumber === 13): ?>
                                        <label class="radio-label">
                                            <input type="radio" 
                                                name="answers[<?= (int)$q->questionId ?>][response]" 
                                                value="N/A" 
                                                class="q-radio" 
                                                data-qnum="<?= (int)$q->questionNumber ?>" 
                                                <?= $savedRes === 'N/A' ? 'checked' : '' ?> 
                                                <?= $disabledAttr ?>> N/A
                                        </label>
                                    <?php endif; ?>
                                </div>

                                <div style="width: 100%;">
                                    <?php 
                                        // Calcular filas iniciales estimadas en PHP según la longitud del comentario guardado
                                        $commentText = (string)$savedComment;
                                        $textLength  = mb_strlen($commentText, 'UTF-8');
                                        // Asigna 1 fila por defecto, o incrementa dinámicamente según la cantidad de caracteres
                                        $estimatedRows = ($textLength > 100) ? (int)ceil($textLength / 80) : 1;
                                    ?>
                                    <textarea name="answers[<?= (int)$q->questionId ?>][comment]" 
                                            class="comment-input auto-expand" 
                                            rows="<?= $estimatedRows ?>" 
                                            placeholder="Comentarios o justificación..."><?= htmlspecialchars($commentText, ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
                            </div>

                            <?php if ($q->questionNumber == 28): ?>
                            <div style="margin-top: 1.5rem; background: #f8fafc; padding: 1.25rem; border-radius: 6px; border: 1px dashed #cbd5e1; overflow-x: auto;">
                                <h4 style="font-size: 0.9rem; color: #1e293b; margin-bottom: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="ri-matrix-line" style="color: var(--accent, #0284c7);"></i> Desglose Analítico Matriz de Riesgo Interno (Prueba 28)
                                </h4>
                                <table class="subtest-table" style="min-width: 600px;">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%; text-align: center;">N°</th>
                                            <th style="width: 65%;">Descripción de la Prueba de Control / Factor de Riesgo</th>
                                            <th style="width: 30%; text-align: center;">Nivel de Riesgo Asignado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $subtests = $pdo->query("SELECT * FROM ac_q28_tests ORDER BY testNumber ASC")->fetchAll(PDO::FETCH_OBJ);
                                        foreach ($subtests as $sub):
                                            $savedRisk = $q28Saved[$sub->testId]['riskValue'] ?? 'No Aplica';
                                        ?>
                                            <tr>
                                                <td style="text-align: center;"><strong><?= $sub->testNumber ?></strong></td>
                                                <td style="line-height: 1.35; color: #334155; font-size: 0.88rem;">
                                                    <?= htmlspecialchars($sub->testText, ENT_QUOTES, 'UTF-8') ?>
                                                </td>
                                                <td style="text-align: center;">
                                                    <select name="q28[<?= $sub->testId ?>]" class="q28-select" onchange="calculateLiveRisk()" style="width: 100%; max-width: 200px;">
                                                        <option value="No Aplica" <?= $savedRisk === 'No Aplica' ? 'selected' : '' ?>>No Aplica (0 pts)</option>
                                                        <option value="Bajo" <?= $savedRisk === 'Bajo' ? 'selected' : '' ?>>Bajo (1 pts)</option>
                                                        <option value="Bajo-Moderado" <?= $savedRisk === 'Bajo-Moderado' ? 'selected' : '' ?>>Bajo-Moderado (2 pts)</option>
                                                        <option value="Moderado" <?= $savedRisk === 'Moderado' ? 'selected' : '' ?>>Moderado (3 pts)</option>
                                                        <option value="Moderado-Alto" <?= $savedRisk === 'Moderado-Alto' ? 'selected' : '' ?>>Moderado-Alto (4 pts)</option>
                                                        <option value="Alto" <?= $savedRisk === 'Alto' ? 'selected' : '' ?>>Alto (5 pts)</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php    
        // Incremento automático de PHP: 'A' -> 'B' -> 'C' ... 'Z' -> 'AA'
        $categoryLetter++; 
        endforeach; ?>
<!-- PRUEBA ESPECIAL: MATRIZ DE RIESGO AL FINAL DE LAS 30 PRUEBAS -->
<div class="accordion-item" style="margin-top: 1.5rem; border: 1px solid #cbd5e1;">
    <div class="accordion-header" style="background: #1e293b; color: #ffffff; display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1.25rem;">
        <span style="font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
            <i class="ri-shield-flash-line" style="color: #38bdf8;"></i> Matriz de Riesgo 
        </span>
        <?php if (!$isClosed): ?>
            <button type="button" onclick="abrirModalRiesgoAC()" style="background: #0284c7; color: #ffffff; border: none; padding: 0.45rem 0.9rem; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                <i class="ri-add-line"></i> Agregar Riesgo
            </button>
        <?php endif; ?>
        
    </div>
    <div style="padding: 1.25rem; background: #ffffff; overflow-x: auto;">
        <table class="subtest-table" style="width: 100%; border-collapse: collapse;" id="tablaMatrizAC">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="width: 12%; border: 1px solid #e2e8f0; padding: 0.6rem;background: #1e293b; color: #ffffff">ID Riesgo</th>
                    <th style="width: 20%; border: 1px solid #e2e8f0; padding: 0.6rem;background: #1e293b; color: #ffffff">Categoría de Riesgo</th>
                    <th style="width: 30%; border: 1px solid #e2e8f0; padding: 0.6rem;background: #1e293b; color: #ffffff">Descripción del Riesgo Identificado</th>
                    <th style="width: 20%; border: 1px solid #e2e8f0; padding: 0.6rem;background: #1e293b; color: #ffffff">Factor / Causa Raíz (Según AC)</th>
                    <th style="width: 12%; border: 1px solid #e2e8f0; padding: 0.6rem; text-align: center;background: #1e293b; color: #ffffff">Nivel de Riesgo Inherente</th>
                    <th style="width: 6%; border: 1px solid #e2e8f0; padding: 0.6rem; text-align: center;background: #1e293b; color: #ffffff">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyMatrizAC">
                <?php if (empty($matrizRiesgosSaved)): ?>
                    <tr id="rowEmptyMatrizAC">
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 1.5rem; border: 1px solid #e2e8f0;">
                            No se han agregado registros a la matriz de riesgo. Haz clic en <strong>Agregar Riesgo</strong>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($matrizRiesgosSaved as $mr): ?>
                        <tr>
                            <td style="border: 1px solid #e2e8f0; padding: 0.6rem; font-weight: 700;">
                                <?= htmlspecialchars($mr->idRiesgo, ENT_QUOTES, 'UTF-8') ?>
                                <input type="hidden" name="matriz_id[]" value="<?= htmlspecialchars($mr->idRiesgo, ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td style="border: 1px solid #e2e8f0; padding: 0.6rem;">
                                <?= htmlspecialchars($mr->categoria, ENT_QUOTES, 'UTF-8') ?>
                                <input type="hidden" name="matriz_categoria[]" value="<?= htmlspecialchars($mr->categoria, ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td style="border: 1px solid #e2e8f0; padding: 0.6rem;">
                                <?= htmlspecialchars($mr->descripcion, ENT_QUOTES, 'UTF-8') ?>
                                <input type="hidden" name="matriz_descripcion[]" value="<?= htmlspecialchars($mr->descripcion, ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td style="border: 1px solid #e2e8f0; padding: 0.6rem;">
                                <?= htmlspecialchars($mr->causaRaiz, ENT_QUOTES, 'UTF-8') ?>
                                <input type="hidden" name="matriz_causa[]" value="<?= htmlspecialchars($mr->causaRaiz, ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <td style="border: 1px solid #e2e8f0; padding: 0.6rem; text-align: center;">
                                <strong><?= htmlspecialchars($mr->nivelRiesgo, ENT_QUOTES, 'UTF-8') ?></strong>
                                <input type="hidden" name="matriz_nivel[]" value="<?= htmlspecialchars($mr->nivelRiesgo, ENT_QUOTES, 'UTF-8') ?>">
                            </td>
                            <!-- Dentro del foreach de filas en la Matriz de Riesgo -->
                            <td style="border: 1px solid #e2e8f0; padding: 0.6rem; text-align: center; white-space: nowrap;">
                                <?php if (!$isClosed): 
                                    if ($permisosModulo5['puede_editar'] == 1) {?>
                                    <button type="button" onclick="editarRiesgoAC(this)" style="background: transparent; border: none; color: #0284c7; cursor: pointer; margin-right: 0.3rem;" title="Editar">
                                        <i class="ri-edit-line" style="font-size: 1.1rem;"></i>
                                    </button>
                                    <button type="button" onclick="eliminarRiesgoAC(this)" style="background: transparent; border: none; color: #ef4444; cursor: pointer;" title="Eliminar">
                                        <i class="ri-delete-bin-line" style="font-size: 1.1rem;"></i>
                                    </button>
                                    <?php } else {?>
                                        <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">Bloqueado</span>
                                    <?php } ?>

                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">Bloqueado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- CUADRO DE CIERRE DE PROYECTO / EVALUACIÓN -->
 <?php if ($userId == 2) {?>
    <div style="margin-top: 2rem; border: 1px solid <?= $isClosed ? '#fecaca' : '#cbd5e1' ?>; border-radius: 8px; background: <?= $isClosed ? '#fef2f2' : '#ffffff' ?>; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; border-bottom: 1px solid <?= $isClosed ? '#fee2e2' : '#f1f5f9' ?>; padding-bottom: 0.75rem;">
        <h3 style="margin: 0; font-size: 1.05rem; color: #0f172a; display: flex; align-items: center; gap: 0.5rem; font-weight: 700;">
            <i class="ri-shield-keyhole-line" style="color: <?= $isClosed ? '#dc2626' : '#0284c7' ?>; font-size: 1.25rem;"></i> Cierre y Estado de la Evaluación
        </h3>
        <?php if ($isClosed): ?>
            <span style="background: #dc2626; color: #ffffff; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                <i class="ri-lock-fill"></i> EVALUACIÓN CERRADA
            </span>
        <?php else: ?>
            <span style="background: #e0f2fe; color: #0369a1; padding: 0.35rem 0.85rem; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.3rem;">
                <i class="ri-time-line"></i> EN PROCESO
            </span>
        <?php endif; ?>
    </div>

    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <p style="margin: 0; font-size: 0.875rem; color: #475569; max-width: 600px;">
            <?php if ($isClosed): ?>
                Esta evaluación ha sido finalizada y cerrada. Los campos se encuentran bloqueados para evitar alteraciones en la auditoría.
            <?php else: ?>
                Selecciona <strong>"Cerrado"</strong> cuando hayas finalizado la auditoría para bloquear la modificación de las respuestas y la matriz de riesgo.
            <?php endif; ?>
        </p>

        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <label for="statusId" style="font-weight: 700; color: #334155; font-size: 0.875rem; whitespace: nowrap;">
                Estado:
            </label>
            <select name="statusId" id="statusId" style="padding: 0.5rem 0.85rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; font-weight: 600; background-color: #ffffff; color: #0f172a; cursor: pointer;">
                <option value="1" <?= $currentStatusId === 1 ? 'selected' : '' ?>>En Proceso</option>
                <option value="2" <?= $currentStatusId === 2 ? 'selected' : '' ?>>Cerrado</option>
            </select>
        </div>
    </div>
</div>
 <?php }?>


        <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; margin-bottom: 4rem;">
            <a href="index.php" class="btn btn-secondary">Regresar al panel</a>
        <?php     // 3. Uso directo en verificacioness
if ($permisosModulo5['puede_editar'] == 1) {?>
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                <i class="ri-save-3-line"></i> Guardar Cuestionario Completo
            </button>
<?php } else {?>
    
<?php } ?>    


            
        
                
            
        </div>

    </form>
</div>

<!-- MODAL PARA REGISTRAR / EDITAR MATRIZ DE RIESGO -->
<div id="modalRiesgoAC" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 100%; max-width: 580px; border-radius: 8px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
        <div style="background: #0f172a; color: #ffffff; padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalRiesgoTituloAC" style="margin: 0; font-size: 1rem; font-weight: 700;">Agregar Riesgo a la Matriz (AC)</h3>
            <button type="button" onclick="cerrarModalRiesgoAC()" style="background: transparent; border: none; color: #ffffff; font-size: 1.25rem; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.25rem;">ID Riesgo:</label>
                <input type="text" id="ac_m_id" placeholder="Ej: R-01" style="width: 100%; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
            </div>
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.25rem;">Categoría de Riesgo:</label>
                <input type="text" id="ac_m_categoria" placeholder="Ej: Financiero / Control Interno" style="width: 100%; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
            </div>
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.25rem;">Descripción del Riesgo Identificado:</label>
                <textarea id="ac_m_descripcion" rows="3" placeholder="Descripción detallada del riesgo..." style="width: 100%; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;"></textarea>
            </div>
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.25rem;">Factor / Causa Raíz (Según AC):</label>
                <textarea id="ac_m_causa" rows="2" placeholder="Causa raíz o factor desencadenante..." style="width: 100%; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;"></textarea>
            </div>
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 0.25rem;">Nivel de Riesgo Inherente:</label>
                <select id="ac_m_nivel" style="width: 100%; padding: 0.45rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.85rem;">
                    <option value="Alto">Alto</option>
                    <option value="Moderado-Alto">Moderado-Alto</option>
                    <option value="Moderado">Moderado</option>
                    <option value="Bajo-Moderado">Bajo-Moderado</option>
                    <option value="Bajo" selected>Bajo</option>
                </select>
            </div>
        </div>
        <div style="padding: 1rem 1.25rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.5rem;">
            <button type="button" onclick="cerrarModalRiesgoAC()" class="btn btn-secondary" style="padding: 0.4rem 1rem;">Cancelar</button>
            <button type="button" id="btnGuardarModalRiesgoAC" onclick="guardarRiesgoAC()" class="btn btn-primary" style="padding: 0.4rem 1rem;">Agregar a la Tabla</button>
        </div>
    </div>
</div>
<script>
// Variable global para rastrear la fila que se está editando
let rowEditingAC = null;

function abrirModalRiesgoAC(btnEdit = null) {
    const modal = document.getElementById('modalRiesgoAC');
    const titulo = document.getElementById('modalRiesgoTituloAC');
    const btnGuardar = document.getElementById('btnGuardarModalRiesgoAC');

    if (btnEdit) {
        // MODO EDICIÓN: Obtener la fila relativa al botón
        rowEditingAC = btnEdit.closest('tr');
        titulo.textContent = 'Editar Riesgo de la Matriz (AC)';
        btnGuardar.textContent = 'Guardar Cambios';

        // Extraer los valores guardados en los inputs ocultos de la fila
        const inputs = rowEditingAC.querySelectorAll('input[type="hidden"]');
        document.getElementById('ac_m_id').value = inputs[0] ? inputs[0].value : '';
        document.getElementById('ac_m_categoria').value = inputs[1] ? inputs[1].value : '';
        document.getElementById('ac_m_descripcion').value = inputs[2] ? inputs[2].value : '';
        document.getElementById('ac_m_causa').value = inputs[3] ? inputs[3].value : '';
        document.getElementById('ac_m_nivel').value = inputs[4] ? inputs[4].value : 'Bajo';
    } else {
        // MODO CREACIÓN
        rowEditingAC = null;
        titulo.textContent = 'Agregar Riesgo a la Matriz (AC)';
        btnGuardar.textContent = 'Agregar a la Tabla';
        limpiarFormularioModalRiesgo();
    }

    modal.style.display = 'flex';
}

function cerrarModalRiesgoAC() {
    document.getElementById('modalRiesgoAC').style.display = 'none';
    limpiarFormularioModalRiesgo();
    rowEditingAC = null;
}

function limpiarFormularioModalRiesgo() {
    document.getElementById('ac_m_id').value = '';
    document.getElementById('ac_m_categoria').value = '';
    document.getElementById('ac_m_descripcion').value = '';
    document.getElementById('ac_m_causa').value = '';
    document.getElementById('ac_m_nivel').value = 'Bajo';
}

function editarRiesgoAC(btn) {
    abrirModalRiesgoAC(btn);
}

function escapeAC(str) {
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function guardarRiesgoAC() {
    const id = document.getElementById('ac_m_id').value.trim();
    const cat = document.getElementById('ac_m_categoria').value.trim();
    const desc = document.getElementById('ac_m_descripcion').value.trim();
    const causa = document.getElementById('ac_m_causa').value.trim();
    const nivel = document.getElementById('ac_m_nivel').value;

    if (!id || !desc) {
        alert('Debes ingresar al menos el ID del Riesgo y su Descripción.');
        return;
    }

    // Estructura interna HTML de la fila con los dos botones de acción
    const rowContent = `
        <td style="border: 1px solid #e2e8f0; padding: 0.6rem; font-weight: 700;">
            ${escapeAC(id)}
            <input type="hidden" name="matriz_id[]" value="${escapeAC(id)}">
        </td>
        <td style="border: 1px solid #e2e8f0; padding: 0.6rem;">
            ${escapeAC(cat)}
            <input type="hidden" name="matriz_categoria[]" value="${escapeAC(cat)}">
        </td>
        <td style="border: 1px solid #e2e8f0; padding: 0.6rem;">
            ${escapeAC(desc)}
            <input type="hidden" name="matriz_descripcion[]" value="${escapeAC(desc)}">
        </td>
        <td style="border: 1px solid #e2e8f0; padding: 0.6rem;">
            ${escapeAC(causa)}
            <input type="hidden" name="matriz_causa[]" value="${escapeAC(causa)}">
        </td>
        <td style="border: 1px solid #e2e8f0; padding: 0.6rem; text-align: center;">
            <strong>${escapeAC(nivel)}</strong>
            <input type="hidden" name="matriz_nivel[]" value="${escapeAC(nivel)}">
        </td>
        <td style="border: 1px solid #e2e8f0; padding: 0.6rem; text-align: center; white-space: nowrap;">
            <button type="button" onclick="editarRiesgoAC(this)" style="background: transparent; border: none; color: #0284c7; cursor: pointer; margin-right: 0.3rem;" title="Editar">
                <i class="ri-edit-line" style="font-size: 1.1rem;"></i>
            </button>
            <button type="button" onclick="eliminarRiesgoAC(this)" style="background: transparent; border: none; color: #ef4444; cursor: pointer;" title="Eliminar">
                <i class="ri-delete-bin-line" style="font-size: 1.1rem;"></i>
            </button>
        </td>
    `;

    if (rowEditingAC) {
        // Actualizar fila existente
        rowEditingAC.innerHTML = rowContent;
    } else {
        // Crear nueva fila
        const emptyRow = document.getElementById('rowEmptyMatrizAC');
        if (emptyRow) {
            emptyRow.remove();
        }

        const tbody = document.getElementById('tbodyMatrizAC');
        const tr = document.createElement('tr');
        tr.innerHTML = rowContent;
        tbody.appendChild(tr);
    }

    cerrarModalRiesgoAC();
}

function eliminarRiesgoAC(btn) {
    const tr = btn.closest('tr');
    tr.remove();

    const tbody = document.getElementById('tbodyMatrizAC');
    if (tbody.children.length === 0) {
        tbody.innerHTML = `
            <tr id="rowEmptyMatrizAC">
                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 1.5rem; border: 1px solid #e2e8f0;">
                    No se han agregado registros a la matriz de riesgo. Haz clic en <strong>Agregar Riesgo</strong>.
                </td>
            </tr>`;
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const textareas = document.querySelectorAll('.comment-input.auto-expand');

    /**
     * Ajusta la altura del textarea según el contenido
     * @param {HTMLTextAreaElement} el 
     */
    const autoResizeTextarea = (el) => {
        el.style.height = 'auto';
        el.style.height = `${el.scrollHeight}px`;
    };

    textareas.forEach((textarea) => {
        // 1. Ajustar altura inicial si ya existe texto cargado desde BD
        autoResizeTextarea(textarea);

        // 2. Escuchar evento de tipeo en tiempo real
        textarea.addEventListener('input', () => {
            autoResizeTextarea(textarea);
        });
    });
});
document.addEventListener('DOMContentLoaded', () => {
    const textareas = document.querySelectorAll('.comment-input.auto-expand');

    /**
     * Recalcula y ajusta la altura del textarea solo si el elemento es visible
     * @param {HTMLTextAreaElement} el 
     */
    const autoResizeTextarea = (el) => {
        // Verificar si el elemento es visible en el DOM (no está oculto por display: none)
        if (el.offsetParent !== null) {
            el.style.height = 'auto';
            el.style.height = `${el.scrollHeight}px`;
        }
    };

    /**
     * Redimensiona todos los textareas visibles en la vista
     */
    const resizeAllVisibleTextareas = () => {
        textareas.forEach((textarea) => autoResizeTextarea(textarea));
    };

    // 1. Escuchar evento de tipeo en tiempo real
    textareas.forEach((textarea) => {
        textarea.addEventListener('input', () => autoResizeTextarea(textarea));
    });

    // 2. Ajuste inicial en carga de la página
    resizeAllVisibleTextareas();

    // 3. Re-evaluar cuando la ventana cargue completamente (estilos CSS y fuentes aplicados)
    window.addEventListener('load', resizeAllVisibleTextareas);

    // 4. SOLUCIÓN ACORDEÓN: Escuchar clics en los encabezados/botones de acordeón
    // Ajusta la clase selector de tu acordeón si usas Bootstrap u otro framework (ej: .accordion-button, .accordion-header)
    const accordionTriggers = document.querySelectorAll('.accordion-header, .accordion-button, .accordion-toggle');

    accordionTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            // Se usa un pequeño retardador (150ms) para permitir que la animación de apertura CSS finalize
            setTimeout(resizeAllVisibleTextareas, 150);
        });
    });
});
</script>

<?php 
include 'js-responder.php'; 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>