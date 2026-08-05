<?php
// ac/conect-responder.php

declare(strict_types=1);

$acId = filter_input(INPUT_GET, 'acId', FILTER_VALIDATE_INT);

if (!$acId || $acId <= 0) {
    die("Error: No se especificó una evaluación válida.");
}

// 1. Obtener la cabecera de la AC junto con los datos relacionales
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
    error_log("Error de BD en AC Header: " . $e->getMessage());
    die("Error de base de datos al consultar la evaluación.");
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
                $responseValue = (!empty($data['response'])) ? trim((string)$data['response']) : null;
                $commentValue  = isset($data['comment']) ? trim((string)$data['comment']) : '';

                $stmtUpdateAnswer->execute([
                    ':response'   => $responseValue,
                    ':comment'    => $commentValue,
                    ':acId'       => $acId,
                    ':questionId' => (int)$qId
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
                'No Aplica'     => 0,
                'Bajo'          => 1,
                'Bajo-Moderado' => 2,
                'Moderado'      => 3,
                'Moderado-Alto' => 4,
                'Alto'          => 5
            ];

            foreach ($_POST['q28'] as $tId => $riskValue) {
                $riskStr = (string)$riskValue;
                $score = $pointsMap[$riskStr] ?? 0;
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

        // C. Guardar la Matriz de Riesgos (Prueba Especial)
        $stmtDeleteMatrix = $pdo->prepare("DELETE FROM ac_matriz_riesgos WHERE acId = :acId");
        $stmtDeleteMatrix->execute([':acId' => $acId]);

        if (isset($_POST['matriz_id']) && is_array($_POST['matriz_id'])) {
            $stmtInsertMatrix = $pdo->prepare("
                INSERT INTO ac_matriz_riesgos (acId, idRiesgo, categoria, descripcion, causaRaiz, nivelRiesgo) 
                VALUES (:acId, :idRiesgo, :categoria, :descripcion, :causaRaiz, :nivelRiesgo)
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
        
        // D. Determinar cualitativamente el Rango de riesgo
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

        // E. Actualizar totales en `ac`
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
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al guardar respuestas AC: " . $e->getMessage());
        die("Error al guardar las respuestas: " . $e->getMessage());
    }
}

// Cargar respuestas guardadas mediante sentencias preparadas
$stmtAnswers = $pdo->prepare("SELECT questionId, response, comment FROM ac_general_answers WHERE acId = :acId");
$stmtAnswers->execute([':acId' => $acId]);
$answersSaved = $stmtAnswers->fetchAll(PDO::FETCH_UNIQUE);

$stmtQ28 = $pdo->prepare("SELECT testId, riskValue FROM ac_q28_answers WHERE acId = :acId");
$stmtQ28->execute([':acId' => $acId]);
$q28Saved = $stmtQ28->fetchAll(PDO::FETCH_UNIQUE);

$stmtMatrix = $pdo->prepare("SELECT * FROM ac_matriz_riesgos WHERE acId = :acId ORDER BY id ASC");
$stmtMatrix->execute([':acId' => $acId]);
$matrizRiesgosSaved = $stmtMatrix->fetchAll(PDO::FETCH_OBJ);

$pageTitle = "Responder Cuestionario AC";
include '../main/h.php';