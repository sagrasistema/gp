<?php

declare(strict_types=1);

// Carga de dependencias y configuración de la base de datos PDO
require_once __DIR__ . '/../main/h.php';
require_once __DIR__ . '/../main/config.php';

/** @var PDO $pdo */

/**
 * Normaliza cualquier formato contable (ej: "(125,170)", "-", "1,047,761") a float.
 */
function parseMontoContable(?string $valor): float
{
    if ($valor === null) {
        return 0.0;
    }

    $valor = trim($valor);

    if ($valor === '' || $valor === '-' || $valor === '—') {
        return 0.0;
    }

    $esNegativo = false;
    if (str_starts_with($valor, '(') && str_ends_with($valor, ')')) {
        $esNegativo = true;
        $valor = substr($valor, 1, -1);
    }

    // Remover separadores de miles
    $valor = str_replace(',', '', $valor);
    $monto = (float)$valor;

    return $esNegativo ? -$monto : $monto;
}

/**
 * Procesa la importación del Sumario de Auditoría.
 */
function importarSumarioCSV(PDO $pdo, int $actividadId, array $fileArray): array
{
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Seleccione un archivo CSV válido para importar.'];
    }

    $tmpFilePath = $fileArray['tmp_name'];
    $extension   = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));

    if ($extension !== 'csv') {
        return ['success' => false, 'message' => 'El archivo adjunto debe ser de formato .csv'];
    }

    $content = file_get_contents($tmpFilePath);
    if ($content === false) {
        return ['success' => false, 'message' => 'No se pudo leer el contenido del archivo.'];
    }

    // Limpieza de caracteres invisibles BOM UTF-8
    $content = preg_replace('/^[\x00-\x1F\x7F\xEF\xBB\xBF]+/', '', $content);
    $lines   = explode("\n", str_replace(["\r\n", "\r"], "\n", trim($content)));

    if (count($lines) <= 1) {
        return ['success' => false, 'message' => 'El archivo está vacío o no posee registros de datos.'];
    }

    // Auto-detectar delimitador
    $delimitador = str_contains($lines[0], ';') ? ';' : ',';

    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO actividad_balance_auditado 
                    (actividad_id, link_agrup, link, codigo, descripcion, balance_cierre, debe, haber, balance_auditado)
                VALUES 
                    (:actividad_id, :link_agrup, :link, :codigo, :descripcion, :balance_cierre, :debe, :haber, :balance_auditado)";

        $stmt = $pdo->prepare($sql);
        $procesados = 0;

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line) || $index === 0) { // Omitir línea de cabeceras
                continue;
            }

            $row = str_getcsv($line, $delimitador);
            if (count($row) < 4) {
                continue;
            }

            $codigo      = trim((string)($row[2] ?? ''));
            $descripcion = trim((string)($row[3] ?? ''));

            if ($codigo === '' || $descripcion === '') {
                continue;
            }

            $linkAgrup     = isset($row[0]) && trim((string)$row[0]) !== '' ? trim((string)$row[0]) : null;
            $link          = isset($row[1]) && trim((string)$row[1]) !== '' ? trim((string)$row[1]) : null;
            $balanceCierre = parseMontoContable((string)($row[4] ?? '0'));
            $debe          = parseMontoContable((string)($row[5] ?? '0'));
            $haber         = parseMontoContable((string)($row[6] ?? '0'));

            // Ecuación contable del balance auditado
            $balanceAuditado = $balanceCierre + $debe - $haber;

            $stmt->execute([
                ':actividad_id'     => $actividadId,
                ':link_agrup'      => $linkAgrup,
                ':link'            => $link,
                ':codigo'          => $codigo,
                ':descripcion'     => $descripcion,
                ':balance_cierre'  => $balanceCierre,
                ':debe'            => $debe,
                ':haber'           => $haber,
                ':balance_auditado' => $balanceAuditado,
            ]);

            $procesados++;
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => "Se importaron exitosamente {$procesados} cuentas al sumario de la actividad #{$actividadId}."
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Error en guardar_actividad.php (Actividad {$actividadId}): " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error al procesar la base de datos: ' . $e->getMessage()
        ];
    }
}

// --------------------------------------------------------------------------
// CONTROLADOR DE PETICIÓN POST
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_actividad'])) {
    
    // Validar y sanitizar el ID de la actividad del campo oculto
    $actividadId = filter_input(INPUT_POST, 'actividad_id', FILTER_VALIDATE_INT);

    if (!$actividadId || $actividadId <= 0) {
        die('Error: El ID de la actividad proporcionado no es válido.');
    }

    if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        $resultado = importarSumarioCSV($pdo, $actividadId, $_FILES['archivo_csv']);

        if ($resultado['success']) {
            // Redireccionar a la vista de renderizado con mensaje de éxito
            header("Location: ver_balance.php?actividad_id={$actividadId}&msg=ok");
            exit;
        } else {
            echo "<div style='color: red; padding: 15px; border: 1px solid red; background: #fef2f2; font-family: sans-serif; margin: 20px;'>";
            echo "<strong>Error al guardar:</strong> " . htmlspecialchars($resultado['message']);
            echo "<br><br><a href='javascript:history.back()'>Volver al formulario</a>";
            echo "</div>";
            exit;
        }
    } else {
        echo "<div style='color: red; padding: 15px; border: 1px solid red; background: #fef2f2; font-family: sans-serif; margin: 20px;'>";
        echo "Por favor, seleccione un archivo CSV para adjuntar antes de guardar.";
        echo "<br><br><a href='javascript:history.back()'>Volver al formulario</a>";
        echo "</div>";
        exit;
    }
}