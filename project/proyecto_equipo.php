<?php
/**
 * Módulo de Asignación de Usuarios y Roles por Proyecto - SAGRAGP
 * PHP 8.x + PDO
 */


require_once 'main/config';

if (!isset($pdo) || !$pdo instanceof PDO) {
    error_log('Error crítico: La variable $pdo no está inicializada.');
    exit('Error del sistema: No hay conexión con la base de datos.');
}

// Obtener y validar el ID del proyecto
$proyecto_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$proyecto_id) {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$tipo_alerta = '';

// Procesar el formulario de asignación o eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'asignar') {
        $usuario_id = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT);
        $rol_proyecto = trim($_POST['rol_proyecto'] ?? '');

        if ($usuario_id && !empty($rol_proyecto)) {
            try {
                // Insertar o actualizar el rol si ya existe en el proyecto
                $stmt = $pdo->prepare('
                    INSERT INTO proyecto_usuarios (proyecto_id, usuario_id, rol_proyecto) 
                    VALUES (:proyecto_id, :usuario_id, :rol_proyecto)
                    ON DUPLICATE KEY UPDATE rol_proyecto = :rol_proyecto_upd
                ');
                $stmt->execute([
                    'proyecto_id' => $proyecto_id,
                    'usuario_id' => $usuario_id,
                    'rol_proyecto' => $rol_proyecto,
                    'rol_proyecto_upd' => $rol_proyecto
                ]);
                $mensaje = 'Usuario asignado correctamente al proyecto.';
                $tipo_alerta = 'success';
            } catch (PDOException $e) {
                error_log('Error al asignar usuario al proyecto: ' . $e->getMessage());
                $mensaje = 'Ocurrió un error al procesar la asignación.';
                $tipo_alerta = 'error';
            }
        } else {
            $mensaje = 'Debe seleccionar un usuario y definir un rol válido.';
            $tipo_alerta = 'error';
        }
    } elseif ($accion === 'remover') {
        $asignacion_id = filter_input(INPUT_POST, 'asignacion_id', FILTER_VALIDATE_INT);
        if ($asignacion_id) {
            try {
                $stmt = $pdo->prepare('DELETE FROM proyecto_usuarios WHERE id = :id AND proyecto_id = :proyecto_id');
                $stmt->execute([
                    'id' => $asignacion_id,
                    'proyecto_id' => $proyecto_id
                ]);
                $mensaje = 'Usuario removido del proyecto exitosamente.';
                $tipo_alerta = 'success';
            } catch (PDOException $e) {
                error_log('Error al remover usuario del proyecto: ' . $e->getMessage());
                $mensaje = 'Ocurrió un error al eliminar la asignación.';
                $tipo_alerta = 'error';
            }
        }
    }
}

