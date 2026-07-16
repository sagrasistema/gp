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
/* Contenedor principal con espacio controlado para evitar desbordes */
.gauge-container {
    position: relative;
    width: 140px; 
    height: 90px; 
    margin: 0;
    overflow: visible; /* Permite que los números sobresalgan sutilmente */
}

/* Semicírculo de fondo con los COLORES MUCHO MÁS GRANDES (Grosor: 20px) */
.gauge-arc {
    position: absolute;
    top: 0;
    left: 10px; /* Centrado dentro de los 140px de ancho */
    width: 120px; 
    height: 120px; 
    border-radius: 50%;
    box-sizing: border-box;
    border: 20px solid #e2e8f0; /* Subido a 20px para mayor impacto visual */
    
    /* Degradado cónico: Verde -> Amarillo -> Naranja -> Rojo */
    background: conic-gradient(
        from 180deg at 50% 50%,
        #22c55e 0deg,   /* Verde */
        #eab308 60deg,  /* Amarillo */
        #f97316 120deg, /* Naranja */
        #ef4444 180deg, /* Rojo */
        #e2e8f0 180deg
    );
    
    /* Máscara radial exacta: Radio total (60px) - grosor (20px) = 40px */
    mask: radial-gradient(circle, transparent 39px, #000 40px);
    -webkit-mask: radial-gradient(circle, transparent 39px, #000 40px);
    transform: rotate(90deg); 
}

/* Aguja del tacómetro ajustada al nuevo radio */
.gauge-needle {
    position: absolute;
    bottom: 30px; /* Alineado al nuevo eje central */
    left: 50%;
    width: 4px; 
    height: 44px; /* Longitud ideal para no pisar la franja gruesa de color */
    background-color: #1e293b;
    border-radius: 3px; 
    transform-origin: bottom center;
    /* La rotación se calcula dinámicamente con tu PHP */
    transform: translateX(-50%) rotate(<?= $rotationAngle ?? 0 ?>deg);
    transition: transform 1s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
}

/* Pin del centro de rotación */
.gauge-center-pin {
    position: absolute;
    bottom: 25px; /* Ajustado al eje de la aguja */
    left: 50%;
    width: 12px; 
    height: 12px; 
    background-color: #1e293b;
    border-radius: 50%;
    transform: translateX(-50%);
    border: 2px solid #fff; 
    z-index: 3;
}

/* --- ESTILOS DE LOS NÚMEROS DE CRITERIOS --- */
.gauge-label {
    position: absolute;
    font-size: 11px;
    font-weight: 700;
    color: #64748b; /* Color slate intermedio elegante */
    font-family: system-ui, -apple-system, sans-serif;
    user-select: none;
    line-height: 1;
}

/* Posiciones calculadas para calzar en las zonas de color */
.lbl-criterio-1 {
    bottom: 25px;
    left: 0px; /* Esquina inferior izquierda (Zona Verde) */
}

.lbl-criterio-2 {
    top: 5px;
    left: 32px; /* Cuadrante superior izquierdo (Zona Amarilla) */
}

.lbl-criterio-3 {
    top: 5px;
    right: 32px; /* Cuadrante superior derecho (Zona Naranja) */
}

.lbl-criterio-4 {
    bottom: 25px;
    right: 0px; /* Esquina inferior derecha (Zona Roja) */
}
</style>