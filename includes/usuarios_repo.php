<?php
/**
 * includes/usuarios_repo.php
 *
 * Consultas SQL de la tabla "usuarios". Todas son consultas preparadas:
 * los datos nunca se concatenan dentro del SQL (evita inyección SQL).
 * Cada función recibe la conexión $pdo (ver includes/db.php).
 */

/**
 * Busca un usuario por email. Devuelve sus datos o null si no existe.
 */
function buscar_usuario_por_email(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, nombre_usuario, email, password_hash
         FROM usuarios
         WHERE email = ?'
    );
    $stmt->execute([$email]);

    $usuario = $stmt->fetch();
    return $usuario === false ? null : $usuario;
}

/**
 * Indica si ya existe una cuenta con ese email.
 */
function existe_email(PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    return $stmt->fetchColumn() !== false;
}

/**
 * Indica si ya existe una cuenta con ese nombre de usuario.
 */
function existe_nombre_usuario(PDO $pdo, string $nombre_usuario): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE nombre_usuario = ? LIMIT 1');
    $stmt->execute([$nombre_usuario]);

    return $stmt->fetchColumn() !== false;
}

/**
 * Crea un usuario nuevo y devuelve su id.
 * Recibe el hash de la contraseña, NUNCA la contraseña en claro.
 */
function crear_usuario(PDO $pdo, string $nombre_usuario, string $email, string $password_hash): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre_usuario, email, password_hash)
         VALUES (?, ?, ?)'
    );
    $stmt->execute([$nombre_usuario, $email, $password_hash]);

    return (int) $pdo->lastInsertId();
}
