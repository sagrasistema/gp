<?php

declare(strict_types=1);

/**
 * Procesador e Importador de Archivos CSV para Actividades de Auditoría.
 *
 * Soporta auto-detección de delimitadores (;, ,, \t) y formateo de montos.
 */

// Inclusiones de dependencias y configuración
require_once __DIR__ . '/../main/h.php'; 
require_once __DIR__ . '/../main/config.php'; 

/**
 * Detecta automáticamente el delimitador de un archivo CSV.
 */
function detectarDelimitadorCSV(string $filePath): string
{
    $delimitadores = [';' => 0, ',' => 0, "\t" => 0];
    $handle = @fopen($filePath, 'r');
    
    if ($handle) {
        $primeraLined = fgets($handle, 4096) ?: '';
        fclose($handle);
        
        foreach ($delimitadores as $delimitador => &$count) {
            $count = count(str_getcsv($primeraLined, $delimitador));
        }
        
        arsort($delimitadores);
        return array_key_first($delimitadores) ?? ';';
    }

    return ';';
}

/**
 * Procesa e importa el archivo CSV a la base de datos dentro de una transacción.
 */
function procesarEImportarCSV(PDO $pdo, int $actividadId, array $fileArray): array
{
    // 1. Validaciones de entrada
    if ($actividadId <= 0) {
        return ['success' => false, 'message' => 'El ID de la actividad no es válido.'];
    }

    if (!isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir el archivo o ningún archivo seleccionado.'];
    }

    $extension = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        return ['success' => false, 'message' => 'El archivo debe tener extensión .csv'];
    }

    $tmpFilePath = $fileArray['tmp_name'];

    try {
        // Detectar delimitador automáticamente (;, , o tab)
        $delimitador = detectarDelimitadorCSV($tmpFilePath);

        // 2. Configurar lectura streaming mediante SplFileObject
        $file = new SplFileObject($tmpFilePath, 'r');
        $file->setFlags(
            SplFileObject::READ_CSV | 
            SplFileObject::READ_AHEAD | 
            SplFileObject::SKIP_EMPTY | 
            SplFileObject::DROP_NEW_LINE
        );
        $file->setCsvControl($delimitador);

        // 3. Iniciar Transacción Atómica
        $pdo->beginTransaction();

        $sql = "INSERT INTO actividad_datos_csv (actividad_id, codigo, descripcion, monto, observaciones) 
                VALUES (:actividad_id, :codigo, :descripcion, :monto, :observaciones)";
        $stmt = $pdo->prepare($sql);

        $filasProcesadas = 0;
        $esEncabezado = true;

        foreach ($file as $row) {
            // Ignorar filas totalmente vacías
            if (empty($row) || $row === [null] || !is_array($row)) {
                continue;
            }

            // Omitir la fila 1 (encabezados: codigo, descripcion, monto, observaciones)
            if ($esEncabezado) {
                $esEncabezado = false;
                continue;
            }

            // Mapeo según la estructura del Excel (A:0, B:1, C:2, D:3)
            $codigo        = isset($row[0]) && trim((string)$row[0]) !== '' ? trim((string)$row[0]) : null;
            $descripcion   = isset($row[1]) && trim((string)$row[1]) !== '' ? trim((string)$row[1]) : null;
            $rawMonto      = isset($row[2]) ? trim((string)$row[2]) : '0';
            $observaciones = isset($row[3]) && trim((string)$row[3]) !== '' ? trim((string)$row[3]) : null;

            // Omite la fila si está completamente limpia sin datos útiles
            if ($codigo === null && $descripcion === null && $rawMonto === '0') {
                continue;
            }

            // Normalizar monto: convierte "65846948" o "1.250,50" a float nativo
            if (str_contains($rawMonto, ',')) {
                $rawMonto = str_replace('.', '', $rawMonto);
                $rawMonto = str_replace(',', '.', $rawMonto);
            }
            $montoLimpio = (float)$rawMonto;

            // Inserción parametrizada
            $stmt->execute([
                ':actividad_id' => $actividadId,
                ':codigo'       => $codigo,
                ':descripcion'  => $descripcion,
                ':monto'        => $montoLimpio,
                ':observaciones'=> $observaciones,
            ]);

            $filasProcesadas++;
        }

        // Commit si todas las inserciones fueron exitosas
        $pdo->commit();

        return [
            'success' => true,
            'message' => "Se importaron exitosamente {$filasProcesadas} registros a la actividad.",
            'filas'   => $filasProcesadas
        ];

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Error crítico en importación CSV (Actividad {$actividadId}): " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error al procesar el archivo CSV. Verifique el formato e intente nuevamente.'
        ];
    }
}

// --------------------------------------------------------------------------
// DISPARADOR / PROCESAMIENTO DEL FORMULARIO
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar ID de la actividad (desde POST o variable fallback)
    $actividadId = filter_input(INPUT_POST, 'actividad_id', FILTER_VALIDATE_INT) ?? 1;

    if (isset($_FILES['archivo_csv'])) {
        // Ejecutar función de importación
        $respuesta = procesarEImportarCSV($pdo, $actividadId, $_FILES['archivo_csv']);

        // Retornar JSON si es llamada AJAX o guardar en sesión
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($respuesta);
            exit;
        }

        if ($respuesta['success']) {
            $_SESSION['flash_message'] = $respuesta['message'];
        } else {
            $_SESSION['flash_error'] = $respuesta['message'];
        }
    }
}