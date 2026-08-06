<?php

declare(strict_types=1);

include '../main/config.php';

// ----------------------------------------------------------------------
// 1. Sanitización y Validación de Entrada (ID de Proyecto)
// ----------------------------------------------------------------------
$proyectoId = filter_input(INPUT_GET, 'proyectoId', FILTER_VALIDATE_INT) 
           ?? filter_input(INPUT_GET, 'proyecto_id', FILTER_VALIDATE_INT);

if (!$proyectoId || $proyectoId <= 0) {
    http_response_code(400);
    echo "Error: El parámetro 'proyectoId' es obligatorio y debe ser un entero válido.";
    exit;
}

/**
 * Escapa caracteres especiales para evitar corromper la estructura OpenXML de Word
 */
function xml_escape(?string $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');
}

try {
    $db = $pdo;

    // ------------------------------------------------------------------
    // 2. Consulta de Datos de la Etapa de Planificación del Proyecto
    // ------------------------------------------------------------------
    
    // Consulta principal del Proyecto
    $sqlProyecto = "SELECT 
                        p.id AS proyectoId,
                        p.nombre AS nombreProyecto,
                        p.cliente_id AS clienteId,
                        p.socio,
                        p.gerente,
                        p.fecha_inicio AS fechaInicio,
                        p.fecha_fin AS fechaFin,
                        p.estado
                    FROM proyectos p
                    WHERE p.id = :proyectoId";

    $stmtProyecto = $db->prepare($sqlProyecto);
    $stmtProyecto->execute([':proyectoId' => $proyectoId]);
    $proyecto = $stmtProyecto->fetch();

    if (!$proyecto) {
        http_response_code(404);
        echo "Error: No se encontró el proyecto con ID {$proyectoId}.";
        exit;
    }

    // Consulta de Actividades / Pruebas Planificadas de Auditoría
    $sqlActividades = "SELECT 
                        a.id,
                        a.name AS nombreActividad,
                        a.priority AS prioridad,
                        a.status AS estadoActividad,
                        aa.descripcion AS pruebaDescripcion,
                        aa.orden
                       FROM actividades a
                       LEFT JOIN audit_actividades aa ON a.id = aa.prueba_id
                       WHERE a.proyecto_id = :proyectoId OR aa.proyecto_id = :proyectoId
                       ORDER BY COALESCE(aa.orden, a.id) ASC";

    $stmtActividades = $db->prepare($sqlActividades);
    $stmtActividades->execute([':proyectoId' => $proyectoId]);
    $actividades = $stmtActividades->fetchAll();

    // ------------------------------------------------------------------
    // 3. Generación del Documento OpenXML (word/document.xml)
    // ------------------------------------------------------------------
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">';
    $xml .= '<w:body>';

    // --- Título del Memorándum de Planificación ---
    $xml .= '<w:p>';
    $xml .= '  <w:pPr><w:jc w:val="center"/><w:spacing w:after="240"/></w:pPr>';
    $xml .= '  <w:r><w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="1B365D"/></w:rPr><w:t>MEMORÁNDUM DE PLANIFICACIÓN DE AUDITORÍA</w:t></w:r>';
    $xml .= '</w:p>';

    $xml .= '<w:p>';
    $xml .= '  <w:pPr><w:jc w:val="center"/><w:spacing w:after="360"/></w:pPr>';
    $xml .= '  <w:r><w:rPr><w:b/><w:sz w:val="22"/><w:color w:val="555555"/></w:rPr><w:t>Proyecto N°: PRJ-' . xml_escape(str_pad((string)$proyecto['proyectoId'], 6, '0', STR_PAD_LEFT)) . '</w:t></w:r>';
    $xml .= '</w:p>';

    // --- SECCIÓN 1: DATOS DEL PROYECTO ---
    $xml .= '<w:p><w:pPr><w:spacing w:before="240" w:after="120"/></w:pPr>';
    $xml .= '  <w:r><w:rPr><w:b/><w:sz w:val="26"/><w:color w:val="2C3E50"/></w:rPr><w:t>1. Alcance y Datos Generales del Proyecto</w:t></w:r>';
    $xml .= '</w:p>';

    $tableBorders = '<w:tblBorders>'
        . '<w:top w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>'
        . '<w:left w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>'
        . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>'
        . '<w:right w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/>'
        . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="E0E0E0"/>'
        . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="E0E0E0"/>'
        . '</w:tblBorders>';

    $xml .= '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/>' . $tableBorders . '</w:tblPr>';

    $infoRows = [
        'Nombre del Proyecto' => $proyecto['nombreProyecto'] ?? 'Sin Nombre',
        'Cliente (ID)'        => (string)($proyecto['clienteId'] ?? 'N/A'),
        'Socio Asignado'      => $proyecto['socio'] ?? 'No asignado',
        'Gerente a Cargo'     => $proyecto['gerente'] ?? 'No asignado',
        'Fecha Inicio Plan'   => $proyecto['fechaInicio'] ?? 'Pendiente',
        'Fecha Cierre Est.'   => $proyecto['fechaFin'] ?? 'Pendiente',
        'Estado del Proyecto' => $proyecto['estado'] ?? 'En Proceso'
    ];

    foreach ($infoRows as $label => $val) {
        $xml .= '<w:tr>';
        $xml .= '  <w:tc><w:tcPr><w:tcW w:w="3000" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="F2F4F4"/></w:tcPr><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>' . xml_escape($label) . '</w:t></w:r></w:p></w:tc>';
        $xml .= '  <w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>' . xml_escape($val) . '</w:t></w:r></w:p></w:tc>';
        $xml .= '</w:tr>';
    }
    $xml .= '</w:tbl>';

    // --- SECCIÓN 2: CRONOGRAMA Y PROGRAMA DE ACTIVIDADES (AUDITORÍA) ---
    $xml .= '<w:p><w:pPr><w:spacing w:before="360" w:after="120"/></w:pPr>';
    $xml .= '  <w:r><w:rPr><w:b/><w:sz w:val="26"/><w:color w:val="2C3E50"/></w:rPr><w:t>2. Plan de Actividades y Pruebas Programadas</w:t></w:r>';
    $xml .= '</w:p>';

    if (!empty($actividades)) {
        $xml .= '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/>' . $tableBorders . '</w:tblPr>';
        
        // Encabezados
        $xml .= '<w:tr>';
        $headers = ['Orden', 'Actividad / Nombre', 'Descripción de la Prueba', 'Prioridad', 'Estado'];
        foreach ($headers as $hdr) {
            $xml .= '<w:tc><w:tcPr><w:shd w:val="clear" w:color="auto" w:fill="1B365D"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/></w:rPr><w:t>' . xml_escape($hdr) . '</w:t></w:r></w:p></w:tc>';
        }
        $xml .= '</w:tr>';

        // Filas
        foreach ($actividades as $index => $act) {
            $orden = $act['orden'] ?? ($index + 1);
            $xml .= '<w:tr>';
            $xml .= '  <w:tc><w:p><w:r><w:t>' . xml_escape((string)$orden) . '</w:t></w:r></w:p></w:tc>';
            $xml .= '  <w:tc><w:p><w:r><w:t>' . xml_escape($act['nombreActividad'] ?? 'N/A') . '</w:t></w:r></w:p></w:tc>';
            $xml .= '  <w:tc><w:p><w:r><w:t>' . xml_escape($act['pruebaDescripcion'] ?? 'Sin detalle registrado') . '</w:t></w:r></w:p></w:tc>';
            $xml .= '  <w:tc><w:p><w:r><w:t>' . xml_escape($act['prioridad'] ?? 'Normal') . '</w:t></w:r></w:p></w:tc>';
            $xml .= '  <w:tc><w:p><w:r><w:rPr><w:b/></w:rPr><w:t>' . xml_escape($act['estadoActividad'] ?? 'Pendiente') . '</w:t></w:r></w:p></w:tc>';
            $xml .= '</w:tr>';
        }
        $xml .= '</w:tbl>';
    } else {
        $xml .= '<w:p><w:r><w:rPr><w:i/></w:rPr><w:t>No existen actividades o pruebas asignadas a la etapa de planificación de este proyecto.</w:t></w:r></w:p>';
    }

    $xml .= '</w:body>';
    $xml .= '</w:document>';

    // ------------------------------------------------------------------
    // 4. Empaquetado ZIP (Generación .docx)
    // ------------------------------------------------------------------
    $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
        . '</Types>';

    $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
        . '</Relationships>';

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('La extensión ZipArchive de PHP no está habilitada en el servidor.');
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'docx_plan_');
    $zip = new ZipArchive();

    if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('No se pudo crear el archivo temporal para exportar Word.');
    }

    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    $zip->addFromString('_rels/.rels', $relsXml);
    $zip->addFromString('word/document.xml', $xml);
    $zip->close();

    // ------------------------------------------------------------------
    // 5. Envío de Cabeceras HTTP
    // ------------------------------------------------------------------
    if (ob_get_length()) {
        ob_end_clean();
    }

    $filename = "Planificacion_Proyecto_" . $proyectoId . ".docx";

    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($tempFile));
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');

    readfile($tempFile);
    @unlink($tempFile);
    exit;

} catch (Exception $e) {
    error_log("Error al exportar planificación de proyecto (Proyecto ID: {$proyectoId}): " . $e->getMessage());
    http_response_code(500);
    echo "Ocurrió un error interno al generar la exportación del proyecto.";
    exit;
}

