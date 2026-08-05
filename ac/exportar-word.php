<?php

declare(strict_types=1);

// 1. Validar y sanitizar entrada
$acId = filter_input(INPUT_GET, 'acId', FILTER_VALIDATE_INT);

if (!$acId) {
    http_response_code(400);
    die('Error: El parámetro acId es obligatorio y debe ser un número entero válido.');
}

// 2. Conexión a Base de Datos con PDO
$dbHost  = 'localhost';
$dbName  = 'sagracom_alberto_1';
$dbUser  = 'sagracom_alberto_t';
$dbPass  = 'sagragp2705';
$charset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    // --- CONSULTAS A LA BASE DE DATOS ---

    // A. Cabecera AC
    $sqlAc = "SELECT 
                a.acId, 
                a.riskScore, 
                a.riskLevel,
                s.serviceName, 
                t.typeName
              FROM ac a
              LEFT JOIN ac_services s ON a.serviceId = s.serviceId
              LEFT JOIN ac_types t ON a.typeId = t.typeId
              WHERE a.acId = :acId";
    
    $stmtAc = $pdo->prepare($sqlAc);
    $stmtAc->execute([':acId' => $acId]);
    $evaluacion = $stmtAc->fetch();

    if (!$evaluacion) {
        http_response_code(404);
        die("Error: No se encontró la evaluación AC con ID {$acId}.");
    }

    // B. Cuestionario General
    $sqlGeneral = "SELECT 
                    cat.categoryName,
                    q.questionNumber,
                    q.questionText,
                    COALESCE(ga.response, 'Sin Respuesta') AS response,
                    COALESCE(ga.comment, '') AS comment
                   FROM ac_questions q
                   JOIN ac_categories cat ON q.categoryId = cat.categoryId
                   LEFT JOIN ac_general_answers ga 
                          ON q.questionId = ga.questionId AND ga.acId = :acId
                   ORDER BY cat.orderNum ASC, q.questionNumber ASC";

    $stmtGeneral = $pdo->prepare($sqlGeneral);
    $stmtGeneral->execute([':acId' => $acId]);
    $preguntasGenerales = $stmtGeneral->fetchAll();

    // C. Desglose de Pruebas de la Pregunta 28
    $sqlQ28 = "SELECT 
                t.testNumber,
                t.testText,
                COALESCE(ans.riskValue, 'N/A') AS riskValue,
                COALESCE(ans.score, 0) AS score
               FROM ac_q28_tests t
               LEFT JOIN ac_q28_answers ans 
                      ON t.testId = ans.testId AND ans.acId = :acId
               ORDER BY t.testNumber ASC";

    $stmtQ28 = $pdo->prepare($sqlQ28);
    $stmtQ28->execute([':acId' => $acId]);
    $pruebasQ28 = $stmtQ28->fetchAll();

    // D. Matriz de Riesgos Identificados
    $sqlMatriz = "SELECT 
                    idRiesgo, 
                    categoria, 
                    descripcion, 
                    causaRaiz, 
                    nivelRiesgo 
                  FROM ac_matriz_riesgos 
                  WHERE acId = :acId 
                  ORDER BY id ASC";

    $stmtMatriz = $pdo->prepare($sqlMatriz);
    $stmtMatriz->execute([':acId' => $acId]);
    $matrizRiesgos = $stmtMatriz->fetchAll();

} catch (PDOException $e) {
    error_log("Error BD en export_word_native: " . $e->getMessage());
    http_response_code(500);
    die("Error de conexión o consulta en la base de datos.");
}

// 3. Cabeceras HTTP para exportación a MS Word
$fileName = "Reporte_Auditoria_AC_{$acId}.doc";

header("Content-Type: application/vnd.ms-word; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$fileName}\"");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

