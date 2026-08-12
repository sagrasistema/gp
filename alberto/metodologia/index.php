<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../main/config.php';

$mensaje = '';
$error = '';

// Procesar la creación de un nuevo servicio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'crear_servicio') {
    $nombreServicio = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (empty($nombreServicio)) {
        $error = 'El nombre del servicio es obligatorio.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmtIns = $pdo->prepare("
                INSERT INTO audit_servicios (nombre, descripcion, estatus) 
                VALUES (:nombre, :descripcion, 1)
            ");
            $stmtIns->execute([
                ':nombre' => $nombreServicio,
                ':descripcion' => $descripcion
            ]);
            $servicioId = (int)$pdo->lastInsertId();

            // Garantizar la creación de las 4 etapas estándar para el nuevo servicio
            $etapasEstandar = [
                1 => 'Planificación',
                2 => 'Estrategia',
                3 => 'Ejecución',
                4 => 'Conclusión'
            ];

            $stmtEtapa = $pdo->prepare("
                INSERT INTO audit_etapas (id_enum, servicio_id, nombre) 
                VALUES (:id_enum, :servicio_id, :nombre)
            ");

            foreach ($etapasEstandar as $orden => $nombreEtapa) {
                $stmtEtapa->execute([
                    ':id_enum' => $orden,
                    ':servicio_id' => $servicioId,
                    ':nombre' => $nombreEtapa
                ]);
            }

            $pdo->commit();
            $mensaje = 'Servicio configurado con éxito junto a sus 4 etapas estándar.';
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error al crear servicio: " . $e->getMessage());
            $error = 'Error interno al guardar el servicio.';
        }
    }
}

// Cargar la lista de servicios
try {
    $stmtServicios = $pdo->query("SELECT * FROM audit_servicios ORDER BY id ASC");
    $servicios = $stmtServicios->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al consultar servicios: " . $e->getMessage());
    $servicios = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Metodología - Servicios de Auditoría</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="view-container" style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2>Metodología de Servicios</h2>
        <button type="button" class="btn btn-primary" onclick="openModalServicio()" style="padding: 0.6rem 1.2rem; background: #1e3a5f; color: #fff; border: none; border-radius: 6px; cursor: pointer;">
            <i class="ri-add-line"></i> + Crear Nuevo Servicio
        </button>
    </div>

    <?php if ($mensaje): ?>
        <div style="padding: 1rem; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 1.5rem;">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="padding: 1rem; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-bottom: 1.5rem;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php foreach ($servicios as $serv): ?>
            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h3 style="margin-top: 0; color: #1e293b;"><?= htmlspecialchars($serv['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p style="color: #64748b; font-size: 0.9rem;"><?= htmlspecialchars($serv['descripcion'] ?? 'Sin descripción', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div style="margin-top: 1.5rem; text-align: right;">
                    <a href="servicio_etapas.php?servicioId=<?= (int)$serv['id'] ?>" class="btn" style="background: #0284c7; color: #fff; padding: 0.5rem 1rem; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">
                        Editar Metodología &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal para Crear Servicio -->
<div id="modalServicio" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; padding: 2rem; border-radius: 12px; max-width: 500px; width: 90%;">
        <h3 style="margin-top: 0; color: #1e293b;">Crear Nuevo Servicio</h3>
        <form action="index.php" method="POST">
            <input type="hidden" name="action_type" value="crear_servicio">
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.3rem;">Nombre del Servicio *</label>
                <input type="text" name="nombre" required style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.3rem;">Descripción</label>
                <textarea name="descripcion" rows="3" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
            </div>

            <div style="text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" onclick="closeModalServicio()" style="padding: 0.5rem 1rem; background: #cbd5e1; border: none; border-radius: 6px; cursor: pointer;">Cancelar</button>
                <button type="submit" style="padding: 0.5rem 1.2rem; background: #1e3a5f; color: #fff; border: none; border-radius: 6px; cursor: pointer;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalServicio() { document.getElementById('modalServicio').style.display = 'flex'; }
function closeModalServicio() { document.getElementById('modalServicio').style.display = 'none'; }
</script>
</body>
</html>