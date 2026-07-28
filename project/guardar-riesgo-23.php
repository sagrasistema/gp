<?php
// /home2/sagracom/public_html/sistemas/project/guardar-riesgo-23.php
declare(strict_types=1);

include '../main/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$proyectoId = filter_input(INPUT_POST, 'proyecto_id', FILTER_VALIDATE_INT);

if (!$proyectoId) {
    echo json_encode(['status' => 'error', 'message' => 'ID de proyecto inválido.']);
    exit;
}

// Captura y sanitización usando los nombres exactos de tus columnas de la base de datos
$origenRiesgo       = trim($_POST['origen_riesgo'] ?? '');
$objetivosNegocio   = trim($_POST['objetivos_negocio'] ?? '');
$riesgoNegocio      = trim($_POST['riesgo_negocio'] ?? '');
$riesgoClave        = trim($_POST['riesgo_clave'] ?? '');
$respuestaControles = trim($_POST['respuesta_controles'] ?? '');
$areaAsercion       = trim($_POST['area_asercion'] ?? '');
$enfoqueAuditoria   = trim($_POST['enfoque_auditoria'] ?? '');
$emisionInforme     = trim($_POST['emision_informe'] ?? '');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO prueba_23_riesgos 
        (proyecto_id, origen_riesgo, objetivos_negocio, riesgo_negocio, riesgo_clave, respuesta_controles, area_asercion, enfoque_auditoria, emision_informe)
        VALUES (:proj, :origen, :objetivos, :r_negocio, :r_clave, :controles, :asercion, :enfoque, :emision)
        ON DUPLICATE KEY UPDATE 
            origen_riesgo = :origen_u, 
            objetivos_negocio = :objetivos_u, 
            riesgo_negocio = :r_negocio_u, 
            riesgo_clave = :r_clave_u, 
            respuesta_controles = :controles_u, 
            area_asercion = :asercion_u, 
            enfoque_auditoria = :enfoque_u, 
            emision_informe = :emision_u
    ");

    $params = [
        ':proj'        => $proyectoId,
        ':origen'      => $origenRiesgo !== '' ? $origenRiesgo : null,
        ':objetivos'   => $objetivosNegocio !== '' ? $objetivosNegocio : null,
        ':r_negocio'   => $riesgoNegocio !== '' ? $riesgoNegocio : null,
        ':r_clave'     => $riesgoClave !== '' ? $riesgoClave : null,
        ':controles'   => $respuestaControles !== '' ? $respuestaControles : null,
        ':asercion'    => $areaAsercion !== '' ? $areaAsercion : null,
        ':enfoque'     => $enfoqueAuditoria !== '' ? $enfoqueAuditoria : null,
        ':emision'     => $emisionInforme !== '' ? $emisionInforme : null,
        ':origen_u'      => $origenRiesgo !== '' ? $origenRiesgo : null,
        ':objetivos_u'   => $objetivosNegocio !== '' ? $objetivosNegocio : null,
        ':r_negocio_u'   => $riesgoNegocio !== '' ? $riesgoNegocio : null,
        ':r_clave_u'     => $riesgoClave !== '' ? $riesgoClave : null,
        ':controles_u'   => $respuestaControles !== '' ? $respuestaControles : null,
        ':asercion_u'    => $areaAsercion !== '' ? $areaAsercion : null,
        ':enfoque_u'     => $enfoqueAuditoria !== '' ? $enfoqueAuditoria : null,
        ':emision_u'     => $emisionInforme !== '' ? $emisionInforme : null,
    ];

    $stmt->execute($params);
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Riesgo guardado correctamente.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al guardar prueba_23_riesgos: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
}