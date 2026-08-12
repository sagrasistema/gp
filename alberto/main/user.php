<?php
/**
 * Guardián de Sesión - Verifica la autenticación del usuario
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no existe la variable de sesión del usuario, redirigir al login
?>