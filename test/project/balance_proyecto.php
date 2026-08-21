<?php

declare(strict_types=1);

require_once __DIR__ . '/../main/h.php';
require_once __DIR__ . '/../main/config.php';
require_once __DIR__ . '/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

/** @var PDO $pdo */

$proyectoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) 
    ?? filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$proyectoId || $proyectoId <= 0) {
    die('Error: ID de proyecto no válido.');
}

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

function formatearMonto(float $valor): string
{
    if (abs($valor) < 0.001) {
        return '-';
    }
    if ($valor < 0) {
        return '(' . number_format(abs($valor), 2, '.', ',') . ')';
    }
    return number_format($valor, 2, '.', ',');
}

// --------------------------------------------------------------------------
// PROCESAMIENTO DEL FORMULARIO POST (CARGA EXCEL DE 12 MESES)
// --------------------------------------------------------------------------
$mensaje = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cargar_balance'])) {
    if (isset($_FILES['archivo_excel']) && $_FILES['archivo_excel']['error'] === UPLOAD_ERR_OK) {
        $tmpFilePath = $_FILES['archivo_excel']['tmp_name'];
        $extension   = strtolower(pathinfo($_FILES['archivo_excel']['name'], PATHINFO_EXTENSION));

        if ($extension !== 'xlsx') {
            $error = 'El archivo debe estar guardado en formato Excel (.xlsx).';
        } else {
            $xlsx = SimpleXLSX::parse($tmpFilePath);
            if (!$xlsx) {
                $error = 'Error al leer el archivo Excel: ' . SimpleXLSX::parseError();
            } else {
                $rows = $xlsx->rows();
                if (count($rows) <= 1) {
                    $error = 'El archivo no contiene filas de datos.';
                } else {
                    try {
                        $pdo->beginTransaction();

                        $deleteStmt = $pdo->prepare("DELETE FROM actividad_balance_auditado WHERE proyecto_id = :proyecto_id");
                        $deleteStmt->execute([':proyecto_id' => $proyectoId]);

                        // Construcción dinámica de columnas de consulta INSERT
                        $columnasMeses = [];
                        $paramsMeses = [];
                        for ($m = 1; $m <= 12; $m++) {
                            $columnasMeses[] = "debe_m{$m}, haber_m{$m}, ajuste_m{$m}";
                            $paramsMeses[]   = ":debe_m{$m}, :haber_m{$m}, :ajuste_m{$m}";
                        }

                        $strColsMeses   = implode(', ', $columnasMeses);
                        $strParamsMeses = implode(', ', $paramsMeses);

                        $sql = "INSERT INTO actividad_balance_auditado 
                                    (proyecto_id, actividad_id, link_eeff_1, rubro_eeff_1, link_eeff_2, rubro_eeff_notas, 
                                     link_centro_costo, tipo_partida, codigo, nombre, codigo_nombre, 
                                     {$strColsMeses}, balance_cierre, total_debe, total_haber, total_ajuste, balance_auditado)
                                VALUES 
                                    (:proyecto_id, :actividad_id, :link_eeff_1, :rubro_eeff_1, :link_eeff_2, :rubro_eeff_notas, 
                                     :link_centro_costo, :tipo_partida, :codigo, :nombre, :codigo_nombre, 
                                     {$strParamsMeses}, :balance_cierre, :total_debe, :total_haber, :total_ajuste, :balance_auditado)";

                        $stmt = $pdo->prepare($sql);
                        $procesados = 0;

                        foreach ($rows as $index => $row) {
                            if ($index === 0 || empty($row)) {
                                continue;
                            }

                            $codigo = isset($row[6]) ? trim((string)$row[6]) : '';
                            $nombre = isset($row[7]) ? trim((string)$row[7]) : '';

                            if ($codigo === '' && $nombre === '') {
                                continue;
                            }

                            $params = [
                                ':proyecto_id'       => $proyectoId,
                                ':actividad_id'     => 0,
                                ':link_eeff_1'       => isset($row[0]) && trim((string)$row[0]) !== '' ? trim((string)$row[0]) : null,
                                ':rubro_eeff_1'      => isset($row[1]) && trim((string)$row[1]) !== '' ? trim((string)$row[1]) : null,
                                ':link_eeff_2'       => isset($row[2]) && trim((string)$row[2]) !== '' ? trim((string)$row[2]) : null,
                                ':rubro_eeff_notas'  => isset($row[3]) && trim((string)$row[3]) !== '' ? trim((string)$row[3]) : null,
                                ':link_centro_costo' => isset($row[4]) && trim((string)$row[4]) !== '' ? trim((string)$row[4]) : null,
                                ':tipo_partida'      => isset($row[5]) && trim((string)$row[5]) !== '' ? trim((string)$row[5]) : null,
                                ':codigo'            => $codigo,
                                ':nombre'            => $nombre,
                                ':codigo_nombre'     => isset($row[8]) && trim((string)$row[8]) !== '' ? trim((string)$row[8]) : null,
                            ];

                            // Lectura cíclica de los 12 meses desde la columna J (índice 9)
                            $colOffset = 9;
                            $sumDebeAnual = 0.0;
                            $sumHaberAnual = 0.0;
                            $sumAjusteAnual = 0.0;

                            for ($m = 1; $m <= 12; $m++) {
                                $d = parseMontoContable($row[$colOffset] ?? 0);
                                $h = parseMontoContable($row[$colOffset + 1] ?? 0);
                                $a = parseMontoContable($row[$colOffset + 2] ?? 0);

                                $params[":debe_m{$m}"]   = $d;
                                $params[":haber_m{$m}"]  = $h;
                                $params[":ajuste_m{$m}"] = $a;

                                $sumDebeAnual   += $d;
                                $sumHaberAnual  += $h;
                                $sumAjusteAnual += $a;

                                $colOffset += 3; // Salta al siguiente mes
                            }

                            $balanceCierre   = parseMontoContable($row[$colOffset] ?? 0);
                            $balanceAuditado = $balanceCierre + $sumDebeAnual - $sumHaberAnual + $sumAjusteAnual;

                            $params[':balance_cierre']   = $balanceCierre;
                            $params[':total_debe']       = $sumDebeAnual;
                            $params[':total_haber']      = $sumHaberAnual;
                            $params[':total_ajuste']     = $sumAjusteAnual;
                            $params[':balance_auditado'] = $balanceAuditado;

                            $stmt->execute($params);
                            $procesados++;
                        }

                        $pdo->commit();
                        $mensaje = "Se cargó exitosamente el balance de 12 meses ({$procesados} registros).";
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $error = 'Error de BD: ' . $e->getMessage();
                    }
                }
            }
        }
    } else {
        $error = 'Seleccione un archivo .xlsx válido.';
    }
}

