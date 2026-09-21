<?php
/**
 * public/dashboard.php
 *
 * PÁGINA PROVISIONAL de la Fase 2: solo sirve para comprobar que el login
 * y la protección de páginas funcionan. La Fase 5 la sustituirá por el
 * dashboard real con el listado de Pokémon.
 */

require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

iniciar_sesion_segura();
require_login();   // sin sesión -> redirige al login

$titulo_pagina = 'Mi Pokédex';
require __DIR__ . '/../includes/cabecera.php';
?>

<section class="tarjeta">
    <h1>¡Hola, <?= h(nombre_usuario_actual()) ?>!</h1>
    <p>Has iniciado sesión correctamente. Aquí estará tu Pokédex personal.</p>
    <p class="texto-suave">Página provisional de la Fase 2: se sustituirá en la Fase 5.</p>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>
