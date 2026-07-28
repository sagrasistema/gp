<?php
// /home2/sagracom/public_html/sistemas/project/guardar-riesgo-23.php
declare(strict_types=1);

include '../main/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

$proyectoId = filter_input(INPUT_POST, 'proyecto_id', FILTER_VALIDATE_INT);
$pruebaId = 23;

if (!$proyectoId) {
    $errorMsg = urlencode("Error: Proyecto no especificado.");
    header("Location: actividades.php?proyectoId=0&pruebaId=23&error={$errorMsg}");
    exit;
}

// Sanitización rigurosa de cada campo proveniente de los selectores del modal
$campo1 = trim($_POST['origen_riesgo'] ?? '');
$campo2 = trim($_POST['objetivos_negocio'] ?? '');
$campo3 = trim($_POST['riesgo_negocio'] ?? '');
$campo4 = trim($_POST['riesgo_clave'] ?? '');
$campo5 = trim($_POST['respuesta_controles'] ?? '');
$campo6 = trim($_POST['area_asercion'] ?? '');
$campo7 = trim($_POST['enfoque_auditoria'] ?? '');
$campo8 = trim($_POST['emision_informe'] ?? '');

try {
    $pdo->beginTransaction();

    $stmtMrSave = $pdo->prepare("
        INSERT INTO prueba_23_riesgos 
        (proyecto_id, campo1, campo2, campo3, campo4, campo5, campo6, campo7, campo8)
        VALUES (:proj, :c1, :c2, :c3, :c4, :c5, :c6, :c7, :c8)
        ON DUPLICATE KEY UPDATE 
            campo1 = :c1_u, campo2 = :c2_u, campo3 = :c3_u, campo4 = :c4_u, 
            campo5 = :c5_u, campo6 = :c6_u, campo7 = :c7_u, campo8 = :c8_u
    ");

    $stmtMrSave->execute([
        ':proj' => $proyectoId,
        ':c1'   => $campo1 !== '' ? $campo1 : null,
        ':c2'   => $campo2 !== '' ? $campo2 : null,
        ':c3'   => $campo3 !== '' ? $campo3 : null,
        ':c4'   => $campo4 !== '' ? $campo4 : null,
        ':c5'   => $campo5 !== '' ? $campo5 : null,
        ':c6'   => $campo6 !== '' ? $campo6 : null,
        ':c7'   => $campo7 !== '' ? $campo7 : null,
        ':c8'   => $campo8 !== '' ? $campo8 : null,
        ':c1_u' => $campo1 !== '' ? $campo1 : null,
        ':c2_u' => $campo2 !== '' ? $campo2 : null,
        ':c3_u' => $campo3 !== '' ? $campo3 : null,
        ':c4_u' => $campo4 !== '' ? $campo4 : null,
        ':c5_u' => $campo5 !== '' ? $campo5 : null,
        ':c6_u' => $campo6 !== '' ? $campo6 : null,
        ':c7_u' => $campo7 !== '' ? $campo7 : null,
        ':c8_u' => $campo8 !== '' ? $campo8 : null,
    ]);

    $pdo->commit();
    header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&success=1");
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al guardar prueba_23_riesgos: " . $e->getMessage());
    $errorMsg = urlencode("Error al procesar el riesgo: " . $e->getMessage());
    header("Location: actividades.php?proyectoId={$proyectoId}&pruebaId={$pruebaId}&error={$errorMsg}");
    exit;
}