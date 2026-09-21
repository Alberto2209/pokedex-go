<?php
/**
 * public/registro.php
 *
 * Registro de usuarios nuevos.
 * Flujo: CSRF -> validar datos -> comprobar duplicados -> password_hash()
 *        -> INSERT -> redirigir al login con un aviso.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validaciones.php';
require_once __DIR__ . '/../includes/usuarios_repo.php';

iniciar_sesion_segura();
require_invitado();   // si ya hay sesión, no se puede volver a registrar

$errores        = [];
$nombre_usuario = '';
$email          = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = trim(post_texto('nombre_usuario'));
    $email          = strtolower(trim(post_texto('email')));
    $password       = post_texto('password');
    $password2      = post_texto('password2');

    if (!verificar_csrf()) {
        $errores['general'] = 'El formulario ha caducado. Inténtalo de nuevo.';
    } else {
        $errores = validar_registro($nombre_usuario, $email, $password, $password2);

        if (empty($errores)) {
            try {
                $pdo = obtener_conexion();

                if (existe_nombre_usuario($pdo, $nombre_usuario)) {
                    $errores['nombre_usuario'] = 'Ese nombre de usuario ya está en uso.';
                }
                if (existe_email($pdo, $email)) {
                    $errores['email'] = 'Ya existe una cuenta con ese email.';
                }

                if (empty($errores)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    crear_usuario($pdo, $nombre_usuario, $email, $hash);

                    poner_flash('exito', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
                    redirigir('login.php');
                }
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    // Clave duplicada: alguien se registró con esos datos justo ahora.
                    $errores['general'] = 'Ese nombre de usuario o email ya está registrado.';
                } else {
                    error_log('[PokéDex GO] Error al registrar usuario: ' . $e->getMessage());
                    $errores['general'] = 'No se pudo crear la cuenta. Inténtalo de nuevo más tarde.';
                }
            }
        }
    }
}

$titulo_pagina = 'Crear cuenta';
require __DIR__ . '/../includes/cabecera.php';
?>

<section class="tarjeta tarjeta-formulario">
    <h1>Crear cuenta</h1>

    <?php if (isset($errores['general'])): ?>
        <div class="alerta alerta-error" role="alert"><?= h($errores['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= h(url('registro.php')) ?>">
        <?= campo_csrf() ?>

        <div class="campo">
            <label for="nombre_usuario">Nombre de usuario</label>
            <input type="text" id="nombre_usuario" name="nombre_usuario"
                   value="<?= h($nombre_usuario) ?>"
                   minlength="<?= NOMBRE_USUARIO_MIN ?>" maxlength="<?= NOMBRE_USUARIO_MAX ?>"
                   pattern="[A-Za-z0-9_]+" title="Solo letras, números y guion bajo"
                   autocomplete="username" required
                   <?= isset($errores['nombre_usuario']) ? 'aria-invalid="true"' : '' ?>>
            <p class="ayuda">De <?= NOMBRE_USUARIO_MIN ?> a <?= NOMBRE_USUARIO_MAX ?> caracteres: letras, números y guion bajo.</p>
            <?php if (isset($errores['nombre_usuario'])): ?>
                <p class="error-campo"><?= h($errores['nombre_usuario']) ?></p>
            <?php endif; ?>
        </div>

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
                   minlength="<?= PASSWORD_LONGITUD_MIN ?>" maxlength="<?= PASSWORD_LONGITUD_MAX ?>"
                   autocomplete="new-password" required
                   <?= isset($errores['password']) ? 'aria-invalid="true"' : '' ?>>
            <p class="ayuda">Mínimo <?= PASSWORD_LONGITUD_MIN ?> caracteres.</p>
            <?php if (isset($errores['password'])): ?>
                <p class="error-campo"><?= h($errores['password']) ?></p>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="password2">Repite la contraseña</label>
            <input type="password" id="password2" name="password2"
                   maxlength="<?= PASSWORD_LONGITUD_MAX ?>"
                   autocomplete="new-password" required
                   <?= isset($errores['password2']) ? 'aria-invalid="true"' : '' ?>>
            <?php if (isset($errores['password2'])): ?>
                <p class="error-campo"><?= h($errores['password2']) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="boton boton-bloque">Crear cuenta</button>
    </form>

    <p class="enlace-alterno">
        ¿Ya tienes cuenta? <a href="<?= h(url('login.php')) ?>">Inicia sesión</a>
    </p>
</section>

<?php require __DIR__ . '/../includes/pie.php'; ?>
