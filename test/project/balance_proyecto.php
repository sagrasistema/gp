<?php

declare(strict_types=1);

require_once __DIR__ . '/../main/h.php';
require_once __DIR__ . '/../main/config.php';
require_once __DIR__ . '/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

/** @var PDO $pdo */
$proyectoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$proyectoId || $proyectoId <= 0) {
    die('Error: ID de proyecto no válido.');
}

/**
 * Normaliza valores contables provenientes de Excel.
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

    $str = str_replace([',', ' '], '', $str);
    $monto = (float)$str;

    return $esNegativo ? -$monto : $monto;
}

/**
 * Formatea montos estilo libro contable.
 */
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
// PROCESAMIENTO DEL FORMULARIO POST (CARGA EXCEL)
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

                        // Limpiar balance previo del proyecto
                        $deleteStmt = $pdo->prepare("DELETE FROM actividad_balance_auditado WHERE proyecto_id = :proyecto_id");
                        $deleteStmt->execute([':proyecto_id' => $proyectoId]);

                        $sql = "INSERT INTO actividad_balance_auditado 
                                    (proyecto_id, actividad_id, link_eeff_1, rubro_eeff_1, link_eeff_2, rubro_eeff_notas, 
                                     link_centro_costo, tipo_partida, codigo, nombre, codigo_nombre, 
                                     balance_cierre, debe, haber, balance_auditado, balance_final_ajustado, diferencia)
                                VALUES 
                                    (:proyecto_id, :actividad_id, :link_eeff_1, :rubro_eeff_1, :link_eeff_2, :rubro_eeff_notas, 
                                     :link_centro_costo, :tipo_partida, :codigo, :nombre, :codigo_nombre, 
                                     :balance_cierre, :debe, :haber, :balance_auditado, :balance_final_ajustado, :diferencia)";

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

                            $balanceCierre   = parseMontoContable($row[9] ?? 0);
                            $debe            = parseMontoContable($row[10] ?? 0);
                            $haber           = parseMontoContable($row[11] ?? 0);
                            $balanceAuditado = parseMontoContable($row[12] ?? 0);
                            $balFinalAjust   = parseMontoContable($row[13] ?? 0);
                            $diferencia      = parseMontoContable($row[14] ?? 0);

                            if ($balanceAuditado === 0.0 && ($balanceCierre != 0.0 || $debe != 0.0 || $haber != 0.0)) {
                                $balanceAuditado = $balanceCierre + $debe - $haber;
                            }

                            $stmt->execute([
                                ':proyecto_id'            => $proyectoId,
                                ':actividad_id'          => 0, // Por defecto al proyecto
                                ':link_eeff_1'            => isset($row[0]) && trim((string)$row[0]) !== '' ? trim((string)$row[0]) : null,
                                ':rubro_eeff_1'           => isset($row[1]) && trim((string)$row[1]) !== '' ? trim((string)$row[1]) : null,
                                ':link_eeff_2'            => isset($row[2]) && trim((string)$row[2]) !== '' ? trim((string)$row[2]) : null,
                                ':rubro_eeff_notas'       => isset($row[3]) && trim((string)$row[3]) !== '' ? trim((string)$row[3]) : null,
                                ':link_centro_costo'      => isset($row[4]) && trim((string)$row[4]) !== '' ? trim((string)$row[4]) : null,
                                ':tipo_partida'           => isset($row[5]) && trim((string)$row[5]) !== '' ? trim((string)$row[5]) : null,
                                ':codigo'                 => $codigo,
                                ':nombre'                 => $nombre,
                                ':codigo_nombre'          => isset($row[8]) && trim((string)$row[8]) !== '' ? trim((string)$row[8]) : null,
                                ':balance_cierre'         => $balanceCierre,
                                ':debe'                   => $debe,
                                ':haber'                  => $haber,
                                ':balance_auditado'       => $balanceAuditado,
                                ':balance_final_ajustado' => $balFinalAjust,
                                ':diferencia'             => $diferencia,
                            ]);

                            $procesados++;
                        }

                        $pdo->commit();
                        $mensaje = "Se cargaron exitosamente {$procesados} registros al balance del proyecto.";
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
// CONSULTA DE REGISTROS Y TOTALIZACIÓN
// --------------------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM actividad_balance_auditado WHERE proyecto_id = :proyecto_id ORDER BY id ASC");
$stmt->execute([':proyecto_id' => $proyectoId]);
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Acumuladores de Totales
$totales = [
    'balance_cierre'         => 0.0,
    'debe'                   => 0.0,
    'haber'                  => 0.0,
    'balance_auditado'       => 0.0,
    'balance_final_ajustado' => 0.0,
    'diferencia'             => 0.0,
];

