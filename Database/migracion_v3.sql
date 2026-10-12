-- ===========================================================
--  MIGRACIÓN v2 -> v3 : control de acceso en puerta
--  Añade a la tabla tiquetes los campos de validación.
--  Ejecutar UNA sola vez sobre una base ya en v2.
-- ===========================================================

-- Ajusta / descomenta si tu base de datos tiene otro nombre:
-- USE `terminal`;

ALTER TABLE tiquetes
  ADD COLUMN IF NOT EXISTS validado_en DATETIME NULL,
  ADD COLUMN IF NOT EXISTS validado_por INT NULL,
  ADD CONSTRAINT fk_tiquetes_validador FOREIGN KEY (validado_por) REFERENCES usuarios(id) ON DELETE SET NULL;
