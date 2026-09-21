<?php
/**
 * includes/funciones.php
 *
 * Funciones auxiliares generales que usan todas las páginas:
 * escapar HTML, construir URLs, redirigir, leer campos de formularios
 * y mensajes flash (avisos que se muestran una sola vez).
 */

/**
 * Escapa un texto para mostrarlo de forma segura en HTML (evita XSS).
 * Todo dato que se imprima en una página debe pasar por h().
 */
function h($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Devuelve la ruta URL donde Apache publica la carpeta public/ (sin barra final).
 * Se lee de config.php (sección 'app' > 'url_base'). Si no existe, se usa '/pokedex-go'.
 */
function url_base(): string
{
    static $base = null;

    if ($base === null) {
        $base = '/pokedex-go';   // valor por defecto

        $ruta = __DIR__ . '/../config/config.php';
        if (is_file($ruta)) {
            $config = require $ruta;
            if (is_array($config)
                && isset($config['app']['url_base'])
                && is_string($config['app']['url_base'])) {
                $base = rtrim($config['app']['url_base'], '/');
            }
        }
    }

    return $base;
}

/**
 * Construye una URL de la aplicación a partir de una ruta dentro de public/.
 * Ejemplo: url('login.php')  ->  /pokedex-go/login.php
 */
function url(string $ruta = ''): string
{
    return url_base() . '/' . ltrim($ruta, '/');
}

/**
 * Redirige a otra página de la aplicación y detiene el script.
 * Solo se le pasan rutas escritas por nosotros, nunca datos del usuario.
 */
function redirigir(string $ruta): void
{
    header('Location: ' . url($ruta));
    exit;
}

/**
 * Devuelve el valor de un campo POST como texto.
 * Si no existe, o no es texto (p. ej. un atacante envía campo[]=x), devuelve ''.
 */
function post_texto(string $campo): string
{
    $valor = $_POST[$campo] ?? '';
    return is_string($valor) ? $valor : '';
}

/**
 * Guarda un mensaje flash en la sesión. Se mostrará en la siguiente página
 * que se cargue y después desaparece (patrón POST -> Redirect -> GET).
 * Tipos usados: 'exito', 'error', 'info'.
 */
function poner_flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

/**
 * Devuelve el mensaje flash pendiente (y lo elimina), o null si no hay.
 */
function obtener_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}
