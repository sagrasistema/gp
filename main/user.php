<?php
/**
 * Guardián de Sesión - Verifica la autenticación del usuario
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no existe la variable de sesión del usuario, redirigir al login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}?>