try {
    // Obtener información del proyecto (Asegúrate de que la tabla de proyectos se llame 'proyectos')
    $stmtProyecto = $pdo->prepare('SELECT * FROM proyectos WHERE id = :id LIMIT 1');
    $stmtProyecto->execute(['id' => $proyecto_id]);
    $proyecto = $stmtProyecto->fetch(PDO::FETCH_ASSOC);

    if (!$proyecto) {
        header('Location: index.php');
        exit;
    }

    // Obtener lista general de usuarios para el selector
    $stmtUsuarios = $pdo->query('SELECT id, nombre_completo, username FROM usuarios ORDER BY nombre_completo ASC');
    $listaUsuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

    // Obtener el equipo actual asignado a este proyecto
    $stmtEquipo = $pdo->prepare('
        ，pu.id as asignacion_id, u.nombre_completo, u.username, pu.rol_proyecto, pu.fecha_asignacion 
        FROM proyecto_usuarios pu
        JOIN usuarios u ON pu.usuario_id = u.id
        WHERE pu.proyecto_id = :proyecto_id
        ORDER BY pu.fecha_asignacion DESC
    ');
    // Corrección limpia del query anterior para evitar problemas tipográficos:
    $stmtEquipo = $pdo->prepare('
        SELECT pu.id as asignacion_id, u.nombre_completo, u.username, pu.rol_proyecto, pu.fecha_asignacion 
        FROM proyecto_usuarios pu
        JOIN usuarios u ON pu.usuario_id = u.id
        WHERE pu.proyecto_id = :proyecto_id
        ORDER BY pu.fecha_asignacion DESC
    ');
    $stmtEquipo->execute(['proyecto_id' => $proyecto_id]);
    $equipoActual = $stmtEquipo->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error en consulta de proyecto_equipo.php: ' . $e->getMessage());
    exit('Error crítico al cargar la información del proyecto.');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipo - <?php echo htmlspecialchars($proyecto['nombre'] ?? 'Proyecto', ENT_QUOTES, 'UTF-8'); ?> - SAGRAGP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --bg-dark: #0f0f11;
            --bg-form: #16161a;
            --bg-card: #1c1c21;
            --accent-cian: #00bcd4;
            --accent-glow: rgba(0, 188, 212, 0.15);
            --text-main: #ffffff;
            --text-muted: #8e9297;
            --border-color: #2a2b2f;
            --success-color: #10b981;
            --error-color: #ef4444;
            --font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font-family); background-color: var(--bg-dark); color: var(--text-main); padding: 30px; }

        .container { max-width: 1000px; margin: 0 auto; }
        
        .header-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .back-link { color: var(--accent-cian); text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        .back-link:hover { text-decoration: underline; }

        .card { background-color: var(--bg-form); border: 1px solid var(--border-color); border-radius: 10px; padding: 25px; margin-bottom: 25px; }
        .card h2 { font-size: 20px; margin-bottom: 15px; color: var(--text-main); display: flex; align-items: center; gap: 10px; }

        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background-color: rgba(16, 185, 129, 0.15); border: 1px solid var(--success-color); color: var(--success-color); }
        .alert-error { background-color: rgba(239, 68, 68, 0.15); border: 1px solid var(--error-color); color: var(--error-color); }

        .form-grid { display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 15px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; }
        .form-label { font-size: 12px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; }
        .form-control { padding: 12px; background-color: var(--bg-dark); border: 1.5px solid var(--border-color); border-radius: 6px; color: var(--text-main); font-size: 14px; }
        .form-control:focus { outline: none; border-color: var(--accent-cian); box-shadow: 0 0 0 3px var(--accent-glow); }

        .btn { padding: 12px 20px; background-color: var(--accent-cian); color: #121212; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background-color: #00acc1; }
        .btn-danger { background-color: rgba(239, 68, 68, 0.2); color: var(--error-color); border: 1px solid var(--error-color); padding: 6px 12px; font-size: 13px; }
        .btn-danger:hover { background-color: var(--error-color); color: #ffffff; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 12px; }
        tr:hover { background-color: rgba(255, 255, 255, 0.01); }
    </style>
</head>
<body>

    <div class="container">
        <div class="header-nav">
            <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Volver a Proyectos</a>
            <h1>Proyecto: <?php echo htmlspecialchars($proyecto['nombre'] ?? 'Sin Nombre', ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_alerta; ?>">
                <i class="fas fa-<?php echo $tipo_alerta === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulario de Asignación -->
        <div class="card">
            <h2><i class="fas fa-user-plus" style="color: var(--accent-cian);"></i> Asignar Usuario al Proyecto</h2>
            <form action="proyecto_equipo.php?id=<?php echo $proyecto_id; ?>" method="POST">
                <input type="hidden" name="accion" value="asignar">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="usuario_id" class="form-label">Seleccionar Usuario</label>
                        <select name="usuario_id" id="usuario_id" class="form-control" required>
                            <option value="">-- Seleccione --</option>
                            <?php foreach ($listaUsuarios as $usr): ?>
                                <option value="<?php echo $usr['id']; ?>">
                                    <?php echo htmlspecialchars($usr['nombre_completo'] . ' (' . $usr['username'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="rol_proyecto" class="form-label">Rol en el Proyecto</label>
                        <input type="text" id="rol_proyecto" name="rol_proyecto" class="form-control" placeholder="Ej. Auditor Líder, Revisor, Administrador" required>
                    </div>

                    <button type="submit" class="btn">Asignar Rol</button>
                </div>
            </form>
        </div>

        <!-- Tabla de Equipo Actual -->
        <div class="card">
            <h2><i class="fas fa-users" style="color: var(--accent-cian);"></i> Equipo Asignado al Proyecto</h2>
            <?php if (empty($equipoActual)): ?>
                <p style="color: var(--text-muted); font-size: 14px; padding: 10px 0;">No hay usuarios asignados a este proyecto todavía.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Usuario</th>
                            <th>Rol en el Proyecto</th>
                            <th>Fecha de Asignación</th>
                            <th style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipoActual as $miembro): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($miembro['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($miembro['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span style="color: var(--accent-cian); font-weight: 500;"><?php echo htmlspecialchars($miembro['rol_proyecto'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($miembro['fecha_asignacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="text-align: right;">
                                    <form action="proyecto_equipo.php?id=<?php echo $proyecto_id; ?>" method="POST" style="display:inline;" onsubmit="return confirm('¿Está seguro de remover a este usuario del proyecto?');">
                                        <input type="hidden" name="accion" value="remover">
                                        <input type="hidden" name="asignacion_id" value="<?php echo $miembro['asignacion_id']; ?>">
                                        <button type="submit" class="btn-danger"><i class="fas fa-trash-alt"></i> Remover</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>