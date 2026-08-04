<?php

declare(strict_types=1);

// v/terminos/responder-terminos.php
include '../main/config.php';

$terminoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$terminoId || $terminoId <= 0) {
    die("Error: Registro de Términos y Condiciones no especificado.");
}

try {
    // Cargar datos de la cabecera
    $stmtHeader = $pdo->prepare("
        SELECT tc.*, c.name AS clientName
        FROM terminos_condiciones tc
        INNER JOIN clientes c ON tc.cliente_id = c.id
        WHERE tc.id = :id
    ");
    $stmtHeader->execute([':id' => $terminoId]);
    $headerData = $stmtHeader->fetch(PDO::FETCH_OBJ);

    if (!$headerData) {
        die("Error: El registro de términos y condiciones no existe.");
    }

    // Cargar los 4 ítems asociados
    $stmtItems = $pdo->prepare("
        SELECT * FROM terminos_condiciones_items 
        WHERE termino_id = :termino_id 
        ORDER BY orden ASC
    ");
    $stmtItems->execute([':termino_id' => $terminoId]);
    $itemsList = $stmtItems->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error al cargar vista de respuesta de Términos: " . $e->getMessage());
    die("Error crítico de base de datos.");
}

$pageTitle = "Responder Términos y Condiciones";
include '../main/h.php';
?>
<link rel="stylesheet" href="../main/layout.css">
<?php include '../main/layout_header.php'; ?>

<div class="view-container">
    
    <!-- ENCABEZADO -->
     <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">
            <i class="ri-file-list-3-line" style="color: #2563eb;"></i> Responder Términos y Condiciones
        </h1>
        
    </div>
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

        <a href="#" class="btn-control-disabled" data-tooltip="Crear Registro" onclick="return false;">
            <i class="ri-add-line"></i>
        </a>

        <a href="../terminos/index.php" class="btn btn-primary" data-tooltip="Cancelar (Atrás)">
            <i class="ri-close-circle-line"></i> 
        </a>
    </div>


    <!-- Cabecera de Metadatos del Proyecto -->
    <div class="meta-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.25rem; border-radius: 12px; background: #ffffff; border: 1px solid var(--border-color);">
        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Cliente / Empresa</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($headerData->clientName ?? 'N/D', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio Líder</span><br>
                <strong style="color: #1e293b;"></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; border-right: 1px solid #e2e8f0; padding-right: 1rem; padding-left: 0.5rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Proyecto / Alcance</span><br>
                <strong style="color: #1e293b;"><?= htmlspecialchars($headerData->servicio, ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Socio de Calidad</span><br>
                <strong style="color: #1e293b;"></strong>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; padding-left: 0.5rem; font-size: 0.9rem;">
            <div>
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Fecha de Remisión</span><br>
                <strong style="color: #1e293b;"></strong>
            </div>
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem;">
                <span style="font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 600;">Gerente Encargado</span><br>
                <strong style="color: #1e293b;"></strong>
            </div>
        </div>
    </div>




    <!-- LISTADO DE LAS 4 ACTIVIDADES -->
    <div class="terminos-container" style="display: flex; flex-direction: column; gap: 0.6rem;">
        <?php foreach ($itemsList as $item): ?>
            <div class="termino-row" style="display: flex; align-items: center; background: #94a3b8; color: #ffffff; border-radius: 4px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                
                <div style="padding: 0.85rem 1.25rem; background: rgba(0, 0, 0, 0.12); font-weight: 700; font-size: 1rem; min-width: 45px; text-align: center;">
                    <?= $item->orden ?>
                </div>

                <div style="flex: 1; padding: 0.85rem 1.25rem; font-weight: 600; font-size: 0.95rem; letter-spacing: 0.2px;">
                    <?= htmlspecialchars($item->item_nombre, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div style="padding: 0 1rem;">
                    <?php if ($item->estado === 'completado'): ?>
                        <span style="background: #16a34a; color: #fff; font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 600;">Completado</span>
                    <?php else: ?>
                        <span style="background: rgba(255,255,255,0.2); color: #f8fafc; font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 500;">Pendiente</span>
                    <?php endif; ?>
                </div>

                <!-- Reemplazar la etiqueta <a> en responder-terminos.php -->
                <a href="<?= $item->item_key === 'frecuencia' ? 'formulario-frecuencia.php' : 'formulario-termino.php' ?>?terminoId=<?= $terminoId ?>&item=<?= $item->item_key ?>" 
                title="Editar <?= htmlspecialchars($item->item_nombre, ENT_QUOTES, 'UTF-8') ?>"
                style="display: flex; align-items: center; justify-content: center; padding: 0.85rem 1.25rem; background: rgba(0, 0, 0, 0.08); color: #ffffff; text-decoration: none; transition: background 0.2s;">
                    <i class="ri-pencil-fill" style="font-size: 1.1rem;"></i>
                </a>

            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>