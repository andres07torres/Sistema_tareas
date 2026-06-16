<?php
/**
 * Script para crear la tabla documentos_drive
 * Ejecutar: php scratch/create_documentos_drive.php
 */
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();

    $sql = "
    CREATE TABLE IF NOT EXISTS documentos_drive (
        id SERIAL PRIMARY KEY,
        materia_id INTEGER NOT NULL REFERENCES materias(id) ON DELETE CASCADE,
        archivo_id VARCHAR(500) NOT NULL,
        nombre VARCHAR(500) NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        enlace TEXT NOT NULL,
        detectado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notificado BOOLEAN DEFAULT FALSE,
        UNIQUE(materia_id, archivo_id)
    );

    CREATE INDEX IF NOT EXISTS idx_docs_notificado ON documentos_drive(notificado);
    CREATE INDEX IF NOT EXISTS idx_docs_materia ON documentos_drive(materia_id);
    ";

    $db->exec($sql);
    echo "✓ Tabla 'documentos_drive' creada exitosamente.\n";

} catch (PDOException $e) {
    echo "✗ Error al crear la tabla: " . $e->getMessage() . "\n";
    exit(1);
}
