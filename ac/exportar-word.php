<?php

declare(strict_types=1);

#require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;
use PhpOffice\PhpWord\IOFactory;

// 1. Configuración de conexión segura PDO
$host     = '127.0.0.1';
$db       = 'sagracom_alberto_1';
$user     = 'tu_usuario';
$pass     = 'tu_contraseña';
$charset  = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$acId = filter_input(INPUT_GET, 'acId', FILTER_VALIDATE_INT);

if (!$acId) {
    http_response_code(400);
    die('Error: ID de evaluación no válido o no proporcionado.');
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 2. Consulta de Cabecera (Tabla: ac, ac_services, ac_types)
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
        die("Error: No se encontró la evaluación con ID {$acId}.");
    }

    // 3. Consulta de Preguntas Generales y Respuestas (Tablas: ac_questions, ac_categories, ac_general_answers)
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

    // 4. Consulta del Desglose de la Pregunta 28 (Tablas: ac_q28_tests, ac_q28_answers)
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

    // ---------------------------------------------------------
    // 5. Construcción del Documento Word (PHPWord)
    // ---------------------------------------------------------
    $phpWord = new PhpWord();
    $phpWord->getSettings()->setThemeFontLang(new Language(Language::ES_ES));

    // Estilos de Tablas y Textos
    $tableStyle = [
        'borderSize'  => 6,
        'borderColor' => '999999',
        'cellMargin'  => 80,
    ];
    $headerCellStyle = ['bgColor' => 'F2F2F2'];
    $boldText        = ['bold' => true];
    $titleStyle      = ['size' => 16, 'bold' => true, 'color' => '1B365D'];
    $subTitleStyle   = ['size' => 13, 'bold' => true, 'color' => '2C3E50'];

    $section = $phpWord->addSection();

    // Título Principal
    $section->addText("Reporte de Evaluación de Riesgos #{$evaluacion['acId']}", $titleStyle, ['alignment' => Jc::CENTER]);
    $section->addTextBreak(1);

    // Resumen de Cabecera
    $section->addText("Servicio: " . ($evaluacion['serviceName'] ?? 'N/A'), $boldText);
    $section->addText("Tipo de Auditoría: " . ($evaluacion['typeName'] ?? 'N/A'));
    $section->addText("Puntaje Total de Riesgo: " . $evaluacion['riskScore'], $boldText);
    $section->addText("Nivel de Riesgo Global: " . $evaluacion['riskLevel'], $boldText);
    $section->addTextBreak(1);

    // Sección: Cuestionario General
    $section->addText("1. Cuestionario General de Auditoría", $subTitleStyle);
    $section->addTextBreak(1);

    $tableGen = $section->addTable($tableStyle);
    $tableGen->addRow();
    $tableGen->addCell(800, $headerCellStyle)->addText('#', $boldText);
    $tableGen->addCell(2500, $headerCellStyle)->addText('Categoría', $boldText);
    $tableGen->addCell(4000, $headerCellStyle)->addText('Pregunta', $boldText);
    $tableGen->addCell(1200, $headerCellStyle)->addText('Respuesta', $boldText);
    $tableGen->addCell(2500, $headerCellStyle)->addText('Comentario', $boldText);

    foreach ($preguntasGenerales as $p) {
        $tableGen->addRow();
        $tableGen->addCell(800)->addText((string)$p['questionNumber']);
        $tableGen->addCell(2500)->addText($p['categoryName']);
        $tableGen->addCell(4000)->addText($p['questionText']);
        $tableGen->addCell(1200)->addText($p['response']);
        $tableGen->addCell(2500)->addText($p['comment']);
    }

    $section->addTextBreak(2);

    // Sección: Detalle de las 21 Pruebas (Pregunta 28)
    $section->addText("2. Desglose Analítico - Pregunta 28 (Pruebas de Riesgo)", $subTitleStyle);
    $section->addTextBreak(1);

    $tableQ28 = $section->addTable($tableStyle);
    $tableQ28->addRow();
    $tableQ28->addCell(800, $headerCellStyle)->addText('Prueba #', $boldText);
    $tableQ28->addCell(5500, $headerCellStyle)->addText('Descripción del Riesgo / Prueba', $boldText);
    $tableQ28->addCell(2200, $headerCellStyle)->addText('Valor de Riesgo', $boldText);
    $tableQ28->addCell(1000, $headerCellStyle)->addText('Puntaje', $boldText);

    foreach ($pruebasQ28 as $q28) {
        $tableQ28->addRow();
        $tableQ28->addCell(800)->addText((string)$q28['testNumber']);
        $tableQ28->addCell(5500)->addText($q28['testText']);
        $tableQ28->addCell(2200)->addText($q28['riskValue']);
        $tableQ28->addCell(1000)->addText((string)$q28['score']);
    }

    // 6. Descarga directa del archivo Word generado
    $fileName = "Evaluacion_Riesgo_AC_{$acId}.docx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;

} catch (PDOException $e) {
    error_log("Error PDO en impresión Word: " . $e->getMessage());
    http_response_code(500);
    echo "Error interno de base de datos al procesar el documento.";
} catch (\Throwable $e) {
    error_log("Error al generar Word: " . $e->getMessage());
    http_response_code(500);
    echo "Ocurrió un error al construir el archivo Word.";
}