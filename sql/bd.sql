-- Crear la base de datos
CREATE DATABASE inventario;
USE inventario;

-- Tabla de herramientas/inventario
CREATE TABLE herramientas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    cantidad_total INT NOT NULL DEFAULT 0,
    ubicacion VARCHAR(50) NOT NULL,
    estado ENUM('Disponible', 'En uso', 'Mantenimiento', 'No disponible') DEFAULT 'Disponible',
    codigo_barras VARCHAR(100) UNIQUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de usuarios (profesores, alumnos, directivos)
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo ENUM('profesor', 'alumno', 'administrativo') NOT NULL,
    grado_seccion VARCHAR(50), -- Para alumnos: "4to A", "5to B", etc.
    materia VARCHAR(100), -- Para profesores
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    token_recuperacion VARCHAR(255) NULL,
    token_expiracion DATETIME NULL
);

-- Tabla de préstamos/reservas
CREATE TABLE prestamos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    herramienta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    cantidad_prestada INT NOT NULL,
    fecha_prestamo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_devolucion_estimada DATE,
    fecha_devolucion_real TIMESTAMP NULL,
    estado ENUM('activo', 'completado', 'vencido') DEFAULT 'activo',
    observaciones TEXT,
    FOREIGN KEY (herramienta_id) REFERENCES herramientas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Tabla para historial de movimientos
CREATE TABLE historial_inventario (
    id INT PRIMARY KEY AUTO_INCREMENT,
    herramienta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo_movimiento ENUM('prestamo', 'devolucion', 'ajuste', 'ingreso') NOT NULL,
    cantidad INT NOT NULL,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    FOREIGN KEY (herramienta_id) REFERENCES herramientas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- =============================================
-- NUEVAS TABLAS PARA EL SISTEMA DE SOLICITUDES
-- =============================================

-- Tabla para categorías de recursos TIC
CREATE TABLE categorias_recursos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para recursos TIC disponibles
CREATE TABLE recursos_tic (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    categoria_id INT,
    stock_total INT NOT NULL DEFAULT 0,
    stock_disponible INT NOT NULL DEFAULT 0,
    ubicacion VARCHAR(50),
    estado ENUM('Disponible', 'Mantenimiento', 'No disponible') DEFAULT 'Disponible',
    codigo_barras VARCHAR(100) UNIQUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias_recursos(id)
);

-- Tabla para solicitudes de notebooks
CREATE TABLE solicitudes_notebooks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    cantidad_solicitada INT NOT NULL CHECK (cantidad_solicitada <= 20),
    aula_solicitante VARCHAR(50) NOT NULL,
    profesor_encargado VARCHAR(100) NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_uso DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    proposito TEXT,
    estado ENUM('pendiente', 'aprobada', 'rechazada', 'completada') DEFAULT 'pendiente',
    observaciones_admin TEXT,
    fecha_aprobacion TIMESTAMP NULL,
    administrador_id INT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (administrador_id) REFERENCES usuarios(id)
);

-- Tabla para solicitudes de recursos TIC
CREATE TABLE solicitudes_recursos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    recurso_id INT NOT NULL,
    cantidad_solicitada INT NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_uso DATE NOT NULL,
    hora_inicio TIME,
    hora_fin TIME,
    proposito TEXT,
    estado ENUM('pendiente', 'aprobada', 'rechazada', 'completada') DEFAULT 'pendiente',
    observaciones_admin TEXT,
    fecha_aprobacion TIMESTAMP NULL,
    administrador_id INT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (recurso_id) REFERENCES recursos_tic(id),
    FOREIGN KEY (administrador_id) REFERENCES usuarios(id)
);

