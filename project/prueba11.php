<?php
declare(strict_types=1);

// Procesamiento POST específico para la Prueba 11 (Revisión Analítica)
if ((int)$pruebaId === 11 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action_type'] ?? '';

    if ($action === 'add_analitica_item') {
        $tipo = filter_input(INPUT_POST, 'tipo', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $tipoRubro = trim($_POST['tipo_rubro'] ?? '');
        $saldoActual = filter_input(INPUT_POST, 'saldo_actual', FILTER_VALIDATE_FLOAT) ?: 0.00;
        $saldoAnterior = filter_input(INPUT_POST, 'saldo_anterior', FILTER_VALIDATE_FLOAT) ?: 0.00;
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (in_array($tipo, ['activo', 'pasivo', 'patrimonio'], true) && !empty($tipoRubro)) {
            try {
                $stmtIns = $pdo->prepare("
                    INSERT INTO proyecto_revision_analitica 
                    (proyecto_id, prueba_id, tipo, tipo_rubro, saldo_actual, saldo_anterior, observaciones)
                    VALUES (:proj, :pr, :tipo, :rubro, :actual, :anterior, :obs)
                ");
                $stmtIns->execute([
                    ':proj'    => $proyectoId,
                    ':pr'      => $pruebaId,
                    ':tipo'    => $tipo,
                    ':rubro'   => $tipoRubro,
                    ':actual'  => $saldoActual,
                    ':anterior'=> $saldoAnterior,
                    ':obs'     => $observaciones
                ]);
            } catch (PDOException $e) {
                error_log("Error al insertar partida analítica: " . $e->getMessage());
            }
        }
    } elseif ($action === 'delete_analitica_item') {
        $itemId = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
        if ($itemId) {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM proyecto_revision_analitica WHERE id = :id AND proyecto_id = :proj AND prueba_id = :pr");
                $stmtDel->execute([':id' => $itemId, ':proj' => $proyectoId, ':pr' => $pruebaId]);
            } catch (PDOException $e) {
                error_log("Error al eliminar partida analítica: " . $e->getMessage());
            }
        }
    }
}

// Carga de partidas analíticas agrupadas para la vista
$analiticaItems = ['activo' => [], 'pasivo' => [], 'patrimonio' => []];
if ((int)$pruebaId === 11) {
    try {
        $stmtAnalitica = $pdo->prepare("
            SELECT * FROM proyecto_revision_analitica 
            WHERE proyecto_id = :proj AND prueba_id = :pr 
            ORDER BY id ASC
        ");
        $stmtAnalitica->execute([':proj' => $proyectoId, ':pr' => $pruebaId]);
        while ($row = $stmtAnalitica->fetch(PDO::FETCH_OBJ)) {
            $analiticaItems[$row->tipo][] = $row;
        }
    } catch (PDOException $e) {
        error_log("Error al cargar partidas analíticas: " . $e->getMessage());
    }
}
// v/proyectos/revision-analitica.php
if ((int)$pruebaId === 11):
?>
<div style="margin-top: 2.5rem; margin-bottom: 1.5rem;">
    <h3 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin-bottom: 1rem;">Módulos Financieros Especiales</h3>

    <!-- Contenedor del Acordeón para la Prueba 11 -->
    <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; background: #ffffff;">
        
        <!-- Cabecera del Acordeón -->
        <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid var(--accent, #0284c7);">
            <span style="color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ri-bar-chart-box-line" style="color: var(--accent, #0284c7);"></i> Estado de Situación Financiera (Revisión Analítica)
            </span>
            <i class="ri-arrow-down-s-line" style="transition: transform 0.2s ease;"></i>
        </div>

        <!-- Contenido Desplegable del Acordeón -->
        <div class="accordion-content" style="display: none; padding: 1.25rem; background: #ffffff;">
            
            <!-- Botones de Acción para Agregar Partidas -->
            <div style="display: flex; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
                <button type="button" class="btn" onclick="openAnaliticaModal('activo')" style="background: #2563eb; color: #fff; padding: 0.4rem 0.85rem; font-size: 0.85rem; border-radius: 6px; border:none; cursor:pointer; font-weight: 600;"><i class="ri-add-line"></i> Activo</button>
                <button type="button" class="btn" onclick="openAnaliticaModal('pasivo')" style="background: #ea580c; color: #fff; padding: 0.4rem 0.85rem; font-size: 0.85rem; border-radius: 6px; border:none; cursor:pointer; font-weight: 600;"><i class="ri-add-line"></i> Pasivo</button>
                <button type="button" class="btn" onclick="openAnaliticaModal('patrimonio')" style="background: #16a34a; color: #fff; padding: 0.4rem 0.85rem; font-size: 0.85rem; border-radius: 6px; border:none; cursor:pointer; font-weight: 600;"><i class="ri-add-line"></i> Patrimonio</button>
            </div>

            <!-- Tabla Financiera -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: 8px; overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;">
                            <th style="padding: 0.75rem;">Rubro</th>
                            <th style="padding: 0.75rem;">Tipo</th>
                            <th style="padding: 0.75rem; text-align: right;">Saldo Actual</th>
                            <th style="padding: 0.75rem; text-align: right;">Saldo Anterior</th>
                            <th style="padding: 0.75rem; text-align: right;">Var en Bs</th>
                            <th style="padding: 0.75rem; text-align: right;">Var en %</th>
                            <th style="padding: 0.75rem; text-align: center;">Observaciones</th>
                            <th style="padding: 0.75rem; text-align: center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totActCur = 0; $totActAnt = 0;
                        $totPasCur = 0; $totPasAnt = 0;
                        $totPatCur = 0; $totPatAnt = 0;

                        foreach (['activo', 'pasivo', 'patrimonio'] as $cat):
                            $itemsSec = $analiticaItems[$cat];
                            $subCur = 0; $subAnt = 0;
                        ?>
                            <tr style="background: #f1f5f9; font-weight: bold; color: #1e293b;">
                                <td colspan="8" style="padding: 0.5rem 0.75rem; text-transform: uppercase;"><?= ucfirst($cat) ?></td>
                            </tr>
                            <?php if (!empty($itemsSec)): ?>
                                <?php foreach ($itemsSec as $item): 
                                    $varBs = (float)$item->saldo_actual - (float)$item->saldo_anterior;
                                    $varPorc = ((float)$item->saldo_anterior != 0) ? ($varBs / (float)$item->saldo_anterior) * 100 : 0;
                                    
                                    if ($cat === 'activo') { $totActCur += (float)$item->saldo_actual; $totActAnt += (float)$item->saldo_anterior; $subCur += (float)$item->saldo_actual; $subAnt += (float)$item->saldo_anterior; }
                                    if ($cat === 'pasivo') { $totPasCur += (float)$item->saldo_actual; $totPasAnt += (float)$item->saldo_anterior; $subCur += (float)$item->saldo_actual; $subAnt += (float)$item->saldo_anterior; }
                                    if ($cat === 'patrimonio') { $totPatCur += (float)$item->saldo_actual; $totPatAnt += (float)$item->saldo_anterior; $subCur += (float)$item->saldo_actual; $subAnt += (float)$item->saldo_anterior; }
                                ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 0.75rem; text-transform: capitalize;"><?= htmlspecialchars($item->tipo, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td style="padding: 0.75rem; font-weight: 600; color: #334155;"><?= htmlspecialchars($item->tipo_rubro, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td style="padding: 0.75rem; text-align: right;"><?= number_format((float)$item->saldo_actual, 2, ',', '.') ?></td>
                                        <td style="padding: 0.75rem; text-align: right;"><?= number_format((float)$item->saldo_anterior, 2, ',', '.') ?></td>
                                        <td style="padding: 0.75rem; text-align: right; color: <?= $varBs < 0 ? '#dc2626' : '#16a34a' ?>;"><?= number_format($varBs, 2, ',', '.') ?></td>
                                        <td style="padding: 0.75rem; text-align: right;"><?= number_format($varPorc, 2, ',', '.') ?>%</td>
                                        <td style="padding: 0.75rem; text-align: center;"><?= htmlspecialchars($item->observaciones ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <button type="submit" form="delAnalitica_<?= $item->id ?>" style="background: #fee2e2; color: #dc2626; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;" title="Eliminar"><i class="ri-delete-bin-line"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="background: #f8fafc; font-weight: bold; border-top: 1px solid #e2e8f0; color: #334155;">
                                    <td colspan="2" style="padding: 0.6rem 0.75rem; text-align: right;">Total <?= ucfirst($cat) ?></td>
                                    <td style="padding: 0.6rem 0.75rem; text-align: right;"><?= number_format($subCur, 2, ',', '.') ?></td>
                                    <td style="padding: 0.6rem 0.75rem; text-align: right;"><?= number_format($subAnt, 2, ',', '.') ?></td>
                                    <td colspan="4"></td>
                                </tr>
                            <?php else: ?>
                                <tr><td colspan="8" style="padding: 0.75rem; text-align: center; color: #94a3b8; font-style: italic;">Sin registros en <?= $cat ?>.</td></tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <!-- Fila Total General Final -->
                        <?php 
                        $genCur = $totActCur - ($totPasCur + $totPatCur); 
                        $genAnt = $totActAnt - ($totPasAnt + $totPatAnt);
                        ?>
                        <tr style="background: #1e293b; color: #ffffff; font-weight: bold;">
                            <td colspan="2" style="padding: 0.75rem; text-align: right;">TOTAL</td>
                            <td style="padding: 0.75rem; text-align: right;"><?= number_format($genCur, 2, ',', '.') ?></td>
                            <td style="padding: 0.75rem; text-align: right;"><?= number_format($genAnt, 2, ',', '.') ?></td>
                            <td colspan="4" style="padding: 0.75rem;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Formularios ocultos para eliminar filas analíticas -->
    <?php foreach (array_merge($analiticaItems['activo'], $analiticaItems['pasivo'], $analiticaItems['patrimonio']) as $it): ?>
        <form id="delAnalitica_<?= $it->id ?>" action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST" style="display:none;">
            <input type="hidden" name="action_type" value="delete_analitica_item">
            <input type="hidden" name="item_id" value="<?= $it->id ?>">
        </form>
    <?php endforeach; ?>

    <!-- Modal para Agregar Partida Analítica -->
    <div id="analiticaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1150; align-items:center; justify-content:center;">
        <div style="background:#ffffff; padding:2rem; border-radius:12px; max-width:550px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.15);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
                <h3 id="modalAnaliticaTitle" style="margin:0; color:#1e293b; font-size:1.1rem;">Agregar Partida</h3>
                <button type="button" onclick="closeAnaliticaModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#64748b;"><i class="ri-close-line"></i></button>
            </div>
            <form action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST">
                <input type="hidden" name="action_type" value="add_analitica_item">
                <input type="hidden" id="modalTipoAnalitica" name="tipo" value="">

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Tipo / Rubro (Ej. Efectivo, Cuentas por Cobrar...)</label>
                    <input type="text" name="tipo_rubro" required style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Saldo Actual</label>
                        <input type="number" step="0.01" name="saldo_actual" value="0.00" required style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Saldo Anterior</label>
                        <input type="number" step="0.01" name="saldo_anterior" value="0.00" required style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem;">
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Observaciones (ID de referencia)</label>
                    <input type="text" name="observaciones" placeholder="Ej. 6, 7..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem;">
                </div>
                <div style="text-align:right; display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeAnaliticaModal()" style="padding: 0.5rem 1rem;">Cancelar</button>
                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.25rem;">Guardar Partida</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAnaliticaModal(tipo) {
        document.getElementById('modalTipoAnalitica').value = tipo;
        document.getElementById('modalAnaliticaTitle').innerText = 'Nuevo Registro en ' + tipo.toUpperCase();
        document.getElementById('analiticaModal').style.display = 'flex';
    }
    function closeAnaliticaModal() {
        document.getElementById('analiticaModal').style.display = 'none';
    }
    </script>
</div>
<?php endif; ?>