<?php
/**
 * scripts/importar_catalogo.php
 *
 * Importa especies de Pokémon desde PokéAPI a la tabla pokemon_catalogo.
 *
 * Es un PROCESO INDEPENDIENTE: se ejecuta a mano, desde la línea de
 * comandos, cuando tú quieras. No se ejecuta nunca desde el navegador ni
 * cuando un usuario entra en la web. Por eso vive en scripts/ y no en
 * public/, y además el propio script rechaza ejecutarse si no es por CLI.
 *
 * USO:
 *   C:\xampp\php\php.exe scripts\importar_catalogo.php
 *   C:\xampp\php\php.exe scripts\importar_catalogo.php 1 151
 *   C:\xampp\php\php.exe scripts\importar_catalogo.php 152 251
 *
 * Los dos números opcionales son el primer y el último id de Pokédex a
 * importar (ambos incluidos). Si no se indican, se usa el rango de
 * Kanto (1-151), definido más abajo en las constantes.
 *
 * El script es seguro de ejecutar varias veces: si una especie ya existe
 * se actualiza, nunca se duplica (ver guardar_pokemon_catalogo()).
 *
 * Fair Use Policy de PokéAPI: no requiere autenticación, pero pide no
 * saturar el servidor. Por eso: solo se importan especies base (sin
 * formas alternativas, que tienen id >= 10000), se espera una pequeña
 * pausa entre peticiones, y el resultado se guarda en NUESTRA base de
 * datos para no tener que volver a pedirlo cada vez que se usa la app.
 */

// --- Solo por línea de comandos, nunca desde el navegador ---
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se puede ejecutar desde la línea de comandos.');
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/pokemon_catalogo_repo.php';

// Rango por defecto: Generación I (Kanto). Para importar más especies,
// pasa los ids como argumentos, por ejemplo:
//   php importar_catalogo.php 1 251     (hasta Johto)
// El número más alto de especie base cambia con cada nueva generación;
// para saber cuál es el actual, consulta:
//   https://pokeapi.co/api/v2/pokemon-species/?limit=1   (campo "count")
const CATALOGO_ID_INICIO_POR_DEFECTO = 1;
const CATALOGO_ID_FIN_POR_DEFECTO    = 151;

// Pausa entre peticiones, en microsegundos (200 000 = 0,2 segundos).
// No es obligatorio, pero PokéAPI pide no saturar su servidor.
const PAUSA_ENTRE_PETICIONES = 200000;

/**
 * Descarga y decodifica el JSON de una URL con cURL.
 * Devuelve el array decodificado, o null si algo falla (y explica el
 * motivo por la salida estándar).
 */
function obtener_json(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'PokedexGO-Importador/1.0 (proyecto educativo DAM)',
    ]);

    $cuerpo       = curl_execute_seguro($ch);
    $codigo_http  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error_curl   = curl_error($ch);
    curl_close($ch);

    if ($cuerpo === false) {
        echo "  -> Error de conexión: {$error_curl}" . PHP_EOL;
        return null;
    }

    if ($codigo_http !== 200) {
        echo "  -> Respuesta HTTP {$codigo_http} (se omite este Pokémon)" . PHP_EOL;
        return null;
    }

    $datos = json_decode($cuerpo, true);
    if (!is_array($datos)) {
        echo '  -> La respuesta no es un JSON válido' . PHP_EOL;
        return null;
    }

    return $datos;
}

/**
 * Pequeño envoltorio para que un fallo de cURL no lance un error de PHP,
 * sino que se trate igual que cualquier otro fallo de red.
 */
function curl_execute_seguro($ch)
{
    return curl_exec($ch);
}

/**
 * A partir de la respuesta de /pokemon/{id}/, extrae tipo 1 y tipo 2
 * según el campo "slot" (1 = tipo principal, 2 = tipo secundario).
 * Devuelve [tipo_1, tipo_2] (tipo_2 es null si solo tiene un tipo).
 */
