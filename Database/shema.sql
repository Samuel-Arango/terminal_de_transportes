-- ===========================================
-- BASE DE DATOS: Terminal de Transporte
-- ===========================================



-- ===========================================
-- ROLES DE USUARIO
-- ===========================================
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(50) UNIQUE NOT NULL
);

INSERT INTO roles (nombre) VALUES 
('admin'),
('empresa'),
('cliente');

-- ===========================================
-- USUARIOS (administradores, empresas, clientes)
-- ===========================================
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL,
  pass_hash VARCHAR(255) NOT NULL,
  rol_id INT NOT NULL,
  FOREIGN KEY (rol_id) REFERENCES roles(id)
);

INSERT INTO usuarios (nombre, email, pass_hash, rol_id) VALUES
('Administrador', 'admin@terminal.com', SHA2('123456',256), 1),
('Empresa', 'empresa@terminal.com', SHA2('123456',256), 2),
('Cliente', 'cliente@terminal.com', SHA2('123456',256), 3);

-- ===========================================
-- INFORMACIÓN DE LA TERMINAL
-- ===========================================
CREATE TABLE terminal (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  direccion VARCHAR(200),
  telefono VARCHAR(20),
  ciudad VARCHAR(100)
);

INSERT INTO terminal (nombre, direccion, telefono, ciudad) VALUES
('Terminal de Transportes', 'Av. Principal #45-20', '3105555555', 'Bogotá');

-- ===========================================
-- EMPRESAS DE TRANSPORTE VINCULADAS
-- ===========================================
CREATE TABLE empresas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  terminal_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  contacto VARCHAR(120),
  FOREIGN KEY (terminal_id) REFERENCES terminal(id)
);

INSERT INTO empresas (terminal_id, nombre, contacto) VALUES
(1, 'Alas del Norte', 'alas@transporte.com'),
(1, 'Roda Futura', 'roda@transporte.com'),
(1, 'Mar y Tierra', 'marytierra@transporte.com'),
(1, 'Cometa Express', 'cometa@transporte.com');

-- ===========================================
-- VEHÍCULOS (camiones, buses, taxis, etc.)
-- ===========================================
CREATE TABLE vehiculos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  tipo VARCHAR(60) NOT NULL,
  capacidad INT NOT NULL,
  placa VARCHAR(20) UNIQUE,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

INSERT INTO vehiculos (empresa_id, tipo, capacidad, placa) VALUES
(1, 'Bus', 40, 'AAA123'),
(2, 'Buseta', 25, 'BBB456'),
(3, 'Aerovan', 12, 'CCC789'),
(4, 'Taxi', 4, 'TXS897');

-- ===========================================
-- RUTAS Y DESTINOS
-- ===========================================
CREATE TABLE rutas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT NOT NULL,
  origen VARCHAR(120) NOT NULL,
  destino VARCHAR(120) NOT NULL,
  distancia_km DECIMAL(10,2),
  costo_base DECIMAL(10,2),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id)
);

INSERT INTO rutas (empresa_id, origen, destino, distancia_km, costo_base) VALUES
(1, 'Bogotá', 'Medellín', 420, 85000),
(2, 'Bogotá', 'Tunja', 150, 35000),
(3, 'Riosucio', 'Manizales', 93, 30000),
(4, 'Pereira', 'Manizales', 53, 20000);

-- ===========================================
-- HORARIOS DE SALIDA Y LLEGADA
-- ===========================================
CREATE TABLE horarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ruta_id INT NOT NULL,
  vehiculo_id INT NOT NULL,
  salida TIME NOT NULL,
  llegada TIME NOT NULL,
  FOREIGN KEY (ruta_id) REFERENCES rutas(id),
  FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id)
);

INSERT INTO horarios (ruta_id, vehiculo_id, salida, llegada) VALUES
(1, 1, '07:00:00', '14:00:00'),
(2, 3, '09:00:00', '11:30:00');

-- ===========================================
-- DISPONIBILIDAD DE CUPOS POR HORARIO
-- ===========================================
CREATE TABLE disponibilidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  horario_id INT NOT NULL,
  cupos_disponibles INT NOT NULL,
  FOREIGN KEY (horario_id) REFERENCES horarios(id)
);

INSERT INTO disponibilidades (horario_id, cupos_disponibles) VALUES
(1, 40),
(2, 12);

-- ===========================================
-- COTIZACIONES (costos por pasajeros)
-- ===========================================
CREATE TABLE cotizaciones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  ruta_id INT NOT NULL,
  personas INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (ruta_id) REFERENCES rutas(id)
);

-- ===========================================
-- COMPRAS DE TIQUETES EN LÍNEA
-- ===========================================
CREATE TABLE compras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  cotizacion_id INT NOT NULL,
  fecha_compra DATETIME DEFAULT CURRENT_TIMESTAMP,
  estado VARCHAR(20) DEFAULT 'pagado',
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id)
);


