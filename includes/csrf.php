<?php
/**
 * includes/csrf.php
 *
 * Protección CSRF: evita que otra web envíe formularios en nombre del
 * usuario sin que este lo sepa.
 *
 * Funcionamiento:
 *   1. Se genera un token aleatorio y se guarda en la sesión.
 *   2. Cada formulario POST lleva ese token en un campo oculto.
 *   3. Al recibir el formulario se comprueba que el token coincide.
 *
 * Uso en un formulario:   imprimir campo_csrf() dentro del <form>
 * Uso al procesarlo:      if (!verificar_csrf()) { ...error... }
 *
 * Requiere que la sesión esté iniciada (iniciar_sesion_segura()).
 */

require_once __DIR__ . '/funciones.php';

/**
 * Devuelve el token CSRF de la sesión actual (lo crea si no existe).
 */
function token_csrf(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Devuelve el <input> oculto con el token, listo para pegar en un formulario.
 */
function campo_csrf(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(token_csrf()) . '">';
}

/**
 * Comprueba que el token recibido por POST coincide con el de la sesión.
 * hash_equals() compara en tiempo constante (evita ataques por temporización).
 */
function verificar_csrf(): bool
{
    $enviado  = $_POST['csrf_token'] ?? '';
    $guardado = $_SESSION['csrf_token'] ?? '';

    return is_string($enviado)
        && is_string($guardado)
        && $guardado !== ''
        && hash_equals($guardado, $enviado);
}
