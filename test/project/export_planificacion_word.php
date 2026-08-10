<?php

declare(strict_types=1);

// Habilitar reporte de errores solo para el log interno, oculto al usuario
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// 1. INCLUSIÓN DE LA CONEXIÓN A LA BASE DE DATOS
// Asegúrate de que este archivo instancie correctamente tu objeto PDO ($db)
include '../main/config.php';
$db = $pdo;

try {
    // 2. VALIDACIÓN Y SANITIZACIÓN DE LA ENTRADA
    // Obtenemos y validamos estrictamente que prueba_id sea un número entero válido
    $pruebaId = filter_input(INPUT_GET, 'prueba_id', FILTER_VALIDATE_INT);

    if (!$pruebaId || $pruebaId <= 0) {
        throw new Exception("Parámetro de prueba inválido o ausente.");
    }

    // 3. CONSULTA SEGURA A LA BASE DE DATOS (Prevención de Inyección SQL)
    // Utilizamos la tabla audit_actividades identificada previamente
    $sql = "SELECT orden, descripcion 
            FROM audit_actividades 
            WHERE prueba_id = :prueba_id 
            ORDER BY orden ASC";
            
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':prueba_id', $pruebaId, PDO::PARAM_INT);
    $stmt->execute();
    
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($actividades)) {
        // Podríamos redirigir o mostrar un mensaje si no hay datos
        die("No existen actividades de planificación para la prueba solicitada.");
    }

    // 4. CONFIGURACIÓN DE CABECERAS HTTP PARA EXPORTACIÓN A WORD
    $filename = "Planificacion_Auditoria_Prueba_" . $pruebaId . "_" . date('Ymd_His') . ".doc";

    header("Content-Type: application/vnd.ms-word; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    // 5. GENERACIÓN DEL CONTENIDO (HTML interpretado por Word)
    // Usamos sintaxis HEREDOC para mantener el HTML limpio
    $htmlContent = <<<HTML
    <html xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:w="urn:schemas-microsoft-com:office:word"
          xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Planificación de Auditoría</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 11pt;
            }
            h1 {
                text-align: center;
                font-size: 16pt;
                color: #333333;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #000000;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .orden-col {
                width: 10%;
                text-align: center;
            }
            .desc-col {
                width: 90%;
            }
        </style>
    </head>
    <body>
        <h1>Planificación Detallada de Auditoría</h1>
        <p><strong>ID de Prueba:</strong> {$pruebaId}</p>
        <p><strong>Fecha de Emisión:</strong> <!--FECHA_ACTUAL--></p>
        
        <table>
            <thead>
                <tr>
                    <th class="orden-col">Orden</th>
                    <th class="desc-col">Descripción de la Actividad</th>
                </tr>
            </thead>
            <tbody>
HTML;

    // Reemplazamos la fecha actual en el HTML
    $htmlContent = str_replace('<!--FECHA_ACTUAL-->', date('d/m/Y'), $htmlContent);

    // Iteramos sobre los resultados y sanitizamos la salida para evitar XSS si hay caracteres especiales
    foreach ($actividades as $actividad) {
        $orden = htmlspecialchars((string)$actividad['orden'], ENT_QUOTES, 'UTF-8');
        $descripcion = htmlspecialchars((string)$actividad['descripcion'], ENT_QUOTES, 'UTF-8');
        
        $htmlContent .= <<<HTML
                <tr>
                    <td class="orden-col">{$orden}</td>
                    <td class="desc-col">{$descripcion}</td>
                </tr>
HTML;
    }

    // Cerramos las etiquetas HTML
    $htmlContent .= <<<HTML
            </tbody>
        </table>
    </body>
    </html>
HTML;

    // 6. SALIDA AL NAVEGADOR
    // El echo imprimirá el HTML directo al buffer, pero las cabeceras forzarán la descarga como .doc
    echo $htmlContent;
    exit;

} catch (PDOException $e) {
    // Error de base de datos
    error_log("Error de DB en export_planificacion_word: " . $e->getMessage());
    die("Ocurrió un error interno al consultar los datos. Por favor, contacte al soporte.");
} catch (Exception $e) {
    // Error de validación de negocio
    error_log("Error de validación en export_planificacion_word: " . $e->getMessage());
    die("Error: " . $e->getMessage());
}