-- Tabla para kits de robótica
CREATE TABLE kits_robotica (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    componentes TEXT,
    stock_total INT NOT NULL DEFAULT 0,
    stock_disponible INT NOT NULL DEFAULT 0,
    ubicacion VARCHAR(50),
    estado ENUM('Disponible', 'Mantenimiento', 'No disponible') DEFAULT 'Disponible',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para solicitudes de kits de robótica
CREATE TABLE solicitudes_robotica (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    kit_id INT NOT NULL,
    cantidad_solicitada INT NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_uso DATE NOT NULL,
    hora_inicio TIME,
    hora_fin TIME,
    proposito TEXT,
    estado ENUM('pendiente', 'aprobada', 'rechazada', 'completada') DEFAULT 'pendiente',
    observaciones_admin TEXT,
    fecha_aprobacion TIMESTAMP NULL,
    administrador_id INT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (kit_id) REFERENCES kits_robotica(id),
    FOREIGN KEY (administrador_id) REFERENCES usuarios(id)
);

-- =============================================
-- DATOS INICIALES
-- =============================================

-- Insertar categorías predeterminadas
INSERT INTO categorias_recursos (nombre, descripcion) VALUES
('Desarrollo', 'Recursos para programación y desarrollo'),
('Robótica', 'Kits y componentes de robótica'),
('Electrónica', 'Componentes electrónicos y placas'),
('Redes', 'Equipos de networking y conectividad'),
('Multimedia', 'Equipos de audio, video y producción');

-- Insertar recursos TIC predeterminados
INSERT INTO recursos_tic (nombre, descripcion, categoria_id, stock_total, stock_disponible, ubicacion) VALUES
('Arduino Uno', 'Placa Arduino Uno R3 con cable USB', 3, 15, 15, 'Laboratorio A'),
('Raspberry Pi 4', 'Placa Raspberry Pi 4B 4GB RAM', 3, 10, 10, 'Laboratorio B'),
('Sensor Ultrasónico HC-SR04', 'Sensor de distancia por ultrasonido', 3, 25, 25, 'Cajón Sensores'),
('Kit Robot Educativo', 'Kit completo para construcción de robot educativo', 2, 8, 8, 'Armario Robótica'),
('Cámara Web HD', 'Cámara web 1080p para videoconferencias', 5, 12, 12, 'Sala Multimedia'),
('Switch Red 8 Puertos', 'Switch de red Ethernet 8 puertos', 4, 5, 5, 'Sala Servidores'),
('Kit Sensores Arduino', 'Set de 10 sensores diferentes para Arduino', 3, 18, 18, 'Laboratorio A'),
('Monitor 24"', 'Monitor LED 24 pulgadas Full HD', 5, 6, 6, 'Almacén Monitores');

-- Insertar kits de robótica predeterminados
INSERT INTO kits_robotica (nombre, descripcion, componentes, stock_total, stock_disponible, ubicacion) VALUES
('Kit Robot Seguidor de Línea', 'Kit para construir robot seguidor de línea', 'Chasis, motores, sensores IR, Arduino, ruedas', 5, 5, 'Armario Robótica'),
('Kit Robot Brazo Mecánico', 'Kit de brazo robótico educativo', 'Servomotores, estructura metálica, controlador', 3, 3, 'Armario Robótica'),
('Kit Drone Educativo', 'Kit para ensamblar drone educativo', 'Motores, hélices, controladora, batería', 4, 4, 'Estante Drones'),
('Kit IoT Básico', 'Kit para proyectos de Internet de las Cosas', 'ESP32, sensores, display, módulos WiFi', 10, 10, 'Cajón IoT');

-- Insertar algunos notebooks en la tabla herramientas (para compatibilidad)
INSERT INTO herramientas (nombre, descripcion, cantidad_total, ubicacion, estado) VALUES
('Notebook Dell Latitude', 'Laptop Dell Latitude i5 8GB RAM 256GB SSD', 25, 'Laboratorio de Computación', 'Disponible'),
('Notebook HP ProBook', 'Laptop HP ProBook i7 16GB RAM 512GB SSD', 15, 'Laboratorio de Computación', 'Disponible'),
('Notebook Lenovo ThinkPad', 'Laptop Lenovo ThinkPad i5 8GB RAM 256GB SSD', 20, 'Laboratorio de Computación', 'Disponible');

-- =============================================
-- VISTAS ÚTILES
-- =============================================

-- Vista para ver solicitudes de notebooks con información del usuario
CREATE VIEW vista_solicitudes_notebooks AS
SELECT 
    sn.*,
    u.nombre as usuario_nombre,
    u.apellido as usuario_apellido,
    u.email as usuario_email,
    u.tipo as usuario_tipo,
    admin.nombre as admin_nombre,
    admin.apellido as admin_apellido
FROM solicitudes_notebooks sn
LEFT JOIN usuarios u ON sn.usuario_id = u.id
LEFT JOIN usuarios admin ON sn.administrador_id = admin.id;

-- Vista para ver solicitudes de recursos con información del recurso
CREATE VIEW vista_solicitudes_recursos AS
SELECT 
    sr.*,
    u.nombre as usuario_nombre,
    u.apellido as usuario_apellido,
    u.email as usuario_email,
    r.nombre as recurso_nombre,
    r.descripcion as recurso_descripcion,
    c.nombre as categoria_nombre,
    admin.nombre as admin_nombre,
    admin.apellido as admin_apellido
FROM solicitudes_recursos sr
LEFT JOIN usuarios u ON sr.usuario_id = u.id
LEFT JOIN recursos_tic r ON sr.recurso_id = r.id
LEFT JOIN categorias_recursos c ON r.categoria_id = c.id
LEFT JOIN usuarios admin ON sr.administrador_id = admin.id;

-- Vista para ver solicitudes de robótica
CREATE VIEW vista_solicitudes_robotica AS
SELECT 
    srob.*,
    u.nombre as usuario_nombre,
    u.apellido as usuario_apellido,
    u.email as usuario_email,
    kr.nombre as kit_nombre,
    kr.descripcion as kit_descripcion,
    admin.nombre as admin_nombre,
    admin.apellido as admin_apellido
FROM solicitudes_robotica srob
LEFT JOIN usuarios u ON srob.usuario_id = u.id
LEFT JOIN kits_robotica kr ON srob.kit_id = kr.id
LEFT JOIN usuarios admin ON srob.administrador_id = admin.id;

-- Agregar campo de código de barras a kits_robotica si no existe
ALTER TABLE kits_robotica ADD COLUMN codigo_barras VARCHAR(100) UNIQUE;

-- Agregar campo para identificar quién agregó el recurso
ALTER TABLE recursos_tic ADD COLUMN agregado_por INT;
ALTER TABLE kits_robotica ADD COLUMN agregado_por INT;
ALTER TABLE recursos_tic ADD FOREIGN KEY (agregado_por) REFERENCES usuarios(id);
ALTER TABLE kits_robotica ADD FOREIGN KEY (agregado_por) REFERENCES usuarios(id);

-- Agregar campo para fecha de adición
ALTER TABLE recursos_tic ADD COLUMN fecha_adicion TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE kits_robotica ADD COLUMN fecha_adicion TIMESTAMP DEFAULT CURRENT_TIMESTAMP;