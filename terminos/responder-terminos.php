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

<div class="view-container" style="padding: 2rem; max-width: 1100px; margin: 0 auto;">
    
    <!-- ENCABEZADO -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 700; color: #0f172a; margin: 0;">
                <i class="ri-file-list-3-line" style="color: #2563eb;"></i> Responder Términos y Condiciones
            </h1>
            <p style="margin: 0.25rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                Cliente: <strong><?= htmlspecialchars($headerData->clientName, ENT_QUOTES, 'UTF-8') ?></strong> | 
                Servicio: <strong><?= htmlspecialchars($headerData->servicio, ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </div>
        <a href="index.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; text-decoration: none; background: #e2e8f0; color: #334155; border-radius: 6px; font-weight: 600;">
            <i class="ri-arrow-left-line"></i> Volver al Listado
        </a>
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

                <a href="formulario-termino.php?terminoId=<?= $terminoId ?>&item=<?= $item->item_key ?>" 
                   title="Editar <?= htmlspecialchars($item->item_nombre, ENT_QUOTES, 'UTF-8') ?>"
                   style="display: flex; align-items: center; justify-content: center; padding: 0.85rem 1.25rem; background: rgba(0, 0, 0, 0.08); color: #ffffff; text-decoration: none; transition: background 0.2s;"
                   onmouseover="this.style.background='rgba(0, 0, 0, 0.25)'"
                   onmouseout="this.style.background='rgba(0, 0, 0, 0.08)'">
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