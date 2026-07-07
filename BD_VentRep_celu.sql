CREATE DATABASE VenRep_celu;
USE VenRep_celu;

-- Solicitud de Empleo
CREATE TABLE Solicitudes_Empleado (
    Id_solicitud INT AUTO_INCREMENT PRIMARY KEY, 
    Nombre VARCHAR(25) NOT NULL, 
    Apellido_pat VARCHAR(10) NOT NULL, 
    Apellido_mat VARCHAR(10), 
    Genero ENUM('Femenino', 'Masculino', 'Prefiero no Decirlo') NOT NULL, 
    Calle VARCHAR(45), 
    Num_exterior INT, 
    Num_interior INT, 
    Colonia VARCHAR(30), 
    Codigo_pos INT, 
    Municipio VARCHAR(25), 
    Estado VARCHAR(25), 
    Correo VARCHAR(50) UNIQUE NOT NULL, 
    Telefono VARCHAR(20), 
    RFC VARCHAR(13), 
    CURP VARCHAR(18) NOT NULL, 
    NSS VARCHAR(11) NOT NULL, 
    Estado_civil ENUM('Casado', 'Soltero', 'Viudo'), 
    Certificado_estudio VARCHAR(255), 
    Comprobante_domicilio VARCHAR(255), 
    Estado_soli ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente', 
    Fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO Solicitudes_Empleado (Nombre, Apellido_pat, Apellido_mat, Genero, Calle, Num_exterior, Num_interior, Colonia, Codigo_pos, Municipio, Estado, Correo, Telefono, RFC, CURP, NSS, Estado_civil, Certificado_estudio, Comprobante_domicilio, Estado_soli, Fecha_solicitud) 
VALUES ('Diana Berenice', 'Pérez', 'Ramírez', 'Femenino', 'Faisan Norte', 134, 0, 'Valle Sur', 67280, 'Benito Juárez', 'Nuevo León', 'dianape@gmail.com', '8122218070', 'PERD050703LS3', 'PERD050703RNLRMNA2', '14785257859', 'Soltero', 'certificado.pdf', 'comprobante.pdf', 'aprobado', '2024-12-05');

-- Tabla Usuario
CREATE TABLE Usuario (
    Id_usuario INT AUTO_INCREMENT PRIMARY KEY, 
    Id_solicitud INT NOT NULL, 
    Nombre VARCHAR(25) NOT NULL, 
    Apellido_pat VARCHAR(10) NOT NULL, 
    Apellido_mat VARCHAR(10), 
    Genero ENUM('Femenino', 'Masculino', 'Prefiero no Decirlo') NOT NULL, 
    Calle VARCHAR(45), 
    Num_exterior INT, 
    Num_interior INT, 
    Colonia VARCHAR(30), 
    Codigo_pos INT, 
    Municipio VARCHAR(25), 
    Estado VARCHAR(25), 
    Correo VARCHAR(50) UNIQUE NOT NULL, 
    Telefono VARCHAR(10), 
    RFC VARCHAR(13), 
    CURP VARCHAR(18) NOT NULL, 
    NSS VARCHAR(11) NOT NULL, 
    Estado_civil ENUM('Casado', 'Soltero', 'Viudo'), 
    Rol ENUM('Admin', 'Editor', 'Consultor') NOT NULL, 
    Salario DECIMAL(10,2) CHECK (Salario > 0), 
    Password VARCHAR(100) NOT NULL, 
    FOREIGN KEY (Id_solicitud) REFERENCES Solicitudes_Empleado(Id_solicitud)
);

INSERT INTO Usuario (Id_solicitud, Nombre, Apellido_pat, Apellido_mat, Genero, Calle, Num_exterior, Num_interior, Colonia, Codigo_pos, Municipio, Estado, Correo, Telefono, RFC, CURP, NSS, Estado_civil, Rol, Salario, Password) 
VALUES (1, 'Diana Berenice', 'Pérez', 'Ramírez', 'Femenino', 'Faisan Norte', 134, 0, 'Valle Sur', 67280, 'Benito Juárez', 'Nuevo León', 'dianape@gmail.com', '8122218070', 'PERD050703LS3', 'PERD050703RNLRMNA2', '14785257859', 'Soltero', 'Admin', 2000, 'anaiD03');

-- Documentos Usuarios
CREATE TABLE Documentos_Empleado (
    Id_documento INT AUTO_INCREMENT PRIMARY KEY, 
    Id_usuario INT NOT NULL, 
    Tipo_documento ENUM('certificado_estudio', 'identificacion', 'comprobante_domicilio') NOT NULL,
    Nombre_archivo VARCHAR(255) NOT NULL, 
    Ruta_archivo VARCHAR(255) NOT NULL, 
    Fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (Id_usuario) REFERENCES Usuario(Id_usuario) ON DELETE CASCADE
);

-- Tabla Clientes
CREATE TABLE Clientes (
    Id_cliente INT AUTO_INCREMENT PRIMARY KEY, 
    Nombre VARCHAR(25) NOT NULL, 
    Apellido_pat VARCHAR(10) NOT NULL, 
    Apellido_mat VARCHAR(10), 
    Genero ENUM('Femenino', 'Masculino', 'Prefiero no Decirlo') NOT NULL, 
    Calle VARCHAR(45), 
    Num_exterior INT, 
    Num_interior INT, 
    Colonia VARCHAR(30), 
    Codigo_pos INT, 
    Municipio VARCHAR(25), 
    Estado VARCHAR(25), 
    Telefono VARCHAR(10), 
    Correo_cli VARCHAR(100) UNIQUE, 
    Password VARCHAR(100) NOT NULL, 
    Nota_domicilio VARCHAR(100)
);

-- Tabla Proveedores
CREATE TABLE Proveedores (
    Id_proveedor INT AUTO_INCREMENT PRIMARY KEY, 
    Nombre_empresa VARCHAR(30) NOT NULL, 
    Calle_provee VARCHAR(45) NOT NULL, 
    Num_exterior INT NOT NULL, 
    Num_interior INT NOT NULL, 
    Colonia VARCHAR(30) NOT NULL, 
    Codigo_pos INT, 
    Municipio VARCHAR(25), 
    Estado VARCHAR(25), 
    Telefono VARCHAR(10), 
    Correo VARCHAR(50)
);

INSERT INTO Proveedores (Nombre_empresa, Calle_provee, Num_exterior, Num_interior, Colonia, Codigo_pos, Municipio, Estado, Telefono, Correo) 
VALUES ('Apple México', 'Paseo de la Reforma', 243, 0, 'Cuauhtémoc', 06500, 'Cuauhtémoc', 'CDMX', '5550015500', 'proveedor.apple@apple.com'), 
('Samsung Electronics', 'Mariano Escobedo', 476, 12, 'Anzures', 11590, 'Miguel Hidalgo', 'CDMX', '5550024400', 'ventas.mx@samsung.com'), 
('Motorola Comercial', 'Av. Insurgentes Sur', 1602, 4, 'Crédito Constructor', 03940, 'Benito Juárez', 'CDMX', '5550033300', 'distribucion@motorola.com');

-- Tabla Compra
CREATE TABLE Compra (
    Id_compra INT AUTO_INCREMENT PRIMARY KEY, 
    Id_proveedor INT NOT NULL, 
    Fecha_compra DATETIME, 
    Subtotal_comp DECIMAL(10,2) CHECK (Subtotal_comp > 0), 
    Total_comp DECIMAL(10,2) CHECK (Total_comp > 0), 
    Num_remision INT CHECK (Num_remision > 0),
    FOREIGN KEY (Id_proveedor) REFERENCES Proveedores(Id_proveedor)
);

-- Tabla Productos
CREATE TABLE Productos (
    Id_producto INT AUTO_INCREMENT PRIMARY KEY, 
    Id_proveedor INT NOT NULL, 
    Num_lote INT, 
    Nombre_produ VARCHAR(100) NOT NULL, 
    Marca VARCHAR(50) NOT NULL, 
    Modelo VARCHAR(50) NOT NULL,
    Precio_inicial DECIMAL(10,2) NOT NULL CHECK (Precio_inicial > 0), 
    Precio_final DECIMAL(10,2) NOT NULL CHECK (Precio_final > 0), 
    Stock INT CHECK (Stock >= 0), 
    Fecha_llegada DATETIME,
    FOREIGN KEY (Id_proveedor) REFERENCES Proveedores(Id_proveedor)
);

INSERT INTO Productos (Id_proveedor, Num_lote, Nombre_produ, Marca, Modelo, Precio_inicial, Precio_final, Stock, Fecha_llegada) VALUES
(2, 23, 'iPhone 17 Pro Max', 'iphone', 'A3110', 21500.00, 24999, 10, '2026-03-15'),
(2, 12, 'iPhone 17', 'iphone', 'A3100', 19500, 22999, 15, '2025-09-25'),
(1, 14, 'iPhone 16 Pro Max', 'iphone', 'A3080', 25000, 29999, 8, '2026-05-05'),
(3, 15, 'iPhone 16', 'iphone', 'A3070', 15200, 17999, 12, '2026-04-25'),
(2, 134, 'Galaxy S25 Ultra', 'samsung', 'SM-S938', 18000, 21999, 10, '2026-04-02'),
(1, 123, 'Galaxy S25+', 'samsung', 'SM-S937', 10500, 12999, 12, '2026-04-25'),
(2, 145, 'Galaxy Z Fold 6', 'samsung', 'SM-F958', 13500, 16999, 5, '2026-02-11'),
(3, 167, 'Galaxy Z Flip 6', 'samsung', 'SM-F741', 7600, 9999, 8, '2026-01-25'),
(2, 452, 'Edge 50 Ultra', 'motorola', 'XT2405', 6700, 8999, 10, '2026-05-18'),
(1, 985, 'Edge 50 Pro', 'motorola', 'XT2403', 6700, 8999, 12, '2025-08-17'),
(2, 325, 'Razr 50 Ultra', 'motorola', 'XT2451', 3900, 5499, 6, '2025-02-15'),
(3, 852, 'Moto G85', 'motorola', 'XT2421', 2200, 3499, 20, '2026-07-05'),
(2, 953, 'Xiaomi 15 Ultra', 'xiaomi', '24010PN30G', 1200, 2499, 8, '2026-02-14'),
(3, 658, 'Xiaomi 15 Pro', 'xiaomi', '24010PN30F', 10500, 12999, 10, '2026-06-05'),
(1, 412, 'Redmi Note 14 Pro', 'xiaomi', '24090PN30G', 15200, 17999, 15, '2026-05-15'),
(2, 665, 'POCO X7 Pro', 'xiaomi', '24080PN30G', 7600, 9999, 12, '2025-12-31'),
(1, 225, 'AirPods Pro 2', 'audifonos', 'A3047', 2200, 3499, 20, '2026-07-05'),
(3, 853, 'AirPods 4', 'audifonos', 'A3050', 4299, 3000, 15, '2026-04-18'),
(3, 657, 'Audífonos Diadema BT Pro', 'audifonos', 'BT-100', 1200, 2499, 10, '2026-05-15'),
(2, 541, 'Audífonos EarPods', 'audifonos', 'EarPods', 250, 599, 25, '2026-02-26'),
(1, 876, 'Cargador iPhone 20W', 'cargadores', 'A2305', 200, 499, 30, '2026-06-25'),
(3, 874, 'Cargador USB-C 30W', 'cargadores', 'GaN30', 200, 500, 25, '2026-02-20'),
(2, 985, 'Cable USB-C a USB-C', 'cargadores', 'C-USB-C', 199, 250, 40, '2026-06-15'),
(1, 674, 'Cable Lightning a USB-C', 'cargadores', 'L-USB-C', 70, 199, 35, '2026-06-12');

-- Tabla Liquidacion
CREATE TABLE Liquidacion (
    Id_liqui INT AUTO_INCREMENT PRIMARY KEY, 
    Id_productos INT NOT NULL, 
    Num_lote INT, 
    Marca VARCHAR(50), 
    Modelo VARCHAR(20), 
    Precio_final DECIMAL(10,2) NOT NULL, 
    Stock INT NOT NULL, 
    Precio_liqui DECIMAL(10,2), 
    Fecha_llegada DATETIME, 
    FOREIGN KEY (Id_productos) REFERENCES Productos(Id_producto) ON DELETE CASCADE, 
    CONSTRAINT chk_precio_final_liq CHECK (Precio_final > 0), 
    CONSTRAINT chk_precio_liqui CHECK (Precio_liqui > 0)
);

-- Tabla Servicio_reparacion
CREATE TABLE Servicio_reparacion (
    Id_servicio INT AUTO_INCREMENT PRIMARY KEY, 
    Descripcion VARCHAR(100) NOT NULL, 
    Costo DECIMAL(10,2) NOT NULL CHECK (Costo > 0), 
    Status VARCHAR(50) DEFAULT 'Pendiente'
);

INSERT INTO Servicio_reparacion (Descripcion, Costo, Status) VALUES
('Cambio de Pantalla', 1500.00, 'Activo'),
('Cambio de Batería', 800.00, 'Activo'),
('Reparación de Cámara', 600.00, 'Activo'),
('Reparación de Audio', 500.00, 'Activo'),
('Limpieza por Humedad', 900.00, 'Activo'),
('Reparación de Puerto Carga', 400.00, 'Activo'),
('Diagnóstico General', 300.00, 'Activo'),
('Reparación de Placa', 1200.00, 'Activo'),
('Cambio de Conector de Carga', 350.00, 'Activo'),
('Reparación de Botones', 250.00, 'Activo');

-- Tabla Citas
CREATE TABLE Citas (
    Id_cita INT AUTO_INCREMENT PRIMARY KEY, 
    Id_usuario INT NOT NULL, 
    Fecha DATE NOT NULL, 
    Hora TIME NOT NULL, 
    Id_cliente INT NOT NULL, 
    Id_servicio INT NOT NULL, 
    Status VARCHAR(40) DEFAULT 'Programada', 
    Descripcion VARCHAR(200) DEFAULT NULL, 
    FOREIGN KEY (Id_usuario) REFERENCES Usuario(Id_usuario),
    FOREIGN KEY (Id_cliente) REFERENCES Clientes(Id_cliente), 
    FOREIGN KEY (Id_servicio) REFERENCES Servicio_reparacion(Id_servicio)
);

-- Tabla Ordenes_reparacion
CREATE TABLE Ordenes_reparacion (
    Id_orden INT AUTO_INCREMENT PRIMARY KEY, 
    Id_cliente INT NOT NULL, 
    Id_servicio INT NOT NULL, 
    Id_usuario INT NOT NULL, 
    Diagnostico VARCHAR(200),
    Status VARCHAR(50) DEFAULT 'En proceso', 
    Costo_total DECIMAL(10,2), 
    FOREIGN KEY (Id_cliente) REFERENCES Clientes(Id_cliente), 
    FOREIGN KEY (Id_servicio) REFERENCES Servicio_reparacion(Id_servicio),
    FOREIGN KEY (Id_usuario) REFERENCES Usuario(Id_usuario)
);

-- Tabla Garantia_servicio
CREATE TABLE Garantia_servicio (
    Id_orden INT NOT NULL, 
    Id_cliente INT NOT NULL, 
    Id_servicio INT NOT NULL, 
    Id_usuario INT NOT NULL, 
    Fecha_entrega DATETIME, 
    FOREIGN KEY (Id_orden) REFERENCES Ordenes_reparacion(Id_orden) ON DELETE CASCADE, 
    FOREIGN KEY (Id_cliente) REFERENCES Clientes(Id_cliente) ON DELETE CASCADE,
    FOREIGN KEY (Id_usuario) REFERENCES Usuario(Id_usuario) ON DELETE CASCADE, 
    FOREIGN KEY (Id_servicio) REFERENCES Servicio_reparacion(Id_servicio) ON DELETE CASCADE
);

-- Tabla Ventas
CREATE TABLE Ventas (
    Id_venta INT AUTO_INCREMENT PRIMARY KEY, 
    fecha_venta DATETIME DEFAULT CURRENT_TIMESTAMP,
    Subtotal_venta DECIMAL(10,2) NOT NULL CHECK (Subtotal_venta > 0), 
    Id_cliente INT NOT NULL,
    Id_usuario INT NOT NULL, 
    Id_producto INT NOT NULL, 
    FOREIGN KEY (Id_cliente) REFERENCES Clientes(Id_cliente), 
    FOREIGN KEY (Id_usuario) REFERENCES Usuario(Id_usuario),
    FOREIGN KEY (Id_producto) REFERENCES Productos(Id_producto)
);

-- Tabla Garantia_venta
CREATE TABLE Garantia_venta (
    Id_venta INT NOT NULL,
    Id_cliente INT NOT NULL, 
    Id_servicio INT NOT NULL, 
    Id_usuario INT NOT NULL, 
    Fecha_entrega DATETIME,
    FOREIGN KEY (Id_venta) REFERENCES Ventas(Id_venta) ON DELETE CASCADE,
    FOREIGN KEY (Id_cliente) REFERENCES Clientes(Id_cliente) ON DELETE CASCADE, 
    FOREIGN KEY (Id_usuario) REFERENCES Usuario(Id_usuario) ON DELETE CASCADE, 
    FOREIGN KEY (Id_servicio) REFERENCES Servicio_reparacion(Id_servicio) ON DELETE CASCADE
);

-- Trigger contratar empleado
DELIMITER //
CREATE TRIGGER contratar_empleado
AFTER UPDATE ON Solicitudes_Empleado
FOR EACH ROW
BEGIN
    IF NEW.Estado_soli = 'aprobado' AND OLD.Estado_soli = 'pendiente' THEN
        INSERT INTO Usuario (Id_solicitud, Nombre, Apellido_pat, Apellido_mat, Genero, Calle, Num_exterior, Num_interior, Colonia, Codigo_pos, Municipio, Estado, Correo, 
        Telefono, RFC, NSS, Estado_civil, Rol, Salario, Password)
        VALUES (NEW.Id_solicitud, NEW.Nombre, NEW.Apellido_pat, NEW.Apellido_mat, NEW.Genero, NEW.Calle, NEW.Num_exterior, NEW.Num_interior, NEW.Colonia, 
        NEW.Codigo_pos, NEW.Municipio, NEW.Estado, NEW.Correo, NEW.Telefono, NEW.RFC, NEW.NSS, NEW.Estado_civil, 'Consultor', 8000.00, 'password_temporal123');
    END IF;
END //
DELIMITER ;

-- Trigger Actualizar stock automáticamente al insertar una venta
DELIMITER //
CREATE TRIGGER actualizar_stock
AFTER INSERT ON Ventas
FOR EACH ROW
BEGIN
    UPDATE Productos 
    SET Stock = Stock - 1 
    WHERE Id_producto = NEW.Id_producto;
END //
DELIMITER ;

-- Trigger para GARANTÍA DE VENTA
DELIMITER //
CREATE TRIGGER generar_garantia_venta
AFTER INSERT ON Ventas
FOR EACH ROW
BEGIN
    INSERT INTO Garantia_venta (Id_venta, Id_cliente, Id_servicio, Id_usuario, Fecha_entrega) 
    VALUES (NEW.Id_venta, NEW.Id_cliente, 1, NEW.Id_usuario, DATE_ADD(NEW.fecha_venta, INTERVAL 30 DAY));
END //
DELIMITER ;

-- Trigger para GARANTÍA DE SERVICIO
ALTER TABLE Garantia_servicio ADD UNIQUE KEY unique_orden (Id_orden);
DELIMITER //
CREATE TRIGGER generar_garantia_servicio
AFTER UPDATE ON Ordenes_reparacion
FOR EACH ROW
BEGIN
    IF NEW.Status IN ('Completada', 'Entregada') THEN
        INSERT IGNORE INTO Garantia_servicio (Id_orden, Id_cliente, Id_servicio, Id_usuario, Fecha_entrega)
        VALUES (NEW.Id_orden, NEW.Id_cliente, NEW.Id_servicio, NEW.Id_usuario, NOW());
    END IF;
END //
DELIMITER ;
