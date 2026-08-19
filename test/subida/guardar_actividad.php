<?php

declare(strict_types=1);

require_once __DIR__ . '/../main/h.php';
require_once __DIR__ . '/../main/config.php';

// Cargar la librería nativa de un solo archivo (Sin Composer)
require_once __DIR__ . '/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

/** @var PDO $pdo */

/**
 * Normaliza valores contables provenientes de Excel: "(125,170)", "-", "1,047,761" a float.
 *
 * @param mixed $valor Valor directo de la celda de Excel.
 * @return float Valor numérico limpio.
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

    // Limpieza de separadores de miles
    $str = str_replace([',', ' '], '', $str);
    $monto = (float)$str;

    return $esNegativo ? -$monto : $monto;
}

/**
 * Procesa la importación de una hoja de cálculo Excel .xlsx usando SimpleXLSX.
 *
 * @param PDO $pdo Conexión PDO activa.
 * @param int $actividadId ID de la actividad en ejecución.
 * @param array $fileArray Matriz $_FILES['archivo_csv'].
 * @return array Estado de la transacción.
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
        return [
            'success' => false,
            'message' => 'El archivo debe estar guardado en formato Excel (.xlsx).'
        ];
    }

    // Instanciar el lector de Excel nativo
    $xlsx = SimpleXLSX::parse($tmpFilePath);

    if (!$xlsx) {
        return [
            'success' => false,
            'message' => 'Error al parsear el archivo Excel: ' . SimpleXLSX::parseError()
        ];
    }

    $rows = $xlsx->rows();

    if (count($rows) <= 1) {
        return ['success' => false, 'message' => 'El archivo Excel está vacío o no contiene filas.'];
    }

    try {
        $pdo->beginTransaction();

        // Limpiar registros antiguos de esta actividad para evitar duplicación
        $deleteStmt = $pdo->prepare("DELETE FROM actividad_balance_auditado WHERE actividad_id = :actividad_id");
        $deleteStmt->execute([':actividad_id' => $actividadId]);

        $sql = "INSERT INTO actividad_balance_auditado 
                    (actividad_id, link_agrup, link, codigo, descripcion, balance_cierre, debe, haber, balance_auditado)
                VALUES 
                    (:actividad_id, :link_agrup, :link, :codigo, :descripcion, :balance_cierre, :debe, :haber, :balance_auditado)";

        $stmt = $pdo->prepare($sql);
        $procesados = 0;

        foreach ($rows as $index => $row) {
            // Ignorar la fila 0 (Encabezados: Link Agrup, Link, CODIGO, DESCRIPCION, etc.)
            if ($index === 0 || empty($row)) {
                continue;
            }

            // Mapeo exacto de las columnas del archivo Excel:
            // Columna A (0): Link Agrup
            // Columna B (1): Link
            // Columna C (2): CODIGO
            // Columna D (3): DESCRIPCION
            // Columna E (4): Balance Cierre
            // Columna F (5): Debe
            // Columna G (6): Haber
            // Columna H (7): Balance Auditado

            $codigo      = isset($row[2]) ? trim((string)$row[2]) : '';
            $descripcion = isset($row[3]) ? trim((string)$row[3]) : '';

            // Omitir filas sin código o descripción (líneas vacías o de total al final)
            if ($codigo === '' || $descripcion === '') {
                continue;
            }

            $linkAgrup     = isset($row[0]) && trim((string)$row[0]) !== '' ? trim((string)$row[0]) : null;
            $link          = isset($row[1]) && trim((string)$row[1]) !== '' ? trim((string)$row[1]) : null;
            $balanceCierre = parseMontoContable($row[4] ?? 0);
            $debe          = parseMontoContable($row[5] ?? 0);
            $haber         = parseMontoContable($row[6] ?? 0);

            // Cálculo contable: Balance Auditado = Cierre + Debe - Haber
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
            'message' => "Se importaron exitosamente {$procesados} filas de auditoría desde Excel."
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Error al procesar Excel (Actividad {$actividadId}): " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error de base de datos al importar: ' . $e->getMessage()
        ];
    }
}

// --------------------------------------------------------------------------
// PROCESADOR DE FORMULARIO POST
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
    } else {
        echo "<div style='color: #b91c1c; padding: 15px; border: 1px solid #fca5a5; background: #fef2f2; font-family: sans-serif; margin: 20px; border-radius: 6px;'>";
        echo "Por favor, seleccione un archivo de Excel (.xlsx) antes de enviar el formulario.";
        echo "<br><br><a href='javascript:history.back()'>Volver al formulario</a>";
        echo "</div>";
        exit;
    }
}