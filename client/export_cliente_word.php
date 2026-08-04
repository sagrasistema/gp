<?php

declare(strict_types=1);

// 1. Validar entrada $_GET
$clienteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$clienteId) {
    http_response_code(400);
    die('Error: El ID del cliente es obligatorio y debe ser un valor entero válido.');
}

// 2. Conexión a Base de Datos (PDO)
$dbHost  = '127.0.0.1';
$dbName  = 'sagracom_alberto_1';
$dbUser  = 'tu_usuario';
$dbPass  = 'tu_contraseña';
$charset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    // Consulta con todos los campos de la tabla `clientes`
    $sql = "SELECT 
                id, name, rif, email, phone, address, city, state_geo, zip_code,
                website, instagram, linkedin, country, employees, income_level,
                sector, service, service_desc, sector_desc, status
            FROM clientes 
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $clienteId]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        http_response_code(404);
        die("Error: No se encontró ningún cliente registrado con el ID {$clienteId}.");
    }

} catch (PDOException $e) {
    error_log("Error de BD en export_cliente_word: " . $e->getMessage());
    http_response_code(500);
    die("Error interno de base de datos al consultar la ficha del cliente.");
}

// 3. Formatear nombre del archivo para descarga
$nombreLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', $cliente['name']);
$fileName = "Ficha_Cliente_{$cliente['id']}_{$nombreLimpio}.doc";

// 4. Cabeceras HTTP para Microsoft Word
header("Content-Type: application/vnd.ms-word; charset=utf-8");
header("Content-Disposition: attachment; filename=\"{$fileName}\"");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

// 5. Renderizado HTML / CSS compatible con Microsoft Word
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:w="urn:schemas-microsoft-com:office:word" 
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="utf-8">
    <title>Ficha del Cliente - <?= htmlspecialchars($cliente['name']) ?></title>
    <style>
        @page {
            size: 21cm 29.7cm; /* Tamaño A4 */
            margin: 1.8cm 2cm 1.8cm 2cm;
        }
        body {
            font-family: 'Calibri', 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            color: #2D3748;
            line-height: 1.4;
        }
        /* Header Principal */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 3px solid #1B365D;
            padding-bottom: 10px;
        }
        .title {
            font-size: 20pt;
            font-weight: bold;
            color: #1B365D;
            margin: 0;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 10pt;
            color: #718096;
            margin-top: 3px;
        }
        .status-badge {
            background-color: #E6FFFA;
            color: #234E52;
            border: 1px solid #B2F5EA;
            padding: 4px 12px;
            font-weight: bold;
            font-size: 10pt;
            text-align: center;
        }

        /* Secciones del Documento */
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1B365D;
            background-color: #EDF2F7;
            padding: 6px 10px;
            margin-top: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #1B365D;
        }

        /* Tablas de Datos */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table td {
            padding: 6px 8px;
            font-size: 10pt;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #4A5568;
            width: 25%;
            background-color: #F7FAFC;
        }
        .value {
            color: #1A202C;
            width: 75%;
        }

        /* Cajas de Descripción */
        .desc-box {
            background-color: #F8FAFC;
            border: 1px solid #CBD5E1;
            padding: 10px;
            font-size: 9.5pt;
            color: #334155;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <!-- Encabezado Ficha -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="title"><?= htmlspecialchars($cliente['name']) ?></div>
                <div class="subtitle">Ficha Técnica de Cliente | ID Registro: #<?= htmlspecialchars((string)$cliente['id']) ?></div>
            </td>
            <td style="width: 30%; text-align: right;">
                <span class="status-badge">ESTATUS: <?= htmlspecialchars(strtoupper((string)($cliente['status'] ?? 'ACTIVO'))) ?></span>
            </td>
        </tr>
    </table>

    <!-- 1. Identificación y Datos Legales -->
    <div class="section-title">1. Identificación y Datos Legales</div>
    <table class="data-table">
        <tr>
            <td class="label">Razon Social / Nombre:</td>
            <td class="value"><strong><?= htmlspecialchars($cliente['name']) ?></strong></td>
        </tr>
        <tr>
            <td class="label">RIF / Identificación:</td>
            <td class="value"><?= htmlspecialchars($cliente['rif'] ?: 'No aplica') ?></td>
        </tr>
        <tr>
            <td class="label">País:</td>
            <td class="value"><?= htmlspecialchars($cliente['country'] ?: 'Venezuela') ?></td>
        </tr>
    </table>

    <!-- 2. Información de Contacto y Ubicación -->
    <div class="section-title">2. Contacto y Ubicación Física</div>
    <table class="data-table">
        <tr>
            <td class="label">Correo Electrónico:</td>
            <td class="value"><?= htmlspecialchars($cliente['email'] ?: 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Teléfono de Contacto:</td>
            <td class="value"><?= htmlspecialchars($cliente['phone'] ?: 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Dirección Fiscal:</td>
            <td class="value"><?= htmlspecialchars(trim((string)$cliente['address']) ?: 'No registrada') ?></td>
        </tr>
        <tr>
            <td class="label">Ciudad / Estado / C.P.:</td>
            <td class="value">
                <?= htmlspecialchars($cliente['city'] ?: 'N/A') ?> 
                <?= $cliente['state_geo'] ? ' - ' . htmlspecialchars($cliente['state_geo']) : '' ?>
                <?= $cliente['zip_code'] ? ' (ZIP: ' . htmlspecialchars($cliente['zip_code']) . ')' : '' ?>
            </td>
        </tr>
    </table>

    <!-- 3. Presencia Digital -->
    <div class="section-title">3. Presencia Digital y Redes</div>
    <table class="data-table">
        <tr>
            <td class="label">Sitio Web Oficial:</td>
            <td class="value"><?= htmlspecialchars($cliente['website'] ?: 'No registrado') ?></td>
        </tr>
        <tr>
            <td class="label">Instagram:</td>
            <td class="value"><?= htmlspecialchars($cliente['instagram'] ?: 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">LinkedIn:</td>
            <td class="value"><?= htmlspecialchars($cliente['linkedin'] ?: 'N/A') ?></td>
        </tr>
    </table>

    <!-- 4. Perfil Comercial y Operativo -->
    <div class="section-title">4. Perfil Comercial y Operativo</div>
    <table class="data-table">
        <tr>
            <td class="label">Sector Económico:</td>
            <td class="value"><?= htmlspecialchars($cliente['sector'] ?: 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Servicio Contratado:</td>
            <td class="value"><strong><?= htmlspecialchars($cliente['service'] ?: 'N/A') ?></strong></td>
        </tr>
        <tr>
            <td class="label">Nivel de Empleados:</td>
            <td class="value"><?= htmlspecialchars($cliente['employees'] ?: 'N/A') ?></td>
        </tr>
        <tr>
            <td class="label">Nivel de Ingresos:</td>
            <td class="value"><?= htmlspecialchars($cliente['income_level'] ?: 'N/A') ?></td>
        </tr>
    </table>

    <!-- 5. Detalles Ampliados -->
    <?php if (!empty($cliente['service_desc'])): ?>
        <div class="section-title">5. Descripción del Servicio</div>
        <div class="desc-box">
            <?= nl2br(htmlspecialchars($cliente['service_desc'])) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($cliente['sector_desc'])): ?>
        <div class="section-title">6. Descripción del Sector / Operación</div>
        <div class="desc-box">
            <?= nl2br(htmlspecialchars($cliente['sector_desc'])) ?>
        </div>
    <?php endif; ?>

</body>
</html>