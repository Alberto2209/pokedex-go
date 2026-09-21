<?php
/**
 * public/login.php
 *
 * Inicio de sesión con email y contraseña.
 * Flujo: CSRF -> validar formato -> autenticar() -> iniciar sesión -> dashboard.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validaciones.php';
require_once __DIR__ . '/../includes/usuarios_repo.php';

iniciar_sesion_segura();
require_invitado();   // si ya hay sesión, va directo al dashboard

// logout.php redirige aquí con ?salida=1 para mostrar el aviso de despedida.
if (($_GET['salida'] ?? '') === '1') {
    poner_flash('info', 'Has cerrado sesión correctamente.');
}

$errores = [];
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim(post_texto('email')));
    $password = post_texto('password');

    if (!verificar_csrf()) {
        $errores['general'] = 'El formulario ha caducado. Inténtalo de nuevo.';
    } else {
        $errores = validar_login($email, $password);

        if (empty($errores)) {
            $usuario = autenticar(obtener_conexion(), $email, $password);

            if ($usuario === null) {
                // Mensaje genérico: no se revela si falló el email o la contraseña.
                $errores['general'] = 'Email o contraseña incorrectos.';
            } else {
                iniciar_sesion_usuario((int) $usuario['id'], $usuario['nombre_usuario']);
                redirigir('dashboard.php');
            }
        }
    }
}

$titulo_pagina = 'Iniciar sesión';
require __DIR__ . '/../includes/cabecera.php';
?>

<section class="tarjeta tarjeta-formulario">
    <h1>Iniciar sesión</h1>

    <?php if (isset($errores['general'])): ?>
        <div class="alerta alerta-error" role="alert"><?= h($errores['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= h(url('login.php')) ?>">
        <?= campo_csrf() ?>

        <div class="campo">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= h($email) ?>"
                   maxlength="<?= EMAIL_LONGITUD_MAX ?>"
                   autocomplete="email" required
                   <?= isset($errores['email']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errores['email'])): ?>
                <p class="error-campo"><?= h($errores['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password"
                   autocomplete="current-password" required
                   <?= isset($errores['password']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errores['password'])): ?>
                <p class="error-campo"><?= h($errores['password']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="boton boton-bloque">Entrar</button>
    </form>

    <p class="enlace-alterno">
        ¿No tienes cuenta? <a href="<?= h(url('registro.php')) ?>">Regístrate</a>
    </p>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>
