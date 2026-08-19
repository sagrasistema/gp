<?php

declare(strict_types=1);

require_once __DIR__ . '/../main/h.php';
require_once __DIR__ . '/../main/config.php';
require_once __DIR__ . '/balance_service.php';

/** @var PDO $pdo */

$actividadId = filter_input(INPUT_GET, 'actividad_id', FILTER_VALIDATE_INT) ?? 123;
$mensajeExito = isset($_GET['msg']) && $_GET['msg'] === 'ok';

$cuentas = obtenerBalanceAuditado($pdo, $actividadId);

/**
 * Formatea montos estilo libro contable.
 */
function formatearMonto(float $valor): string
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
        body { font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; margin: 15px; color: #1e293b; }
        .container { background: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .alert-success { background-color: #dcfce7; color: #15803d; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #bbf7d0; font-size: 0.875rem; }
        .table-responsive { overflow-x: auto; max-width: 100%; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; white-space: nowrap; }
        th { background-color: #22c55e; color: #ffffff; padding: 8px 6px; border: 1px solid #16a34a; text-align: center; font-weight: 600; }
        td { padding: 6px; border: 1px solid #e2e8f0; }
        tr:nth-child(even) { background-color: #f1f5f9; }
        tr:hover { background-color: #e2e8f0; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 600; }
        .monto-negativo { color: #dc2626; }
    </style>
</head>
<body>

<div class="container">
    <h3 style="margin-top: 0;">Sumario / Balance Auditado (Actividad ID: <?= htmlspecialchars((string)$actividadId) ?>)</h3>

    <?php if ($mensajeExito): ?>
        <div class="alert-success">
            ✓ ¡Los datos de la hoja de Excel han sido importados y guardados correctamente en la base de datos!
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Link EEFF</th>
                    <th>Rubro EEFF</th>
                    <th>Link EEFF</th>
                    <th>Rubro EEFF y Notas</th>
                    <th>Link Centro Costo</th>
                    <th>Tipo Partida</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Código y Nombre</th>
                    <th>Balance Cierre</th>
                    <th>Debe</th>
                    <th>Haber</th>
                    <th>Balance Auditado</th>
                    <th>Balande Final Ajustado</th>
                    <th>Diferencia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cuentas)): ?>
                    <tr>
                        <td colspan="15" class="text-center" style="padding: 20px; color: #64748b;">
                            No existen datos cargados para esta actividad.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cuentas as $row): 
                        $bCierre   = (float)$row['balance_cierre'];
                        $debe      = (float)$row['debe'];
                        $haber     = (float)$row['haber'];
                        $bAudit    = (float)$row['balance_auditado'];
                        $bAjust    = (float)$row['balance_final_ajustado'];
                        $diferencia = (float)$row['diferencia'];
                    ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars((string)($row['link_eeff_1'] ?? '')) ?></td>
                            <td class="text-left"><?= htmlspecialchars((string)($row['rubro_eeff_1'] ?? '')) ?></td>
                            <td class="text-center"><?= htmlspecialchars((string)($row['link_eeff_2'] ?? '')) ?></td>
                            <td class="text-left"><?= htmlspecialchars((string)($row['rubro_eeff_notas'] ?? '')) ?></td>
                            <td class="text-center"><?= htmlspecialchars((string)($row['link_centro_costo'] ?? '')) ?></td>
                            <td class="text-center"><?= htmlspecialchars((string)($row['tipo_partida'] ?? '')) ?></td>
                            <td class="text-left font-bold"><?= htmlspecialchars($row['codigo']) ?></td>
                            <td class="text-left"><?= htmlspecialchars($row['nombre']) ?></td>
                            <td class="text-left"><?= htmlspecialchars((string)($row['codigo_nombre'] ?? '')) ?></td>
                            <td class="text-right <?= $bCierre < 0 ? 'monto-negativo' : '' ?>"><?= formatearMonto($bCierre) ?></td>
                            <td class="text-right"><?= formatearMonto($debe) ?></td>
                            <td class="text-right"><?= formatearMonto($haber) ?></td>
                            <td class="text-right font-bold <?= $bAudit < 0 ? 'monto-negativo' : '' ?>"><?= formatearMonto($bAudit) ?></td>
                            <td class="text-right <?= $bAjust < 0 ? 'monto-negativo' : '' ?>"><?= formatearMonto($bAjust) ?></td>
                            <td class="text-right <?= $diferencia < 0 ? 'monto-negativo' : '' ?>"><?= formatearMonto($diferencia) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>