// --------------------------------------------------------------------------
// CONSULTA Y TOTALIZACIÓN ANUAL
// --------------------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM actividad_balance_auditado WHERE proyecto_id = :proyecto_id ORDER BY id ASC");
$stmt->execute([':proyecto_id' => $proyectoId]);
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inicializar acumuladores dinámicos
$totalesMeses = [];
for ($m = 1; $m <= 12; $m++) {
    $totalesMeses[$m] = ['debe' => 0.0, 'haber' => 0.0, 'ajuste' => 0.0];
}

$totalesConsolidados = [
    'cierre'   => 0.0,
    'debe'     => 0.0,
    'haber'    => 0.0,
    'ajuste'   => 0.0,
    'auditado' => 0.0,
];

foreach ($cuentas as $c) {
    for ($m = 1; $m <= 12; $m++) {
        $totalesMeses[$m]['debe']   += (float)$c["debe_m{$m}"];
        $totalesMeses[$m]['haber']  += (float)$c["haber_m{$m}"];
        $totalesMeses[$m]['ajuste'] += (float)$c["ajuste_m{$m}"];
    }
    $totalesConsolidados['cierre']   += (float)$c['balance_cierre'];
    $totalesConsolidados['debe']     += (float)$c['total_debe'];
    $totalesConsolidados['haber']    += (float)$c['total_haber'];
    $totalesConsolidados['ajuste']   += (float)$c['total_ajuste'];
    $totalesConsolidados['auditado'] += (float)$c['balance_auditado'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Balance 12 Meses - Proyecto #<?= htmlspecialchars((string)$proyectoId) ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 20px; color: #333; }
        .card { background: #fff; border-radius: 6px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-danger { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        
        .accordion-btn { background-color: #2e7d32; color: white; cursor: pointer; padding: 14px; width: 100%; text-align: left; border: none; outline: none; transition: 0.3s; font-size: 15px; font-weight: bold; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
        .accordion-btn:hover { background-color: #1b5e20; }
        .accordion-content { padding: 0; display: none; background-color: white; overflow: hidden; border: 1px solid #ddd; border-top: none; margin-top: -2px; border-radius: 0 0 4px 4px; }
        
        .table-responsive { overflow-x: auto; max-width: 100%; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; white-space: nowrap; }
        th { background: #2e7d32; color: #fff; padding: 6px; border: 1px solid #1b5e20; text-align: center; }
        th.mes-header { background: #1b5e20; border-bottom: 2px solid #0a3d0e; }
        td { padding: 5px; border: 1px solid #e0e0e0; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .tr-total { background-color: #e8f5e9 !important; font-weight: bold; border-top: 2px solid #2e7d32; }
    </style>
</head>
<body>

<div class="card">
    <h2>Balance Anual (12 Meses) - Proyecto #<?= htmlspecialchars((string)$proyectoId) ?></h2>
    <a href="index.php">← Volver a Proyectos</a>
    <hr>

    <?php if ($mensaje): ?><div class="alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="proyecto_id" value="<?= htmlspecialchars((string)$proyectoId) ?>">
        <label for="archivo_excel"><strong>Cargar Archivo Excel de 12 Meses (.xlsx):</strong></label><br><br>
        <input type="file" name="archivo_excel" id="archivo_excel" accept=".xlsx" required>
        <button type="submit" name="cargar_balance" style="padding: 6px 15px; background: #0284c7; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Procesar Balance Anual
        </button>
    </form>
</div>

<!-- Acordeón Desplegable -->
<button class="accordion-btn" onclick="toggleAccordion()">
    <span><i class="ri-table-line"></i> Desplegar Tabla de Balance Anual (<?= count($cuentas) ?> Cuentas)</span>
    <span id="acc-icon">▼</span>
</button>

<div class="accordion-content" id="accordion-panel">
    <div class="table-responsive">
        <table>
            <thead>
                <!-- Fila Superior de Encabezados Agrupados -->
                <tr>
                    <th rowspan="2">Link EEFF</th>
                    <th rowspan="2">Rubro EEFF</th>
                    <th rowspan="2">Link 2</th>
                    <th rowspan="2">Rubro y Notas</th>
                    <th rowspan="2">Centro Costo</th>
                    <th rowspan="2">Tipo</th>
                    <th rowspan="2">Código</th>
                    <th rowspan="2">Nombre</th>
                    <th rowspan="2">Código y Nombre</th>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <th colspan="3" class="mes-header">MES <?= $m ?></th>
                    <?php endfor; ?>
                    <th colspan="5" class="mes-header" style="background:#0f172a;">CONSOLIDADO ANUAL</th>
                </tr>
                <!-- Fila Inferior con Sub-columnas -->
                <tr>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <th>Debe</th>
                        <th>Haber</th>
                        <th>Ajuste</th>
                    <?php endfor; ?>
                    <th style="background:#1e293b;">B. Cierre</th>
                    <th style="background:#1e293b;">Tot. Debe</th>
                    <th style="background:#1e293b;">Tot. Haber</th>
                    <th style="background:#1e293b;">Tot. Ajuste</th>
                    <th style="background:#1e293b;">B. Auditado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cuentas)): ?>
                    <tr><td colspan="49" class="text-center" style="padding:20px;">No se registran datos para este proyecto.</td></tr>
                <?php else: ?>
                    <?php foreach ($cuentas as $row): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars((string)$row['link_eeff_1']) ?></td>
                            <td><?= htmlspecialchars((string)$row['rubro_eeff_1']) ?></td>
                            <td class="text-center"><?= htmlspecialchars((string)$row['link_eeff_2']) ?></td>
                            <td><?= htmlspecialchars((string)$row['rubro_eeff_notas']) ?></td>
                            <td class="text-center"><?= htmlspecialchars((string)$row['link_centro_costo']) ?></td>
                            <td class="text-center"><?= htmlspecialchars((string)$row['tipo_partida']) ?></td>
                            <td><strong><?= htmlspecialchars($row['codigo']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td><?= htmlspecialchars((string)$row['codigo_nombre']) ?></td>

                            <!-- 12 Meses de Transacciones -->
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <td class="text-right"><?= formatearMonto((float)$row["debe_m{$m}"]) ?></td>
                                <td class="text-right"><?= formatearMonto((float)$row["haber_m{$m}"]) ?></td>
                                <td class="text-right"><?= formatearMonto((float)$row["ajuste_m{$m}"]) ?></td>
                            <?php endfor; ?>

                            <!-- Totales de la Cuenta -->
                            <td class="text-right"><?= formatearMonto((float)$row['balance_cierre']) ?></td>
                            <td class="text-right"><?= formatearMonto((float)$row['total_debe']) ?></td>
                            <td class="text-right"><?= formatearMonto((float)$row['total_haber']) ?></td>
                            <td class="text-right"><?= formatearMonto((float)$row['total_ajuste']) ?></td>
                            <td class="text-right"><strong><?= formatearMonto((float)$row['balance_auditado']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Fila Final de Totales Acumulados -->
                    <tr class="tr-total">
                        <td colspan="9" class="text-right">TOTALES GENERALES:</td>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <td class="text-right"><?= formatearMonto($totalesMeses[$m]['debe']) ?></td>
                            <td class="text-right"><?= formatearMonto($totalesMeses[$m]['haber']) ?></td>
                            <td class="text-right"><?= formatearMonto($totalesMeses[$m]['ajuste']) ?></td>
                        <?php endfor; ?>
                        <td class="text-right"><?= formatearMonto($totalesConsolidados['cierre']) ?></td>
                        <td class="text-right"><?= formatearMonto($totalesConsolidados['debe']) ?></td>
                        <td class="text-right"><?= formatearMonto($totalesConsolidados['haber']) ?></td>
                        <td class="text-right"><?= formatearMonto($totalesConsolidados['ajuste']) ?></td>
                        <td class="text-right"><?= formatearMonto($totalesConsolidados['auditado']) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleAccordion() {
    const panel = document.getElementById('accordion-panel');
    const icon = document.getElementById('acc-icon');
    if (panel.style.display === "block") {
        panel.style.display = "none";
        icon.innerText = "▼";
    } else {
        panel.style.display = "block";
        icon.innerText = "▲";
    }
}
</script>

</body>
</html>