/** @var PDO $pdo */

try {
    // A. Consultar Información General del Proyecto
    $sqlProyecto = "SELECT id, nombre, fecha_inicio FROM proyectos WHERE id = :proyecto_id LIMIT 1";
    $stmtProyecto = $pdo->prepare($sqlProyecto);
    $stmtProyecto->execute([':proyecto_id' => $proyectoId]);
    $proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);

    if (!$proyecto) {
        http_response_code(404);
        die("Error: No se encontró el proyecto con ID {$proyectoId}.");
    }

    // B. Consultar Respuestas Generales de Pruebas de Planificación (Excluyendo especiales si aplica)
    $sqlRespuestas = "SELECT 
                        pr.prueba_id, 
                        p.nombre_prueba, 
                        pr.respuesta, 
                        pr.observaciones 
                      FROM proyecto_respuestas pr
                      INNER JOIN pruebas p ON pr.prueba_id = p.id
                      WHERE pr.proyecto_id = :proyecto_id AND p.etapa = 2
                      ORDER BY p.id ASC";
    $stmtRespuestas = $pdo->prepare($sqlRespuestas);
    $stmtRespuestas->execute([':proyecto_id' => $proyectoId]);
    $respuestasGenerales = $stmtRespuestas->fetchAll(PDO::FETCH_ASSOC);

    // C. Consultar PRUEBA ESPECIAL 11: Revisión Analítica Preliminar
    $sqlP11 = "SELECT id, tipo, tipo_rubro, saldo_actual, saldo_anterior, observaciones 
               FROM proyecto_revision_analitica 
               WHERE proyecto_id = :proyecto_id AND prueba_id = 11 
               ORDER BY id ASC";
    $stmtP11 = $pdo->prepare($sqlP11);
    $stmtP11->execute([':proyecto_id' => $proyectoId]);
    $rowsP11 = $stmtP11->fetchAll(PDO::FETCH_ASSOC);

    $analiticaItems = ['activo' => [], 'pasivo' => [], 'patrimonio' => []];
    foreach ($rowsP11 as $row) {
        if (isset($analiticaItems[$row['tipo']])) {
            $analiticaItems[$row['tipo']][] = $row;
        }
    }

    // D. Consultar PRUEBA ESPECIAL 16: Determinación de Materialidad
    $sqlP16 = "SELECT * FROM proyecto_materialidad 
               WHERE proyecto_id = :proyecto_id AND prueba_id = 16 
               LIMIT 1";
    $stmtP16 = $pdo->prepare($sqlP16);
    $stmtP16->execute([':proyecto_id' => $proyectoId]);
    $materialidad = $stmtP16->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error BD en export_planificacion_word: " . $e->getMessage());
    http_response_code(500);
    die("Error interno al recuperar los datos del reporte.");
}