function extraer_tipos(array $datos_pokemon): array
{
    $tipo_1 = null;
    $tipo_2 = null;

    foreach ($datos_pokemon['types'] ?? [] as $entrada) {
        $slot   = $entrada['slot'] ?? null;
        $nombre = $entrada['type']['name'] ?? null;

        if ($slot === 1) {
            $tipo_1 = $nombre;
        } elseif ($slot === 2) {
            $tipo_2 = $nombre;
        }
    }

    return [$tipo_1, $tipo_2];
}

// --- Leer el rango desde los argumentos de la línea de comandos ---
$id_inicio = isset($argv[1]) ? (int) $argv[1] : CATALOGO_ID_INICIO_POR_DEFECTO;
$id_fin    = isset($argv[2]) ? (int) $argv[2] : CATALOGO_ID_FIN_POR_DEFECTO;

if ($id_inicio < 1 || $id_fin < $id_inicio) {
    exit('Rango no válido. Uso: php importar_catalogo.php [id_inicio] [id_fin]' . PHP_EOL);
}

if (!function_exists('curl_init')) {
    exit(
        'Falta la extensión cURL de PHP.' . PHP_EOL .
        'Actívala en C:\xampp\php\php.ini quitando el ";" de la línea ";extension=curl"' . PHP_EOL .
        'y reinicia Apache desde el panel de XAMPP.' . PHP_EOL
    );
}

echo '=== Importación del catálogo PokéDex GO ===' . PHP_EOL;
echo "Rango: id {$id_inicio} a {$id_fin} (" . ($id_fin - $id_inicio + 1) . ' especies)' . PHP_EOL;
echo PHP_EOL;

$pdo = obtener_conexion();

$total_creados     = 0;
$total_actualizados = 0;
$total_fallidos     = [];
$inicio_cronometro  = microtime(true);

for ($id = $id_inicio; $id <= $id_fin; $id++) {
    echo "[{$id}/{$id_fin}] ";

    $url   = "https://pokeapi.co/api/v2/pokemon/{$id}/";
    $datos = obtener_json($url);

    if ($datos === null) {
        $total_fallidos[] = $id;
        continue;
    }

    $nombre = $datos['name'] ?? null;
    [$tipo_1, $tipo_2] = extraer_tipos($datos);

    if ($nombre === null || $tipo_1 === null) {
        echo '  -> Respuesta incompleta (falta nombre o tipo), se omite' . PHP_EOL;
        $total_fallidos[] = $id;
        continue;
    }

    try {
        $ya_existia = existe_pokemon_en_catalogo($pdo, $id);
        guardar_pokemon_catalogo($pdo, $id, $nombre, $tipo_1, $tipo_2);

        if ($ya_existia) {
            $total_actualizados++;
            echo "actualizado: {$nombre} ({$tipo_1}" . ($tipo_2 !== null ? "/{$tipo_2}" : '') . ')' . PHP_EOL;
        } else {
            $total_creados++;
            echo "creado: {$nombre} ({$tipo_1}" . ($tipo_2 !== null ? "/{$tipo_2}" : '') . ')' . PHP_EOL;
        }
    } catch (PDOException $e) {
        echo '  -> Error al guardar en la base de datos: ' . $e->getMessage() . PHP_EOL;
        $total_fallidos[] = $id;
    }

    // Pequeña pausa para no saturar PokéAPI (excepto tras el último).
    if ($id < $id_fin) {
        usleep(PAUSA_ENTRE_PETICIONES);
    }
}

$segundos = round(microtime(true) - $inicio_cronometro, 1);

echo PHP_EOL;
echo '=== Resumen ===' . PHP_EOL;
echo "Creados:     {$total_creados}" . PHP_EOL;
echo "Actualizados: {$total_actualizados}" . PHP_EOL;
echo 'Fallidos:    ' . count($total_fallidos)
    . (empty($total_fallidos) ? '' : ' (ids: ' . implode(', ', $total_fallidos) . ')') . PHP_EOL;
echo "Tiempo:      {$segundos} s" . PHP_EOL;
echo 'Total en el catálogo ahora mismo: ' . contar_pokemon_catalogo($pdo) . PHP_EOL;

if (!empty($total_fallidos)) {
    echo PHP_EOL . 'Para reintentar solo los que fallaron, vuelve a ejecutar el script' . PHP_EOL
        . 'con el rango que los cubra (es seguro: no se duplican).' . PHP_EOL;
}
