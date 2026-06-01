CREATE TABLE IF NOT EXISTS mascotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    especie ENUM('perro','gato','otro') NOT NULL DEFAULT 'perro',
    raza VARCHAR(100),
    edad INT NOT NULL DEFAULT 0,
    descripcion TEXT,
    estado ENUM('disponible','en_proceso','adoptado') NOT NULL DEFAULT 'disponible',
    fecha_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO mascotas (nombre, especie, raza, edad, descripcion, estado) VALUES
('Toby', 'perro', 'Labrador', 3, 'Muy juguetón y cariñoso, ideal para familias con niños.', 'disponible'),
('Luna', 'gato', 'Siamés', 2, 'Tranquila e independiente, perfecta para departamento.', 'disponible'),
('Rocky', 'perro', 'Mestizo', 5, 'Calmado, le gustan los paseos largos.', 'en_proceso'),
('Mishi', 'gato', 'Común europeo', 1, 'Cachorrita muy activa y curiosa.', 'adoptado');
