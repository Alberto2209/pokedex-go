<?php
/**
 * public/index.php
 *
 * Punto de entrada de la aplicación: no muestra nada, solo redirige.
 *   - Con sesión iniciada   -> dashboard
 *   - Sin sesión            -> login
 */

require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';

iniciar_sesion_segura();

if (esta_logueado()) {
    redirigir('dashboard.php');
}

redirigir('login.php');
