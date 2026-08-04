<?php
declare(strict_types=1);

// v/ac/exportar-word.php

#require_once '../../vendor/autoload.php'; // Ajusta la ruta a tu vendor/autoload.php
require_once '../main/config.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;

// 1. Sanitización y Validación de Entrada
$acId = filter_input(INPUT_GET, 'acId', FILTER_VALIDATE_INT);

if (!$acId) {
    http_response_code(400);
    die('Identificador de evaluación no válido o faltante.');
}

try {
    // 2. Consulta de Datos Principales de la Evaluación (PDO Prepared Statement)
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

    // 3. Consulta de Respuestas Guardadas
    $stmtAns = $pdo->prepare("SELECT questionId, response, comment FROM ac_answers WHERE acId = :acId");
    $stmtAns->execute([':acId' => $acId]);
    $rawAnswers = $stmtAns->fetchAll(PDO::FETCH_OBJ);

    $answersSaved = [];
    foreach ($rawAnswers as $ans) {
        $answersSaved[$ans->questionId] = [
            'response' => $ans->response,
            'comment'  => $ans->comment
        ];
    }

    // 4. Consulta de Respuestas de la Pregunta 28 (Matriz de Riesgo)
    $stmtQ28 = $pdo->prepare("SELECT testId, riskValue FROM ac_q28_answers WHERE acId = :acId");
    $stmtQ28->execute([':acId' => $acId]);
    $rawQ28 = $stmtQ28->fetchAll(PDO::FETCH_OBJ);

    $q28Saved = [];
    foreach ($rawQ28 as $q28) {
        $q28Saved[$q28->testId] = $q28->riskValue;
    }

    // 5. Inicializar Documento Word y Estilos Modernos
    $phpWord = new PhpWord();
    $phpWord->getSettings()->setThemeFontLang(new Language(Language::SPANISH_MODERN));

    // Estilos Globales de Fuente y Párrafo
    $fontFamily = 'Arial';
    
    $phpWord->addTitleStyle(1, [
        'name' => $fontFamily, 'size' => 18, 'color' => '0F172A', 'bold' => true
    ], ['spaceAfter' => 120]);

    $phpWord->addTitleStyle(2, [
        'name' => $fontFamily, 'size' => 13, 'color' => '0284C7', 'bold' => true
    ], ['spaceBefore' => 200, 'spaceAfter' => 100]);

    // Crear Sección con Márgenes Limpios (2 cm)
    $section = $phpWord->addSection([
        'marginTop'    => 1134,
        'marginBottom' => 1134,
        'marginLeft'   => 1134,
        'marginRight'  => 1134
    ]);

    // Encabezado del Documento
    $section->addTitle('INFORME DE EVALUACIÓN DE ACEPTACIÓN Y CONTINUIDAD (A&C)', 1);
    $section->addText('Generado el: ' . date('d/m/Y H:i'), ['name' => $fontFamily, 'size' => 9, 'color' => '64748B'], ['spaceAfter' => 200]);

    // Color según nivel de riesgo
    $riskColor = match ($acData->riskLevel ?? 'Bajo') {
        'Moderado'      => 'EAB308',
        'Moderado-Alto' => 'F97316',
        'Alto'          => 'EF4444',
        default         => '22C55E',
    };

    // 6. Tarjeta Resumen Metadatos (Tabla Estilizada)
    $metaTableStyle = [
        'borderSize'  => 6,
        'borderColor' => 'E2E8F0',
        'cellMargin'  => 100
    ];
    $phpWord->addTableStyle('MetaTable', $metaTableStyle);
    $tableMeta = $section->addTable('MetaTable');

    // Fila 1: Datos Principales
    $tableMeta->addRow();
    $tableMeta->addCell(4500, ['bgColor' => 'F8FAFC'])->addText('Cliente / Empresa:', ['bold' => true, 'size' => 9, 'name' => $fontFamily]);
    $tableMeta->addCell(4500, ['bgColor' => 'FFFFFF'])->addText($acData->clientName ?? 'N/A', ['size' => 9, 'name' => $fontFamily]);

    $tableMeta->addRow();
    $tableMeta->addCell(4500, ['bgColor' => 'F8FAFC'])->addText('Tipo de Evaluación:', ['bold' => true, 'size' => 9, 'name' => $fontFamily]);
    $tableMeta->addCell(4500, ['bgColor' => 'FFFFFF'])->addText($acData->typeName ?? 'N/A', ['size' => 9, 'name' => $fontFamily]);

    $tableMeta->addRow();
    $tableMeta->addCell(4500, ['bgColor' => 'F8FAFC'])->addText('Naturaleza del Servicio:', ['bold' => true, 'size' => 9, 'name' => $fontFamily]);
    $tableMeta->addCell(4500, ['bgColor' => 'FFFFFF'])->addText($acData->serviceName ?? 'N/A', ['size' => 9, 'name' => $fontFamily]);

    $tableMeta->addRow();
    $tableMeta->addCell(4500, ['bgColor' => 'F8FAFC'])->addText('Período de la A&C:', ['bold' => true, 'size' => 9, 'name' => $fontFamily]);
    $periodText = (!empty($acData->startDate) && !empty($acData->endDate))
        ? "Desde {$acData->startDate} Hasta {$acData->endDate}"
        : "SIN ASIGNAR";
    $tableMeta->addCell(4500, ['bgColor' => 'FFFFFF'])->addText($periodText, ['size' => 9, 'name' => $fontFamily]);

    $tableMeta->addRow();
    $tableMeta->addCell(4500, ['bgColor' => 'F8FAFC'])->addText('Socio Líder / Gerente:', ['bold' => true, 'size' => 9, 'name' => $fontFamily]);
    $tableMeta->addCell(4500, ['bgColor' => 'FFFFFF'])->addText(($acData->partnerName ?? 'N/A') . ' / ' . ($acData->managerName ?? 'N/A'), ['size' => 9, 'name' => $fontFamily]);

    $section->addTextBreak(1);

    // 7. Bloque Destacado de Riesgo Calculado (Callout)
    $riskTable = $section->addTable([
        'borderLeftSize'  => 36,
        'borderLeftColor' => $riskColor,
        'borderTopSize'   => 0,
        'borderRightSize' => 0,
        'borderBottomSize'=> 0,
        'cellMargin'      => 120
    ]);
    $riskTable->addRow();
    $riskCell = $riskTable->addCell(9000, ['bgColor' => 'F8FAFC']);
    $riskCell->addText('NIVEL DE RIESGO CALCULADO MATRIZ', ['bold' => true, 'size' => 10, 'color' => '475569', 'name' => $fontFamily]);
    $riskCell->addText(
        ($acData->riskScore ?? 0) . ' Puntos — Categoría: ' . strtoupper($acData->riskLevel ?? 'Bajo'),
        ['bold' => true, 'size' => 12, 'color' => $riskColor, 'name' => $fontFamily]
    );

    $section->addTextBreak(1);

    // 8. Renderizado de Categorías y Cuestionario
    $section->addTitle('Resultados del Cuestionario de Auditoría', 2);

    $categories = $pdo->query("SELECT * FROM ac_categories ORDER BY orderNum ASC")->fetchAll(PDO::FETCH_OBJ);

    $qTableStyle = [
        'borderSize'  => 4,
        'borderColor' => 'CBD5E1',
        'cellMargin'  => 80
    ];
    $phpWord->addTableStyle('QuestionTable', $qTableStyle);

    foreach ($categories as $cat) {
        $section->addText($cat->categoryName, ['bold' => true, 'size' => 11, 'color' => '0F172A', 'name' => $fontFamily], ['spaceBefore' => 150, 'spaceAfter' => 60]);

        $stmtQ = $pdo->prepare("SELECT * FROM ac_questions WHERE categoryId = :catId ORDER BY questionNumber ASC");
        $stmtQ->execute([':catId' => $cat->categoryId]);
        $questions = $stmtQ->fetchAll(PDO::FETCH_OBJ);

        if (empty($questions)) {
            continue;
        }

        $table = $section->addTable('QuestionTable');
        
        // Cabecera de la tabla de preguntas
        $table->addRow(280, ['tblHeader' => true]);
        $table->addCell(800, ['bgColor' => '1E293B'])->addText('N°', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8, 'name' => $fontFamily], ['alignment' => Jc::CENTER]);
        $table->addCell(5200, ['bgColor' => '1E293B'])->addText('Pregunta / Criterio de Control', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8, 'name' => $fontFamily]);
        $table->addCell(1000, ['bgColor' => '1E293B'])->addText('Respuesta', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8, 'name' => $fontFamily], ['alignment' => Jc::CENTER]);
        $table->addCell(2000, ['bgColor' => '1E293B'])->addText('Comentarios / Justificación', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8, 'name' => $fontFamily]);

        foreach ($questions as $q) {
            $res = $answersSaved[$q->questionId]['response'] ?? 'Sin Responder';
            $com = $answersSaved[$q->questionId]['comment'] ?? '-';

            $resColor = match ($res) {
                'Si'    => '16A34A',
                'No'    => 'DC2626',
                default => '64748B'
            };

            $table->addRow();
            $table->addCell(800)->addText((string)$q->questionNumber, ['bold' => true, 'size' => 8, 'name' => $fontFamily], ['alignment' => Jc::CENTER]);
            $table->addCell(5200)->addText($q->questionText, ['size' => 8, 'name' => $fontFamily]);
            $table->addCell(1000)->addText($res, ['bold' => true, 'color' => $resColor, 'size' => 8, 'name' => $fontFamily], ['alignment' => Jc::CENTER]);
            $table->addCell(2000)->addText($com, ['size' => 8, 'italic' => true, 'color' => '334155', 'name' => $fontFamily]);

            // 9. Desglose Especial para la Pregunta 28 (Matriz Analítica)
            if ((int)$q->questionNumber === 28) {
                $subtests = $pdo->query("SELECT * FROM ac_q28_tests ORDER BY testNumber ASC")->fetchAll(PDO::FETCH_OBJ);

                if (!empty($subtests)) {
                    $section->addText('Desglose Analítico - Matriz de Riesgo Interno (Detalle Prueba 28):', ['bold' => true, 'size' => 9, 'color' => '0284C7', 'name' => $fontFamily], ['spaceBefore' => 100, 'spaceAfter' => 50]);

                    $subTable = $section->addTable('QuestionTable');
                    $subTable->addRow(250, ['tblHeader' => true]);
                    $subTable->addCell(800, ['bgColor' => '0284C7'])->addText('Item', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8, 'name' => $fontFamily], ['alignment' => Jc::CENTER]);
                    $subTable->addCell(6200, ['bgColor' => '0284C7'])->addText('Factor de Riesgo / Prueba de Control', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8, 'name' => $fontFamily]);
                    $subTable->addCell(2000, ['bgColor' => '0284C7'])->addText('Riesgo Asignado', ['bold' => true, 'color' => 'FFFFFF', 'size' => 8, 'name' => $fontFamily], ['alignment' => Jc::CENTER]);

                    foreach ($subtests as $sub) {
                        $riskVal = $q28Saved[$sub->testId] ?? 'No Aplica';

                        $subTable->addRow();
                        $subTable->addCell(800)->addText((string)$sub->testNumber, ['bold' => true, 'size' => 8, 'name' => $fontFamily], ['alignment' => Jc::CENTER]);
                        $subTable->addCell(6200)->addText($sub->testText, ['size' => 8, 'name' => $fontFamily]);
                        $subTable->addCell(2000)->addText($riskVal, ['bold' => true, 'size' => 8, 'name' => $fontFamily], ['alignment' => Jc::CENTER]);
                    }
                }
            }
        }
    }

    // 10. Forzar Descarga Directa del Documento Word
    $filename = 'Informe_AC_Cliente_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $acData->clientName ?? 'Auditoria') . '_' . date('Ymd') . '.docx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save('php://output');
    exit;

} catch (PDOException $e) {
    error_log('Error SQL en exportar-word.php: ' . $e->getMessage());
    http_response_code(500);
    die('Error al recuperar los datos del informe.');
} catch (Exception $e) {
    error_log('Error en generación de Word: ' . $e->getMessage());
    http_response_code(500);
    die('Ocurrió un error al generar el archivo Word.');
}