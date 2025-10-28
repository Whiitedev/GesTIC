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
    tipo ENUM('profesor', 'alumno', 'directivo', 'administrativo') NOT NULL,
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