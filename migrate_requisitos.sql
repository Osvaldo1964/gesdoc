-- ══════════════════════════════════════════════════════════════
-- MIGRACIÓN: Requisitos Habilitantes por Proceso
-- Ejecutar sobre una BD gesdoc ya existente (una sola vez)
-- ══════════════════════════════════════════════════════════════
USE gesdoc;

-- Tabla 1: Requisitos habilitantes del proceso
CREATE TABLE IF NOT EXISTS proceso_requisitos_habilitantes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    tipo       ENUM('Jurídico','Técnico','Financiero') NOT NULL,
    descripcion TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

-- Tabla 2: Cumplimiento de requisitos por participante
CREATE TABLE IF NOT EXISTS proceso_requisitos_cumplimiento (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    requisito_id    INT NOT NULL,
    participacion_id INT NOT NULL,
    cumple          ENUM('Pendiente','Cumple','No Cumple') NOT NULL DEFAULT 'Pendiente',
    observacion     TEXT NULL,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_req_part (requisito_id, participacion_id),
    FOREIGN KEY (requisito_id)     REFERENCES proceso_requisitos_habilitantes(id) ON DELETE CASCADE,
    FOREIGN KEY (participacion_id) REFERENCES proceso_participaciones(id)         ON DELETE CASCADE
);
