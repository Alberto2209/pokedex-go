<?php
/**
 * includes/cabecera.php
 *
 * Parte superior común de todas las páginas: <head>, menú y avisos.
 * Se incluye DESPUÉS de procesar formularios y hacer redirecciones,
 * porque a partir de aquí ya se envía HTML al navegador.
 *
 * La página puede definir antes:  $titulo_pagina = 'Iniciar sesión';
 */

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

iniciar_sesion_segura();

$titulo_completo = (isset($titulo_pagina) ? $titulo_pagina . ' · ' : '') . 'PokéDex GO';
$flash           = obtener_flash();
$logueado        = esta_logueado();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Imprescindible para que la web se adapte al ancho de móviles y tablets -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($titulo_completo) ?></title>
    <link rel="stylesheet" href="<?= h(url('assets/css/estilos.css')) ?>">
</head>
<body>

<header class="cabecera-sitio">
    <div class="contenedor barra">
        <a class="marca" href="<?= h(url('index.php')) ?>">PokéDex GO</a>

        <nav class="navegacion" aria-label="Navegación principal">
            <?php if ($logueado): ?>
                <a href="<?= h(url('dashboard.php')) ?>">Mi Pokédex</a>
                <span class="usuario-actual"><?= h(nombre_usuario_actual()) ?></span>

                <!-- Cerrar sesión es un formulario POST con token CSRF -->
                <form class="form-logout" method="post" action="<?= h(url('logout.php')) ?>">
                    <?= campo_csrf() ?>
                    <button type="submit" class="boton-nav">Cerrar sesión</button>
                </form>
            <?php else: ?>
                <a href="<?= h(url('login.php')) ?>">Iniciar sesión</a>
                <a href="<?= h(url('registro.php')) ?>">Registrarse</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="contenedor contenido">
    <?php if ($flash !== null): ?>
        <div class="alerta alerta-<?= h($flash['tipo']) ?>" role="alert">
            <?= h($flash['texto']) ?>
        </div>
    <?php endif; ?>
