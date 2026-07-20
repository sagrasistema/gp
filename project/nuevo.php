<?php

// v/proyectos/nuevo.php
$baseDir = "../main/";
include '../main/config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientId = filter_input(INPUT_POST, 'clientId', FILTER_VALIDATE_INT);
    $nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
    $descripcion = htmlspecialchars(trim($_POST['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8');
    $fechaInicio = trim($_POST['fecha_inicio'] ?? '');

    if (!$clientId || empty($nombre) || empty($fechaInicio)) {
        die("Error: Parámetros del proyecto incompletos.");
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO proyectos (cliente_id, nombre, descripcion, fecha_inicio) VALUES (:cliente_id, :nombre, :descripcion, :fecha_inicio)");
        $stmt->execute([
            ':cliente_id'  => $clientId,
            ':nombre'       => $nombre,
            ':descripcion'  => !empty($descripcion) ? $descripcion : null,
            ':fecha_inicio' => $fechaInicio
        ]);

        $pdo->commit();
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error de sistema al crear el proyecto: " . $e->getMessage());
    }
}

$pageTitle = "Iniciar Proyecto de Auditoría";
include '../main/h.php'; 
?>
<link rel="stylesheet" href="../main/layout.css">
<?php
include '../main/layout_header.php'; 
?>

<div class="view-container">
    <div class="card" style="max-width: 700px; margin: 2rem auto; padding: 2.5rem; background: var(--bg-card); border-radius: 16px; border: 1px solid var(--border-color);">
        <h3>Iniciar Nuevo Proyecto de Auditoría</h3>
        <form action="nuevo.php" method="POST" style="margin-top: 1.5rem;">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.5rem;">Cliente Corporativo Asociado</label>
                <select name="clientId" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color);">
                    <option value="" disabled selected>-- Seleccione un Cliente --</option>
                    <?php
                    $stmt = $pdo->query("SELECT id, name FROM clientes WHERE status = 'Activo' ORDER BY name ASC");
                    while($c = $stmt->fetch(PDO::FETCH_OBJ)) {
                        echo "<option value='{$c->id}'>" . htmlspecialchars($c->name, ENT_QUOTES, 'UTF-8') . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.5rem;">Nombre del Proyecto / Alcance</label>
                <input type="text" name="nombre" required placeholder="Ej: Auditoría de Estados Financieros 2026" style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color);">
            </div>
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.5rem;">Descripción Corta</label>
                <textarea name="descripcion" rows="3" style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color);"></textarea>
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label style="display:block; font-weight:600; margin-bottom:0.5rem;">Fecha de Inicio de Labores</label>
                <input type="date" name="fecha_inicio" required style="width:100%; padding:0.75rem; border-radius:8px; border:1px solid var(--border-color);">
            </div>
            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Crear Proyecto</button>
            </div>
        </form>
    </div>
</div>

<?php 
include '../main/layout_footer.php'; 
include '../main/footer.php'; 
?>