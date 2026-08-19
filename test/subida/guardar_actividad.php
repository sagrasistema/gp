
<?php

declare(strict_types=1);

/**
 * Procesador e Importador de Archivos CSV para Actividades de Auditoría.
 *
 * @param PDO $pdo Instancia de conexión a la base de datos.
 * @param int $actividadId ID de la actividad a la que se vincularán los datos.
 * @param array $fileArray Matriz correspondiente a $_FILES['archivo_csv'].
 * @param string $delimitador Delimitador del CSV (por defecto ';' o ',').
 * @return array Estado del procesamiento con mensaje y cantidad de filas procesadas.
 */
function procesarEImportarCSV(PDO $pdo, int $actividadId, array $fileArray, string $delimitador = ';'): array
{
    // 1. Validaciones previas de carga
    if ($actividadId <= 0) {
        return ['success' => false, 'message' => 'El ID de la actividad no es válido.'];
    }

    if (!isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error o ningún archivo subido al servidor.'];
    }

    $extension = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if ($extension !== 'csv') {
        return ['success' => false, 'message' => 'El archivo debe estar en formato .csv (delimitado por comas o punto y coma).'];
    }

    $tmpFilePath = $fileArray['tmp_name'];

    try {
        // 2. Abrir archivo mediante SplFileObject para lectura eficiente de memoria
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

        // Sentencia preparada única (Alta optimización)
        $sql = "INSERT INTO actividad_datos_csv (actividad_id, codigo, descripcion, monto, observaciones) 
                VALUES (:actividad_id, :codigo, :descripcion, :monto, :observaciones)";
        $stmt = $pdo->prepare($sql);

        $filasProcesadas = 0;
        $esEncabezado = true;

        foreach ($file as $index => $row) {
            // Ignorar filas vacías o malformadas
            if (empty($row) || $row === [null] || count($row) < 1) {
                continue;
            }

            // Omitir la primera línea si contiene los títulos/encabezados del Excel
            if ($esEncabezado) {
                $esEncabezado = false;
                continue;
            }

            // Mapeo seguro de columnas del CSV (Ajustar índices según la estructura del Excel)
            $codigo      = isset($row[0]) ? trim((string)$row[0]) : null;
            $descripcion = isset($row[1]) ? trim((string)$row[1]) : null;
            
            // Limpieza de formato de moneda (ej: "1.250,50" -> "1250.50")
            $rawMonto    = isset($row[2]) ? trim((string)$row[2]) : '0';
            $montoLimpio = (float)str_replace(['.', ','], ['', '.'], $rawMonto);

            $observaciones = isset($row[3]) ? trim((string)$row[3]) : null;

            // Ejecución de la consulta preparada
            $stmt->execute([
                ':actividad_id' => $actividadId,
                ':codigo'       => $codigo,
                ':descripcion'  => $descripcion,
                ':monto'        => $montoLimpio,
                ':observaciones'=> $observaciones,
            ]);

            $filasProcesadas++;
        }

        // Confirmar los cambios si todo fue correcto
        $pdo->commit();

        return [
            'success' => true,
            'message' => "Se procesó e importó con éxito el archivo CSV ({$filasProcesadas} filas agregadas).",
            'filas'   => $filasProcesadas
        ];

    } catch (Throwable $e) {
        // En caso de cualquier error, descalcula y revierte la base de datos
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log("Error crítico al importar CSV en actividad {$actividadId}: " . $e->getMessage());

        return [
            'success' => false,
            'message' => 'Ocurrió un error al procesar la estructura del archivo. Asegúrese de usar el formato correcto.'
        ];
    }
}

// --------------------------------------------------------------------------
// EJEMPLO DE INTEGRACIÓN EN TU CONTROLADOR / ENDPOINT DE GUARDADO
// --------------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_actividad'])) {
    
    // Validar y sanitizar inputs
    $actividadId = filter_input(INPUT_POST, 'actividad_id', FILTER_VALIDATE_INT);

    if ($actividadId && isset($_FILES['archivo_csv']) && $_FILES['archivo_csv']['error'] === UPLOAD_ERR_OK) {
        
        // Ejecutar importación del CSV
        $resultadoImportacion = procesarEImportarCSV($pdo, $actividadId, $_FILES['archivo_csv'], ';');

        if ($resultadoImportacion['success']) {
            $_SESSION['flash_message'] = $resultadoImportacion['message'];
        } else {
            $_SESSION['flash_error'] = $resultadoImportacion['message'];
        }
    }
}?>