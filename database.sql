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

-- Insertar roles por defecto
INSERT INTO roles (name, description) VALUES 
('Administrador', 'Control total del sistema'),
('Editor', 'Puede subir y modificar documentos'),
('Lector', 'Solo puede ver y descargar documentos');

-- Insertar un usuario admin por defecto (password: admin123)
-- El hash generado es para 'admin123' usando PASSWORD_DEFAULT
INSERT INTO users (role_id, name, email, password) VALUES 
(1, 'Administrador', 'admin@gesdoc.com', '$2y$10$8Tu/y9mKD4cpf4g234QhLuGn4brtFxvRb8kRyK9zpISKD6NZ/zz3W');