// 4. Plantilla HTML/CSS para Microsoft Word
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:w="urn:schemas-microsoft-com:office:word" 
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>Reporte de Evaluación AC</title>
    <style>
        /* Estilos compatibles con Microsoft Word */
        @page {
            size: 21cm 29.7cm; /* A4 */
            margin: 2cm 2cm 2cm 2cm;
        }
        body {
            font-family: 'Calibri', 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            color: #333333;
            line-height: 1.4;
        }
        h1 {
            font-size: 18pt;
            color: #1B365D;
            text-align: center;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 10pt;
            color: #7F8C8D;
            text-align: center;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 13pt;
            color: #1B365D;
            border-bottom: 2px solid #1B365D;
            padding-bottom: 4px;
            margin-top: 20px;
        }
        .summary-box {
            background-color: #F8F9FA;
            border: 1px solid #E2E8F0;
            padding: 12px;
            margin-bottom: 20px;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 4px 8px;
            font-size: 10.5pt;
        }
        .label {
            font-weight: bold;
            color: #2C3E50;
            width: 30%;
        }
        /* Tablas Principales */
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        table.report-table th {
            background-color: #1B365D;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 10pt;
            padding: 8px;
            border: 1px solid #1B365D;
            text-align: left;
        }
        table.report-table td {
            padding: 7px;
            font-size: 9.5pt;
            border: 1px solid #CBD5E1;
            vertical-align: top;
        }
        table.report-table tr:nth-child(even) {
            background-color: #F8FAFC;
        }
        .badge {
            font-weight: bold;
            padding: 2px 6px;
            color: #1B365D;
        }
        .text-center {
            text-align: center;
        }
        .no-data {
            font-style: italic;
            color: #64748B;
            padding: 10px;
        }
    </style>
</head>
<body>

    <h1>Reporte de Evaluación de Riesgos</h1>
    <div class="subtitle">Auditoría Control Interno | Evaluación #<?= htmlspecialchars((string)$evaluacion['acId']) ?></div>

    <!-- Resumen Ejecutivo -->
    <div class="summary-box">
        <table>
            <tr>
                <td class="label">Servicio Evaluado:</td>
                <td><?= htmlspecialchars($evaluacion['serviceName'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label">Tipo de Auditoría:</td>
                <td><?= htmlspecialchars($evaluacion['typeName'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td class="label">Puntaje Total de Riesgo:</td>
                <td><strong><?= htmlspecialchars((string)$evaluacion['riskScore']) ?></strong></td>
            </tr>
            <tr>
                <td class="label">Nivel de Riesgo Global:</td>
                <td><span class="badge"><?= htmlspecialchars(strtoupper((string)$evaluacion['riskLevel'])) ?></span></td>
            </tr>
        </table>
    </div>

    <!-- Sección 1: Cuestionario General -->
    <h2>1. Cuestionario General</h2>
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;">Categoría</th>
                <th style="width: 40%;">Pregunta</th>
                <th style="width: 10%;">Respuesta</th>
                <th style="width: 20%;">Comentario</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($preguntasGenerales as $p): ?>
                <tr>
                    <td class="text-center"><?= htmlspecialchars((string)$p['questionNumber']) ?></td>
                    <td><?= htmlspecialchars($p['categoryName']) ?></td>
                    <td><?= htmlspecialchars($p['questionText']) ?></td>
                    <td class="text-center"><strong><?= htmlspecialchars($p['response']) ?></strong></td>
                    <td><?= htmlspecialchars($p['comment']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>

    <!-- Sección 2: Pregunta 28 -->
    <h2>2. Desglose Analítico - Pregunta 28 (Pruebas de Riesgo)</h2>
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 8%;">Prueba</th>
                <th style="width: 60%;">Descripción del Riesgo / Prueba Operativa</th>
                <th style="width: 20%;">Valor de Riesgo</th>
                <th style="width: 12%;">Puntaje</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pruebasQ28 as $q28): ?>
                <tr>
                    <td class="text-center"><?= htmlspecialchars((string)$q28['testNumber']) ?></td>
                    <td><?= htmlspecialchars($q28['testText']) ?></td>
                    <td><?= htmlspecialchars($q28['riskValue']) ?></td>
                    <td class="text-center"><strong><?= htmlspecialchars((string)$q28['score']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <br>

    <!-- Sección 3: Matriz de Riesgos Identificados -->
    <h2>3. Matriz de Riesgos Identificados</h2>
    <?php if (!empty($matrizRiesgos)): ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 12%;">ID Riesgo</th>
                    <th style="width: 20%;">Categoría</th>
                    <th style="width: 33%;">Descripción del Riesgo</th>
                    <th style="width: 23%;">Causa Raíz</th>
                    <th style="width: 12%;">Nivel</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matrizRiesgos as $m): ?>
                    <tr>
                        <td class="text-center"><strong><?= htmlspecialchars((string)$m['idRiesgo']) ?></strong></td>
                        <td><?= htmlspecialchars((string)$m['categoria']) ?></td>
                        <td><?= htmlspecialchars((string)$m['descripcion']) ?></td>
                        <td><?= htmlspecialchars((string)$m['causaRaiz']) ?></td>
                        <td class="text-center">
                            <span class="badge"><?= htmlspecialchars(strtoupper((string)$m['nivelRiesgo'])) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No se registraron riesgos en la matriz para esta evaluación.</p>
    <?php endif; ?>

</body>
</html>