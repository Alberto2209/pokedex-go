<?php
/**
 * includes/validaciones.php
 *
 * Validación de los datos recibidos en registro y login.
 * Cada función devuelve un array de errores indexado por campo:
 *     []                                    -> todo correcto
 *     ['email' => 'Introduce un email...']  -> hay errores
 *
 * La validación del navegador (required, minlength...) es solo una ayuda
 * para el usuario: la que cuenta de verdad es esta, en el servidor.
 */

// Reglas (fáciles de ajustar). Las usan también los formularios en sus atributos HTML.
const NOMBRE_USUARIO_MIN     = 3;
const NOMBRE_USUARIO_MAX     = 30;
const EMAIL_LONGITUD_MAX     = 100;
const PASSWORD_LONGITUD_MIN  = 8;
const PASSWORD_LONGITUD_MAX  = 72;   // bcrypt ignora lo que pase de 72 bytes

/**
 * Valida los datos del formulario de registro.
 * $nombre y $email deben llegar ya con trim() (y el email en minúsculas).
 * Las contraseñas NO se recortan: los espacios cuentan como parte de ellas.
 */
function validar_registro(string $nombre, string $email, string $password, string $password2): array
{
    $errores = [];

    // Nombre de usuario
    if ($nombre === '') {
        $errores['nombre_usuario'] = 'Escribe un nombre de usuario.';
    } elseif (strlen($nombre) < NOMBRE_USUARIO_MIN || strlen($nombre) > NOMBRE_USUARIO_MAX) {
        $errores['nombre_usuario'] = 'El nombre de usuario debe tener entre '
            . NOMBRE_USUARIO_MIN . ' y ' . NOMBRE_USUARIO_MAX . ' caracteres.';
    } elseif (!preg_match('/^[A-Za-z0-9_]+$/', $nombre)) {
        $errores['nombre_usuario'] = 'Solo puede contener letras, números y guion bajo (_).';
    }

    // Email
    if ($email === '') {
        $errores['email'] = 'Escribe tu email.';
    } elseif (strlen($email) > EMAIL_LONGITUD_MAX) {
        $errores['email'] = 'El email no puede superar los ' . EMAIL_LONGITUD_MAX . ' caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Introduce un email válido.';
    }

    // Contraseña
    if ($password === '') {
        $errores['password'] = 'Escribe una contraseña.';
    } elseif (strlen($password) < PASSWORD_LONGITUD_MIN) {
        $errores['password'] = 'La contraseña debe tener al menos ' . PASSWORD_LONGITUD_MIN . ' caracteres.';
    } elseif (strlen($password) > PASSWORD_LONGITUD_MAX) {
        $errores['password'] = 'La contraseña no puede superar los ' . PASSWORD_LONGITUD_MAX . ' caracteres.';
    }

    // Confirmación (solo se comprueba si la contraseña en sí es válida)
    if (!isset($errores['password']) && $password !== $password2) {
        $errores['password2'] = 'Las contraseñas no coinciden.';
    }

    return $errores;
}

/**
 * Valida los datos del formulario de login.
 * Solo comprueba el formato; si el email existe o la contraseña es
 * correcta lo decide autenticar() en includes/auth.php.
 */
function validar_login(string $email, string $password): array
{
    $errores = [];

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Introduce un email válido.';
    }

    if ($password === '') {
        $errores['password'] = 'Escribe tu contraseña.';
    }

    return $errores;
}
