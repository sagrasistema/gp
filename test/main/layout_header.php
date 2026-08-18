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