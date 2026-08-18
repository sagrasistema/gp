<?php
// main/layout_header.php
// 1. Asegurar la inicialización de la sesión global
if (session_status() === PHP_SESSION_NONE) {
    // Configuración recomendada para la cookie de sesión (disponible en todo el dominio)
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false, // Cambiar a true si usas HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
// 2. Obtención y sanitización estricta del nombre de usuario
$rawUserName   = $_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Usuario';
$nombreUsuario = htmlspecialchars((string)$rawUserName, ENT_QUOTES, 'UTF-8');

// 3. Obtención y casteo estricto del ID de Usuariox
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);

// 4. Obtención y sanitización del Rol de Usuario
$rawRole = $_SESSION['rol'] ?? $_SESSION['role'] ?? 'invitado';
$userRol = htmlspecialchars((string)$rawRole, ENT_QUOTES, 'UTF-8');

// Definimos la ruta base del logo dependiendo de dónde se llame el archivo
// (Si es el index principal usa 'client/logo.png', si es un submódulo usa '../main/logo.png' o similar)
$logoPath = isset($customLogoPath) ? $customLogoPath : '../main/logo.png';
$homePath = isset($customHomePath) ? $customHomePath : '../index.php';
$acPath   = isset($customAcPath) ? $customAcPath : 'index.php';

// Detectar qué botón del Sidebar debe estar activo
$activeTab = isset($currentTab) ? $currentTab : '';

// Asegurar que la sesión esté iniciada si no lo está previamente en el bootstrap global

// 1. Obtener y sanitizar el nombre de usuario autenticado
// Adapta la clave 'user_name' a la clave exacta que usas al procesar el login (ej. 'nombre', 'usuario', etc.)
/**
 * Obtiene los permisos de un usuario para un módulo específico en la tabla 'usuario_permisos'.
 * 
 * @param PDO $pdo Instancia de conexión PDO a la base de datos.
 * @param int $userId ID del usuario obtenido desde la sesión.
 * @param int $moduloId ID del módulo a consultar (ej. 1).
 * @return array Matriz asociativa con las banderas de permisos procesadas como booleanos.
 */
function obtenerPermisosModulo(PDO $pdo, int $userId, int $moduloId = 1): array
{
    // Estructura por defecto en caso de no existir registro
    $permisosPredeterminados = [
        'puede_acceder' => false,
        'puede_ver'     => false,
        'puede_crear'   => false,
        'puede_editar'  => false,
        'puede_eliminar' => false,
    ];

    if ($userId <= 0) {
        return $permisosPredeterminados;
    }

    try {
        $sql = "SELECT puede_acceder, puede_ver, puede_crear, puede_editar, puede_eliminar 
                FROM usuario_permisos 
                WHERE usuario_id = :usuario_id 
                  AND modulo_id = :modulo_id 
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $userId,
            ':modulo_id'  => $moduloId,
        ]);

        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            return $permisosPredeterminados;
        }

        // Cast explicito a bool para garantizar la compatibilidad con PHP 8.x
        return [
            'puede_acceder'  => (bool)$registro['puede_acceder'],
            'puede_ver'      => (bool)$registro['puede_ver'],
            'puede_crear'    => (bool)$registro['puede_crear'],
            'puede_editar'   => (bool)$registro['puede_editar'],
            'puede_eliminar' => (bool)$registro['puede_eliminar'],
        ];

    } catch (PDOException $e) {
        // Registrar error internamente sin exponérselo al usuario
        error_log("Error al consultar permisos de usuario [ID: {$userId}, Modulo: {$moduloId}]: " . $e->getMessage());
        return $permisosPredeterminados;
    }
}

// --------------------------------------------------------------------------
// EJEMPLO DE USO EN TU LÓGICA DE NEGOCIO
// --------------------------------------------------------------------------

// 1. Obtener y sanitizar el ID del usuario desde la sesión activa
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
$moduloId = 1; // Módulo deseado
$moduloId4 = 4; // Módulo deseado
$moduloId5 = 5; // Módulo deseado
$moduloId6 = 6; // Módulo deseado
$moduloId7 = 7; // Módulo deseado

// proyecto
$permisosModulo1 = obtenerPermisosModulo($pdo, $userId, $moduloId);
// Cliente
$permisosModulo4 = obtenerPermisosModulo($pdo, $userId, $moduloId4);
// aceptacion y continuidad
$permisosModulo5 = obtenerPermisosModulo($pdo, $userId, $moduloId5);
// terminos y condiciones
$permisosModulo6 = obtenerPermisosModulo($pdo, $userId, $moduloId6);
// permisos 
$permisosModulo7 = obtenerPermisosModulo($pdo, $userId, $moduloId7);
?>

<header class="main-navbar">
    <div class="navbar-left">
        <div class="navbar-logo-container">
            <img src="<?php echo $logoPath; ?>" alt="SAGRA" class="main-system-logo" onclick="window.location.href='<?php echo $homePath; ?>'">
        </div>
        <span class="navbar-title">SAGRAGP VERSION 2.0</span>
    </div>
    
   <div class="navbar-right">
    <span class="user-name-text"><?= $nombreUsuario ?>  </span>
    <i class="ri-user-line user-avatar"></i>
    <button id="toggle-sidebar-btn" class="btn-toggle">
        <i class="ri-menu-line"></i>
    </button>
</div>
</header>

