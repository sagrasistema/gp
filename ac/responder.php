<?php
// v/ac/responder.php
include '../main/config.php';
include '../ac/conect-responder.php';
// Validar que exista el ID de la evaluación a responder
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
    <div class="meta-summary" style="display: flex; flex-direction: column; gap: 1.25rem; width: 100%;">
        
        <div class="meta-row-top" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1.5rem; width: 100%; align-items: center;">
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

            <div class="meta-item" style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                <span style="font-size: 0.8rem; color: var(--text-muted, #64748b); font-weight: 500;">Riesgo Calculado Matriz</span>
                <?php
                $riskClass = 'risk-bajo';
                $riskIcon = 'ri-checkbox-circle-line';
                
                if ($acData->riskLevel === 'Moderado') { $riskClass = 'risk-moderado'; $riskIcon = 'ri-alert-line'; }
                elseif ($acData->riskLevel === 'Moderado-Alto') { $riskClass = 'risk-moderado-alto'; $riskIcon = 'ri-error-warning-line'; }
                elseif ($acData->riskLevel === 'Alto') { $riskClass = 'risk-alto'; $riskIcon = 'ri-close-circle-line'; }
                ?>
                <span id="live-risk-badge" class="badge-risk <?= $riskClass ?>" style="white-space: nowrap;">
                    <i class="<?= $riskIcon ?>"></i> <?= $acData->riskScore ?> Pts (<?= $acData->riskLevel ?>)
                </span>
            </div>
        </div>

        <hr style="margin: 0; border: 0; border-top: 1px solid var(--border-color, #e2e8f0); opacity: 0.6;">

        <div class="meta-row-bottom" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1.5rem; width: 100%; align-items: center;">
            <div class="meta-item">Socio Líder de A&C <br><strong><?= htmlspecialchars($acData->partnerName, ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="meta-item">Gerente de A&C <br><strong><?= htmlspecialchars($acData->managerName, ENT_QUOTES, 'UTF-8') ?></strong></div>
            <div class="meta-item">Socio de Riesgo <br><strong><?= htmlspecialchars($acData->riskUserId, ENT_QUOTES, 'UTF-8') ?></strong></div>
            
                <div class="meta-item">
                <span style="font-size: 0.8rem; color: var(--text-muted, #64748b); font-weight: 500;">Riesgo Calculado Matriz</span>
                <?php
                $riskClass = 'risk-bajo';
                $riskIcon = 'ri-checkbox-circle-line';
                
                if ($acData->riskLevel === 'Moderado') { $riskClass = 'risk-moderado'; $riskIcon = 'ri-alert-line'; }
                elseif ($acData->riskLevel === 'Moderado-Alto') { $riskClass = 'risk-moderado-alto'; $riskIcon = 'ri-error-warning-line'; }
                elseif ($acData->riskLevel === 'Alto') { $riskClass = 'risk-alto'; $riskIcon = 'ri-close-circle-line'; }
                ?>
                <span id="live-risk-badge" class="badge-risk <?= $riskClass ?>" style="white-space: nowrap;">
                    <i class="<?= $riskIcon ?>"></i> <?= $acData->riskScore ?> Pts (<?= $acData->riskLevel ?>)
                </span>
            </div>
<div class="meta-item" style="display: flex; justify-content: flex-end; width: 100%;">
    <div class="gauge-wrapper">
        <svg class="gauge-svg" viewBox="0 -8 200 118" width="160" height="94">
            
            <path d="M 8 100 A 92 92 0 0 1 35 35 L 52 52 A 68 68 0 0 0 32 100 Z" fill="#22c55e" />
            
            <path d="M 35 35 A 92 92 0 0 1 100 8 L 100 32 A 68 68 0 0 0 52 52 Z" fill="#eab308" />
            
            <path d="M 100 8 A 92 92 0 0 1 165 35 L 148 52 A 68 68 0 0 0 100 32 Z" fill="#f97316" />
            
            <path d="M 165 35 A 92 92 0 0 1 192 100 L 168 100 A 68 68 0 0 0 148 52 Z" fill="#ef4444" />

            <text x="2" y="112" class="gauge-text">0</text>
            <text x="23" y="24" class="gauge-text">25</text>
            <text x="100" y="-1" class="gauge-text">50</text>
            <text x="177" y="24" class="gauge-text">75</text>
            <text x="198" y="112" class="gauge-text">100</text>

            <?php
            // Capturamos el score de riesgo de tu sistema (ej: 64 Pts)
            $score = isset($acData->riskScore) ? (float)$acData->riskScore : 0;
            
            // Forzamos límites para evitar que la aguja se descontrole
            $clampedScore = max(0, min(100, $score));
            
            // Mapeo perfecto: 0 Pts -> -90° (Izquierda), 100 Pts -> 90° (Derecha)
            $angle = -90 + (($clampedScore - 0) / (100 - 0)) * 180;
            ?>
            <g transform="rotate(<?= $angle ?>, 100, 100)">
                <path d="M 97.5 100 L 99.5 10 L 100.5 10 L 102.5 100 Z" fill="#1e293b" />
            </g>

            <circle cx="100" cy="100" r="11" fill="#1e293b" stroke="#ffffff" stroke-width="2.5" />
            <circle cx="100" cy="100" r="4" fill="#94a3b8" />
        </svg>
    </div>
</div>

            
        </div>

    </div>

    <div class="activities-grid-card">
        <h3><i class="ri-grid-fill" style="color: var(--accent);"></i> Progreso General de Actividades (1-30)</h3>
        <div class="activities-grid">
            <?php 
            // Generar los 30 botones del progreso interactivo
            for ($i = 1; $i <= 30; $i++): 
                // Verificar si esta pregunta ya fue respondida en BD
                // Nota: se asume que las preguntas tienen IDs correlativos o que asociamos los números de forma directa.
                // Buscaremos dinámicamente si la pregunta con questionNumber = $i tiene respuesta.
                $isCompleted = false;
                foreach($answersSaved as $qId => $ans) {
                    // Como el ID de pregunta puede diferir, buscaremos más abajo la asociación exacta
                }
            ?>
                <a href="#question-<?= $i ?>" id="grid-box-<?= $i ?>" class="activity-box pending" onclick="scrollToQuestion(<?= $i ?>, event)">
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
        
        // Mantendremos un mapeo JS de questionNumber => questionId para el progreso en vivo
        $qNumberToIdMap = [];

        foreach ($categories as $cat):
            $stmtQ = $pdo->prepare("SELECT * FROM ac_questions WHERE categoryId = :catId ORDER BY questionNumber ASC");
            $stmtQ->execute([':catId' => $cat->categoryId]);
            $questions = $stmtQ->fetchAll(PDO::FETCH_OBJ);
        ?>
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <span><?= htmlspecialchars($cat->categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                
                <div class="accordion-content">
                    <?php foreach ($questions as $q): 
                        $savedRes = $answersSaved[$q->questionId]['response'] ?? '';
                        $savedComment = $answersSaved[$q->questionId]['comment'] ?? '';
                        
                        // Guardar la correspondencia de número a ID
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
                                               name="answers[<?= $q->questionId ?>][response]" 
                                               value="Si" 
                                               class="q-radio" 
                                               data-qnum="<?= $q->questionNumber ?>" 
                                               <?= $savedRes === 'Si' ? 'checked' : '' ?>> Sí
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" 
                                               name="answers[<?= $q->questionId ?>][response]" 
                                               value="No" 
                                               class="q-radio" 
                                               data-qnum="<?= $q->questionNumber ?>" 
                                               <?= $savedRes === 'No' ? 'checked' : '' ?>> No
                                    </label>
                                </div>
                                
                                <div>
                                    <input type="text" name="answers[<?= $q->questionId ?>][comment]" class="comment-input" placeholder="Comentarios o justificación..." value="<?= htmlspecialchars($savedComment, ENT_QUOTES, 'UTF-8') ?>">
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
        <?php endforeach; ?>

        <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end; margin-bottom: 4rem;">
            <a href="index.php" class="btn btn-secondary">Regresar al panel</a>
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                <i class="ri-save-3-line"></i> Guardar Cuestionario Completo
            </button>
        </div>
    </form>
</div>

<?php 
// Cierre del layout modular y scripts de navegación móvil
include 'js-responder.php'; 
include '../main/layout_footer.php'; 

// Footer nativo del sistema
include '../main/footer.php'; 
?>