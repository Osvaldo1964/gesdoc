-- ══════════════════════════════════════════════════════════════
-- MIGRACIÓN: Porcentaje de participación en proceso_participaciones
-- Ejecutar sobre una BD gesdoc ya existente (una sola vez)
-- ══════════════════════════════════════════════════════════════
USE gesdoc;

ALTER TABLE proceso_participaciones
    ADD COLUMN porcentaje_participacion DECIMAL(5,2) NOT NULL DEFAULT 100.00
    AFTER entity_id;
