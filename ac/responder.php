<?php
// v/ac/responder.php
include '../main/config.php';
include '../ac/conect-responder.php';

// --- VALIDACIÓN E INICIALIZACIÓN DE VARIABLES CRÍTICAS ---
$acId = isset($_GET['acId']) ? (int)$_GET['acId'] : 0;
if ($acId === 0) {
    die("Error: ID de evaluación no válido o ausente.");
}

// Inicializar arrays para evitar warnings de tipo "Undefined variable" si no vienen cargados del include
if (!isset($respuestasNormales)) {
    $respuestasNormales = [];
}
if (!isset($answersSaved)) {
    $answersSaved = [];
}
if (!isset($q28Saved)) {
    $q28Saved = [];
}

// --- LÓGICA ESPECIAL PARA LA PREGUNTA 28 ---
// 1. Contar el total de subpruebas configuradas en el sistema
$stmtTotal28 = $pdo->query("SELECT COUNT(*) FROM ac_q28_tests");
$totalSubtests = (int)$stmtTotal28->fetchColumn();

// 2. Contar cuántas subpruebas ya han sido respondidas para este acId
$stmtResp28 = $pdo->prepare("
    SELECT COUNT(*) 
    FROM ac_q28_answers 
    WHERE acId = :acId 
      AND riskValue IS NOT NULL 
      AND riskValue != ''
");
$stmtResp28->execute([':acId' => $acId]);
$answeredSubtests = (int)$stmtResp28->fetchColumn();

// 3. Determinar si la pregunta 28 está completamente lista
$isQ28Complete = ($answeredSubtests >= $totalSubtests && $totalSubtests > 0);
?>

<style>
    /* Estilos del Contenedor de la Tarjeta */
    .activities-grid-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .activities-grid-card h3 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        margin-top: 0;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Cuadrícula de 5 columnas compacta */
    .activities-grid {
        display: grid !important;
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 8px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    /* Estilo de cada Cajita Numérica */
    .activity-box {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        aspect-ratio: 1 / 1 !important; /* Cuadrados perfectos */
        width: 100% !important;
        border-radius: 6px !important;
        font-size: 0.85rem !important;
        font-weight: 700 !important;
        text-decoration: none !important;
        transition: all 0.2s ease-in-out !important;
        box-sizing: border-box !important;
        border: 1.5px solid #cbd5e1 !important;
    }

    /* Estado Pendiente (Rojo / Gris Suave) */
    .activity-box.pending {
        background-color: #fef2f2 !important;
        color: #ef4444 !important;
        border-color: #fca5a5 !important;
    }
    .activity-box.pending:hover {
        background-color: #fee2e2 !important;
        border-color: #f87171 !important;
        transform: translateY(-2px) !important;
    }

    /* Estado Completado / Listo (Verde) */
    .activity-box.completed {
        background-color: #ecfdf5 !important;
        color: #10b981 !important;
        border-color: #a7f3d0 !important;
    }
    .activity-box.completed:hover {
        background-color: #d1fae5 !important;
        border-color: #34d399 !important;
        transform: translateY(-2px) !important;
    }
</style>

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

    <div class="meta-summary">
        <div class="meta-item">Client / Empresa <strong><?= htmlspecialchars($acData->clientName ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item">Tipo Evaluación <strong><?= htmlspecialchars($acData->typeName ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item">Servicio Requerido <strong><?= htmlspecialchars($acData->serviceName ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong></div>

        <div class="meta-item" style="margin-left: auto; text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
            <span style="font-size: 0.8rem; color: var(--text-muted, #64748b); font-weight: 500;">Riesgo Calculado Matriz</span>
            <?php
            $riskClass = 'risk-bajo';
            $riskIcon = 'ri-checkbox-circle-line';
            $riskLevel = $acData->riskLevel ?? 'Bajo';
            $riskScore = $acData->riskScore ?? 0;
            
            if ($riskLevel === 'Moderado') { $riskClass = 'risk-moderado'; $riskIcon = 'ri-alert-line'; }
            elseif ($riskLevel === 'Moderado-Alto') { $riskClass = 'risk-moderado-alto'; $riskIcon = 'ri-error-warning-line'; }
            elseif ($riskLevel === 'Alto') { $riskClass = 'risk-alto'; $riskIcon = 'ri-close-circle-line'; }
            ?>
            <span id="live-risk-badge" class="badge-risk <?= $riskClass ?>">
                <i class="<?= $riskIcon ?>"></i> <?= $riskScore ?> Pts (<?= $riskLevel ?>)
            </span>
        </div>
    </div>

    <div class="activities-grid-card">
        <h3><i class="ri-grid-fill" style="color: var(--accent);"></i> Progreso General de Actividades (1-30)</h3>
        
        <div class="activities-grid">
            <?php for ($i = 1; $i <= 30; $i++): 
                // Evaluamos el estado inicial de completación cargado desde la Base de Datos
                if ($i === 28) {
                    $statusClass = $isQ28Complete ? 'completed' : 'pending';
                } else {
                    $statusClass = isset($completedActivities[$i]) ? 'completed' : 'pending';
                }
            ?>
                <a href="#question-<?php echo $i; ?>" 
                   class="activity-box <?php echo $statusClass; ?>" 
                   id="grid-box-<?php echo $i; ?>"
                   onclick="scrollToQuestion(<?php echo $i; ?>, event)">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>

        <div class="progress-container" style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span id="progress-text-label" style="font-size: 0.85rem; font-weight: 600; color: #475569;">Progreso del Formulario</span>
                <span id="progress-percent-text" style="font-size: 0.85rem; font-weight: 700; color: #10b981;">0%</span>
            </div>
            <div style="width: 100%; height: 6px; background-color: #e2e8f0; border-radius: 9999px; overflow: hidden;">
                <div id="progress-fill-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #10b981, #059669); border-radius: 9999px; transition: width 0.4s ease;"></div>
            </div>
        </div>
    </div>

    <form action="responder.php?acId=<?= $acId ?>" method="POST">
        
        <?php
        $categories = $pdo->query("SELECT * FROM ac_categories ORDER BY orderNum ASC")->fetchAll(PDO::FETCH_OBJ);
        
        [cite_start]// Mapeo JS de questionNumber => questionId para el progreso en vivo [cite: 364]
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
                        $savedComment = $answersSaved[$q->questionId]['comment'] ?? '' // <-- ¡Falta el punto y coma aquí!

                        // Guardar la correspondencia de número a ID
                        $qNumberToIdMap[$q->questionNumber] = [ 'id' => $q->questionId, 'completed' => (!empty($savedRes)) ];
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
                                                    <select name="q28[<?= $sub->testId ?>]" class="q28-select" style="width: 100%; max-width: 200px;">
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

<script>
// Guardar el estado inicial cargado de la base de datos
const backendProgress = <?= json_encode($qNumberToIdMap) ?>;

function toggleAccordion(headerElement) {
    const item = headerElement.parentElement;
    if (item.classList.contains('active')) {
        item.classList.remove('active');
    } else {
        document.querySelectorAll('.accordion-item').forEach(el => el.classList.remove('active'));
        item.classList.add('active');
    }
}

// Navegación con scroll inteligente y apertura automática de acordeón
function scrollToQuestion(qNum, event) {
    if(event) event.preventDefault();
    const targetElement = document.getElementById(`question-${qNum}`);
    if (targetElement) {
        // Encontrar si el elemento está dentro de un acordeón colapsado y abrirlo
        const accordionItem = targetElement.closest('.accordion-item');
        if (accordionItem && !accordionItem.classList.contains('active')) {
            const header = accordionItem.querySelector('.accordion-header');
            toggleAccordion(header);
        }
        
        // Scroll suave al contenedor de la pregunta
        setTimeout(() => {
            targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 150);
    }
}

// Función para verificar si la pregunta 28 está completamente respondida (todas las subpruebas asignadas)
function isQ28FullyAnswered() {
    const selects = document.querySelectorAll('.q28-select');
    if (selects.length === 0) return false;
    
    let answeredCount = 0;
    selects.forEach(select => {
        if (select.value && select.value !== '') {
            // Se considera completado si han elegido un valor válido
            answeredCount++;
        }
    });
    return answeredCount === selects.length;
}

// Recalcular porcentaje global y actualizar barra de progreso dinámicamente [cite: 363]
function updateLiveProgressBar() {
    const totalQuestions = 30;
    const completedBoxes = document.querySelectorAll('.activities-grid .activity-box.completed').length;
    const percent = Math.round((completedBoxes / totalQuestions) * 100);
    
    const percentText = document.getElementById('progress-percent-text');
    const fillBar = document.getElementById('progress-fill-bar');
    const progressTextLabel = document.getElementById('progress-text-label');
    
    if (percentText) percentText.innerText = `${percent}%`;
    if (fillBar) fillBar.style.width = `${percent}%`;
    if (progressTextLabel) progressTextLabel.innerText = `Progreso del Formulario (${percent}%)`;
}

function updateProgressGrid() {
    // 1. Cargar estados iniciales desde PHP y mapear la cuadrícula
    Object.keys(backendProgress).forEach(qNum => {
        const box = document.getElementById(`grid-box-${qNum}`);
        if(box) {
            // Caso Especial Pregunta 28: Depende de que todas las subpruebas estén llenas 
            if (parseInt(qNum) === 28) {
                if (isQ28FullyAnswered()) {
                    box.classList.remove('pending');
                    box.classList.add('completed');
                } else {
                    box.classList.remove('completed');
                    box.classList.add('pending');
                }
            } else {
                // Flujo normal para las demás preguntas de Sí o No 
                if(backendProgress[qNum].completed) {
                    box.classList.remove('pending');
                    box.classList.add('completed');
                } else {
                    box.classList.remove('completed');
                    box.classList.add('pending');
                }
            }
        }
    });

    // Calcular y renderizar el progreso inicial
    updateLiveProgressBar();

    // 2. Escuchar cambios dinámicos en los radios (Preguntas 1 - 27 y 29 - 30)
    document.querySelectorAll('.q-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const qNum = this.getAttribute('data-qnum');
            if (parseInt(qNum) === 28) return; // Ignoramos la 28 aquí

            const box = document.getElementById(`grid-box-${qNum}`);
            if (box && this.checked) {
                box.classList.remove('pending');
                box.classList.add('completed');
                updateLiveProgressBar();
            }
        });
    });

    // 3. Escuchar cambios dinámicos en los Selects de la Pregunta 28
    document.querySelectorAll('.q28-select').forEach(select => {
        select.addEventListener('change', function() {
            const box = document.getElementById('grid-box-28');
            if (box) {
                if (isQ28FullyAnswered()) {
                    box.classList.remove('pending');
                    box.classList.add('completed');
                } else {
                    box.classList.remove('completed');
                    box.classList.add('pending');
                }
                updateLiveProgressBar();
            }
        });
    });
}

function calculateLiveRisk() {
    const selects = document.querySelectorAll('.q28-select');
    let score = 0;
    
    const pointsMap = {
        'No Aplica': 0,
        'Bajo': 1,
        'Bajo-Moderado': 2,
        'Moderado': 3,
        'Moderado-Alto': 4,
        'Alto': 5
    };

    selects.forEach(select => {
        score += pointsMap[select.value] || 0;
    });

    let level = 'Bajo';
    let cssClass = 'risk-bajo';
    let iconClass = 'ri-checkbox-circle-line';

    if (score <= 25) {
        level = 'Bajo'; cssClass = 'risk-bajo'; iconClass = 'ri-checkbox-circle-line';
    } else if (score <= 55) {
        level = 'Moderado'; cssClass = 'risk-moderado'; iconClass = 'ri-alert-line';
    } else if (score <= 85) {
        level = 'Moderado-Alto'; cssClass = 'risk-moderado-alto'; iconClass = 'ri-error-warning-line';
    } else {
        level = 'Alto'; cssClass = 'risk-alto'; iconClass = 'ri-close-circle-line';
    }

    const badge = document.getElementById('live-risk-badge');
    if (badge) {
        badge.className = `badge-risk ${cssClass}`;
        badge.innerHTML = `<i class="${iconClass}"></i> ${score} Pts (${level})`;
    }
}

document.addEventListener("DOMContentLoaded", () => {
    // Escuchar cambios para actualizar el badge del cálculo del nivel de riesgo acumulado
    document.querySelectorAll('.q28-select').forEach(select => {
        select.addEventListener('change', calculateLiveRisk);
    });

    calculateLiveRisk();
    updateProgressGrid();
});
</script>

<?php 
// Cierre del layout modular y scripts de navegación móvil
include '../main/layout_footer.php'; 

// Footer nativo del sistema
include '../main/footer.php'; 
?>