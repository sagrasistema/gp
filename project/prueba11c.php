<?php
declare(strict_types=1);

// Carga de partidas analíticas agrupadas exclusivamente para la vista
$analiticaItems = ['activo' => [], 'pasivo' => [], 'patrimonio' => []];

if ((int)$pruebaId === 11 && isset($pdo, $proyectoId)) {
    try {
        $stmtAnalitica = $pdo->prepare("
            SELECT id, proyecto_id, prueba_id, tipo, tipo_rubro, saldo_actual, saldo_anterior, observaciones 
            FROM proyecto_revision_analitica_c
            WHERE proyecto_id = :proj AND prueba_id = :pr 
            ORDER BY id ASC
        ");
        $stmtAnalitica->execute([
            ':proj' => $proyectoId,
            ':pr'   => $pruebaId
        ]);

        while ($row = $stmtAnalitica->fetch(PDO::FETCH_OBJ)) {
            if (isset($analiticaItems[$row->tipo])) {
                $analiticaItems[$row->tipo][] = $row;
            }
        }
    } catch (PDOException $e) {
        error_log("Error al cargar partidas analíticas (Prueba 11): " . $e->getMessage());
    }
}

if ((int)$pruebaId === 128):
?>
<div style="margin-top: 2.5rem; margin-bottom: 1.5rem;">
    <h3 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin-bottom: 1rem;">Módulos Financieros Especiales</h3>

    <!-- Acordeón Estado de Situación Financiera -->
    <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; overflow: hidden; background: #ffffff;">
        
        <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid var(--accent, #0284c7);">
            <span style="color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                <i class="ri-bar-chart-box-line" style="color: var(--accent, #0284c7);"></i> Estado de Situación Financiera (Revisión Analítica)
            </span>
            <i class="ri-arrow-down-s-line" style="transition: transform 0.2s ease;"></i>
        </div>

        <div class="accordion-content" style="display: none; padding: 1.25rem; background: #ffffff;">
            
            <!-- Botones de Acción para Abrir Modales -->
            <div style="display: flex; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
                <button type="button" class="btn" onclick="openAnaliticaModal('activo')" style="background: #2563eb; color: #fff; padding: 0.4rem 0.85rem; font-size: 0.85rem; border-radius: 6px; border:none; cursor:pointer; font-weight: 600;"><i class="ri-add-line"></i> Activo</button>
                <button type="button" class="btn" onclick="openAnaliticaModal('pasivo')" style="background: #ea580c; color: #fff; padding: 0.4rem 0.85rem; font-size: 0.85rem; border-radius: 6px; border:none; cursor:pointer; font-weight: 600;"><i class="ri-add-line"></i> Pasivo</button>
                <button type="button" class="btn" onclick="openAnaliticaModal('patrimonio')" style="background: #16a34a; color: #fff; padding: 0.4rem 0.85rem; font-size: 0.85rem; border-radius: 6px; border:none; cursor:pointer; font-weight: 600;"><i class="ri-add-line"></i> Patrimonio</button>
            </div>

            <!-- Tabla de Consolidado Financiero -->
            <div style="background: #ffffff; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; overflow-x: auto;">
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
                        $totActCur = 0.0; $totActAnt = 0.0;
                        $totPasCur = 0.0; $totPasAnt = 0.0;
                        $totPatCur = 0.0; $totPatAnt = 0.0;

                        foreach (['activo', 'pasivo', 'patrimonio'] as $cat):
                            $itemsSec = $analiticaItems[$cat];
                            $subCur = 0.0; $subAnt = 0.0;
                        ?>
                            <tr style="background: #f1f5f9; font-weight: bold; color: #1e293b;">
                                <td colspan="8" style="padding: 0.5rem 0.75rem; text-transform: uppercase;"><?= ucfirst($cat) ?></td>
                            </tr>
                            <?php if (!empty($itemsSec)): ?>
                                <?php foreach ($itemsSec as $item): 
                                    $sActual = (float)$item->saldo_actual;
                                    $sAnterior = (float)$item->saldo_anterior;
                                    $varBs = $sActual - $sAnterior;
                                    $varPorc = ($sAnterior != 0.0) ? ($varBs / $sAnterior) * 100 : 0.0;
                                    
                                    if ($cat === 'activo') { $totActCur += $sActual; $totActAnt += $sAnterior; }
                                    if ($cat === 'pasivo') { $totPasCur += $sActual; $totPasAnt += $sAnterior; }
                                    if ($cat === 'patrimonio') { $totPatCur += $sActual; $totPatAnt += $sAnterior; }
                                    
                                    $subCur += $sActual; 
                                    $subAnt += $sAnterior;
                                ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 0.75rem; text-transform: capitalize;"><?= htmlspecialchars((string)$item->tipo, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td style="padding: 0.75rem; font-weight: 600; color: #334155;"><?= htmlspecialchars((string)$item->tipo_rubro, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td style="padding: 0.75rem; text-align: right;"><?= number_format($sActual, 2, ',', '.') ?></td>
                                        <td style="padding: 0.75rem; text-align: right;"><?= number_format($sAnterior, 2, ',', '.') ?></td>
                                        <td style="padding: 0.75rem; text-align: right; color: <?= $varBs < 0 ? '#dc2626' : '#16a34a' ?>;"><?= number_format($varBs, 2, ',', '.') ?></td>
                                        <td style="padding: 0.75rem; text-align: right;"><?= number_format($varPorc, 2, ',', '.') ?>%</td>
                                        <td style="padding: 0.75rem; text-align: center;"><?= htmlspecialchars((string)($item->observaciones ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td style="padding: 0.75rem; text-align: center;">
                                            <button type="submit" form="delAnalitica_<?= (int)$item->id ?>" style="background: #fee2e2; color: #dc2626; border: none; padding: 0.25rem 0.5rem; border-radius: 4px; cursor: pointer;" title="Eliminar"><i class="ri-delete-bin-line"></i></button>
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

                        <!-- Fila Total General Consolidado -->
                        <?php 
                        $genCur = $totActCur - ($totPasCur + $totPatCur); 
                        $genAnt = $totActAnt - ($totPasAnt + $totPatAnt);
                        ?>
                        <tr style="background: #1e293b; color: #ffffff; font-weight: bold;">
                            <td colspan="2" style="padding: 0.75rem; text-align: right;">TOTAL ESTRUCTURAL</td>
                            <td style="padding: 0.75rem; text-align: right;"><?= number_format($genCur, 2, ',', '.') ?></td>
                            <td style="padding: 0.75rem; text-align: right;"><?= number_format($genAnt, 2, ',', '.') ?></td>
                            <td colspan="4" style="padding: 0.75rem;"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
<?php endif; ?>