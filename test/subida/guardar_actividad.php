<?php

declare(strict_types=1);

// Carga de configuración y encabezados
require_once __DIR__ . '/../main/h.php'; 
require_once __DIR__ . '/../main/config.php'; 

/**
 * Función robusta para procesar e importar el CSV de Actividades.
 *
 * @param PDO $pdo Instancia de conexión a MySQL/MariaDB.
 * @param int $actividadId ID de la actividad vinculada.
 * @param array $fileArray $_FILES['archivo_csv']
 * @return array Estado de la ejecución.
 */
function procesarEImportarCSV(PDO $pdo, int $actividadId, array $fileArray): array
{
    // 1. Asegurar que PDO lance excepciones en errores SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Validaciones estrictas
    if ($actividadId <= 0) {
        return ['success' => false, 'message' => 'El ID de la actividad no es válido.'];
    }

    if (!isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir el archivo al servidor.'];
    }

    $tmpFilePath = $fileArray['tmp_name'];

    // 3. Lectura inicial y detección automática de delimitador (, o ;)
    $content = file_get_contents($tmpFilePath);
    if ($content === false) {
        return ['success' => false, 'message' => 'No se pudo leer el archivo cargado.'];
    }

    // Remover UTF-8 BOM si existe
    $content = preg_replace('/^[\x00-\x1F\x7F\xEF\xBB\xBF]+/', '', $content);
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", trim($content)));

    if (count($lines) <= 1) {
        return ['success' => false, 'message' => 'El archivo está vacío o solo contiene encabezados.'];
    }

    // Detectar si la primera línea usa coma ',' o punto y coma ';'
    $delimitador = (str_contains($lines[0], ';')) ? ';' : ',';

    try {
        // 4. Iniciar Transacción
        $pdo->beginTransaction();

        $sql = "INSERT INTO actividad_datos_csv (actividad_id, codigo, descripcion, monto, observaciones) 
                VALUES (:actividad_id, :codigo, :descripcion, :monto, :observaciones)";
        $stmt = $pdo->prepare($sql);

        $filasProcesadas = 0;

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Omitir encabezado (Fila 0: codigo,descripcion,monto,observaciones)
            if ($index === 0) {
                continue;
            }

            // Parsear la línea CSV usando el delimitador detectado
            $row = str_getcsv($line, $delimitador);

            $codigo        = isset($row[0]) && trim($row[0]) !== '' ? trim($row[0]) : null;
            $descripcion   = isset($row[1]) && trim($row[1]) !== '' ? trim($row[1]) : null;
            $rawMonto      = isset($row[2]) ? trim($row[2]) : '0';
            $observaciones = isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : null;

            // Formatear monto
            $monto = (float)str_replace(',', '.', $rawMonto);

            // Ejecutar inserción
            $stmt->execute([
                ':actividad_id' => $actividadId,
                ':codigo'       => $codigo,
                ':descripcion'  => $descripcion,
                ':monto'        => $monto,
                ':observaciones'=> $observaciones,
            ]);

            $filasProcesadas++;
        }

        // Confirmar guardado
        $pdo->commit();

        return [
            'success' => true,
            'message' => "¡Éxito! Se guardaron {$filasProcesadas} registros correctamente en la base de datos.",
            'filas'   => $filasProcesadas
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Registra el error exacto de MySQL en el log de PHP
        error_log("Error SQL al guardar CSV: " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Error de Base de Datos: ' . $e->getMessage()
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'success' => false,
            'message' => 'Error inesperado: ' . $e->getMessage()
        ];
    }
}

// --------------------------------------------------------------------------
// EJECUCIÓN DIRECTA DEL PROCESO DE CARGA
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actividadId = (int)($_POST['actividad_id'] ?? 1);

    if (isset($_FILES['archivo_csv'])) {
        $res = procesarEImportarCSV($pdo, $actividadId, $_FILES['archivo_csv']);

        if ($res['success']) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>{$res['message']}</div>";
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>{$res['message']}</div>";
        }
    }
}