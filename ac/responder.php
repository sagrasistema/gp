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

        // A. Guardar las respuestas a las 30 preguntas generales
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            $stmtUpdateAnswer = $pdo->prepare("
                UPDATE ac_general_answers 
                SET response = :response, comment = :comment 
                WHERE acId = :acId AND questionId = :questionId
            ");
            foreach ($_POST['answers'] as $qId => $data) {
                $stmtUpdateAnswer->execute([
                    ':response'   => $data['response'] ?? null,
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
                UPDATE ac_q28_answers 
                SET riskValue = :riskValue, score = :score 
                WHERE acId = :acId AND testId = :testId
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
                    ':riskValue' => $riskValue,
                    ':score'     => $score,
                    ':acId'      => $acId,
                    ':testId'    => $tId
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

<style>
    .ac-container { max-width: 1000px; margin: 20px auto; padding: 0 15px; font-family: 'Segoe UI', Roboto, sans-serif; }
    
    /* Resumen Superior */
    .meta-summary { background: #fff; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color, #e2e8f0); margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 2rem; }
    .meta-item { font-size: 0.9rem; color: var(--text-muted, #64748b); }
    .meta-item strong { color: var(--text-main, #0f172a); display: block; font-size: 1.05rem; }
    
    /* Acordeones Principales (Categorías) */
    .accordion-item { background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; margin-bottom: 0.5rem; overflow: hidden; }
    .accordion-header { background: #fff; padding: 1rem 1.25rem; font-size: 0.95rem; font-weight: 600; color: #334155; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; transition: background 0.2s; border-left: 4px solid var(--accent, #0284c7); }
    .accordion-header:hover { background: #f8fafc; }
    .accordion-header i { font-size: 1.2rem; color: #64748b; transition: transform 0.2s; }
    
    /* Clase activa para mostrar contenido */
    .accordion-item.active .accordion-header { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; }
    .accordion-item.active .accordion-header i { transform: rotate(180deg); }
    .accordion-content { display: none; padding: 1.25rem; background: #fafafa; }
    .accordion-item.active .accordion-content { display: block; }

    /* Filas de Preguntas */
    .question-row { background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 1.25rem; margin-bottom: 0.75rem; }
    .question-text { font-size: 0.95rem; font-weight: 500; color: #1e293b; margin-bottom: 1rem; line-height: 1.4; }
    .question-inputs { display: grid; grid-template-columns: 180px 1fr; gap: 1.5rem; align-items: center; }
    
    /* Inputs de Opción */
    .radio-group { display: flex; gap: 1.25rem; }
    .radio-label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; cursor: pointer; font-weight: 600; color: #475569; }
    .radio-label input { width: 17px; height: 17px; accent-color: var(--accent, #0284c7); }
    
    .comment-input { width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0.5rem 0.75rem; font-size: 0.88rem; outline: none; transition: border-color 0.2s; }
    .comment-input:focus { border-color: var(--accent, #0284c7); }

    /* Tabla de la Matriz Q28 */
    .subtest-table { width: 100%; border-collapse: collapse; margin-top: 1.25rem; font-size: 0.88rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; }
    .subtest-table th { background: #f8fafc; text-align: left; padding: 0.75rem; font-size: 0.8rem; color: #64748b; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
    .subtest-table td { padding: 0.75rem; border-bottom: 1px solid #e2e8f0; color: #334155; }
    .subtest-table select { padding: 0.4rem; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 0.85rem; width: 100%; max-width: 180px; background: #fff; outline: none; }
    .subtest-table select:focus { border-color: var(--accent, #0284c7); }
    
    .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
</style>

<div class="ac-container">
    <header style="margin-bottom: 1.5rem;">
        <img src="../main/logo.png" alt="Logo Corporativo" class="brand-logo" style="cursor: pointer;" onclick="window.location.href='../index.php'">
        <h1><i class="ri-survey-line"></i> Ejecutar Cuestionario</h1>
        <a href="index.php" class="btn-back"><i class="ri-arrow-left-line"></i></a>
    </header>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
            <i class="ri-checkbox-circle-fill"></i> Respuestas guardadas y nivel de riesgo recalculado de forma correcta.
        </div>
    <?php endif; ?>

    <div class="meta-summary">
        <div class="meta-item">Client / Empresa <strong><?= htmlspecialchars($acData->clientName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item">Tipo Evaluación <strong><?= htmlspecialchars($acData->typeName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item">Servicio Requerido <strong><?= htmlspecialchars($acData->serviceName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item" style="margin-left: auto; text-align: right;">
            Riesgo Calculado Matriz 
            <strong id="live-risk-badge" style="font-size: 1.2rem; color: var(--accent, #0284c7);">
                <?= $acData->riskScore ?> Pts (<?= $acData->riskLevel ?>)
            </strong>
        </div>
    </div>

    <form action="responder.php?acId=<?= $acId ?>" method="POST">
        
        <?php
        $categories = $pdo->query("SELECT * FROM ac_categories ORDER BY orderNum ASC")->fetchAll(PDO::FETCH_OBJ);
        
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
                    ?>
                        <div class="question-row">
                            <div class="question-text">
                                <strong><?= $q->questionNumber ?>.</strong> <?= htmlspecialchars($q->questionText, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            
                            <div class="question-inputs">
                                <div class="radio-group">
                                    <label class="radio-label">
                                        <input type="radio" name="answers[<?= $q->questionId ?>][response]" value="Si" <?= $savedRes === 'Si' ? 'checked' : '' ?> required> Sí
                                    </label>
                                    <label class="radio-label">
                                        <input type="radio" name="answers[<?= $q->questionId ?>][response]" value="No" <?= $savedRes === 'No' ? 'checked' : '' ?>> No
                                    </label>
                                </div>
                                
                                <div>
                                    <input type="text" name="answers[<?= $q->questionId ?>][comment]" class="comment-input" placeholder="Comentarios o justificación..." value="<?= htmlspecialchars($savedComment, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>

                            <?php if ($q->questionNumber == 28): ?>
                                <div style="margin-top: 1.5rem; background: #f8fafc; padding: 1.25rem; border-radius: 6px; border: 1px dashed #cbd5e1;">
                                    <h4 style="font-size: 0.9rem; color: #1e293b; margin-bottom: 0.5rem; font-weight: 700;"><i class="ri-matrix-line"></i> Desglose Analítico Matriz de Riesgo Interno (Prueba 28)</h4>
                                    <table class="subtest-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">N°</th>
                                                <th>Descripción de la Prueba de Control</th>
                                                <th style="width: 25%;">Nivel de Riesgo Asignado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $subtests = $pdo->query("SELECT * FROM ac_q28_tests ORDER BY testNumber ASC")->fetchAll(PDO::FETCH_OBJ);
                                            foreach ($subtests as $sub):
                                                $savedRisk = $q28Saved[$sub->testId]['riskValue'] ?? 'No Aplica';
                                            ?>
                                                <tr>
                                                    <td><strong><?= $sub->testNumber ?></strong></td>
                                                    <td><?= htmlspecialchars($sub->testText, ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td>
                                                        <select name="q28[<?= $sub->testId ?>]" class="q28-select" onchange="calculateLiveRisk()">
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
// Manejar la apertura y el colapso del acordeón
function toggleAccordion(headerElement) {
    const item = headerElement.parentElement;
    
    // Si ya está activo, lo cerramos; si no, lo abrimos
    if (item.classList.contains('active')) {
        item.classList.remove('active');
    } else {
        // Opcional: Cerrar otros acordeones abiertos para mantener orden (limpieza visual)
        document.querySelectorAll('.accordion-item').forEach(el => el.classList.remove('active'));
        item.classList.add('active');
    }
}

// Calcular riesgo en tiempo real
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
    if (score <= 25) level = 'Bajo';
    else if (score <= 55) level = 'Moderado';
    else if (score <= 85) level = 'Moderado-Alto';
    else level = 'Alto';

    document.getElementById('live-risk-badge').innerText = `${score} Pts (${level})`;
}

document.addEventListener("DOMContentLoaded", calculateLiveRisk);
</script>

<?php include '../main/footer.php'; ?>