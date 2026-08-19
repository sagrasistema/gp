<?php

declare(strict_types=1);

require_once __DIR__ . '/../main/h.php';
require_once __DIR__ . '/../main/config.php';

/**
 * Normaliza valores contables como "(125,170)", "-", "1,047,761" a float numérico.
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

    $valor = str_replace(',', '', $valor);
    $monto = (float)$valor;

    return $esNegativo ? -$monto : $monto;
}

/**
 * Inserta un registro individual de balance auditado en la base de datos.
 */
function guardarRegistroBalance(PDO $pdo, int $actividadId, array $data): bool
{
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $linkAgrup     = isset($data['link_agrup']) ? trim((string)$data['link_agrup']) : null;
    $link          = isset($data['link']) ? trim((string)$data['link']) : null;
    $codigo        = trim((string)($data['codigo'] ?? ''));
    $descripcion   = trim((string)($data['descripcion'] ?? ''));
    $balanceCierre = parseMontoContable((string)($data['balance_cierre'] ?? '0'));
    $debe          = parseMontoContable((string)($data['debe'] ?? '0'));
    $haber         = parseMontoContable((string)($data['haber'] ?? '0'));

    // Ecuación contable: Balance Auditado = Balance Cierre + Debe - Haber
    $balanceAuditado = $balanceCierre + $debe - $haber;

    if (empty($codigo) || empty($descripcion)) {
        return false;
    }

    $sql = "INSERT INTO actividad_balance_auditado 
                (actividad_id, link_agrup, link, codigo, descripcion, balance_cierre, debe, haber, balance_auditado)
            VALUES 
                (:actividad_id, :link_agrup, :link, :codigo, :descripcion, :balance_cierre, :debe, :haber, :balance_auditado)";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':actividad_id'     => $actividadId,
        ':link_agrup'      => $linkAgrup !== '' ? $linkAgrup : null,
        ':link'            => $link !== '' ? $link : null,
        ':codigo'          => $codigo,
        ':descripcion'     => $descripcion,
        ':balance_cierre'  => $balanceCierre,
        ':debe'            => $debe,
        ':haber'           => $haber,
        ':balance_auditado' => $balanceAuditado,
    ]);
}

/**
 * Procesa masivamente un archivo CSV con la hoja de trabajo.
 */
function importarSumarioCSV(PDO $pdo, int $actividadId, array $fileArray): array
{
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($actividadId <= 0 || !isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Archivo de entrada no válido.'];
    }

    $content = file_get_contents($fileArray['tmp_name']);
    if ($content === false) {
        return ['success' => false, 'message' => 'Error al leer el archivo.'];
    }

    // Remover caracteres BOM o invisibles de codificación UTF-8
    $content = preg_replace('/^[\x00-\x1F\x7F\xEF\xBB\xBF]+/', '', $content);
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", trim($content)));

    if (count($lines) <= 1) {
        return ['success' => false, 'message' => 'El archivo no contiene filas procesables.'];
    }

    $delimitador = str_contains($lines[0], ';') ? ';' : ',';

    try {
        $pdo->beginTransaction();
        $procesados = 0;

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line) || $index === 0) { // Salta los encabezados
                continue;
            }

            $row = str_getcsv($line, $delimitador);
            if (count($row) < 4) {
                continue;
            }

            $registro = [
                'link_agrup'     => $row[0] ?? null,
                'link'           => $row[1] ?? null,
                'codigo'         => $row[2] ?? null,
                'descripcion'    => $row[3] ?? null,
                'balance_cierre' => $row[4] ?? '0',
                'debe'           => $row[5] ?? '0',
                'haber'          => $row[6] ?? '0',
            ];

            if (!empty($registro['codigo']) && !empty($registro['descripcion'])) {
                guardarRegistroBalance($pdo, $actividadId, $registro);
                $procesados++;
            }
        }

        $pdo->commit();

        return [
            'success' => true,
            'message' => "Se registraron {$procesados} filas correctamente.",
            'filas'   => $procesados
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Error de importación CSV: " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Ocurrió un error al procesar la carga masiva.'
        ];
    }
}

/**
 * Consulta las cuentas pertenecientes a una actividad especificada.
 */
function obtenerBalanceAuditado(PDO $pdo, int $actividadId): array
{
    $sql = "SELECT link_agrup, link, codigo, descripcion, balance_cierre, debe, haber, balance_auditado 
            FROM actividad_balance_auditado 
            WHERE actividad_id = :actividad_id 
            ORDER BY id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':actividad_id' => $actividadId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}