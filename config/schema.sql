CREATE TABLE IF NOT EXISTS tareas (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(500) NOT NULL,
    descripcion TEXT,
    fecha_apertura DATE,
    fecha_entrega DATE NOT NULL,
    limite_drive DATE,
    materia VARCHAR(200) NOT NULL,
    tipo VARCHAR(100),
    estado VARCHAR(50) DEFAULT 'pendiente'
);

CREATE INDEX IF NOT EXISTS idx_tareas_estado ON tareas(estado);
CREATE INDEX IF NOT EXISTS idx_tareas_fecha ON tareas(fecha_entrega);
CREATE INDEX IF NOT EXISTS idx_tareas_materia ON tareas(materia);

CREATE TABLE IF NOT EXISTS materias (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL UNIQUE,
    drive_link TEXT
);

CREATE TABLE IF NOT EXISTS suscriptores (
    chat_id BIGINT PRIMARY KEY,
    nombre VARCHAR(200),
    tipo_chat VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS documentos_drive (
    id SERIAL PRIMARY KEY,
    materia_id INTEGER NOT NULL REFERENCES materias(id) ON DELETE CASCADE,
    archivo_id VARCHAR(500) NOT NULL,
    nombre VARCHAR(500) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    enlace TEXT NOT NULL,
    detectado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_at TIMESTAMP,
    notificado BOOLEAN DEFAULT FALSE,
    UNIQUE(materia_id, archivo_id)
);

CREATE INDEX IF NOT EXISTS idx_docs_notificado ON documentos_drive(notificado);
CREATE INDEX IF NOT EXISTS idx_docs_materia ON documentos_drive(materia_id);

CREATE TABLE IF NOT EXISTS control_envios (
    fecha DATE PRIMARY KEY
);
