DROP DATABASE IF EXISTS gesdoc;
CREATE DATABASE IF NOT EXISTS gesdoc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gesdoc;

-- Usuarios y Roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Empresas Individuales
CREATE TABLE companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    legal_representative VARCHAR(150) NULL,
    nit VARCHAR(50) NOT NULL UNIQUE,
    rut_updated_at DATE NULL,
    rup_updated_at DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Consorcios o Uniones Temporales
CREATE TABLE consortiums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    nit VARCHAR(50) NULL, -- Algunos consorcios tienen NIT propio, otros no
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Relación para saber qué empresas conforman un Consorcio y su porcentaje de participación
CREATE TABLE consortium_members (
    consortium_id INT NOT NULL,
    company_id INT NOT NULL,
    participation_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- ej: 33.33, 50.00
    PRIMARY KEY (consortium_id, company_id),
    FOREIGN KEY (consortium_id) REFERENCES consortiums(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Estructura de Carpetas
CREATE TABLE folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id INT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES folders(id) ON DELETE CASCADE
);

-- Documentos (Metadatos Core)
CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    folder_id INT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    keywords TEXT NULL,          -- Palabras clave separadas por comas para optimizar búsquedas
    status ENUM('Borrador', 'En Revisión', 'Aprobado', 'Archivado') DEFAULT 'Borrador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE CASCADE
);

-- Relación Polimórfica: Documentos vinculados a Empresas o Consorcios
CREATE TABLE document_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    entity_type ENUM('Company', 'Consortium') NOT NULL,
    entity_id INT NOT NULL, -- ID de la Empresa o del Consorcio según entity_type
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

-- Versiones del Documento (Archivos Físicos)
CREATE TABLE document_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    version VARCHAR(10) NOT NULL, -- ej. "1.0", "1.1"
    file_path VARCHAR(500) NOT NULL, -- Ruta externa, ej. "D:/gesdoc_storage/doc_123_v1.0.pdf"
    file_size BIGINT NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Log de Auditoría
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ══════════════════════════════════════════════════════════════
-- MÓDULO: PROCESOS LICITATORIOS
-- ══════════════════════════════════════════════════════════════

-- Estados financieros de empresas (un registro por empresa/año)
CREATE TABLE empresa_estados_financieros (
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

-- Procesos licitatorios
CREATE TABLE procesos (
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

-- Indicadores financieros requeridos por proceso (6 estándar)
CREATE TABLE proceso_indicadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    indicador ENUM('razon_corriente','nivel_endeudamiento','capital_trabajo','patrimonio','roa','roe') NOT NULL,
    operador ENUM('>=','<=','>','<','=') NOT NULL DEFAULT '>=',
    valor_requerido DECIMAL(20,4) NOT NULL,
    habilitado TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_proceso_indicador (proceso_id, indicador),
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

-- Criterios puntuables por proceso
CREATE TABLE proceso_criterios_puntuables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    puntaje_maximo DECIMAL(8,2) NOT NULL DEFAULT 0,
    descripcion TEXT NULL,
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

-- Participaciones: empresa o consorcio en un proceso
CREATE TABLE proceso_participaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proceso_id INT NOT NULL,
    entity_type ENUM('Company','Consortium') NOT NULL,
    entity_id INT NOT NULL,
    estado ENUM('En Análisis','Presentada','No Presentada') DEFAULT 'En Análisis',
    notas TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_proceso_entidad (proceso_id, entity_type, entity_id),
    FOREIGN KEY (proceso_id) REFERENCES procesos(id) ON DELETE CASCADE
);

-- Insertar roles por defecto
INSERT INTO roles (name, description) VALUES 
('Administrador', 'Control total del sistema'),
('Editor', 'Puede subir y modificar documentos'),
('Lector', 'Solo puede ver y descargar documentos');

-- Insertar un usuario admin por defecto (password: admin123)
-- El hash generado es para 'admin123' usando PASSWORD_DEFAULT
INSERT INTO users (role_id, name, email, password) VALUES 
(1, 'Administrador', 'admin@gesdoc.com', '$2y$10$8Tu/y9mKD4cpf4g234QhLuGn4brtFxvRb8kRyK9zpISKD6NZ/zz3W');
