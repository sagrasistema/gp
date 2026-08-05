<?php
// main/layout_header.php

// Definimos la ruta base del logo dependiendo de dónde se llame el archivo
// (Si es el index principal usa 'client/logo.png', si es un submódulo usa '../main/logo.png' o similar)
$logoPath = isset($customLogoPath) ? $customLogoPath : '../main/logo.png';
$homePath = isset($customHomePath) ? $customHomePath : '../index.php';
$acPath   = isset($customAcPath) ? $customAcPath : 'index.php';

// Detectar qué botón del Sidebar debe estar activo
$activeTab = isset($currentTab) ? $currentTab : '';

// Asegurar que la sesión esté iniciada si no lo está previamente en el bootstrap global
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Obtener y sanitizar el nombre de usuario autenticado
// Adapta la clave 'user_name' a la clave exacta que usas al procesar el login (ej. 'nombre', 'usuario', etc.)
$rawUserName = $_SESSION['nombre_completo'] 
    ?? $_SESSION['username'] 
    ?? 'Usuario';

$userNameClean = htmlspecialchars((string)$rawUserName, ENT_QUOTES, 'UTF-8');

?>

<header class="main-navbar">
    <div class="navbar-left">
        <div class="navbar-logo-container">
            <img src="<?php echo $logoPath; ?>" alt="SAGRA" class="main-system-logo" onclick="window.location.href='<?php echo $homePath; ?>'">
        </div>
        <span class="navbar-title">SAGRAGP VERSION 2.0</span>
    </div>
    
   <div class="navbar-right">
    <span class="user-name-text"><?= $userNameClean ?></span>
    <i class="ri-user-line user-avatar"></i>
    <button id="toggle-sidebar-btn" class="btn-toggle">
        <i class="ri-menu-line"></i>
    </button>
</div>
</header>

<div class="app-body">
    <aside class="main-sidebar">
        <nav class="sidebar-menu">
            <a href="<?php echo $homePath; ?>" class="menu-item <?php echo ($activeTab === 'inicio') ? 'active' : ''; ?>">
                <i class="ri-home-4-line"></i>
                <span>Inicio</span>
            </a>
            <a href="<?php echo $acPath; ?>" class="menu-item <?php echo ($activeTab === 'aceptacion') ? 'active' : ''; ?>">
                <i class="ri-shield-check-line"></i>
                <span>Aceptación</span>
            </a>
            <a href="<?php echo $acPath; ?>" class="menu-item <?php echo ($activeTab === 'terminos') ? 'active' : ''; ?>">
                <i class="ri-file-text-line"></i>
                <span>Terminos y Condiciones</span>
            </a>
            <a href="<?php echo $acPath; ?>" class="menu-item <?php echo ($activeTab === 'proyecto') ? 'active' : ''; ?>">
                <i class="ri-briefcase-line"></i>
                <span>Proyectos</span>
            </a>
            <a href="#" class="menu-item style-disabled">
                <i class="ri-customer-service-2-line"></i>
                <span>Soporte IT</span>
            </a>
        </nav>
    </aside>
    
    <main class="main-content">