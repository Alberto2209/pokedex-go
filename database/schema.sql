-- =====================================================================
-- PokéDex GO - Fase 1: esquema de la base de datos
--
-- Compatible con MariaDB 10.2+ y MySQL 8.0.16+ (las versiones que
-- incluye XAMPP actualmente).
--
-- Cómo usarlo:
--   phpMyAdmin > pestaña "Importar" (o pestaña "SQL") > ejecutar este archivo.
--
-- Es seguro ejecutarlo más de una vez: usa IF NOT EXISTS y no borra datos.
-- Por lo mismo, si modificas una tabla en este archivo, el cambio NO se
-- aplica sobre una tabla que ya existe. Para empezar de cero durante el
-- desarrollo (BORRA TODOS LOS DATOS) ejecuta antes:
--     DROP DATABASE IF EXISTS pokedex_go;
-- =====================================================================

CREATE DATABASE IF NOT EXISTS pokedex_go
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pokedex_go;


-- ---------------------------------------------------------------------
-- Tabla 1: usuarios
-- Cuentas de la aplicación. El login se hace con el email.
-- Nota: con la colación utf8mb4_unicode_ci, "Ana@x.com" y "ana@x.com"
-- se consideran iguales, así que el UNIQUE no permite duplicarlos.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre_usuario  VARCHAR(30)   NOT NULL,                       -- nombre visible
    email           VARCHAR(100)  NOT NULL,                       -- se usa para el login
    password_hash   VARCHAR(255)  NOT NULL,                       -- resultado de password_hash()
    fecha_registro  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_nombre_usuario (nombre_usuario),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Tabla 2: pokemon_catalogo
-- Especies base importadas una sola vez desde PokéAPI (Fase 3).
-- El id es el número de Pokédex, por eso NO es AUTO_INCREMENT.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pokemon_catalogo (
    id      SMALLINT UNSIGNED  NOT NULL,                          -- nº de Pokédex (1 = bulbasaur)
    nombre  VARCHAR(50)        NOT NULL,                          -- formato PokéAPI: "pikachu", "mr-mime"
    tipo_1  VARCHAR(20)        NOT NULL,                          -- "electric", "fire"...
    tipo_2  VARCHAR(20)        NULL,                              -- NULL si solo tiene un tipo

    PRIMARY KEY (id),
    KEY idx_catalogo_nombre (nombre)                              -- acelera el autocompletado
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Tabla 3: pokemon_usuario
-- Los Pokémon de cada usuario. Se permiten repetidos de la misma especie
-- (en el juego tienes muchos Pokémon iguales con IV distintos).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pokemon_usuario (
    id                  INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    usuario_id          INT UNSIGNED       NOT NULL,              -- dueño del Pokémon
    pokemon_id          SMALLINT UNSIGNED  NOT NULL,              -- especie (catálogo)
    pc                  SMALLINT UNSIGNED  NOT NULL,
    ps                  SMALLINT UNSIGNED  NOT NULL,
    iv_ataque           TINYINT UNSIGNED   NOT NULL,
    iv_defensa          TINYINT UNSIGNED   NOT NULL,
    iv_ps               TINYINT UNSIGNED   NOT NULL,

    -- Columna generada: MySQL/MariaDB la calcula y la guarda solos.
    -- No se puede insertar ni actualizar a mano, así que nunca habrá un
    -- valor incoherente. Permite filtrar y ordenar por IV directamente en SQL.
    iv_porcentaje       DECIMAL(5,2)
        GENERATED ALWAYS AS (ROUND((iv_ataque + iv_defensa + iv_ps) / 45 * 100, 2)) STORED,

    fecha_alta          DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    -- Índice compuesto: todas las consultas filtran por usuario_id, y con
    -- iv_porcentaje al final también acelera ordenar por IV. Al empezar por
    -- usuario_id, sirve además para buscar solo por usuario (y para la FK).
    KEY idx_pokemon_usuario_usuario_iv (usuario_id, iv_porcentaje),
    KEY idx_pokemon_usuario_pokemon (pokemon_id),

    -- Segunda barrera de seguridad: cada IV debe estar entre 0 y 15.
    -- (Los nombres de CHECK y FK deben ser únicos en toda la base en MySQL.)
    CONSTRAINT chk_pokemon_usuario_iv_ataque  CHECK (iv_ataque  BETWEEN 0 AND 15),
    CONSTRAINT chk_pokemon_usuario_iv_defensa CHECK (iv_defensa BETWEEN 0 AND 15),
    CONSTRAINT chk_pokemon_usuario_iv_ps      CHECK (iv_ps      BETWEEN 0 AND 15),

    -- Si se borra un usuario, se borran también sus Pokémon.
    CONSTRAINT fk_pokemon_usuario_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- No se puede borrar una especie del catálogo si algún usuario la tiene.
    CONSTRAINT fk_pokemon_usuario_catalogo
        FOREIGN KEY (pokemon_id) REFERENCES pokemon_catalogo (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