<div class="app-body">
    <aside class="main-sidebar">
        <nav class="sidebar-menu">
            <?php if ($activeTab === 'inicio') {?>
            <a href="<?php echo $homePath; ?>" class="menu-item <?php echo ($activeTab === 'inicio') ? 'active' : ''; ?>">
                <i class="ri-home-4-line"></i>
                <span>Inicio</span>
            </a>

            <?php if ($permisosModulo4['puede_acceder'] == 1) {?>
                <?php if ($permisosModulo4['puede_ver'] == 1) {?>
                <a href="client/index.php" class="menu-item <?php echo ($activeTab === 'clientes') ? 'active' : ''; ?>">
                <?php } else {?>
                <a href="#" class="menu-item <?php echo ($activeTab === 'clientes') ? 'active' : ''; ?>">
                <?php } ?>
                    <i class="ri-user-line"></i>
                    <span>Clientes</span>
                </a>
            <?php }?>
            
            <?php if ($permisosModulo5['puede_acceder'] == 1) {?>
                <?php if ($permisosModulo5['puede_ver'] == 1) {?>
                    <a href="ac/index.php" class="menu-item <?php echo ($activeTab === 'aceptacion') ? 'active' : ''; ?>">
                <?php } else {?>
                    <a href="#" class="menu-item <?php echo ($activeTab === 'aceptacion') ? 'active' : ''; ?>">
                <?php } ?>
                    <i class="ri-shield-check-line"></i>
                    <span>Aceptación</span>
                </a>
            <?php }?>
            
            <?php if ($permisosModulo6['puede_acceder'] == 1) {?>
                <?php if ($permisosModulo6['puede_ver'] == 1) {?>
                    <a href="terminos/index.php" class="menu-item <?php echo ($activeTab === 'terminos') ? 'active' : ''; ?>">
                <?php } else {?>
                    <a href="#" class="menu-item <?php echo ($activeTab === 'terminos') ? 'active' : ''; ?>">
                <?php } ?>
                    <i class="ri-file-text-line"></i>
                    <span>Terminos y Condiciones</span>
                </a>
            <?php }?>

            <?php if ($permisosModulo1['puede_acceder'] == 1) {?>
                <?php if ($permisosModulo1['puede_ver'] == 1) {?>
                    <a href="project/index.php" class="menu-item <?php echo ($activeTab === 'proyecto') ? 'active' : ''; ?>">
                <?php } else {?>
                    <a href="#" class="menu-item <?php echo ($activeTab === 'proyecto') ? 'active' : ''; ?>">
                <?php } ?>
                    <i class="ri-briefcase-line"></i>
                    <span>Proyectos</span>
                </a>
            <?php }?>
            
    
            <?php } else  {?>
            <a href="<?php echo $homePath; ?>" class="menu-item <?php echo ($activeTab === 'inicio') ? 'active' : ''; ?>">
                <i class="ri-home-4-line"></i>
                <span>Inicio</span>
            </a>
            
            <?php if ($permisosModulo4['puede_acceder'] == 1) {?>
                <?php if ($permisosModulo4['puede_ver'] == 1) {?>
                <a href="../client/index.php" class="menu-item <?php echo ($activeTab === 'clientes') ? 'active' : ''; ?>">
                <?php } else {?>
                <a href="#" class="menu-item <?php echo ($activeTab === 'clientes') ? 'active' : ''; ?>">
                <?php } ?>
                    <i class="ri-user-line"></i>
                    <span>Clientes</span>
                </a>
            <?php }?>
            
            <?php if ($permisosModulo5['puede_acceder'] == 1) {?>
                <?php if ($permisosModulo5['puede_ver'] == 1) {?>
                    <a href="../ac/index.php" class="menu-item <?php echo ($activeTab === 'aceptacion') ? 'active' : ''; ?>">
                <?php } else {?>
                    <a href="#" class="menu-item <?php echo ($activeTab === 'aceptacion') ? 'active' : ''; ?>">
                <?php } ?>
                    <i class="ri-shield-check-line"></i>
                    <span>Aceptación</span>
                </a>
            <?php }?>

            <?php if ($permisosModulo6['puede_acceder'] == 1) {?>
                <?php if ($permisosModulo6['puede_ver'] == 1) {?>
                    <a href="../terminos/index.php" class="menu-item <?php echo ($activeTab === 'terminos') ? 'active' : ''; ?>">
                <?php } else {?>
                    <a href="#" class="menu-item <?php echo ($activeTab === 'terminos') ? 'active' : ''; ?>">
                <?php } ?>
                    <i class="ri-file-text-line"></i>
                    <span>Terminos y Condiciones</span>
                </a>
            <?php }?>
            
            <?php if ($permisosModulo1['puede_acceder'] == 1) {?>
                <?php if ($permisosModulo1['puede_ver'] == 1) {?>
                    <a href="../project/index.php" class="menu-item <?php echo ($activeTab === 'proyecto') ? 'active' : ''; ?>">
                <?php } else {?>
                    <a href="#" class="menu-item <?php echo ($activeTab === 'proyecto') ? 'active' : ''; ?>">
                <?php } ?>
                    <i class="ri-briefcase-line"></i>
                    <span>Proyectos</span>
                </a>
            <?php }?>
            
            <a href="#" class="menu-item style-disabled">
                <i class="ri-customer-service-2-line"></i>
                <span>Soporte IT</span>
            </a>
            <?php }?>
          

        </nav>
    </aside>
    
    <main class="main-content">