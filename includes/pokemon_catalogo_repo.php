<?php
/**
 * includes/pokemon_catalogo_repo.php
 *
 * Consultas SQL de la tabla "pokemon_catalogo" y utilidades relacionadas
 * (formato del nombre, URL de la imagen). Todas las consultas son
 * preparadas. Cada función recibe la conexión $pdo (ver includes/db.php).
 *
 * Quién usa este archivo:
 *   - scripts/importar_catalogo.php  (guardar_pokemon_catalogo)
 *   - Fases futuras (Fase 4 en adelante), para el autocompletado y para
 *     mostrar los datos de la especie en la Pokédex del usuario.
 */

/**
 * Guarda una especie en el catálogo, o la actualiza si ya existía.
 *
 * Es un "upsert": una única consulta que evita duplicados por diseño
 * (el id es la clave primaria) y de paso actualiza los datos si la
 * especie ya estaba guardada. Así el script de importación se puede
 * ejecutar varias veces sin problema.
 */
function guardar_pokemon_catalogo(PDO $pdo, int $id, string $nombre, string $tipo_1, ?string $tipo_2): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO pokemon_catalogo (id, nombre, tipo_1, tipo_2)
         VALUES (:id, :nombre, :tipo_1, :tipo_2)
         ON DUPLICATE KEY UPDATE
             nombre = VALUES(nombre),
             tipo_1 = VALUES(tipo_1),
             tipo_2 = VALUES(tipo_2)'
    );

    $stmt->execute([
        'id'     => $id,
        'nombre' => $nombre,
        'tipo_1' => $tipo_1,
        'tipo_2' => $tipo_2,
    ]);
}

/**
 * Indica si una especie ya existe en el catálogo (id = número de Pokédex).
 * Solo se usa para informar en pantalla durante la importación
 * (creado / actualizado); no hace falta para evitar duplicados, porque
 * guardar_pokemon_catalogo() ya lo garantiza.
 */
function existe_pokemon_en_catalogo(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM pokemon_catalogo WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);

    return $stmt->fetchColumn() !== false;
}

/**
 * Número de especies guardadas actualmente en el catálogo.
 */
function contar_pokemon_catalogo(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM pokemon_catalogo')->fetchColumn();
}

/**
 * Devuelve una especie del catálogo por su id (número de Pokédex), o null
 * si no existe.
 */
function obtener_pokemon_catalogo_por_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre, tipo_1, tipo_2
         FROM pokemon_catalogo
         WHERE id = ?'
    );
    $stmt->execute([$id]);

    $pokemon = $stmt->fetch();
    return $pokemon === false ? null : $pokemon;
}

/**
 * Busca especies del catálogo cuyo nombre contenga el texto dado
 * (usado más adelante para el autocompletado al añadir un Pokémon).
 * Devuelve como máximo $limite resultados, ordenados por nombre.
 */
function buscar_pokemon_catalogo(PDO $pdo, string $texto, int $limite = 10): array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre, tipo_1, tipo_2
         FROM pokemon_catalogo
         WHERE nombre LIKE :texto
         ORDER BY nombre
         LIMIT :limite'
    );

    // "%texto%" se construye aquí, nunca concatenando el LIKE en el SQL.
    $stmt->bindValue('texto', '%' . $texto . '%', PDO::PARAM_STR);
    $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * URL de la imagen oficial de una especie (artwork oficial de PokéAPI).
 * No se guarda en la base de datos: se construye al vuelo a partir del id,
 * tal y como se decidió en la arquitectura.
 */
function url_imagen_pokemon_catalogo(int $id): string
{
    return 'https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/'
        . $id . '.png';
}

/**
 * Formatea el nombre de PokéAPI ("mr-mime", "nidoran-f") a un formato
 * más legible para mostrar en pantalla ("Mr Mime", "Nidoran F").
 */
function nombre_visible_pokemon(string $nombre_pokeapi): string
{
    return ucwords(str_replace('-', ' ', $nombre_pokeapi));
}
