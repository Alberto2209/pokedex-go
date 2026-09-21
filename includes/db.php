<?php
/**
 * includes/db.php
 *
 * Conexión a la base de datos con PDO.
 *
 * Uso desde cualquier página:
 *
 *     require_once __DIR__ . '/../includes/db.php';   // ajusta la ruta según la carpeta
 *     $pdo = obtener_conexion();
 *     $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
 *     $stmt->execute([$email]);
 */

/**
 * Detiene la aplicación cuando hay un problema de configuración o de conexión.
 *
 * SEGURIDAD: el detalle técnico (que puede incluir usuario, host o rutas)
 * se guarda solo en el log del servidor. Al navegador llega un mensaje genérico.
 */
function error_fatal_bd(string $detalle_para_log): void
{
    error_log('[PokéDex GO] ' . $detalle_para_log);
    http_response_code(500);
    exit('Error interno del servidor. Revisa el log de errores de PHP/Apache para ver el detalle.');
}

/**
 * Carga config/config.php y devuelve solo la sección de la base de datos.
 * Comprueba que el archivo existe y que tiene todas las claves necesarias.
 */
function cargar_config(): array
{
    // __DIR__ es la carpeta de este archivo (includes/), así que la ruta
    // funciona sin importar desde qué página se cargue.
    $ruta = __DIR__ . '/../config/config.php';

    if (!is_file($ruta)) {
        error_fatal_bd('Falta config/config.php. Copia config/config.example.php como config.php y rellena tus datos.');
    }

    $config = require $ruta;

    if (!is_array($config) || !isset($config['db']) || !is_array($config['db'])) {
        error_fatal_bd('config/config.php no tiene el formato esperado (falta la sección "db").');
    }

    // array_key_exists (y no empty) porque la contraseña de XAMPP en local puede ser ''.
    $claves_necesarias = ['host', 'puerto', 'nombre', 'usuario', 'password', 'charset'];
    foreach ($claves_necesarias as $clave) {
        if (!array_key_exists($clave, $config['db'])) {
            error_fatal_bd('Falta la clave "' . $clave . '" en la sección "db" de config/config.php.');
        }
    }

    return $config['db'];
}

/**
 * Devuelve la conexión PDO. La crea la primera vez y después reutiliza la
 * misma (la variable "static" conserva su valor entre llamadas).
 */
function obtener_conexion(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $db = cargar_config();

    $dsn = 'mysql:host=' . $db['host']
         . ';port=' . $db['puerto']
         . ';dbname=' . $db['nombre']
         . ';charset=' . $db['charset'];

    $opciones = [
        // Los errores de SQL lanzan excepciones en lugar de fallar en silencio.
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // fetch() devuelve arrays asociativos: $fila['nombre_usuario'].
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Consultas preparadas reales en el servidor (no simuladas por PHP).
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $db['usuario'], $db['password'], $opciones);
    } catch (PDOException $e) {
        // Nunca se muestra $e->getMessage() en pantalla: solo va al log.
        error_fatal_bd('No se pudo conectar a la base de datos: ' . $e->getMessage());
    }

    return $pdo;
}
