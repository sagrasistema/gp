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
    $stmtAC =$pdo->prepare("
        SELECT ac.*, c.name AS clientName, t.typeName, s.serviceName 
        FROM ac 
        JOIN clientes c ON ac.clientId = c.id
        JOIN ac_types t ON ac.typeId = t.typeId
        JOIN ac_services s ON ac.serviceId = s.serviceId
        WHERE ac.acId = :acId
    ");
    $stmtAC->execute([':acId' =>$acId]);
    $acData =$stmtAC->fetch(PDO::FETCH_OBJ);

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
            $stmtUpdateAnswer =$pdo->prepare("
                UPDATE ac_general_answers 
                SET response = :response, comment = :comment 
                WHERE acId = :acId AND questionId = :questionId
            ");
            foreach ($_POST['answers'] as$qId => $data) {$stmtUpdateAnswer->execute([
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
            $stmtUpdateQ28 =$pdo->prepare("
                UPDATE ac_q28_answers 
                SET riskValue = :riskValue, score = :score 
                WHERE acId = :acId AND testId = :testId
            ");
            
            // Tabla de equivalencia de puntos
            $pointsMap = [
                'No Aplica'       => 0,
                'Bajo'            => 1,
                'Bajo-Moderado'   => 2,
                'Moderado'        => 3,
                'Moderado-Alto'   => 4,
                'Alto'            => 5
            ];

            foreach ($_POST['q28'] as$tId => $riskValue) {$score = $pointsMap[$riskValue] ?? 0;
                $totalScore +=$score;

                $stmtUpdateQ28->execute([
                    ':riskValue' => $riskValue,
                    ':score'     => $score,
                    ':acId'      => $acId,
                    ':testId'    => $tId
                ]);
            }
        }

        // C. Determinar cualitativamente el Rango del nivel de riesgo en base al Score
        // Máximo score posible = 21 pruebas * 5 puntos = 105 puntos.
        if ($totalScore <= 25) {$riskLevel = 'Bajo';
        } elseif ($totalScore <= 55) {$riskLevel = 'Moderado';
        } elseif ($totalScore <= 85) {$riskLevel = 'Moderado-Alto';
        } else {
            $riskLevel = 'Alto';
        }

        // D. Actualizar los totales acumulados en la tabla cabecera `ac`
        $stmtUpdateAC =$pdo->prepare("
            UPDATE ac SET riskScore = :riskScore, riskLevel = :riskLevel WHERE acId = :acId
        ");
        $stmtUpdateAC->execute([
            ':riskScore' => $totalScore,
            ':riskLevel' => $riskLevel,
            ':acId'      => $acId
        ]);

        $pdo->commit();
        
        // Refrescar para ver los cambios guardados
        header("Location: responder.php?acId=" . $acId . "&success=1");
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction())$pdo->rollBack();
        die("Error al guardar las respuestas: " . $e->getMessage());
    }
}

// 2. Cargar datos guardados previamente para pintar el formulario (Persistencia)
// Cargar respuestas generales
$answersSaved =$pdo->query("SELECT questionId, response, comment FROM ac_general_answers WHERE acId = $acId")->fetchAll(PDO::FETCH_UNIQUE);
// Cargar respuestas de subpruebas Q28
$q28Saved =$pdo->query("SELECT testId, riskValue FROM ac_q28_answers WHERE acId = $acId")->fetchAll(PDO::FETCH_UNIQUE);

$pageTitle = "Responder Cuestionario AC";
include '../main/h.php';
?>

<style>
    .ac-container { max-width: 950px; margin: 20px auto; padding: 0 15px; }
    .meta-summary { background: #fff; padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 2rem; }
    .meta-item { font-size: 0.9rem; color: var(--text-muted); }
    .meta-item strong { color: var(--text-main); display: block; font-size: 1.05rem; }
    
    /* Estilos del Acordeón */
    .accordion-item { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 0.75rem; overflow: hidden; }
    .accordion-header { background: #f8fafc; padding: 1rem 1.25rem; font-size: 1rem; font-weight: 600; color: var(--text-main); cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; transition: background 0.2s; }
    .accordion-header:hover { background: #f1f5f9; }
    .accordion-header i { font-size: 1.25rem; transition: transform 0.2s; }
    .accordion-item.active .accordion-header { background: #e2e8f0; border-bottom: 1px solid var(--border-color); }
    .accordion-item.active .accordion-header i { transform: rotate(180deg); }
    .accordion-content { display: none; padding: 1.25rem; }
    .accordion-item.active .accordion-content { display: block; }

    /* Estructura Interna de Preguntas */
    .question-row { padding: 1rem 0; border-bottom: 1px solid #f1f5f9; }
    .question-row:last-child { border-bottom: none; }
    .question-text { font-size: 0.95rem; font-weight: 500; color: #1e293b; margin-bottom: 0.75rem; line-height: 1.4; }
    .question-inputs { display: grid; grid-template-columns: 180px 1fr; gap: 1.5rem; align-items: start; }
    
    /* Inputs de Radio Estilizados */
    .radio-group { display: flex; gap: 1rem; }
    .radio-label { display: flex; align-items: center; gap: 0.35rem; font-size: 0.9rem; cursor: pointer; font-weight: 600; }
    .radio-label input { width: 16px; height: 16px; accent-color: var(--accent); }
    
    /* Cuadro de texto */
    .comment-input { width: 100%; border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem; font-size: 0.88rem; resize: vertical; min-height: 38px; font-family: inherit; }
    .comment-input:focus { border-color: var(--accent); outline: none; }

    /* Estilos Especiales de la Matriz Q28 (Sub-tabla) */
    .subtest-table { width: 100%; border-collapse: collapse; margin-top: 1rem; font-size: 0.9rem; }
    .subtest-table th { background: #f1f5f9; text-align: left; padding: 0.6rem; font-size: 0.8rem; color: var(--text-muted); }
    .subtest-table td { padding: 0.6rem; border-bottom: 1px solid #f1f5f9; }
    .subtest-table select { padding: 0.35rem; border-radius: 4px; border: 1px solid var(--border-color); font-size: 0.85rem; width: 100%; max-width: 160px; background: #fff; }
    
    /* Alerta de Éxito Flotante */
    .alert-success { background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
</style>

<div class="ac-container">
    <header style="margin-bottom: 1.5rem;">
        <img src="../main/logo.png" alt="Logo Corporativo" class="brand-logo" style="cursor: pointer;" onclick="window.location.href='../index.php'">
        <h1><i class="ri-survey-line"></i> Ejecutar Cuestionario</h1>
        <a href="index.php" class="btn-back"><i class="ri-arrow-left-line"></i></a>
    </header>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success">
            <i class="ri-checkbox-circle-fill"></i> Respuestas y cálculo de matriz de riesgo actualizados exitosamente.
        </div>
    <?php endif; ?>

    <div class="meta-summary">
        <div class="meta-item">Client / Empresa <strong><?= htmlspecialchars($acData->clientName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item">Tipo Evaluación <strong><?= htmlspecialchars($acData->typeName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item">Servicio Requerido <strong><?= htmlspecialchars($acData->serviceName, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="meta-item" style="margin-left: auto; text-align: right;">
            Riesgo Calculado Matriz 
            <strong id="live-risk-badge" style="font-size: 1.2rem; color: var(--accent);">
                <?= $acData->riskScore ?> Pts (<?= $acData->riskLevel ?>)
            </strong>
        </div>
    </div>

    <form action="responder.php?acId=<?= $acId ?>" method="POST">
        
        <?php
        // Consultar categorías y preguntas organizadas dinámicamente
        $categories =$pdo->query("SELECT * FROM ac_categories ORDER BY orderNum ASC")->fetchAll(PDO::FETCH_OBJ);
        
        foreach ($categories as$cat):
            // Traer las preguntas de esta categoría en específico
            $stmtQ =$pdo->prepare("SELECT * FROM ac_questions WHERE categoryId = :catId ORDER BY questionNumber ASC");
            $stmtQ->execute([':catId' =>$cat->categoryId]);
            $questions =$stmtQ->fetchAll(PDO::FETCH_OBJ);
        ?>
            <div class="accordion-item">
                <div class="accordion-header" onclick="toggleAccordion(this)">
                    <span>Categoría <?= $cat->orderNum ?>: <?= htmlspecialchars($cat->categoryName, ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>
                
                <div class="accordion-content">
                    <?php foreach ($questions as$q): 
                        // Recuperar valores persistentes si ya existen respuestas previas
                        $savedRes =$answersSaved[$q->questionId]['response'] ?? '';$savedComment = $answersSaved[$q->questionId]['comment'] ?? '';
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
                                    <input type="text" name="answers[<?= $q->questionId ?>][comment]" class="comment-input" placeholder="Justificación o comentarios..." value="<?= htmlspecialchars($savedComment, ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>

                            <?php if ($q->questionNumber == 28): ?>
                                <div style="margin-top: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 6px; border: 1px dashed #cbd5e1;">
                                    <h4 style="font-size: 0.9rem; color: var(--text-main); margin-bottom: 0.5rem;"><i class="ri-matrix-line"></i> Desglose Analítico Matriz de Riesgo Interno (Prueba 28)</h4>
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
                                            $subtests =$pdo->query("SELECT * FROM ac_q28_tests ORDER BY testNumber ASC")->fetchAll(PDO::FETCH_OBJ);
                                            foreach ($subtests as $sub):$savedRisk = $q28Saved[$sub->testId]['riskValue'] ?? 'No Aplica';
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
                <i class="ri-save-3