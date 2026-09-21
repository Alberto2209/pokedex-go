<?php
/**
 * includes/auth.php
 *
 * Sesiones PHP, login, logout y protección de páginas.
 *
 * REGLA DE ORO: el id del usuario que está usando la aplicación sale
 * SIEMPRE de la sesión (usuario_actual_id()), nunca de un formulario
 * ni de la URL. Así un usuario no puede pedir datos de otro.
 */

require_once __DIR__ . '/funciones.php';
require_once __DIR__ . '/usuarios_repo.php';

/**
 * Inicia la sesión PHP con una configuración segura.
 * Es seguro llamarla varias veces: si ya está iniciada, no hace nada.
 * Debe llamarse ANTES de enviar cualquier salida al navegador.
 */
function iniciar_sesion_segura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Nombre propio para no chocar con otras aplicaciones PHP de localhost.
    session_name('pokedex_sid');

    // Solo se aceptan ids de sesión que el servidor haya creado, y solo por cookie.
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    // "secure" se activa solo si la petición llega por HTTPS (en local no).
    $es_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,          // la cookie muere al cerrar el navegador
        'path'     => '/',
        'secure'   => $es_https,
        'httponly' => true,       // JavaScript no puede leer la cookie
        'samesite' => 'Lax',      // no se envía en peticiones POST desde otras webs
    ]);

    session_start();
}

/**
 * Indica si hay un usuario con sesión iniciada.
 */
function esta_logueado(): bool
{
    iniciar_sesion_segura();
    return isset($_SESSION['usuario_id']);
}

/**
 * Devuelve el id del usuario con sesión iniciada (0 si no hay ninguno).
 * Llamar solo después de require_login(). Un 0 nunca coincide con un
 * usuario real, así que en caso de error las consultas no devolverían nada.
 */
function usuario_actual_id(): int
{
    return (int) ($_SESSION['usuario_id'] ?? 0);
}

/**
 * Devuelve el nombre del usuario con sesión iniciada ('' si no hay).
 */
function nombre_usuario_actual(): string
{
    return (string) ($_SESSION['nombre_usuario'] ?? '');
}

/**
 * Comprueba email y contraseña.
 * Devuelve los datos del usuario si son correctos, o null si no lo son.
 */
function autenticar(PDO $pdo, string $email, string $password): ?array
{
    $usuario = buscar_usuario_por_email($pdo, $email);

    if ($usuario === null) {
        // Se hace un cálculo equivalente al de password_verify() para que
        // la respuesta tarde parecido exista o no el email (evita deducir
        // qué emails están registrados midiendo el tiempo).
        password_hash($password, PASSWORD_DEFAULT);
        return null;
    }

    if (!password_verify($password, $usuario['password_hash'])) {
        return null;
    }

    return $usuario;
}

/**
 * Marca al usuario como identificado en la sesión.
 * Se llama solo tras un login correcto.
 */
function iniciar_sesion_usuario(int $id, string $nombre_usuario): void
{
    // Nuevo id de sesión tras el login: evita la "fijación de sesión".
    session_regenerate_id(true);

    $_SESSION['usuario_id']     = $id;
    $_SESSION['nombre_usuario'] = $nombre_usuario;

    // Token CSRF nuevo para la sesión ya identificada.
    unset($_SESSION['csrf_token']);
}

/**
 * Cierra la sesión: vacía los datos, borra la cookie y destruye la sesión.
 */
function cerrar_sesion(): void
{
    $_SESSION = [];

    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $cookie['path'],
        'domain'   => $cookie['domain'],
        'secure'   => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);

    session_destroy();
}

/**
 * Protege una página privada: si no hay sesión, redirige al login.
 * Se pone al principio de cada página que requiera iniciar sesión.
 */
function require_login(): void
{
    if (!esta_logueado()) {
        poner_flash('error', 'Debes iniciar sesión para acceder a esa página.');
        redirigir('login.php');
    }
}

/**
 * Para login y registro: si ya hay sesión, no tiene sentido mostrarlos,
 * así que se redirige al dashboard.
 */
function require_invitado(): void
{
    if (esta_logueado()) {
        redirigir('dashboard.php');
    }
}
