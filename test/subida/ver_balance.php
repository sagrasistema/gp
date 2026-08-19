<?php

declare(strict_types=1);

require_once __DIR__ . '/../main/h.php';
require_once __DIR__ . '/../main/config.php';
require_once __DIR__ . '/guardar_actividad.php';

/** @var PDO $pdo */

$actividadId = filter_input(INPUT_GET, 'actividad_id', FILTER_VALIDATE_INT) ?? 1;
$cuentas = obtenerBalanceAuditado($pdo, $actividadId);

/**
 * Formatea valores numéricos a representación gráfica contable.
 */
function formatearContable(float $valor): string
{
    if (abs($valor) < 0.001) {
        return '-';
    }

    if ($valor < 0) {
        return '(' . number_format(abs($valor), 0, '.', ',') . ')';
    }

    return number_format($valor, 0, '.', ',');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sumario / Balance Auditado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 20px;
            color: #1e293b;
        }
        .card {
            background: #ffffff;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th {
            background-color: #cbd5e1;
            color: #0f172a;
            font-weight: bold;
            padding: 8px;
            border: 1px solid #94a3b8;
            text-align: center;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        tr:hover {
            background-color: #e2e8f0;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .codigo-cuenta { font-weight: bold; color: #1e293b; }
        .monto-negativo { color: #b91c1c; }
        .empty-row {
            text-align: center;
            padding: 15px;
            color: #64748b;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="card">
    <h3 style="margin-top: 0;">Sumario de Balance Auditado (Actividad ID: <?= htmlspecialchars((string)$actividadId) ?>)</h3>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Link Agrup</th>
                    <th>Link</th>
                    <th>CÓDIGO</th>
                    <th>DESCRIPCIÓN</th>
                    <th>Balance Cierre</th>
                    <th>Debe</th>
                    <th>Haber</th>
                    <th>Balance Auditado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cuentas)): ?>
                    <tr>
                        <td colspan="8" class="empty-row">No hay registros cargados para esta actividad.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cuentas as $item): 
                        $bCierre = (float)$item['balance_cierre'];
                        $debe    = (float)$item['debe'];
                        $haber   = (float)$item['haber'];
                        $bAudit  = (float)$item['balance_auditado'];
                    ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars((string)($item['link_agrup'] ?? '')) ?></td>
                            <td class="text-center"><?= htmlspecialchars((string)($item['link'] ?? '')) ?></td>
                            <td class="text-left codigo-cuenta"><?= htmlspecialchars($item['codigo']) ?></td>
                            <td class="text-left"><?= htmlspecialchars($item['descripcion']) ?></td>
                            <td class="text-right <?= $bCierre < 0 ? 'monto-negativo' : '' ?>">
                                <?= formatearContable($bCierre) ?>
                            </td>
                            <td class="text-right">
                                <?= formatearContable($debe) ?>
                            </td>
                            <td class="text-right">
                                <?= formatearContable($haber) ?>
                            </td>
                            <td class="text-right <?= $bAudit < 0 ? 'monto-negativo' : '' ?>" style="font-weight: bold;">
                                <?= formatearContable($bAudit) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>