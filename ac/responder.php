<?php
// v/ac/responder.php
include '../main/config.php';

// Validar que exista el ID de la evaluación a responder
$acId = filter_input(INPUT_GET, 'acId', FILTER_VALIDATE_INT);

if (!$acId) {
    die("Error: No se especificó una evaluación válida.");
}

// 1. Obtener la cabecera de la AC junto con el nombre del cliente
try {
    $stmtAC = $pdo->prepare("
        SELECT ac.*, c.name AS clientName, t.typeName, s.serviceName 
        FROM ac 
        JOIN clientes c ON ac.clientId = c.id
        JOIN ac_types t ON ac.typeId = t.typeId
        JOIN ac_services s ON ac.serviceId = s.serviceId
        WHERE ac.acId = :acId
    ");
    $stmtAC->execute([':acId' => $acId]);
    $acData = $stmtAC->fetch(PDO::FETCH_OBJ);

    if (!$acData) {
        die("Error: La evaluación solicitada no existe.");
    }
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}

// ==========================================
// LÓGICA DE PROCESAMIENTO / GUARDADO (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // A. Guardar las respuestas a las 30 preguntas generales (Permitiendo vacíos)
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            $stmtUpdateAnswer = $pdo->prepare("
                UPDATE ac_general_answers 
                SET response = :response, comment = :comment 
                WHERE acId = :acId AND questionId = :questionId
            ");
            foreach ($_POST['answers'] as $qId => $data) {
                $responseValue = (!empty($data['response'])) ? $data['response'] : null;

                $stmtUpdateAnswer->execute([
                    ':response'   => $responseValue,
                    ':comment'    => $data['comment'] ?? '',
                    ':acId'       => $acId,
                    ':questionId' => $qId
                ]);
            }
        }
        
        // B. Guardar las 21 subpruebas de la Pregunta 28 y calcular el Score
        $totalScore = 0;
        if (isset($_POST['q28']) && is_array($_POST['q28'])) {
            
            $stmtUpdateQ28 = $pdo->prepare("
                INSERT INTO ac_q28_answers (acId, testId, riskValue, score) 
                VALUES (:acId, :testId, :riskValue, :score)
                ON DUPLICATE KEY UPDATE riskValue = :riskValueUpdate, score = :scoreUpdate
            ");
            
            $pointsMap = [
                'No Aplica'       => 0,
                'Bajo'            => 1,
                'Bajo-Moderado'   => 2,
                'Moderado'        => 3,
                'Moderado-Alto'   => 4,
                'Alto'            => 5
            ];

            foreach ($_POST['q28'] as $tId => $riskValue) {
                $score = $pointsMap[$riskValue] ?? 0;
                $totalScore += $score;

                $stmtUpdateQ28->execute([
                    ':acId'             => $acId,
                    ':testId'           => $tId,
                    ':riskValue'        => $riskValue,
                    ':score'            => $score,
                    ':riskValueUpdate'  => $riskValue,
                    ':scoreUpdate'      => $score
                ]);
            }
        }
        
        // C. Determinar cualitativamente el Rango de riesgo
        if ($totalScore <= 25) {
            $riskLevel = 'Bajo';
        } elseif ($totalScore <= 55) {
            $riskLevel = 'Moderado';
        } elseif ($totalScore <= 85) {
            $riskLevel = 'Moderado-Alto';
        } else {
            $riskLevel = 'Alto';
        }

        // D. Actualizar totales en `ac`
        $stmtUpdateAC = $pdo->prepare("
            UPDATE ac SET riskScore = :riskScore, riskLevel = :riskLevel WHERE acId = :acId
        ");
        $stmtUpdateAC->execute([
            ':riskScore' => $totalScore,
            ':riskLevel' => $riskLevel,
            ':acId'      => $acId
        ]);

        $pdo->commit();
        
        header("Location: responder.php?acId=" . $acId . "&success=1");
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error al guardar las respuestas: " . $e->getMessage());
    }
}

// Cargar respuestas guardadas
$answersSaved = $pdo->query("SELECT questionId, response, comment FROM ac_general_answers WHERE acId = $acId")->fetchAll(PDO::FETCH_UNIQUE);
$q28Saved = $pdo->query("SELECT testId, riskValue FROM ac_q28_answers WHERE acId = $acId")->fetchAll(PDO::FETCH_UNIQUE);

$pageTitle = "Responder Cuestionario AC";
include '../main/h.php';
?>

<link rel="stylesheet" href="../main/layout.css">

<style>
    /* Estilos internos específicos del Formulario y Acordeones */
    .view-container-form { width: 100%; max-width: 1000px; margin: 0 auto; }
    
    .meta-summary { background: #fff; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color, #e2e8f0); margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 2rem; }
    .meta-item { font-size: 0.9rem; color: var(--text-muted, #64748b); }
    .meta-item strong { color: var(--text-main, #0f172a); display: block; font-size: 1.05rem; }
    
    /* CUADRÍCULA DE ACTIVIDADES (PROGRESO) */
    .activities-grid-card { background: #fff; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 1.25rem; margin-bottom: 1.5rem; }
    .activities-grid-card h3 { font-size: 0.95rem; font-weight: 700; margin-top: 0; margin-bottom: 0.75rem; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
    .activities-grid { display: grid; grid-template-columns: repeat(15, 1fr); gap: 0.5rem; }
    .activity-box { display: flex; align-items: center; justify-content: center; height: 35px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; text-decoration: none; border: 1px solid #cbd5e1; transition: all 0.2s ease-in-out; cursor: pointer; }
    
    /* Estados de la Cuadrícula */
    .activity-box.pending { background: #f1f5f9; color: #64748b; border-color: #cbd5e1; }
    .activity-box.completed { background: #10b981; color: #ffffff; border-color: #059669; }
    .activity-box:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }

    .accordion-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 0.5rem; overflow: hidden; }
    .accordion-header { background: #fff; padding: 1rem 1.25rem; font-size: 0.95rem; font-weight: 600; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; transition: background 0.2s; border-left: 4px solid var(--accent, #0284c7); }
    .accordion-header:hover { background: #f8fafc; }
    .accordion-header i { font-size: 1.2rem; color: #64748b; transition: transform 0.2s; }
    
    .accordion-item.active .accordion-header { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
    .accordion-item.active .accordion-header i { transform: rotate(180deg); }
    .accordion-content { display: none; padding: 1.25rem; background: #fafafa; }
    .accordion-item.active .accordion-content { display: block; }

    .question-row { background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 1.25rem; margin-bottom: 0.75rem; scroll-margin-top: 80px; }
    .question-text { font-size: 0.95rem; font-weight: 500; color: #1e293b; margin-bottom: 1rem; line-height: 1.4; }
    .question-inputs { display: grid; grid-template-columns: 180px 1fr; gap: 1.5rem; align-items: center; }
    
    .radio-group { display: flex; gap: 1.25rem; }
    .radio-label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; cursor: pointer; font-weight: 600; color: #475569; }
    .radio-label input { width: 17px; height: 17px; accent-color: var(--accent, #0284c7); }
    
    .comment-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0.5rem 0.75rem; font-size: 0.88rem; outline: none; transition: border-color 0.2s; }
    .comment-input:focus { border-color: var(--accent, #0284c7); }

    .subtest-table { width: 100%; border-collapse: collapse; margin-top: 1.25rem; font-size: 0.88rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; }
    .subtest-table th { background: #f8fafc; text-align: left; padding: 0.75rem; font-size: 0.8rem; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
    .subtest-table td { padding: 0.75rem; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .subtest-table select { padding: 0.4rem; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 0.85rem; width: 100%; max-width: 180px; background: #fff; outline: none; }
    .subtest-table select:focus { border-color: var(--accent, #0284c7); }
    
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }

    .badge-risk { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.95rem; font-weight: 700; transition: all 0.3s ease; }
    .badge-risk.risk-bajo { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .badge-risk.risk-moderado { background-color: #fefce8; color: #854d0e; border: 1px solid #fef08a; }
    .badge-risk.risk-moderado-alto { background-color: #fff7ed; color: #9a3412; border: 1px solid #ffedd5; }
    .badge-risk.risk-alto { background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }

    @media (max-width: 768px) {
        .meta-summary { flex-direction: column; gap: 1rem !important; }
        .meta-item:last-child { align-items: flex-start !important; text-align: left !important; margin-left: 0 !important; }
        .question-inputs { grid-template-columns: 1fr; gap: 1rem; }
        .activities-grid { grid-template-columns: repeat(6, 1fr); }
    }
</style>

<?php
// Mapeo dinámico de rutas del layout de la subcarpeta ac/
$customLogoPath = '../main/logo.png'; 
$customHomePath = '../index.php';     
$customAcPath   = 'index.php';  
$currentTab     = 'aceptacion'; 

include '../main/layout_header.php'; 
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

    <div class="meta-summary">
        <div class="meta-item">Client / Empresa <strong><?= htmlspecialchars($acData->clientName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item">Tipo Evaluación <strong><?= htmlspecialchars($acData->typeName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item">Servicio Requerido <strong><?= htmlspecialchars($acData->serviceName, ENT_QUOTES, 'UTF-8') ?></strong></div>

        <div class="meta-item" style="margin-left: auto; text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
            <span style="font-size: 0.8rem; color: var(--text-muted, #64748b); font-weight: 500;">Riesgo Calculado Matriz</span>
            <?php
            $riskClass = 'risk-bajo';
            $riskIcon = 'ri-checkbox-circle-line';
            
            if ($acData->riskLevel === 'Moderado') { $riskClass = 'risk-moderado'; $riskIcon = 'ri-alert-line'; }
            elseif ($acData->riskLevel === 'Moderado-Alto') { $riskClass = 'risk-moderado-alto'; $riskIcon = 'ri-error-warning-line'; }
            elseif ($acData->riskLevel === 'Alto') { $riskClass = 'risk-alto'; $riskIcon = 'ri-close-circle-line'; }
            ?>
            <span id="live-risk-badge" class="badge-risk <?= $riskClass ?>">
                <i class="<?= $riskIcon ?>"></i> <?= $acData->riskScore ?> Pts (<?= $acData->riskLevel ?>)
            </span>
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

function updateProgressGrid() {
    // 1. Cargar estados iniciales desde PHP
    Object.keys(backendProgress).forEach(qNum => {
        const box = document.getElementById(`grid-box-${qNum}`);
        if(box) {
            if(backendProgress[qNum].completed) {
                box.classList.remove('pending');
                box.classList.add('completed');
            } else {
                box.classList.remove('completed');
                box.classList.add('pending');
            }
        }
    });

    // 2. Escuchar cambios dinámicos en los radios para actualizar la UI sin guardar
    document.querySelectorAll('.q-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const qNum = this.getAttribute('data-qnum');
            const box = document.getElementById(`grid-box-${qNum}`);
            if (box && this.checked) {
                box.classList.remove('pending');
                box.classList.add('completed');
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