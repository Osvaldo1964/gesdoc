-- ══════════════════════════════════════════════════════════════
-- MIGRACIÓN: Módulo Procesos Licitatorios
-- Ejecutar sobre una BD gesdoc ya existente
-- ══════════════════════════════════════════════════════════════
USE gesdoc;

CREATE TABLE IF NOT EXISTS empresa_estados_financieros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    periodo YEAR NOT NULL,
    activo_corriente DECIMAL(20,2) DEFAULT 0,
    activo_no_corriente DECIMAL(20,2) DEFAULT 0,
    total_activos DECIMAL(20,2) DEFAULT 0,
    pasivo_corriente DECIMAL(20,2) DEFAULT 0,
    pasivo_no_corriente DECIMAL(20,2) DEFAULT 0,
    total_pasivos DECIMAL(20,2) DEFAULT 0,
    patrimonio DECIMAL(20,2) DEFAULT 0,
    ingresos_operacionales DECIMAL(20,2) DEFAULT 0,
    utilidad_neta DECIMAL(20,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_company_periodo (company_id, periodo),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS procesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    entidad_contratante VARCHAR(255) NOT NULL,
    numero_proceso VARCHAR(100) NULL,
    objeto TEXT NULL,
    modalidad ENUM('Licitación Pública','Selección Abreviada','Concurso de Méritos','Contratación Directa','Mínima Cuantía') DEFAULT 'Licitación Pública',
    presupuesto_oficial DECIMAL(20,2) NULL,
    fecha_apertura DATE NULL,
    fecha_cierre DATE NULL,
    estado ENUM('En Preparación','Presentada','Adjudicada','Desierta','Cancelada') DEFAULT 'En Preparación',
    periodo_financiero YEAR NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS proceso_indicadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    indicador ENUM('razon_corriente','nivel_endeudamiento','capital_trabajo','patrimonio','roa','roe') NOT NULL,
    operador ENUM('>=','<=','>','<','=') NOT NULL DEFAULT '>=',
    valor_requerido DECIMAL(20,4) NOT NULL,
    habilitado TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_proceso_indicador (proceso_id, indicador),
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS proceso_criterios_puntuables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    puntaje_maximo DECIMAL(8,2) NOT NULL DEFAULT 0,
    descripcion TEXT NULL,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS proceso_participaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    entity_type ENUM('Company','Consortium') NOT NULL,
    entity_id INT NOT NULL,
    porcentaje_participacion DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    estado ENUM('En Análisis','Presentada','No Presentada') DEFAULT 'En Análisis',
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_proceso_entidad (proceso_id, entity_type, entity_id),
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);
