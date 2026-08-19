<?php

declare(strict_types=1);

require_once __DIR__ . '/../main/h.php';
require_once __DIR__ . '/../main/config.php';
require_once __DIR__ . '/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

/** @var PDO $pdo */

/**
 * Convierte montos contables de Excel ("-", "(188)", "12,815,312") a float.
 */
function parseMontoContable(mixed $valor): float
{
    if ($valor === null) {
        return 0.0;
    }

    if (is_numeric($valor)) {
        return (float)$valor;
    }

    $str = trim((string)$valor);

    if ($str === '' || $str === '-' || $str === '—' || $str === '–') {
        return 0.0;
    }

    $esNegativo = false;
    if (str_starts_with($str, '(') && str_ends_with($str, ')')) {
        $esNegativo = true;
        $str = substr($str, 1, -1);
    }

    $str = str_replace([',', ' '], '', $str);
    $monto = (float)$str;

    return $esNegativo ? -$monto : $monto;
}

/**
 * Mapea e inserta las 15 columnas del archivo Excel (.xlsx).
 */
function importarSumarioExcelNativo(PDO $pdo, int $actividadId, array $fileArray): array
{
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al recibir el archivo subido.'];
    }

    $tmpFilePath = $fileArray['tmp_name'];
    $extension   = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));

    if ($extension !== 'xlsx') {
        return ['success' => false, 'message' => 'El archivo debe estar guardado en formato Excel (.xlsx).'];
    }

    $xlsx = SimpleXLSX::parse($tmpFilePath);

    if (!$xlsx) {
        return ['success' => false, 'message' => 'Error al leer el archivo Excel: ' . SimpleXLSX::parseError()];
    }

    $rows = $xlsx->rows();

    if (count($rows) <= 1) {
        return ['success' => false, 'message' => 'El archivo Excel no contiene filas de datos.'];
    }

    try {
        $pdo->beginTransaction();

        $deleteStmt = $pdo->prepare("DELETE FROM actividad_balance_auditado WHERE actividad_id = :actividad_id");
        $deleteStmt->execute([':actividad_id' => $actividadId]);

        $sql = "INSERT INTO actividad_balance_auditado 
                    (actividad_id, link_eeff_1, rubro_eeff_1, link_eeff_2, rubro_eeff_notas, 
                     link_centro_costo, tipo_partida, codigo, nombre, codigo_nombre, 
                     balance_cierre, debe, haber, balance_auditado, balance_final_ajustado, diferencia)
                VALUES 
                    (:actividad_id, :link_eeff_1, :rubro_eeff_1, :link_eeff_2, :rubro_eeff_notas, 
                     :link_centro_costo, :tipo_partida, :codigo, :nombre, :codigo_nombre, 
                     :balance_cierre, :debe, :haber, :balance_auditado, :balance_final_ajustado, :diferencia)";

        $stmt = $pdo->prepare($sql);
        $procesados = 0;

        foreach ($rows as $index => $row) {
            // Ignorar fila 0 (Encabezados)
            if ($index === 0 || empty($row)) {
                continue;
            }

            $codigo = isset($row[6]) ? trim((string)$row[6]) : '';
            $nombre = isset($row[7]) ? trim((string)$row[7]) : '';

            if ($codigo === '' && $nombre === '') {
                continue;
            }

            $linkEeff1       = isset($row[0]) && trim((string)$row[0]) !== '' ? trim((string)$row[0]) : null;
            $rubroEeff1      = isset($row[1]) && trim((string)$row[1]) !== '' ? trim((string)$row[1]) : null;
            $linkEeff2       = isset($row[2]) && trim((string)$row[2]) !== '' ? trim((string)$row[2]) : null;
            $rubroEeffNotas  = isset($row[3]) && trim((string)$row[3]) !== '' ? trim((string)$row[3]) : null;
            $linkCentroCosto = isset($row[4]) && trim((string)$row[4]) !== '' ? trim((string)$row[4]) : null;
            $tipoPartida     = isset($row[5]) && trim((string)$row[5]) !== '' ? trim((string)$row[5]) : null;
            $codigoNombre    = isset($row[8]) && trim((string)$row[8]) !== '' ? trim((string)$row[8]) : null;

            $balanceCierre   = parseMontoContable($row[9] ?? 0);
            $debe            = parseMontoContable($row[10] ?? 0);
            $haber           = parseMontoContable($row[11] ?? 0);
            $balanceAuditado = parseMontoContable($row[12] ?? 0);
            $balFinalAjust   = parseMontoContable($row[13] ?? 0);
            $diferencia      = parseMontoContable($row[14] ?? 0);

            // Recalcular saldo auditado si viene en cero desde el Excel
            if ($balanceAuditado === 0.0 && ($balanceCierre != 0.0 || $debe != 0.0 || $haber != 0.0)) {
                $balanceAuditado = $balanceCierre + $debe - $haber;
            }

            $stmt->execute([
                ':actividad_id'           => $actividadId,
                ':link_eeff_1'            => $linkEeff1,
                ':rubro_eeff_1'           => $rubroEeff1,
                ':link_eeff_2'            => $linkEeff2,
                ':rubro_eeff_notas'       => $rubroEeffNotas,
                ':link_centro_costo'      => $linkCentroCosto,
                ':tipo_partida'           => $tipoPartida,
                ':codigo'                 => $codigo,
                ':nombre'                 => $nombre,
                ':codigo_nombre'          => $codigoNombre,
                ':balance_cierre'         => $balanceCierre,
                ':debe'                   => $debe,
                ':haber'                  => $haber,
                ':balance_auditado'       => $balanceAuditado,
                ':balance_final_ajustado' => $balFinalAjust,
                ':diferencia'             => $diferencia,
            ]);

            $procesados++;
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => "Se importaron exitosamente {$procesados} filas de auditoría."
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Error guardando Excel (Actividad {$actividadId}): " . $e->getMessage());

        return ['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()];
    }
}

// --------------------------------------------------------------------------
// PROCESADOR DE CONTROLADOR POST
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_actividad'])) {

    $actividadId = filter_input(INPUT_POST, 'actividad_id', FILTER_VALIDATE_INT);

    if (!$actividadId || $actividadId <= 0) {
        die('Error: El ID de la actividad no es válido.');
    }

    if (isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        $resultado = importarSumarioExcelNativo($pdo, $actividadId, $_FILES['archivo_csv']);

        if ($resultado['success']) {
            header("Location: ver_balance.php?actividad_id={$actividadId}&msg=ok");
            exit;
        } else {
            echo "<div style='color: #b91c1c; padding: 15px; border: 1px solid #fca5a5; background: #fef2f2; font-family: sans-serif; margin: 20px; border-radius: 6px;'>";
            echo "<strong>Error al guardar:</strong> " . htmlspecialchars($resultado['message']);
            echo "<br><br><a href='javascript:history.back()'>Volver al formulario</a>";
            echo "</div>";
            exit;
        }
    }
}