<?php
include 'conect-actividades.php';
?>
<div class="view-container">
    <div class="table-actions-container">
        <a href="#" class="btn-control-disabled" data-tooltip="Atrás" onclick="return false;">
            <i class="ri-arrow-go-back-line"></i> 
        </a>
        <a href="#" class="btn-control-disabled" data-tooltip="Capturar Pantalla" onclick="return false;">
            <i class="ri-screenshot-2-line"></i>
        </a>
        <a href="#" class="btn-control-disabled" data-tooltip="Instrucciones" onclick="return false;">
            <i class="ri-book-open-line"></i> 
        </a>
        <a href="nuevo.php" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;">
            <i class="ri-add-line"></i>
        </a>
        <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>
    <!-- Cabecera de Metadatos del Proyecto -->
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px; background: #ffffff; border: 1px solid var(--border-color);">
        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Cliente / Empresa</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio Líder</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioLider ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; padding-left: 0.5rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Proyecto / Alcance</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->nombre ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio de Calidad</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->socioCalidad ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; padding-left: 0.5rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Remisión</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->fechaRemision ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Gerente Encargado</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>
    </div>

    <!-- Cabecera de Metadatos del Proyecto -->
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px; background: #ffffff; border: 1px solid var(--border-color);">
        <div style="display: flex; flex-direction: column; gap: 0.2rem; border-right: 1px solid #e2e8f0; padding-right: 0.75rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Realizado por:</span>
            <strong style="color: #1e293b; font-size: 0.9rem;"><?= htmlspecialchars($projectData->gerente ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.2rem; border-right: 1px solid #e2e8f0; padding-right: 0.75rem; padding-left: 0.5rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha</span>
            <strong style="color: #1e293b; font-size: 0.9rem;"><?= htmlspecialchars($projectData->fechaRemision ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.2rem; border-right: 1px solid #e2e8f0; padding-right: 0.75rem; padding-left: 0.5rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Revisado</span>
            <strong style="color: #1e293b; font-size: 0.9rem;"><?= htmlspecialchars($projectData->socioLider ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.2rem; border-right: 1px solid #e2e8f0; padding-right: 0.75rem; padding-left: 0.5rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha</span>
            <strong style="color: #1e293b; font-size: 0.9rem;"><?= htmlspecialchars($projectData->fechaRemision ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div style="display: flex; flex-direction: column; gap: 0.2rem; padding-left: 0.5rem; font-size: 0.85rem;">
            <span style="font-size: 0.7rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Estatus</span>
            <strong style="color: #1e293b; font-size: 0.9rem; text-transform: capitalize;"><?= str_replace('_', ' ', $estadoActualPrueba) ?></strong>
        </div>
    </div>

    <!-- Cabecera de la Prueba -->
    <div style="background: #1e293b; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <span style="font-size: 0.75rem; font-weight: 700; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.25rem;">
            <?= htmlspecialchars($metaPrueba->catNombre, ENT_QUOTES, 'UTF-8') ?>
        </span>
        <h2 style="margin: 0 0 1rem 0; font-size: 1.35rem; color: #ffffff; font-weight: 700; line-height: 1.4;">
            <?= htmlspecialchars($metaPrueba->nombre, ENT_QUOTES, 'UTF-8') ?>
        </h2>
        <button type="button" onclick="openNormaModal()" style="background: #0284c7; color: #ffffff; border: none; font-size: 0.85rem; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="ri-book-line"></i> Norma de Referencia
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="padding:1rem; background:#d1fae5; color:#065f46; border-radius:8px; margin-bottom:1.5rem;">
            <i class="ri-checkbox-circle-fill"></i> Operación ejecutada con éxito.
        </div>
    <?php endif; ?>

    <!-- FORMULARIO PRINCIPAL DE ACTIVIDADES -->
    <form action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST">
        <input type="hidden" name="action_type" value="save_all">
        
        <?php foreach ($listaActividades as $act): ?>
            <div class="card-actividad" style="background:#ffffff; border: 1px solid var(--border-color); padding:1.5rem; border-radius:8px; margin-bottom:1.25rem;">
                <div style="display:flex; justify-content:space-between; margin-bottom:1rem; gap:1rem;">
                    <div style="font-size:1rem; color:#334155; line-height:1.4;">
                        <strong>Actividad <?= $act->orden ?>:</strong> <?= $act->descripcion; ?>
                    </div>
                    <div>
                        <label style="font-weight:700; font-size:0.85rem; color:#475569; display:flex; align-items:center; gap:0.25rem; cursor:pointer;">
                            <input type="checkbox" name="actividades_data[<?= $act->id ?>][completado]" value="1" <?= $act->is_ok ? 'checked' : '' ?>> Realizado
                        </label>
                    </div>
                </div>
                <textarea name="actividades_data[<?= $act->id ?>][contenido]" placeholder="Escriba aquí los hallazgos, papeles de trabajo o evidencias analizadas..." rows="4" style="width:100%; padding:0.75rem; border-radius:6px; border:1px solid #cbd5e1; font-family:inherit; resize:vertical;"><?= htmlspecialchars($act->respuesta, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        <?php endforeach; ?>
        <!-- INCLUSIÓN AUTOMÁTICA DE LA REVISIÓN ANALÍTICA SÓLO PARA LA PRUEBA 11 -->
        <?php 
            if ((int)$pruebaId === 11) {
                include 'prueba11.php';
            } elseif ((int)$pruebaId === 16) {
                include 'prueba16.php';
            }
            
        ?>

        <!-- SECCIÓN DE ACORDEONES POR CADA INDICADOR (CI, CG, SC, AA) -->
        <div style="margin: 2.5rem 0 1.5rem 0;">
            <h3 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin-bottom: 1rem;">Indicadores y Puntos de Control</h3>
            
         <?php 
            $indicadoresMeta = [
                'CI' => ['nombre' => 'Debilidades de Control Interno (CI)', 'color' => '#ca8a04'], // Amarillo Riesgo
                'CG' => ['nombre' => 'Carta de Gerencia (CG)', 'color' => '#ea580c'],          // Naranja Riesgo
                'SC' => ['nombre' => 'Situaciones Críticas (SC)', 'color' => '#dc2626'],       // Rojo Riesgo
                'AA' => ['nombre' => 'Asuntos de Auditoría (AA)', 'color' => '#2563eb']        // Azul Estándar
            ];

            foreach ($indicadoresMeta as $key => $meta):
                $items = $detallesPorTipo[$key] ?? [];
            ?>
                <div class="accordion-item" style="margin-bottom: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; background: #ffffff;">
        <!-- Cabecera del Acordeón con color dinámico -->
                <div class="accordion-header" onclick="toggleAccordion(this)" style="background: #f1f5f9; padding: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-left: 5px solid <?= $meta['color'] ?>;">
                    <span style="color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ri-file-list-3-line" style="color: <?= $meta['color'] ?>;"></i> <?= $meta['nombre'] ?> 
                        <span style="font-size: 0.75rem; background: #e2e8f0; color: #334155; padding: 0.15rem 0.5rem; border-radius: 9999px;"><?= count($items) ?> registros</span>
                    </span>
                    <i class="ri-arrow-down-s-line"></i>
                </div>

                <!-- Contenido del Acordeón -->
                <div class="accordion-content" style="display: none; padding: 1.25rem; background: #ffffff;">
                    <div style="margin-bottom: 1rem;">
                        <button type="button" class="btn btn-primary" onclick="openIndicatorModal('<?= $key ?>')" style="padding: 0.4rem 0.85rem; font-size: 0.85rem; background: <?= $meta['color'] ?>; border-color: <?= $meta['color'] ?>;">
                            <i class="ri-add-line"></i> Punto de control
                        </button>
                    </div>

                        <!-- Tabla de Registros del Indicador -->
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569;">
                                        <th style="padding: 0.75rem;">Rubro</th>
                                        <th style="padding: 0.75rem;">Título</th>
                                        <th style="padding: 0.75rem;">Descripción</th>
                                        <th style="padding: 0.75rem;">Recomendación del Asunto</th>
                                        <th style="padding: 0.75rem; text-align: center;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                                <td style="padding: 0.75rem; font-weight: 600; color: #334155;"><?= htmlspecialchars($item->rubro ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                                <td style="padding: 0.75rem; font-weight: 600; color: #1e293b;"><?= htmlspecialchars($item->titulo, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td style="padding: 0.75rem; color: #475569;"><?= nl2br(htmlspecialchars($item->descripcion, ENT_QUOTES, 'UTF-8')) ?></td>
                                                <td style="padding: 0.75rem; color: #475569;"><?= nl2br(htmlspecialchars($item->recomendacion ?? '-', ENT_QUOTES, 'UTF-8')) ?></td>
                                                <td style="padding: 0.75rem; text-align: center;">
                                                    <button type="submit" form="deleteForm_<?= $item->id ?>" class="btn" style="background: #fee2e2; color: #dc2626; border: none; padding: 0.3rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem;" title="Eliminar">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" style="padding: 1rem; text-align: center; color: #64748b; font-style: italic;">No hay registros agregados en este indicador.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- CAJA DE ESTATUS GENERAL DE LA PRUEBA -->
        <div style="background: #ffffff; border: 1px solid var(--border-color); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div>
                <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem; color: #1e293b;">Estatus General de la Prueba</h4>
                <p style="margin: 0; font-size: 0.85rem; color: #64748b;">El estatus se actualizará al guardar todo el formulario.</p>
            </div>
            <div>
                <select name="estado_prueba" class="status-select" style="padding: 0.6rem 1rem; border-radius: 8px; font-size: 0.9rem; border: 1px solid #cbd5e1; font-weight: 600; background: #f8fafc;">
                    <option value="en_proceso" <?= $estadoActualPrueba === 'en_proceso' ? 'selected' : '' ?>>⏳ En proceso</option>
                    <option value="completado" <?= $estadoActualPrueba === 'completado' ? 'selected' : '' ?>>✅ Completado</option>
                    <option value="por_corregir_lider" <?= $estadoActualPrueba === 'por_corregir_lider' ? 'selected' : '' ?>>⚠️ Por Corregir Lider</option>
                    <option value="por_corregir_riesgo" <?= $estadoActualPrueba === 'por_corregir_riesgo' ? 'selected' : '' ?>>🚨 Por Corregir Riesgo</option>
                    <option value="revisado" <?= $estadoActualPrueba === 'revisado' ? 'selected' : '' ?>>🔹 Revisado</option>
                    <option value="cerrado" <?= $estadoActualPrueba === 'cerrado' ? 'selected' : '' ?>>🔒 Cerrado</option>
                </select>
            </div>
            <div>
                <a href="responder.php?proyectoId=<?= $proyectoId ?>" class="btn btn-secondary">Volver al Panel</a>
                <button type="submit" class="btn btn-primary" style="padding:0.75rem 2.5rem;"><i class="ri-save-3-line"></i> Guardar Todo</button>
            </div>
        </div>

       
    </form>

    <!-- Formularios ocultos individuales para eliminar registros de indicadores -->
    <?php foreach ($allDetalles as $det): ?>
        <form id="deleteForm_<?= $det->id ?>" action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST" style="display:none;">
            <input type="hidden" name="action_type" value="delete_indicador_detalle">
            <input type="hidden" name="detalle_id" value="<?= $det->id ?>">
        </form>
    <?php endforeach; ?>
</div>

<!-- MODAL PARA AGREGAR PUNTO DE CONTROL DE INDICADOR -->
<div id="indicatorModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1100; align-items:center; justify-content:center;">
    <div style="background:#ffffff; padding:2rem; border-radius:12px; max-width:650px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
            <h3 id="modalIndicatorTitle" style="margin:0; color:#1e293b; font-size:1.15rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="ri-add-box-line" style="color:var(--accent);"></i> Nuevo Punto de Control
            </h3>
            <button type="button" onclick="closeIndicatorModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#64748b;">
                <i class="ri-close-line"></i>
            </button>
        </div>

        <form action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST">
            <input type="hidden" name="action_type" value="add_indicador_detalle">
            <input type="hidden" id="modalTipoIndicador" name="tipo_indicador" value="">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Rubro</label>
                <input type="text" name="rubro" placeholder="Ej. Activo Corriente, Cuentas por Cobrar..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Título del Asunto / Hallazgo *</label>
                <input type="text" name="titulo" required placeholder="Título resumido..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Descripción *</label>
                <textarea name="descripcion" required rows="3" placeholder="Descripción detallada de la debilidad o hallazgo..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.3rem;">Recomendación del Asunto</label>
                <textarea name="recomendacion" rows="3" placeholder="Recomendación sugerida..." style="width: 100%; padding: 0.6rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.9rem; font-family: inherit; resize: vertical;"></textarea>
            </div>

            <div style="text-align:right; border-top:1px solid #e2e8f0; padding-top:1rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn btn-secondary" onclick="closeIndicatorModal()" style="padding: 0.5rem 1.25rem;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Guardar Registro</button>
            </div>
        </form>
    </div>
</div>
<!-- Formularios ocultos para eliminar filas analíticas (FUERA DEL FORM PRINCIPAL) -->
    <?php if ((int)$pruebaId === 11): ?>
        <?php foreach (array_merge($analiticaItems['activo'], $analiticaItems['pasivo'], $analiticaItems['patrimonio']) as $it): ?>
            <form id="delAnalitica_<?= $it->id ?>" action="actividades.php?proyectoId=<?= $proyectoId ?>&pruebaId=<?= $pruebaId ?>" method="POST" style="display:none;">
                <input type="hidden" name="action_type" value="delete_analitica_item">
                <input type="hidden" name="item_id" value="<?= $it->id ?>">
            </form>
        <?php endforeach; ?>

        <!-- Modal para Agregar Partida Analítica (FUERA DEL FORM PRINCIPAL) -->
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
    <?php endif; ?>
<!-- Modal de la Norma -->
<div id="normaModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#ffffff; padding:2rem; border-radius:12px; max-width:650px; width:90%; box-shadow:0 10px 25px rgba(0,0,0,0.15); border:1px solid var(--border-color);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
            <h3 style="margin:0; color:#1e293b; font-size:1.15rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="ri-book-2-line" style="color:var(--accent);"></i> Norma Aplicable
            </h3>
            <button type="button" onclick="closeNormaModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#64748b;">
                <i class="ri-close-line"></i>
            </button>
        </div>
        <div style="color:#334155; line-height:1.6; max-height:400px; overflow-y:auto; font-size:0.95rem;">
            <?= !empty($metaPrueba->norma) ? nl2br(htmlspecialchars($metaPrueba->norma, ENT_QUOTES, 'UTF-8')) : '<em style="color:#64748b;">No hay una norma registrada.</em>' ?>
        </div>
        <div style="text-align:right; margin-top:1.5rem; border-top:1px solid #e2e8f0; padding-top:1rem;">
            <button type="button" class="btn btn-primary" onclick="closeNormaModal()" style="padding: 0.5rem 1.5rem;">Cerrar</button>
        </div>
    </div>
</div>
<?php 
include 'js-actividades.php'; 
?>