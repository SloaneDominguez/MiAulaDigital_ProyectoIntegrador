-- Mi Aula Digital · Editor visual por escuela
-- Seguro para ejecutar varias veces. Es una tabla clave/valor: nuevas opciones
-- visuales no requieren cambiar datos académicos ni volver a crear la tabla.
CREATE TABLE IF NOT EXISTS configuracion_escuela (
  clave VARCHAR(64) NOT NULL PRIMARY KEY,
  valor TEXT NOT NULL,
  actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
