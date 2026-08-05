<?php

declare(strict_types=1);

// ac/conect-responder.php

// Obtención y validación segura del ID de evaluación (GET o POST)
$acId = filter_input(INPUT_GET, 'acId', FILTER_VALIDATE_INT) 
    ?: filter_input(INPUT_POST, 'acId', FILTER_VALIDATE_INT);

if (!$acId || $acId <= 0) {
    die("Error: No se especificó una evaluación válida.");
}

// ==========================================
// 1. CONSULTAR CABECERA DE LA EVALUACIÓN (AC)
// ==========================================
try {
    $stmtAC = $pdo->prepare("
        SELECT ac.*, 
               c.name AS clientName, 
               t.typeName, 
               s.serviceName
        FROM ac 
        INNER JOIN clientes c ON ac.clientId = c.id
        INNER JOIN ac_types t ON ac.typeId = t.typeId
        INNER JOIN ac_services s ON ac.serviceId = s.serviceId
        WHERE ac.acId = :acId
    ");
    $stmtAC->execute([':acId' => $acId]);
    $acData = $stmtAC->fetch(PDO::FETCH_OBJ);

    if (!$acData) {
        die("Error: La evaluación solicitada no existe.");
    }
} catch (PDOException $e) {
    error_log("Error de BD en AC Header: " . $e->getMessage());
    die("Error de base de datos al consultar la evaluación.");
}

// ==========================================
// 2. LÓGICA DE PROCESAMIENTO / GUARDADO (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // A. Consultar estado actual registrado en Base de Datos
        $stmtCheckStatus = $pdo->prepare("SELECT statusId FROM ac WHERE acId = :acId");
        $stmtCheckStatus->execute([':acId' => $acId]);
        $currentDbStatus = (int)$stmtCheckStatus->fetchColumn();

        // B. Sanitizar el nuevo statusId recibido desde el formulario
        $rawStatusId = filter_input(INPUT_POST, 'statusId', FILTER_VALIDATE_INT);
        $newStatusId = in_array($rawStatusId, [1, 2], true) ? $rawStatusId : 1;

        // C. REGLA DE SEGURIDAD: Solo modificar preguntas y matriz si el proyecto NO ESTABA cerrado (statusId != 2)
        if ($currentDbStatus !== 2) {

            // 1. Guardar las respuestas a las 30 preguntas generales
            if (isset($_POST['answers']) && is_array($_POST['answers'])) {
                $stmtUpdateAnswer = $pdo->prepare("
                    UPDATE ac_general_answers 
                    SET response = :response, 
                        comment = :comment 
                    WHERE acId = :acId AND questionId = :questionId
                ");

                $allowedResponses = ['Si', 'No', 'N/A'];

                foreach ($_POST['answers'] as $qId => $data) {
                    $rawResponse   = isset($data['response']) ? trim((string)$data['response']) : '';
                    $responseValue = in_array($rawResponse, $allowedResponses, true) ? $rawResponse : null;
                    $commentValue  = isset($data['comment']) ? trim((string)$data['comment']) : '';

                    $stmtUpdateAnswer->execute([
                        ':response'   => $responseValue,
                        ':comment'    => $commentValue,
                        ':acId'       => $acId,
                        ':questionId' => (int)$qId
                    ]);
                }
            }

            // 2. Guardar las subpruebas de la Pregunta 28 y calcular el Score
            $totalScore = 0;
            if (isset($_POST['q28']) && is_array($_POST['q28'])) {
                $stmtUpdateQ28 = $pdo->prepare("
                    INSERT INTO ac_q28_answers (acId, testId, riskValue, score) 
                    VALUES (:acId, :testId, :riskValue, :score)
                    ON DUPLICATE KEY UPDATE 
                        riskValue = :riskValueUpdate, 
                        score = :scoreUpdate
                ");

                $pointsMap = [
                    'No Aplica'     => 0,
                    'Bajo'          => 1,
                    'Bajo-Moderado' => 2,
                    'Moderado'      => 3,
                    'Moderado-Alto' => 4,
                    'Alto'          => 5
                ];

                foreach ($_POST['q28'] as $tId => $riskValue) {
                    $riskStr = (string)$riskValue;
                    $score   = $pointsMap[$riskStr] ?? 0;
                    $totalScore += $score;

                    $stmtUpdateQ28->execute([
                        ':acId'            => $acId,
                        ':testId'          => (int)$tId,
                        ':riskValue'       => $riskStr,
                        ':score'           => $score,
                        ':riskValueUpdate' => $riskStr,
                        ':scoreUpdate'     => $score
                    ]);
                }
            }

            // 3. Guardar la Matriz de Riesgos Identificados
            $stmtDeleteMatrix = $pdo->prepare("DELETE FROM ac_matriz_riesgos WHERE acId = :acId");
            $stmtDeleteMatrix->execute([':acId' => $acId]);

            if (isset($_POST['matriz_id']) && is_array($_POST['matriz_id'])) {
                $stmtInsertMatrix = $pdo->prepare("
                    INSERT INTO ac_matriz_riesgos 
                        (acId, idRiesgo, categoria, descripcion, causaRaiz, nivelRiesgo) 
                    VALUES 
                        (:acId, :idRiesgo, :categoria, :descripcion, :causaRaiz, :nivelRiesgo)
                ");

                $mIds        = $_POST['matriz_id'];
                $mCategorias = $_POST['matriz_categoria'] ?? [];
                $mDesc       = $_POST['matriz_descripcion'] ?? [];
                $mCausas     = $_POST['matriz_causa'] ?? [];
                $mNiveles    = $_POST['matriz_nivel'] ?? [];

                foreach ($mIds as $idx => $idVal) {
                    $idRiesgo = trim((string)$idVal);
                    if ($idRiesgo === '') {
                        continue;
                    }

                    $categoria   = trim((string)($mCategorias[$idx] ?? ''));
                    $descripcion = trim((string)($mDesc[$idx] ?? ''));
                    $causa       = trim((string)($mCausas[$idx] ?? ''));
                    $nivel       = trim((string)($mNiveles[$idx] ?? 'Bajo'));

                    $stmtInsertMatrix->execute([
                        ':acId'        => $acId,
                        ':idRiesgo'    => $idRiesgo,
                        ':categoria'   => $categoria,
                        ':descripcion' => $descripcion,
                        ':causaRaiz'   => $causa,
                        ':nivelRiesgo' => $nivel
                    ]);
                }
            }

            // 4. Determinar cualitativamente el Nivel de riesgo
            if ($totalScore <= 21) {
                $riskLevel = 'Bajo';
            } elseif ($totalScore <= 42) {
                $riskLevel = 'Bajo Moderado';
            } elseif ($totalScore <= 63) {
                $riskLevel = 'Moderado';
            } elseif ($totalScore <= 84) {
                $riskLevel = 'Moderado Alto';
            } else {
                $riskLevel = 'Alto';
            }

            // 5. Actualizar riskScore, riskLevel y statusId en la tabla principal `ac`
            $stmtUpdateAC = $pdo->prepare("
                UPDATE ac 
                SET riskScore = :riskScore, 
                    riskLevel = :riskLevel, 
                    statusId = :statusId 
                WHERE acId = :acId
            ");
            $stmtUpdateAC->execute([
                ':riskScore' => $totalScore,
                ':riskLevel' => $riskLevel,
                ':statusId'  => $newStatusId,
                ':acId'      => $acId
            ]);

        } else {
            // Si la evaluación YA estaba cerrada (currentDbStatus === 2), 
            // solo permitimos actualizar el statusId si el usuario solicita reabrirla (statusId = 1)
            $stmtUpdateStatus = $pdo->prepare("UPDATE ac SET statusId = :statusId WHERE acId = :acId");
            $stmtUpdateStatus->execute([
                ':statusId' => $newStatusId,
                ':acId'     => $acId
            ]);
        }

        $pdo->commit();

        header("Location: responder.php?acId=" . $acId . "&success=1");
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al procesar la evaluación AC: " . $e->getMessage());
        die("Error al procesar la solicitud. Los cambios no fueron guardados.");
    }
}

// ==========================================
// 3. CARGAR DATOS GUARDADOS PARA LA VISTA
// ==========================================
try {
    $stmtAnswers = $pdo->prepare("SELECT questionId, response, comment FROM ac_general_answers WHERE acId = :acId");
    $stmtAnswers->execute([':acId' => $acId]);
    $answersSaved = $stmtAnswers->fetchAll(PDO::FETCH_UNIQUE);

    $stmtQ28 = $pdo->prepare("SELECT testId, riskValue FROM ac_q28_answers WHERE acId = :acId");
    $stmtQ28->execute([':acId' => $acId]);
    $q28Saved = $stmtQ28->fetchAll(PDO::FETCH_UNIQUE);

    $stmtMatrix = $pdo->prepare("SELECT * FROM ac_matriz_riesgos WHERE acId = :acId ORDER BY id ASC");
    $stmtMatrix->execute([':acId' => $acId]);
    $matrizRiesgosSaved = $stmtMatrix->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al cargar respuestas guardadas AC: " . $e->getMessage());
    die("Error al cargar los datos guardados de la evaluación.");
}

// ==========================================
// 4. CONFIGURACIÓN DE LAYOUT Y VISTA
// ==========================================
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

// Cálculo del ángulo del tacómetro basado en el riskScore (0 a 100)
$score = isset($acData->riskScore) ? (float)$acData->riskScore : 0.0;
if ($score < 0) {
    $score = 0.0;
}
if ($score > 100) {
    $score = 100.0;
}

// Fórmula de rotación: -90 grados (mínimo, izquierda) a +90 grados (máximo, derecha)
$rotationAngle = -90 + ($score * 1.8); 
?>

<style>
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
    font-size: 8px;
    font-weight: 700;
    fill: #64748b;
    text-anchor: middle;
}

.comment-input.auto-expand {
    width: 100%;
    min-height: 38px;
    padding: 0.55rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-family: inherit;
    font-size: 0.875rem;
    line-height: 1.45;
    color: #334155;
    background-color: #ffffff;
    resize: none;
    overflow-y: hidden;
    box-sizing: border-box;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.comment-input.auto-expand:focus {
    outline: none;
    border-color: #0284c7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
}
</style>