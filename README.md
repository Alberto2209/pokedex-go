# PokéDex GO

Aplicación web donde cada usuario puede registrarse, iniciar sesión y gestionar su **Pokédex personal de Pokémon GO**, guardando el PC, los PS y los IV de cada Pokémon.

Proyecto de aprendizaje (DAM) desarrollado por fases, con código sencillo y comentado.

## Funcionalidades previstas

- Registro, login y logout con sesiones PHP
- Dashboard personal
- Añadir, editar y eliminar Pokémon
- Imagen del Pokémon (PokéAPI)
- Cálculo automático del porcentaje de IV
- Búsqueda, filtros y ordenación
- Cada usuario solo accede a sus propios Pokémon

## Tecnologías

- HTML, CSS y JavaScript
- PHP (procedural, con PDO)
- MySQL / MariaDB
- XAMPP (Apache + MySQL) en Windows
- PokéAPI
- Git y GitHub

## Estructura del proyecto

```
pokedex-go/
├── database/       ← script SQL de la base de datos
├── config/         ← configuración (config.php NO se sube a Git)
├── includes/       ← lógica PHP privada (conexión, sesión, validaciones...)
├── scripts/        ← scripts que se ejecutan una sola vez (importar catálogo)
└── public/         ← única carpeta accesible desde el navegador
    ├── pokemon/    ← añadir, editar y eliminar Pokémon
    ├── api/        ← endpoints JSON (autocompletado)
    └── assets/     ← CSS, JavaScript e imágenes
```

## Base de datos

Nombre: `pokedex_go`

| Tabla | Descripción |
|---|---|
| `usuarios` | Cuentas de usuario |
| `pokemon_catalogo` | Especies de Pokémon importadas desde PokéAPI |
| `pokemon_usuario` | Pokémon de cada usuario (PC, PS, IV) |

## Cálculo del IV

```
IV % = (IV Ataque + IV Defensa + IV PS) / 45 * 100
```

Cada IV debe estar entre 0 y 15.

## Instalación

1. **Base de datos:** importa `database/schema.sql` en phpMyAdmin (crea la base de datos `pokedex_go` y sus tablas).
2. **Configuración:** copia `config/config.example.php` como `config/config.php` y ajusta tus credenciales de MySQL.
3. **Catálogo de Pokémon:** importa las especies desde PokéAPI ejecutando, desde la línea de comandos:
   ```
   C:\xampp\php\php.exe scripts\importar_catalogo.php
   ```
   Es un paso manual, se hace una sola vez (o cuando quieras añadir más especies). No es necesario repetirlo cada vez que arrancas la aplicación. Más detalle en los comentarios del propio script.
4. **Apache:** añade un `Alias` a `public/` en `httpd-vhosts.conf` de XAMPP apuntando a tu carpeta del proyecto (ver Fase 2) y reinicia Apache.

## Fases de desarrollo

- [x] **Fase 0:** estructura del proyecto, Git, `.gitignore` y `README`
- [x] **Fase 1:** base de datos, configuración y conexión PDO
- [x] **Fase 2:** registro, login, logout y sesiones
- [x] **Fase 3:** catálogo de Pokémon e integración con PokéAPI
- [ ] **Fase 4:** añadir Pokémon
- [ ] **Fase 5:** dashboard, editar y eliminar
- [ ] **Fase 6:** búsqueda, filtros, orden y paginación
- [ ] **Fase 7:** estilos, seguridad y pulido
- [ ] **Fase 8:** documentación final
