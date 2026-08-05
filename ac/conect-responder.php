<?php
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
        // Clasificación del nivel de riesgo basada en la escala simétrica de 105 puntos
        if ($totalScore <= 21) {
            $riskLevel = 'Bajo';
            $riskClass = 'risk-bajo';
        } elseif ($totalScore <= 42) {
            $riskLevel = 'Bajo Moderado';
            $riskClass = 'risk-bajo-mod';
        } elseif ($totalScore <= 63) {
            $riskLevel = 'Moderado';
            $riskClass = 'risk-mod';
        } elseif ($totalScore <= 84) {
            $riskLevel = 'Moderado Alto';
            $riskClass = 'risk-mod-alto';
        } else {
            $riskLevel = 'Alto';
            $riskClass = 'risk-alto';
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

<?php
// Mapeo dinámico de rutas del layout de la subcarpeta ac/
$customLogoPath = '../main/logo.png'; 
$customHomePath = '../index.php';     
$customAcPath   = 'index.php';  
$currentTab     = 'aceptacion'; 

include '../main/layout_header.php'; 
// 1. Lógica para determinar el ángulo del Tacómetro basado en el riskScore (0 a 100)
$score = isset($acData->riskScore) ? floatval($acData->riskScore) : 0;
if ($score < 0) $score = 0;
if ($score > 100) $score = 100;

// Fórmula: -90 grados (mínimo, izquierda) a +90 grados (máximo, derecha)
$rotationAngle = -90 + ($score * 1.8); 
?>

<style>
/* Contenedor externo del Tacómetro */


/* Contenedor del Tacómetro */
.meta-item-gauge {
    grid-column: 5;
    grid-row: 1 / span 3;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
    padding: 0.25rem;
    box-sizing: border-box;
}

.gauge-wrapper {
    width: 100%;
    max-width: 260px;
    height: auto;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto;
}

.gauge-svg {
    display: block;
    overflow: visible;
}

/* Tipografía y Etiquetas del SVG */
.gauge-label-text {
    font-weight: 900;
    text-anchor: middle;
}

.gauge-text {
    font-family: ui-sans-serif, system-ui, sans-serif;
    font-size: 8px; /* Ligeramente más legible */
    font-weight: 700;
    fill: #64748b; /* Slate 500 para un look moderno */
    text-anchor: middle;
}

</style>