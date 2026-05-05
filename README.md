# GesDoc — Sistema de Gestión Documental Empresarial

**GesDoc** es un ECM (Enterprise Content Management) desarrollado en **PHP Vanilla + MySQL** para la gestión de documentos vinculados a empresas individuales y consorcios (uniones temporales), con especial enfoque en licitaciones y contratos.

---

## ✨ Características Principales

- 🔐 **Autenticación JWT** con expiración de 30 minutos y Cookie HttpOnly
- 🏢 **Gestión de Empresas** con representante legal, RUT y RUP
- 🤝 **Gestión de Consorcios** con porcentajes de participación por empresa
- 📁 **Repositorio Documental** con árbol de carpetas, Drag & Drop y versionado
- 🔍 **Buscador de Licitaciones** con búsqueda cruzada empresa ↔ consorcio
- 📦 **Almacenamiento externo seguro** (fuera del webroot)
- 🚫 **Sin dependencias de CDN** — todas las librerías instaladas localmente

---

## 🛠 Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.0+ (sin frameworks) |
| Base de datos | MySQL / MariaDB |
| Frontend | Bootstrap 5.3, jQuery 3.7, DataTables 1.13, SweetAlert2 |
| Seguridad | JWT (HMAC SHA256) |

---

## 🚀 Instalación

### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/gesdoc.git
cd gesdoc
```

### 2. Configurar el entorno
```bash
cp config.example.php config.php
```
Edita `config.php` con tus datos reales de base de datos, JWT secret y ruta de almacenamiento.

### 3. Crear la base de datos
```bash
mysql -u root -p < database.sql
```
Esto crea la base de datos `gesdoc`, todas las tablas y el usuario administrador por defecto.

### 4. Crear el directorio de almacenamiento externo
```bash
# Windows
mkdir D:\gesdoc_storage

# Linux/Mac
mkdir /home/usuario/gesdoc_storage
chmod 755 /home/usuario/gesdoc_storage
```
Asegúrate de que la ruta coincida con `EXTERNAL_STORAGE_PATH` en tu `config.php`.

### 5. Servir con Apache (XAMPP)
Coloca el proyecto en `htdocs/gesdoc` y accede a:
```
http://localhost/gesdoc
```

---

## 🔑 Credenciales por defecto

> ⚠️ **Cambia estas credenciales inmediatamente en producción.**

| Campo | Valor |
|---|---|
| Correo | `admin@gesdoc.com` |
| Contraseña | `admin123` |

---

## 📁 Estructura del Proyecto

```
gesdoc/
├── api/                    # Endpoints del backend (JWT protegidos)
│   ├── empresas.php
│   ├── consorcios.php
│   ├── repositorio.php
│   └── licitaciones.php
├── assets/
│   ├── css/                # Bootstrap, FontAwesome, DataTables, SweetAlert2 (locales)
│   ├── js/                 # jQuery, Bootstrap JS, helpers.js (locales)
│   └── webfonts/           # Fuentes de FontAwesome
├── docs/
│   └── CARACTERISTICAS.md  # Documentación técnica completa
├── config.example.php      # Plantilla de configuración (copia como config.php)
├── database.sql            # Script DDL completo
├── index.php               # Dashboard principal
├── login.php / logout.php
├── empresas.php
├── consorcios.php
├── repositorio.php
└── licitaciones.php
```

---

## 📖 Documentación

La documentación técnica detallada (base de datos, módulos, API, seguridad) está en:
```
docs/CARACTERISTICAS.md
```

---

## 🗺 Hoja de Ruta

- [ ] Alertas de vencimiento RUT/RUP
- [ ] Exportación a Excel/PDF en módulo de Licitaciones
- [ ] Gestión de Usuarios y Roles (RBAC) desde la UI
- [ ] Flujo de aprobación documental con notificaciones SMTP
- [ ] Log de Auditoría visible en el panel de administración

---

## 📄 Licencia

Uso privado / Propietario. Todos los derechos reservados.
