<?php
declare(strict_types=1);
// 1. Iniciar sesión obligatoriamente antes de procesar lógica o incluir layouts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Validar autenticación de usuario
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
$pageTitle = "Gestión de Usuarios - Panel de Control";
include '../main/h.php'; 
include '../main/config.php'; 


try {
    // Consulta optimizada con LEFT JOIN para obtener los clientes asignados a cada usuarioss
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.nombre_completo,
            u.rol,
            u.created_at,
            GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ') AS clientes_permitidos,
            COUNT(uc.cliente_id) AS total_clientes
        FROM usuarios u
        LEFT JOIN usuario_clientes uc ON uc.usuario_id = u.id
        LEFT JOIN clientes c ON c.id = uc.cliente_id
        GROUP BY u.id
        ORDER BY u.id DESC
    ";
    
    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
    error_log("Error PDO en index.php de Usuarios: " . $e->getMessage());
    $error = "Error al obtener la lista de usuarios.";
    $usuarios = [];
}
$customLogoPath = '../main/logo.png';
$customHomePath = '../index.php';
$customAcPath   = '../ac/index.php';
$currentTab     = 'usuarios'; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Panel de Control</title>
        
    <link rel="shortcut icon" href="../client/favicon.ico" type="image/x-icon">
    <link rel="icon" href="../client/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <link rel="stylesheet" href="main/layout.css">
    <style>
        :root { --primary: #0284c7; --bg-main: #f8fafc; --border: #e2e8f0; --text: #0f172a; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg-main); color: var(--text); padding: 2rem; margin: 0; }
        .container { background: #ffffff; border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem; }
        .table th, .table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); }
        .table th { background: #f1f5f9; font-weight: 700; color: #475569; }
        .badge { background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 600; font-size: 0.75rem; display: inline-block; }
        .badge-admin { background: #fef3c7; color: #92400e; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; border: none; cursor: pointer; }
        .btn-primary { background: var(--primary); color: #ffffff; }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
        .alert { padding: 0.85rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.875rem; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
<?php 
// Incluimos la cabecera del layout visual después de validar la sesión
include '../main/layout_header.php'; 
?>
<div class="container">
    <div class="header">
        <h1 style="font-size: 1.35rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <i class="ri-user-shared-line" style="color: var(--primary);"></i> Gestión de Usuarios y Permisos
        </h1>
        <a href="nuevo.php" class="btn btn-primary">
            <i class="ri-user-add-line"></i> Crear Usuario
        </a>
    </div>

    <?php if (isset($_SESSION['exito'])): ?>
        <div class="alert alert-success"><i class="ri-checkbox-circle-line"></i> <?= htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><i class="ri-error-warning-line"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre Completo</th>
                <th>Rol</th>
                <th>Clientes Asignados</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= (int)$u->id ?></td>
                        <td><strong><?= htmlspecialchars($u->username, ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($u->nombre_completo, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= strtolower($u->rol) === 'admin' ? 'badge-admin' : '' ?>">
                                <?= htmlspecialchars(strtoupper($u->rol), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <?php if (strtolower($u->rol) === 'admin'): ?>
                                <small style="color: #059669; font-weight: 600;"><i class="ri-shield-check-line"></i> Acceso Total (Admin)</small>
                            <?php elseif (!empty($u->clientes_permitidos)): ?>
                                <span style="font-size: 0.85rem; color: #334155;" title="<?= htmlspecialchars($u->clientes_permitidos, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="ri-building-line" style="color: var(--primary);"></i> 
                                    <?= (int)$u->total_clientes ?> cliente(s) asignados
                                </span>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-style: italic;">Sin asignación</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="editar.php?id=<?= (int)$u->id ?>" class="btn btn-primary btn-sm">
                                <i class="ri-edit-line"></i> Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">No se encontraron usuarios registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include '../main/layout_footer.php'; ?>
</body>
</html>