// 3. Cabeceras HTTP para forzar la descarga en Microsoft Word (.doc)
$fileName = "Reporte_Planificacion_Proyecto_{$proyectoId}.doc";

header("Content-Type: application/vnd.ms-word; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$fileName}\"");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

// 4. Plantilla HTML/CSS optimizada para Microsoft Word
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:w="urn:schemas-microsoft-com:office:word" 
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>Reporte de Etapa 2 - Planificación de Auditoría</title>
    <style>
        @page {
            size: 21cm 29.7cm; /* A4 */
            margin: 2cm;
        }
        body {
            font-family: 'Calibri', 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            color: #333333;
            line-height: 1.4;
        }
        h1 {
            font-size: 18pt;
            color: #1E3A8A;
            text-align: center;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 10pt;
            color: #64748B;
            text-align: center;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 13pt;
            color: #1E3A8A;
            border-bottom: 2px solid #1E3A8A;
            padding-bottom: 4px;
            margin-top: 25px;
        }
        .summary-box {
            background-color: #F8FAFC;
            border: 1px solid #CBD5E1;
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
            color: #1E293B;
            width: 30%;
        }
        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        table.report-table th {
            background-color: #1E3A8A;
            color: #FFFFFF;
            font-weight: bold;
            font-size: 10pt;
            padding: 8px;
            border: 1px solid #1E3A8A;
            text-align: left;
        }
        table.report-table td {
            padding: 7px;
            font-size: 9.5pt;
            border: 1px solid #CBD5E1;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bg-category { background-color: #E2E8F0; font-weight: bold; color: #1E293B; }
        .bg-subtotal { background-color: #F1F5F9; font-weight: bold; color: #334155; }
        .bg-total { background-color: #1E293B; color: #FFFFFF; font-weight: bold; }
        .no-data { font-style: italic; color: #64748B; padding: 10px; }
    </style>
</head>
<body>

    <h1>Reporte de Etapa 2: Planificación</h1>
    <div class="subtitle">Sistema de Auditoría | Proyecto #<?= htmlspecialchars((string)$proyecto['id'], ENT_QUOTES, 'UTF-8') ?></div>

    <!-- Resumen Ejecutivo del Proyecto -->
    <div class="summary-box">
        <table>
            <tr>
                <td class="label">Cliente / Entidad:</td>
                <td><?= htmlspecialchars((string)($proyecto['nombre_cliente'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="label">RIF / Identificación:</td>
                <td><?= htmlspecialchars((string)($proyecto['RIF'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="label">Periodo Auditado:</td>
                <td><?= htmlspecialchars((string)($proyecto['periodo'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        </table>
    </div>

    <!-- SECCIÓN 1: PRUEBA ESPECIAL 16 - MATERIALIDAD (NIA 320 / NIA 450) -->
    <h2>1. Determinación de la Materialidad (Prueba Especial 16 - NIA 320 / 450)</h2>
    <?php if ($materialidad): ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Parámetro / Concepto de Evaluación</th>
                    <th style="width: 40%;" class="text-right">Monto / Porcentaje</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Punto de Referencia Seleccionado</strong></td>
                    <td class="text-right"><?= htmlspecialchars((string)($materialidad['punto_referencia'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <tr>
                    <td>Monto Base del Punto de Referencia</td>
                    <td class="text-right">Bs. <?= number_format((float)($materialidad['beneficios_monto'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Tramo Empírico Aplicado (%)</td>
                    <td class="text-right"><?= number_format((float)($materialidad['tramo_porc'] ?? 0), 2, ',', '.') ?>%</td>
                </tr>
                <tr>
                    <td>Cálculo de Importancia Relativa por Tramo</td>
                    <td class="text-right">Bs. <?= number_format((float)($materialidad['tramo_monto'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <tr>
                    <td><strong>Importancia Relativa Seleccionada (Inicial)</strong></td>
                    <td class="text-right"><strong>Bs. <?= number_format((float)($materialidad['importancia_inicial_monto'] ?? 0), 2, ',', '.') ?></strong></td>
                </tr>
                <tr>
                    <td>Recorte / Ajuste Cualitativo Global (%)</td>
                    <td class="text-right"><?= number_format((float)($materialidad['recorte_porc'] ?? 0), 2, ',', '.') ?>%</td>
                </tr>
                <tr>
                    <td>Monto Descontado por Ajuste Cualitativo</td>
                    <td class="text-right">Bs. <?= number_format((float)($materialidad['recorte_monto'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <tr style="background-color: #E2E8F0;">
                    <td><strong>IMPORTANCIA RELATIVA SELECCIONADA (AJUSTADA)</strong></td>
                    <td class="text-right"><strong>Bs. <?= number_format((float)($materialidad['importancia_ajustada_monto'] ?? 0), 2, ',', '.') ?></strong></td>
                </tr>
                <tr>
                    <td>Nivel Mínimo Registro Incorrecciones (De Minimis - NIA 450) %</td>
                    <td class="text-right"><?= number_format((float)($materialidad['minimis_porc'] ?? 0), 2, ',', '.') ?>%</td>
                </tr>
                <tr>
                    <td>Monto Umbral De Minimis (Principal)</td>
                    <td class="text-right">Bs. <?= number_format((float)($materialidad['minimis_monto'] ?? 0), 2, ',', '.') ?></td>
                </tr>
                <tr>
                    <td>Monto Umbral De Minimis (Secundario)</td>
                    <td class="text-right">Bs. <?= number_format((float)($materialidad['minimis_secundario_monto'] ?? 0), 2, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No se han registrado datos para la prueba de Materialidad en este proyecto.</p>
    <?php endif; ?>

    <!-- SECCIÓN 2: PRUEBA ESPECIAL 11 - REVISIÓN ANALÍTICA PRELIMINAR -->
    <h2>2. Revisión Analítica Preliminar (Prueba Especial 11 - Estado de Situación Financiera)</h2>
    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 20%;">Rubro</th>
                <th style="width: 15%;">Tipo</th>
                <th style="width: 15%;" class="text-right">Saldo Actual (Bs.)</th>
                <th style="width: 15%;" class="text-right">Saldo Anterior (Bs.)</th>
                <th style="width: 12%;" class="text-right">Var. En Bs.</th>
                <th style="width: 8%;" class="text-right">Var. %</th>
                <th style="width: 15%;">Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totActCur = 0.0; $totActAnt = 0.0;
            $totPasCur = 0.0; $totPasAnt = 0.0;
            $totPatCur = 0.0; $totPatAnt = 0.0;

            foreach (['activo', 'pasivo', 'patrimonio'] as $cat):
                $itemsSec = $analiticaItems[$cat];
                $subCur = 0.0; $subAnt = 0.0;
            ?>
                <tr class="bg-category">
                    <td colspan="7"><?= strtoupper($cat) ?></td>
                </tr>
                <?php if (!empty($itemsSec)): ?>
                    <?php foreach ($itemsSec as $item): 
                        $sActual = (float)$item['saldo_actual'];
                        $sAnterior = (float)$item['saldo_anterior'];
                        $varBs = $sActual - $sAnterior;
                        $varPorc = ($sAnterior != 0.0) ? ($varBs / $sAnterior) * 100 : 0.0;
                        
                        if ($cat === 'activo') { $totActCur += $sActual; $totActAnt += $sAnterior; }
                        if ($cat === 'pasivo') { $totPasCur += $sActual; $totPasAnt += $sAnterior; }
                        if ($cat === 'patrimonio') { $totPatCur += $sActual; $totPatAnt += $sAnterior; }
                        
                        $subCur += $sActual; 
                        $subAnt += $sAnterior;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$item['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$item['tipo_rubro'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-right"><?= number_format($sActual, 2, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($sAnterior, 2, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($varBs, 2, ',', '.') ?></td>
                            <td class="text-right"><?= number_format($varPorc, 2, ',', '.') ?>%</td>
                            <td><?= htmlspecialchars((string)($item['observaciones'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="bg-subtotal">
                        <td colspan="2" class="text-right">Total <?= ucfirst($cat) ?></td>
                        <td class="text-right"><?= number_format($subCur, 2, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($subAnt, 2, ',', '.') ?></td>
                        <td colspan="3"></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-data text-center">Sin registros asignados en <?= $cat ?>.</td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Total General Consolidado -->
            <?php 
            $genCur = $totActCur - ($totPasCur + $totPatCur); 
            $genAnt = $totActAnt - ($totPasAnt + $totPatAnt);
            ?>
            <tr class="bg-total">
                <td colspan="2" class="text-right">TOTAL ESTRUCTURAL</td>
                <td class="text-right"><?= number_format($genCur, 2, ',', '.') ?></td>
                <td class="text-right"><?= number_format($genAnt, 2, ',', '.') ?></td>
                <td colspan="3"></td>
            </tr>
        </tbody>
    </table>

    <!-- SECCIÓN 3: CUESTIONARIO Y OTRAS PRUEBAS DE PLANIFICACIÓN -->
    <h2>3. Cuestionario y Pruebas Generales de Planificación</h2>
    <?php if (!empty($respuestasGenerales)): ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 10%;" class="text-center">Prueba #</th>
                    <th style="width: 35%;">Nombre de la Prueba</th>
                    <th style="width: 15%;" class="text-center">Respuesta</th>
                    <th style="width: 40%;">Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($respuestasGenerales as $rg): ?>
                    <tr>
                        <td class="text-center"><strong><?= htmlspecialchars((string)$rg['prueba_id'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars((string)$rg['nombre_prueba'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center"><strong><?= htmlspecialchars((string)$rg['respuesta'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars((string)($rg['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No hay respuestas generales registradas para las pruebas de planificación.</p>
    <?php endif; ?>

</body>
</html>