foreach ($cuentas as $c) {
    $totales['balance_cierre']         += (float)$c['balance_cierre'];
    $totales['debe']                   += (float)$c['debe'];
    $totales['haber']                  += (float)$c['haber'];
    $totales['balance_auditado']       += (float)$c['balance_auditado'];
    $totales['balance_final_ajustado'] += (float)$c['balance_final_ajustado'];
    $totales['diferencia']             += (float)$c['diferencia'];
}
?>

<link rel="stylesheet" href="../main/layout.css">

<?php
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'proyectos'; 

include '../main/layout_header.php';
?>


<div class="card">
    <h2>Gestión de Balance de Proyecto #<?= htmlspecialchars((string)$proyectoId) ?></h2>
    <a href="index.php">← Volver a Proyectos</a>
    <hr>

    <?php if ($mensaje): ?><div class="alert-success"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Formulario de Carga -->
    <form action="" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
        <input type="hidden" name="proyecto_id" value="<?= htmlspecialchars((string)$proyectoId) ?>">
        <label for="archivo_excel"><strong>Cargar/Actualizar Balance (.xlsx):</strong></label><br><br>
        <input type="file" name="archivo_excel" id="archivo_excel" accept=".xlsx" required>
        <button type="submit" name="cargar_balance" style="padding: 6px 15px; background: #0284c7; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Procesar e Importar
        </button>
    </form>
</div>

<!-- Acordeón con Tabla Desplegable -->
<button class="accordion-btn" onclick="toggleAccordion()">
    <span><i class="ri-table-line"></i> Ver Tabla de Balance Cargado (<?= count($cuentas) ?> filas)</span>
    <span id="acc-icon">▼</span>
</button>

<div class="accordion-content" id="accordion-panel">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Link EEFF</th>
                    <th>Rubro EEFF</th>
                    <th>Link EEFF 2</th>
                    <th>Rubro y Notas</th>
                    <th>Centro Costo</th>
                    <th>Tipo Partida</th>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Código y Nombre</th>
                    <th>Balance Cierre</th>
                    <th>Debe</th>
                    <th>Haber</th>
                    <th>Balance Auditado</th>
                    <th>Balance Final Ajustado</th>
                    <th>Diferencia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cuentas)): ?>
                    <tr><td colspan="15" class="text-center">No hay datos de balance registrados para este proyecto.</td></tr>
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
                            <td class="text-right"><?= formatearMonto((float)$row['balance_cierre']) ?></td>
                            <td class="text-right"><?= formatearMonto((float)$row['debe']) ?></td>
                            <td class="text-right"><?= formatearMonto((float)$row['haber']) ?></td>
                            <td class="text-right"><strong><?= formatearMonto((float)$row['balance_auditado']) ?></strong></td>
                            <td class="text-right"><?= formatearMonto((float)$row['balance_final_ajustado']) ?></td>
                            <td class="text-right"><?= formatearMonto((float)$row['diferencia']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- Fila de Totalización -->
                    <tr class="tr-total">
                        <td colspan="9" class="text-right">TOTALES GENERALES:</td>
                        <td class="text-right"><?= formatearMonto($totales['balance_cierre']) ?></td>
                        <td class="text-right"><?= formatearMonto($totales['debe']) ?></td>
                        <td class="text-right"><?= formatearMonto($totales['haber']) ?></td>
                        <td class="text-right"><?= formatearMonto($totales['balance_auditado']) ?></td>
                        <td class="text-right"><?= formatearMonto($totales['balance_final_ajustado']) ?></td>
                        <td class="text-right"><?= formatearMonto($totales['diferencia']) ?></td>
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

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>