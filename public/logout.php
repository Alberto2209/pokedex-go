<?php
/**
 * public/logout.php
 *
 * Cierra la sesión. Solo acepta peticiones POST con token CSRF válido
 * (el botón "Cerrar sesión" del menú es un formulario). Si alguien abre
 * esta URL escribiéndola en el navegador (GET), no ocurre nada.
 */

require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

iniciar_sesion_segura();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('index.php');
}

if (!verificar_csrf()) {
    poner_flash('error', 'No se pudo cerrar la sesión. Inténtalo de nuevo.');
    redirigir('index.php');
}

cerrar_sesion();
redirigir('login.php?salida=1');
