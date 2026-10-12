-- ===========================================================
--  MIGRACIÓN v1 -> v2  (ejecutar UNA sola vez sobre la BD existente)
--  Compatible con MariaDB (XAMPP). Añade seguridad, tiquetes
--  y los campos necesarios para el flujo de compra/venta.
-- ===========================================================

-- Ajusta / descomenta si tu base de datos tiene otro nombre:
-- USE `terminal_de_transportes`;

SET NAMES utf8mb4;

-- -----------------------------------------------------------
-- 1) USUARIOS: contraseñas con password_hash() (bcrypt) + estado
-- -----------------------------------------------------------
ALTER TABLE usuarios
  MODIFY pass_hash VARCHAR(255) NOT NULL,
  ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Restablece la contraseña de los usuarios demo a "123456" (bcrypt).
-- Otros usuarios deberán restablecer su contraseña.
UPDATE usuarios SET pass_hash = '$2y$10$uRHppYi4SEssWA6B5BNMZecT7l416kScpFX/qauopPCXfnsVdiP2u' WHERE email = 'admin@terminal.com';
UPDATE usuarios SET pass_hash = '$2y$10$OFu7qMnQP3a/.5rjss9Zd.lknXvf32tnoR7KnSgz9HYVrrKM/ae3u' WHERE email = 'empresa@terminal.com';
UPDATE usuarios SET pass_hash = '$2y$10$8CgaZZ1zrpdD/NPYosz7oeOr0RaZmN1eJWvXC0JfReq0seIooCjOG' WHERE email = 'cliente@terminal.com';

-- -----------------------------------------------------------
-- 2) DISPONIBILIDADES: cupos totales
-- -----------------------------------------------------------
ALTER TABLE disponibilidades
  ADD COLUMN IF NOT EXISTS cupos_totales INT NOT NULL DEFAULT 0;

-- Inicializa cupos_totales con los cupos actuales si estaba en 0
UPDATE disponibilidades SET cupos_totales = cupos_disponibles WHERE cupos_totales = 0;

-- -----------------------------------------------------------
-- 3) COTIZACIONES: referencia opcional a un horario
-- -----------------------------------------------------------
ALTER TABLE cotizaciones
  ADD COLUMN IF NOT EXISTS horario_id INT NULL;

-- -----------------------------------------------------------
-- 4) COMPRAS: soporte de venta en taquilla, totales y canal
-- -----------------------------------------------------------
ALTER TABLE compras
  MODIFY cotizacion_id INT NULL,
  ADD COLUMN IF NOT EXISTS horario_id INT NULL,
  ADD COLUMN IF NOT EXISTS vendedor_id INT NULL,
  ADD COLUMN IF NOT EXISTS cantidad INT NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS total DECIMAL(10,2) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS metodo_pago VARCHAR(30) NOT NULL DEFAULT 'efectivo',
  ADD COLUMN IF NOT EXISTS canal VARCHAR(20) NOT NULL DEFAULT 'web';

-- -----------------------------------------------------------
-- 5) TIQUETES
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS tiquetes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  compra_id       INT NOT NULL,
  codigo          VARCHAR(30) UNIQUE NOT NULL,
  pasajero_nombre VARCHAR(120),
  pasajero_doc    VARCHAR(30),
  asiento         INT,
  estado          VARCHAR(20) NOT NULL DEFAULT 'valido',
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tiquetes_compra FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
  INDEX idx_tiquetes_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 6) Índices útiles
-- -----------------------------------------------------------
CREATE INDEX idx_rutas_origen_destino ON rutas (origen, destino);
CREATE INDEX idx_disp_horario ON disponibilidades (horario_id);
