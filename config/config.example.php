<?php
/**
 * config/config.example.php
 *
 * PLANTILLA de configuración. Este archivo SÍ se sube a Git y no debe
 * contener nunca contraseñas reales.
 *
 * Cómo usarla:
 *   1. Copia este archivo y llama a la copia "config.php" (misma carpeta).
 *   2. Edita config.php con los datos de tu instalación.
 *   3. config.php está en .gitignore, así que no se subirá a GitHub.
 *
 * Valores habituales de XAMPP en local (instalación por defecto):
 *   usuario  => 'root'
 *   password => ''   (vacía)
 *   puerto   => 3306 (compruébalo en el panel de control de XAMPP)
 */

return [
    'db' => [
        // Se usa 127.0.0.1 en lugar de "localhost" porque en Windows
        // "localhost" puede ralentizar la conexión al probar primero IPv6.
        'host'     => '127.0.0.1',
        'puerto'   => 3306,
        'nombre'   => 'pokedex_go',
        'usuario'  => 'TU_USUARIO_MYSQL',
        'password' => 'TU_PASSWORD_MYSQL',
        'charset'  => 'utf8mb4',
    ],

    'app' => [
        // Ruta URL en la que Apache publica la carpeta public/ (sin barra final).
        // Con el Alias de XAMPP configurado en la Fase 2 es '/pokedex-go'.
        // Si public/ fuera algún día la raíz del sitio, se dejaría vacía: ''.
        'url_base' => '/pokedex-go',
    ],
];
