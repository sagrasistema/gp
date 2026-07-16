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

// 1. Lógica para definir la clase de riesgo y el icono según el nivel
$riskClass = 'risk-bajo';
$riskIcon = 'ri-checkbox-circle-line';

if ($acData->riskLevel === 'Moderado') { 
    $riskClass = 'risk-moderado';
    $riskIcon = 'ri-alert-line'; 
} elseif ($acData->riskLevel === 'Moderado-Alto') {
    $riskClass = 'risk-moderado-alto'; 
    $riskIcon = 'ri-error-warning-line';
} elseif ($acData->riskLevel === 'Alto') { 
    $riskClass = 'risk-alto'; 
    $riskIcon = 'ri-close-circle-line';
}

// 2. Lógica matemática para el Score (escala 0 a 100 mapeada a -90deg y 90deg)
$score = isset($acData->score) ? intval($acData->score) : 0;
$clampedScore = max(0, min(100, $score)); // Forzar rango 0-100
$degrees = ($clampedScore * 1.8) - 90; // Convertir a grados (-90 a 90)
?>

<style>
.tacometro-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
}
.tacometro {
    position: relative;
    width: 160px;
    height: 80px; /* Semicírculo */
    background: conic-gradient(from 0deg at 50% 100%, 
        #2ecc71 0deg 45deg,   /* Verde */
        #f1c40f 45deg 90deg,  /* Amarillo */
        #e67e22 90deg 135deg, /* Naranja */
        #e74c3c 135deg 180deg /* Rojo */
    );
    border-radius: 160px 160px 0 0; /* Forma de domo */
    overflow: hidden;
}
.tacometro-mask {
    position: absolute;
    bottom: 0;
    left: 15px;
    width: 130px;
    height: 65px;
    background: #fff; /* O el color de fondo de tu panel */
    border-radius: 130px 130px 0 0;
    z-index: 1;
}
.tacometro-needle {
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 4px;
    height: 70px;
    background: #2c3e50;
    transform-origin: bottom center;
    transform: translateX(-50%) rotate(<?php echo $degrees; ?>deg);
    transition: transform 0.8s ease-out;
    z-index: 3;
}
.tacometro-center {
    position: absolute;
    bottom: -6px;
    left: 50%;
    width: 16px;
    height: 16px;
    background: #2c3e50;
    border-radius: 50%;
    transform: translateX(-50%);
    z-index: 4;
}
.tacometro-score {
    font-size: 14px;
    font-weight: bold;
    margin-top: 5px;
    color: #333;
}
</style>