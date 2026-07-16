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

<?php
// Mapeo dinámico de rutas del layout de la subcarpeta ac/
$customLogoPath = '../main/logo.png'; 
$customHomePath = '../index.php';     
$customAcPath   = 'index.php';  
$currentTab     = 'aceptacion'; 

include '../main/layout_header.php'; 
// 1. Lógica para determinar el ángulo del Tacómetro basado en el riskScore (0 a 100)
// Mapeamos el score (0-100) a un ángulo de rotación de la aguja (de -90deg a 90deg)
$score = isset($acData->riskScore) ? floatval($acData->riskScore) : 0;
if ($score < 0) $score = 0;
if ($score > 100) $score = 100;

// Fórmula: -90 grados (mínimo, izquierda) a +90 grados (máximo, derecha)
$rotationAngle = -90 + ($score * 1.8); 
?>

<style>
    .gauge-container {
        position: relative;
        width: 80px;
        height: 44px;
        margin: 0 auto;
        overflow: hidden;
    }
    /* Semicírculo de fondo (el arco de colores) */
    .gauge-arc {
        position: absolute;
        top: 0;
        left: 0;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        box-sizing: border-box;
        border: 8px solid #e2e8f0; /* Color base gris */
        /* Degradado cónico que simula: Verde -> Amarillo -> Naranja -> Rojo */
        background: conic-gradient(
            from 180deg at 50% 50%,
            #22c55e 0deg,   /* Verde (Riesgo Bajo) */
            #eab308 60deg,  /* Amarillo (Moderado) */
            #f97316 120deg, /* Naranja (Moderado-Alto) */
            #ef4444 180deg, /* Rojo (Alto) */
            #e2e8f0 180deg
        );
        mask: radial-gradient(circle, transparent 30px, #000 31px);
        -webkit-mask: radial-gradient(circle, transparent 30px, #000 31px);
        transform: rotate(90deg); /* Alinea el semicírculo hacia arriba */
    }
    /* La aguja del tacómetro */
    .gauge-needle {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 4px;
        height: 34px;
        background-color: #1e293b; /* Color oscuro para la aguja */
        border-radius: 4px;
        transform-origin: bottom center;
        /* Aplicamos el ángulo dinámico calculado en PHP */
        transform: translateX(-50%) rotate(<?= $rotationAngle ?>deg);
        transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 2;
    }
    /* El pin o centro donde rota la aguja */
    .gauge-center-pin {
        position: absolute;
        bottom: -4px;
        left: 50%;
        width: 12px;
        height: 12px;
        background-color: #1e293b;
        border-radius: 50%;
        transform: translateX(-50%);
        border: 2px solid #fff;
        z-index: 3;
    }
</style>
