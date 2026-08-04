<?php
declare(strict_types=1);

// v/ac/exportar-word.php
// SOLUCIÓN NATIVA: Generación directa a Word mediante cabeceras MSOffice (Sin Composer)

include '../main/config.php';

// 1. Validar y sanitizar la entrada
$acId = filter_input(INPUT_GET, 'acId', FILTER_VALIDATE_INT);

if (!$acId) {
    http_response_code(400);
    die('Identificador de evaluación no válido.');
}

try {
    // 2. Consultar datos principales de la evaluación
    $stmtAc = $pdo->prepare("
        SELECT 
            ac.*,
            c.clientName,
            t.typeName,
            s.serviceName,
            p.partnerName,
            m.managerName
        FROM ac_evaluations ac
        LEFT JOIN clients c ON ac.clientId = c.clientId
        LEFT JOIN ac_types t ON ac.typeId = t.typeId
        LEFT JOIN ac_services s ON ac.serviceId = s.serviceId
        LEFT JOIN partners p ON ac.partnerId = p.partnerId
        LEFT JOIN managers m ON ac.managerId = m.managerId
        WHERE ac.acId = :acId
        LIMIT 1
    ");
    $stmtAc->execute([':acId' => $acId]);
    $acData = $stmtAc->fetch(PDO::FETCH_OBJ);

    if (!$acData) {
        http_response_code(404);
        die('La evaluación solicitada no existe.');
    }

    // 3. Cargar Respuestas Guardadas
    $stmtAns = $pdo->prepare("SELECT questionId, response, comment FROM ac_answers WHERE acId = :acId");
    $stmtAns->execute([':acId' => $acId]);
    $answersSaved = [];
    foreach ($stmtAns->fetchAll(PDO::FETCH_OBJ) as $ans) {
        $answersSaved[$ans->questionId] = [
            'response' => $ans->response,
            'comment'  => $ans->comment
        ];
    }

    // 4. Cargar Subpruebas Pregunta 28
    $stmtQ28 = $pdo->prepare("SELECT testId, riskValue FROM ac_q28_answers WHERE acId = :acId");
    $stmtQ28->execute([':acId' => $acId]);
    $q28Saved = [];
    foreach ($stmtQ28->fetchAll(PDO::FETCH_OBJ) as $q28) {
        $q28Saved[$q28->testId] = $q28->riskValue;
    }

    // 5. Determinar color del badge de riesgo
    $riskColor = match ($acData->riskLevel ?? 'Bajo') {
        'Moderado'      => '#eab308',
        'Moderado-Alto' => '#f97316',
        'Alto'          => '#ef4444',
        default         => '#22c55e',
    };

    // 6. Configurar Cabeceras de Descarga para Microsoft Word
    $filename = 'Informe_AC_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $acData->clientName ?? 'Cliente') . '_' . date('Ymd') . '.doc';

    header("Content-Type: application/vnd.ms-word; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    // 7. Salida HTML Maquetada para Microsoft Word
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:w="urn:schemas-microsoft-com:office:word" 
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>Informe de Aceptación y Continuidad</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #334155; line-height: 1.4; }
        h1 { font-size: 16pt; color: #0f172a; margin-bottom: 5px; }
        h2 { font-size: 12pt; color: #0284c7; margin-top: 20px; border-bottom: 2px solid #0284c7; padding-bottom: 4px; }
        .meta-table, .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
        .meta-table td { padding: 6px 10px; border: 1px solid #cbd5e1; font-size: 9pt; }
        .meta-label { background-color: #f8fafc; font-weight: bold; width: 30%; color: #475569; }
        
        .risk-box { padding: 12px; background-color: #f8fafc; border-left: 5px solid <?= $riskColor ?>; margin: 15px 0; }
        .risk-title { font-size: 9pt; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .risk-value { font-size: 13pt; font-weight: bold; color: <?= $riskColor ?>; }

        .data-table th { background-color: #1e293b; color: #ffffff; padding: 6px; font-size: 8.5pt; text-align: left; border: 1px solid #1e293b; }
        .data-table td { padding: 6px; font-size: 8.5pt; border: 1px solid #cbd5e1; vertical-align: top; }
        .text-center { text-align: center; }
        .badge-si { color: #16a34a; font-weight: bold; }
        .badge-no { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>

    <h1>INFORME DE ACEPTACIÓN Y CONTINUIDAD (A&C)</h1>
    <p style="font-size: 8.5pt; color: #64748b;">Generado el: <?= date('d/m/Y H:i') ?></p>

    <!-- Resumen de Metadatos -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Cliente / Empresa:</td>
            <td><strong><?= htmlspecialchars($acData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong></td>
        </tr>
        <tr>
            <td class="meta-label">Tipo de Evaluación:</td>
            <td><?= htmlspecialchars($acData->typeName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <td class="meta-label">Naturaleza del Servicio:</td>
            <td><?= htmlspecialchars($acData->serviceName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <td class="meta-label">Período de la A&C:</td>
            <td>
                <?php 
                if (!empty($acData->startDate) && !empty($acData->endDate)) {
                    echo "Desde " . htmlspecialchars($acData->startDate, ENT_QUOTES, 'UTF-8') . " Hasta " . htmlspecialchars($acData->endDate, ENT_QUOTES, 'UTF-8');
                } else {
                    echo "SIN ASIGNAR";
                }
                ?>
            </td>
        </tr>
        <tr>
            <td class="meta-label">Socio Líder / Gerente:</td>
            <td><?= htmlspecialchars($acData->partnerName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($acData->managerName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
    </table>

    <!-- Callout de Riesgo -->
    <div class="risk-box">
        <div class="risk-title">Nivel de Riesgo Calculado Matriz</div>
        <div class="risk-value"><?= (float)($acData->riskScore ?? 0) ?> Pts — Categoría: <?= strtoupper(htmlspecialchars($acData->riskLevel ?? 'Bajo', ENT_QUOTES, 'UTF-8')) ?></div>
    </div>

    <!-- Secciones del Cuestionario -->
    <h2>Resultados del Cuestionario de Auditoría</h2>

    <?php
    $categories = $pdo->query("SELECT * FROM ac_categories ORDER BY orderNum ASC")->fetchAll(PDO::FETCH_OBJ);

    foreach ($categories as $cat):
        $stmtQ = $pdo->prepare("SELECT * FROM ac_questions WHERE categoryId = :catId ORDER BY questionNumber ASC");
        $stmtQ->execute([':catId' => $cat->categoryId]);
        $questions = $stmtQ->fetchAll(PDO::FETCH_OBJ);

        if (empty($questions)) continue;
    ?>
        <h3 style="font-size: 10pt; color: #0f172a; margin-top: 15px; margin-bottom: 5px;">
            <?= htmlspecialchars($cat->categoryName, ENT_QUOTES, 'UTF-8') ?>
        </h3>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 8%;" class="text-center">N°</th>
                    <th style="width: 52%;">Pregunta / Criterio de Control</th>
                    <th style="width: 12%;" class="text-center">Respuesta</th>
                    <th style="width: 28%;">Comentarios / Justificación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questions as $q): 
                    $res = $answersSaved[$q->questionId]['response'] ?? 'Sin Responder';
                    $com = $answersSaved[$q->questionId]['comment'] ?? '-';
                    $classRes = ($res === 'Si') ? 'badge-si' : (($res === 'No') ? 'badge-no' : '');
                ?>
                    <tr>
                        <td class="text-center"><strong><?= (int)$q->questionNumber ?></strong></td>
                        <td><?= htmlspecialchars($q->questionText, ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center <?= $classRes ?>"><?= htmlspecialchars($res, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><em><?= htmlspecialchars($com, ENT_QUOTES, 'UTF-8') ?></em></td>
                    </tr>

                    <?php if ((int)$q->questionNumber === 28): 
                        $subtests = $pdo->query("SELECT * FROM ac_q28_tests ORDER BY testNumber ASC")->fetchAll(PDO::FETCH_OBJ);
                        if (!empty($subtests)):
                    ?>
                        <tr>
                            <td colspan="4" style="background-color: #f1f5f9; padding: 10px;">
                                <strong style="color: #0284c7;">Desglose Analítico Matriz de Riesgo Interno (Prueba 28):</strong>
                                <table class="data-table" style="margin-top: 8px;">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%; background-color: #0284c7; border-color: #0284c7;" class="text-center">Item</th>
                                            <th style="width: 65%; background-color: #0284c7; border-color: #0284c7;">Factor de Riesgo / Prueba de Control</th>
                                            <th style="width: 25%; background-color: #0284c7; border-color: #0284c7;" class="text-center">Riesgo Asignado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subtests as $sub): 
                                            $riskVal = $q28Saved[$sub->testId] ?? 'No Aplica';
                                        ?>
                                            <tr>
                                                <td class="text-center"><strong><?= (int)$sub->testNumber ?></strong></td>
                                                <td><?= htmlspecialchars($sub->testText, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="text-center"><strong><?= htmlspecialchars($riskVal, ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php 
                        endif;
                    endif; 
                    ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

</body>
</html>
<?php
    exit;

} catch (PDOException $e) {
    error_log("Error SQL en exportar-word.php: " . $e->getMessage());
    http_response_code(500);
    die("Error al consultar la base de datos.");
}