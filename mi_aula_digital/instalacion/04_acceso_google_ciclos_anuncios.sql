-- Mi Aula Digital · Integración de acceso Google y categorías de anuncios.
-- Ejecutar una sola vez sobre una base existente. El código también comprueba las columnas de acceso automáticamente.
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS metodo_acceso ENUM('local','google','ambos') NOT NULL DEFAULT 'local';
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS google_sub VARCHAR(255) NULL;
ALTER TABLE usuario ADD COLUMN IF NOT EXISTS correo_verificado TINYINT(1) NOT NULL DEFAULT 0;

-- El índice es único. Si ya existe porque se ejecutó antes, omite esta línea al repetir manualmente la migración.
SET @mad_idx_exists := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuario' AND index_name='uk_usuario_google_sub');
SET @mad_idx_sql := IF(@mad_idx_exists=0,'ALTER TABLE usuario ADD UNIQUE KEY uk_usuario_google_sub (google_sub)','SELECT 1');
PREPARE mad_idx_stmt FROM @mad_idx_sql;
EXECUTE mad_idx_stmt;
DEALLOCATE PREPARE mad_idx_stmt;

-- Conversión segura de categorías antiguas a las nuevas.
ALTER TABLE aviso MODIFY Tipo_Aviso ENUM('Institucional','Urgente','Anuncio','Tarea','Reunión') NOT NULL DEFAULT 'Anuncio';
UPDATE aviso SET Tipo_Aviso='Anuncio' WHERE Tipo_Aviso IN ('Institucional','Urgente');
ALTER TABLE aviso MODIFY Tipo_Aviso ENUM('Anuncio','Tarea','Reunión') NOT NULL DEFAULT 'Anuncio';
