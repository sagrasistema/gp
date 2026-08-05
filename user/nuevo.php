<?php
declare(strict_types=1);

require_once __DIR__ . '../main/config.php';
session_start();

// PROCESAMIENTO DEL FORMULARIO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username       = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $nombreCompleto = trim(filter_input(INPUT_POST, 'nombre_completo', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
    $rol            = trim(filter_input(INPUT_POST, 'rol', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'auditor');
    $password       = $_POST['password'] ?? '';
    $clientesInput  = $_POST['clientes'] ?? [];

    $clientesPermitidos = is_array($clientesInput) 
        ? array_map('intval', array_filter($clientesInput, 'is_numeric')) 
        : [];

    if (empty($username) || empty($nombreCompleto) || empty($password)) {
        $_SESSION['error'] = 'Todos los campos requeridos deben ser completados.';
        header('Location: nuevo.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Validar que el username no esté duplicado
        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE username = :usr");
        $stmtCheck->execute([':usr' => $username]);
        if ($stmtCheck->fetch()) {
            throw new Exception("El nombre de usuario '{$username}' ya está registrado.");
        }

        // 2. Insertar usuario
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmtUser = $pdo->prepare("
            INSERT INTO usuarios (username, password, nombre_completo, rol, clientId) 
            VALUES (:usr, :pass, :nombre, :rol, 0)
        ");
        $stmtUser->execute([
            ':usr'    => $username,
            ':pass'   => $passwordHash,
            ':nombre' => $nombreCompleto,
            ':rol'    => $rol
        ]);

        $newUserId = (int)$pdo->lastInsertId();

        // 3. Insertar relacion de clientes asignados
        if (!empty($clientesPermitidos) && $newUserId > 0) {
            $stmtPerm = $pdo->prepare("INSERT INTO usuario_clientes (usuario_id, cliente_id) VALUES (:uid, :cid)");
            foreach ($clientesPermitidos as $clienteId) {
                $stmtPerm->execute([':uid' => $newUserId, ':cid' => $clienteId]);
            }
        }

        $pdo->commit();
        $_SESSION['exito'] = 'Usuario creado con éxito.';
        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error en nuevo.php (Usuarios): " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header('Location: nuevo.php');
        exit;
    }
}

// OBTENER CATÁLOGO DE CLIENTES PARA LA VISTA (GET)
try {
    $stmtClientes = $pdo->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC");
    $listaClientes = $stmtClientes->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error cargando clientes en nuevo.php: " . $e->getMessage());
    $listaClientes = [];
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
    <title>Nuevo Usuario - Sistema de Auditoría</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        :root { --primary: #0284c7; --bg-main: #f8fafc; --border: #e2e8f0; --text: #0f172a; }
        body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg-main); color: var(--text); padding: 2rem; margin: 0; }
        .card { background: #ffffff; border: 1px solid var(--border); border-radius: 12px; max-width: 750px; margin: 0 auto; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-weight: 600; margin-bottom: 0.4rem; font-size: 0.875rem; }
        .form-control { width: 100%; padding: 0.6rem; border: 1px solid var(--border); border-radius: 6px; box-sizing: border-box; font-size: 0.9rem; }
        .clients-box { border: 1px solid var(--border); border-radius: 8px; padding: 1rem; max-height: 200px; overflow-y: auto; background: #f8fafc; }
        .checkbox-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0; font-size: 0.875rem; border-bottom: 1px solid #f1f5f9; }
        .btn-group { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; }
        .btn { padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 0.85rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.875rem; }
    </style>
</head>
<body>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="margin: 0; font-size: 1.25rem;"><i class="ri-user-add-line" style="color: var(--primary);"></i> Crear Nuevo Usuario</h2>
        <a href="index.php" class="btn btn-secondary"><i class="ri-arrow-left-line"></i> Cancelar</a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-danger"><i class="ri-error-warning-line"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="nuevo.php" method="POST">
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Nombre de Usuario (Login)</label>
                <input type="text" name="username" class="form-control" required placeholder="ej. jperez">
            </div>
            <div class="form-group">
                <label class="form-label">Nombre Completo</label>
                <input type="text" name="nombre_completo" class="form-control" required placeholder="ej. Juan Pérez">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Rol de Usuario</label>
                <select name="rol" class="form-control">
                    <option value="auditor">Auditor</option>
                    <option value="Revisor">Revisor</option>
                    <option value="socio">Socio / Gerente</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label"><i class="ri-building-line" style="color: var(--primary);"></i> Asignación de Clientes Permitidos</label>
            <div class="clients-box">
                <?php if (!empty($listaClientes)): ?>
                    <?php foreach ($listaClientes as $cli): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" name="clientes[]" id="cli_<?= (int)$cli->id ?>" value="<?= (int)$cli->id ?>">
                            <label for="cli_<?= (int)$cli->id ?>" style="cursor: pointer; width: 100%;">
                                <?= htmlspecialchars($cli->nombre, ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size: 0.85rem; color: #64748b; margin: 0;">No existen clientes registrados.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">Volver</a>
            <button type="submit" class="btn btn-primary"><i class="ri-save-line"></i> Guardar Usuario</button>
        </div>
    </form>
</div>

</body>
</html>