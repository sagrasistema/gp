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
$score = isset($acData->riskScore) ? floatval($acData->riskScore) : 0;
if ($score < 0) $score = 0;
if ($score > 100) $score = 100;

// Fórmula: -90 grados (mínimo, izquierda) a +90 grados (máximo, derecha)
$rotationAngle = -90 + ($score * 1.8); 
?>

<style>
/* Contenedor exterior que agrupa el tacómetro y sus números */
.gauge-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 180px; /* Tamaño ideal para que sea visible y detallado */
    user-select: none;
}

/* El contenedor que recorta el círculo para simular un semicírculo perfecto */
.gauge-container {
    position: relative;
    width: 160px;
    height: 80px; /* Exactamente la mitad del ancho para el semicírculo */
    overflow: hidden;
}

/* Semicírculo de colores con un grosor de 24px para un look imponente */
.gauge-arc {
    position: absolute;
    top: 0;
    left: 0;
    width: 160px;
    height: 160px; /* Círculo completo que recortará el contenedor */
    border-radius: 50%;
    box-sizing: border-box;
    /* Borde de color grueso de 24px */
    border: 24px solid transparent; 
    
    /* Gradiente cónico con cortes de color limpios imitando la imagen de referencia */
    background: conic-gradient(
        from 270deg at 50% 50%,
        #22c55e 0deg,    /* Verde (Bajo) */
        #eab308 45deg,   /* Amarillo (Moderado) */
        #f97316 135deg,  /* Naranja (Moderado-Alto) */
        #ef4444 180deg,  /* Rojo (Alto) */
        transparent 180deg
    ) border-box;
    
    transform: rotate(-90deg); /* Alinea el inicio en el extremo izquierdo */
    pointer-events: none;
}

/* Aguja estilizada tipo indicador real */
.gauge-needle {
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 4px;
    height: 62px; /* Largo de la aguja */
    background-color: #1e293b;
    border-radius: 4px 4px 0 0;
    transform-origin: bottom center;
    z-index: 5;
    /* Transición fluida para simular el movimiento físico de la aguja */
    transition: transform 1.2s cubic-bezier(0.25, 1, 0.5, 1);
}

/* Pin central con estilo cromado/metálico como el de tu referencia */
.gauge-center-pin {
    position: absolute;
    bottom: -10px; /* Centrado en la base del recorte */
    left: 50%;
    width: 20px;
    height: 20px;
    background: radial-gradient(circle, #f8fafc 0%, #64748b 70%, #1e293b 100%);
    border: 2px solid #ffffff;
    border-radius: 50%;
    transform: translateX(-50%);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.25);
    z-index: 6;
}

/* Contenedor de las etiquetas de criterios */
.gauge-labels {
    position: relative;
    width: 176px; /* Ligeramente más ancho para acompañar la curva */
    height: 20px;
    margin-top: 6px;
}

/* Estilo común para los números */
.label-item {
    position: absolute;
    font-family: system-ui, -apple-system, sans-serif;
    font-size: 11px;
    font-weight: 700;
    color: #475569; /* Slate oscuro para legibilidad */
    text-align: center;
}

/* Posicionamiento exacto de los números según los arcos de color */
.score-1 {
    left: 8px;
    top: -18px; /* Cerca de la base verde */
}

.score-2 {
    left: 42px;
    top: -42px; /* Debajo de la transición verde-amarillo */
}

.score-3 {
    right: 42px;
    top: -42px; /* Debajo de la transición amarillo-naranja */
}

.score-4 {
    right: 8px;
    top: -18px; /* Cerca de la base roja */
}
</style>