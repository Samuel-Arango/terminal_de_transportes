-- ===========================================================
--  BASE DE DATOS: Terminal de Transportes  (esquema v2)
--  Instalación limpia. Motor InnoDB + utf8mb4.
-- ===========================================================

CREATE DATABASE IF NOT EXISTS `terminal`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `terminal`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- ===========================================================
--  ROLES
-- ===========================================================
CREATE TABLE roles (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (nombre) VALUES ('admin'), ('empresa'), ('cliente');

-- ===========================================================
--  USUARIOS
--  pass_hash almacena contraseñas con password_hash() (bcrypt/argon2).
--  NUNCA guardar texto plano ni SHA-256 sin sal.
-- ===========================================================
CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  email      VARCHAR(150) UNIQUE NOT NULL,
  pass_hash  VARCHAR(255) NOT NULL,
  rol_id     INT NOT NULL,
  activo     TINYINT(1) NOT NULL DEFAULT 1,
  creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contraseña demo para los tres usuarios: 123456
INSERT INTO usuarios (nombre, email, pass_hash, rol_id) VALUES
('Administrador', 'admin@terminal.com',    '$2y$10$uRHppYi4SEssWA6B5BNMZecT7l416kScpFX/qauopPCXfnsVdiP2u', 1),
('Empresa',       'empresa@terminal.com',  '$2y$10$OFu7qMnQP3a/.5rjss9Zd.lknXvf32tnoR7KnSgz9HYVrrKM/ae3u', 2),
('Cliente',       'cliente@terminal.com',  '$2y$10$8CgaZZ1zrpdD/NPYosz7oeOr0RaZmN1eJWvXC0JfReq0seIooCjOG', 3);

-- ===========================================================
--  INFORMACIÓN DE LA TERMINAL
-- ===========================================================
CREATE TABLE terminal (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(120) NOT NULL,
  direccion VARCHAR(200),
  telefono  VARCHAR(20),
  ciudad    VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO terminal (nombre, direccion, telefono, ciudad) VALUES
('Terminal de Transportes', 'Av. Principal #45-20', '3105555555', 'Bogotá');

-- ===========================================================
--  EMPRESAS VINCULADAS
-- ===========================================================
CREATE TABLE empresas (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  terminal_id INT NOT NULL,
  nombre      VARCHAR(120) NOT NULL,
  contacto    VARCHAR(120),
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_empresas_terminal FOREIGN KEY (terminal_id) REFERENCES terminal(id) ON DELETE CASCADE,
  INDEX idx_empresas_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO empresas (terminal_id, nombre, contacto) VALUES
(1, 'Alas del Norte', 'alas@transporte.com'),
(1, 'Roda Futura',    'roda@transporte.com'),
(1, 'Mar y Tierra',   'marytierra@transporte.com'),
(1, 'Cometa Express', 'cometa@transporte.com');

-- ===========================================================
--  VEHÍCULOS
-- ===========================================================
CREATE TABLE vehiculos (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  tipo       VARCHAR(60) NOT NULL,
  capacidad  INT NOT NULL,
  placa      VARCHAR(20) UNIQUE,
  activo     TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_vehiculos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO vehiculos (empresa_id, tipo, capacidad, placa) VALUES
(1, 'Bus',    40, 'AAA123'),
(2, 'Buseta', 25, 'BBB456'),
(3, 'Aerovan',12, 'CCC789'),
(4, 'Taxi',    4, 'TXS897');

-- ===========================================================
--  RUTAS
-- ===========================================================
CREATE TABLE rutas (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id   INT NOT NULL,
  origen       VARCHAR(120) NOT NULL,
  destino      VARCHAR(120) NOT NULL,
  distancia_km DECIMAL(10,2),
  costo_base   DECIMAL(10,2),
  activo       TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_rutas_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  INDEX idx_rutas_origen_destino (origen, destino)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO rutas (empresa_id, origen, destino, distancia_km, costo_base) VALUES
(1, 'Bogotá',  'Medellín',  420, 85000),
(2, 'Bogotá',  'Tunja',     150, 35000),
(3, 'Riosucio','Manizales',  93, 30000),
(4, 'Pereira', 'Manizales',  53, 20000);

-- ===========================================================
--  HORARIOS
-- ===========================================================
CREATE TABLE horarios (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  ruta_id     INT NOT NULL,
  vehiculo_id INT NOT NULL,
  salida      TIME NOT NULL,
  llegada     TIME NOT NULL,
  CONSTRAINT fk_horarios_ruta     FOREIGN KEY (ruta_id)     REFERENCES rutas(id) ON DELETE CASCADE,
  CONSTRAINT fk_horarios_vehiculo FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO horarios (ruta_id, vehiculo_id, salida, llegada) VALUES
(1, 1, '07:00:00', '14:00:00'),
(2, 3, '09:00:00', '11:30:00'),
(2, 2, '15:00:00', '17:30:00'),
(3, 3, '06:30:00', '08:00:00'),
(4, 4, '10:00:00', '11:00:00');

-- ===========================================================
--  DISPONIBILIDAD (cupos por horario)
-- ===========================================================
CREATE TABLE disponibilidades (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  horario_id        INT NOT NULL,
  cupos_totales     INT NOT NULL,
  cupos_disponibles INT NOT NULL,
  CONSTRAINT fk_disp_horario FOREIGN KEY (horario_id) REFERENCES horarios(id) ON DELETE CASCADE,
  INDEX idx_disp_horario (horario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO disponibilidades (horario_id, cupos_totales, cupos_disponibles) VALUES
(1, 40, 40),
(2, 25, 25),
(3, 25, 25),
(4, 12, 12),
(5,  4,  4);

-- ===========================================================
--  COTIZACIONES
-- ===========================================================
CREATE TABLE cotizaciones (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  ruta_id    INT NOT NULL,
  horario_id INT NULL,
  personas   INT NOT NULL,
  total      DECIMAL(10,2) NOT NULL,
  creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cotiz_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_cotiz_ruta    FOREIGN KEY (ruta_id)    REFERENCES rutas(id)    ON DELETE CASCADE,
  CONSTRAINT fk_cotiz_horario FOREIGN KEY (horario_id) REFERENCES horarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================================================
--  COMPRAS / VENTAS  (canal: 'web' = compra en línea, 'taquilla' = venta presencial)
-- ===========================================================
CREATE TABLE compras (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id    INT NULL,
  cotizacion_id INT NULL,
  horario_id    INT NULL,
  vendedor_id   INT NULL,
  cantidad      INT NOT NULL DEFAULT 1,
  total         DECIMAL(10,2) NOT NULL DEFAULT 0,
  metodo_pago   VARCHAR(30) NOT NULL DEFAULT 'efectivo',
  canal         VARCHAR(20) NOT NULL DEFAULT 'web',
  estado        VARCHAR(20) NOT NULL DEFAULT 'pagado',
  fecha_compra  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_compras_usuario    FOREIGN KEY (usuario_id)    REFERENCES usuarios(id)    ON DELETE SET NULL,
  CONSTRAINT fk_compras_cotizacion FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE SET NULL,
  CONSTRAINT fk_compras_horario    FOREIGN KEY (horario_id)    REFERENCES horarios(id)    ON DELETE SET NULL,
  CONSTRAINT fk_compras_vendedor   FOREIGN KEY (vendedor_id)   REFERENCES usuarios(id)    ON DELETE SET NULL,
  INDEX idx_compras_fecha (fecha_compra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================================================
--  TIQUETES  (uno por asiento/pasajero)
-- ===========================================================
CREATE TABLE tiquetes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  compra_id       INT NOT NULL,
  codigo          VARCHAR(30) UNIQUE NOT NULL,
  pasajero_nombre VARCHAR(120),
  pasajero_doc    VARCHAR(30),
  asiento         INT,
  estado          VARCHAR(20) NOT NULL DEFAULT 'valido',
  validado_en     DATETIME NULL,
  validado_por    INT NULL,
  creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tiquetes_compra   FOREIGN KEY (compra_id)    REFERENCES compras(id)  ON DELETE CASCADE,
  CONSTRAINT fk_tiquetes_validador FOREIGN KEY (validado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_tiquetes_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
