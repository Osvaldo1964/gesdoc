# GesDoc — Documentación del Sistema
**Versión:** 1.0.1 | **Última actualización:** Mayo 2026

---

## Descripción General

**GesDoc** es un sistema de Gestión Documental Empresarial (ECM - Enterprise Content Management) desarrollado en PHP Vanilla + MySQL. Está diseñado para organizaciones que gestionan documentos vinculados a múltiples empresas y consorcios (uniones temporales), especialmente en contextos de licitaciones y contratos.

---

## 1. Stack Tecnológico

| Capa | Tecnología |
|---|---|
| **Backend** | PHP 8.0+ (sin frameworks) |
| **Base de Datos** | MySQL / MariaDB (PDO) |
| **Frontend** | Bootstrap 5.3, jQuery 3.7, DataTables 1.13 |
| **Notificaciones UI** | SweetAlert2 |
| **Íconos** | FontAwesome 6.4 |
| **Tipografía** | Inter (Google Fonts) |
| **Seguridad API** | JWT (HMAC SHA256) — expiración 30 min |
| **Almacenamiento** | Sistema de archivos externo (`D:\gesdoc_storage`) |

> **Nota:** Todas las librerías JS/CSS (Bootstrap, jQuery, DataTables, SweetAlert2, FontAwesome) están instaladas **localmente** en `assets/` para funcionar sin conexión a internet y maximizar el rendimiento.

---

## 2. Arquitectura de Seguridad

### 2.1 Autenticación JWT
- Al iniciar sesión, el servidor genera un **JSON Web Token** firmado con HMAC SHA256.
- El token se almacena en una **Cookie `HttpOnly`** (inmune a XSS) y opcionalmente en `localStorage` con captura de excepciones (para navegadores con Tracking Prevention).
- **Expiración automática:** 30 minutos de inactividad. Al expirar, el sistema redirige al Login con un mensaje SweetAlert.
- Cada endpoint de la API valida el token antes de procesar cualquier petición.

### 2.2 Almacenamiento Externo Seguro
- Los archivos físicos **no se guardan dentro del directorio público** (`htdocs`).
- Ruta en desarrollo: `D:\gesdoc_storage\`
- Ruta en producción (Hostinger): Configurar en `config.php` → `EXTERNAL_STORAGE_PATH`
- El acceso a los archivos se realiza exclusivamente mediante los endpoints PHP (`api/repositorio.php?action=download`), nunca por URL directa.

### 2.3 Sistema Anti-Caché (Versionado)
- La constante `APP_VERSION` en `config.php` se agrega como parámetro `?v=` a todos los archivos JS y CSS del sistema.
- Al actualizar el sistema, cambiar `APP_VERSION` fuerza a todos los navegadores a descargar los archivos frescos.

---

## 3. Base de Datos

### Diagrama de Tablas

```
roles          users           companies            consortiums
  │               │                │                    │
  └── role_id ────┘                │                    │
                                   │            consortium_members
                                   └─ company_id ───────┘
                                                         │
                            documents ← document_assignments → entity (polymorphic)
                               │
                        document_versions
                               │
                          audit_logs
```

### 3.1 Tabla `companies` — Empresas Individuales
| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT PK | Identificador |
| `name` | VARCHAR(150) | Razón Social (siempre en MAYÚSCULAS) |
| `legal_representative` | VARCHAR(150) | Representante Legal (MAYÚSCULAS) |
| `nit` | VARCHAR(50) UNIQUE | NIT de la empresa |
| `rut_updated_at` | DATE | Última actualización del RUT |
| `rup_updated_at` | DATE | Última actualización del RUP |
| `created_at` | TIMESTAMP | Fecha de registro |

> **Pendiente:** Implementar sistema de alertas automáticas cuando `rut_updated_at` o `rup_updated_at` estén próximas a vencer.

### 3.2 Tabla `consortiums` — Consorcios
| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT PK | Identificador |
| `name` | VARCHAR(150) | Nombre del Consorcio (MAYÚSCULAS) |
| `nit` | VARCHAR(50) | NIT propio (opcional, algunos consorcios no lo tienen) |
| `created_at` | TIMESTAMP | Fecha de creación |

### 3.3 Tabla `consortium_members` — Miembros del Consorcio
| Campo | Tipo | Descripción |
|---|---|---|
| `consortium_id` | INT FK | Referencia al consorcio |
| `company_id` | INT FK | Referencia a la empresa |
| `participation_percentage` | DECIMAL(5,2) | Ej: 33.33, 50.00, 100.00 |

### 3.4 Tabla `documents` — Metadatos de Documentos
| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT PK | Identificador |
| `folder_id` | INT FK NULL | Carpeta contenedora (NULL = raíz) |
| `name` | VARCHAR(255) | Nombre del documento (MAYÚSCULAS) |
| `description` | TEXT | Descripción opcional |
| `status` | ENUM | `Borrador`, `En Revisión`, `Aprobado`, `Archivado` |

### 3.5 Tabla `document_assignments` — Asignación Polimórfica
| Campo | Tipo | Descripción |
|---|---|---|
| `document_id` | INT FK | El documento |
| `entity_type` | ENUM | `Company` o `Consortium` |
| `entity_id` | INT | ID de la empresa o consorcio |

> **Regla clave:** Al asignar un documento a un **Consorcio**, el sistema automáticamente crea vínculos adicionales con todas las **empresas miembro** de ese consorcio. Esto permite buscar documentos por empresa y encontrar tanto sus contratos directos como los contratos de consorcios donde participa.

### 3.6 Tabla `document_versions` — Versiones Físicas
| Campo | Tipo | Descripción |
|---|---|---|
| `document_id` | INT FK | El documento padre |
| `version` | VARCHAR(10) | `v1.0`, `v1.1`, `v2.0`... |
| `file_path` | VARCHAR(500) | Ruta absoluta en el storage externo |
| `file_size` | BIGINT | Tamaño en bytes |
| `uploaded_by` | INT FK | Usuario que subió el archivo |

---

## 4. Módulos del Sistema

### 4.1 Login (`login.php`)
- Formulario con validación de correo y contraseña.
- Genera JWT y lo almacena en Cookie HttpOnly.
- Usa `try/catch` para el `localStorage` (compatibilidad con navegadores estrictos).

### 4.2 Dashboard / Panel Principal (`index.php`)
- Navegación completamente basada en **tarjetas de módulos** (sin menú clásico).
- Sistema de **Breadcrumbs** para navegación jerárquica.
- Tarjetas disponibles: Empresas, Consorcios, Repositorio, Licitaciones.

### 4.3 Módulo de Empresas (`empresas.php` + `api/empresas.php`)
**Funcionalidades:**
- CRUD completo con DataTables (crear, editar, eliminar).
- Formulario en modal con campos: NIT, Razón Social, Representante Legal, Fecha RUT, Fecha RUP.
- Los campos de texto se fuerzan a **MAYÚSCULAS** en frontend (oninput) y backend (strtoupper).
- Validación de NIT duplicado antes de crear o actualizar.
- Protección: No permite eliminar una empresa que pertenezca a un consorcio activo.

### 4.4 Módulo de Consorcios (`consorcios.php` + `api/consorcios.php`)
**Funcionalidades:**
- CRUD completo con interfaz **Maestro-Detalle**.
- Modal con sección dinámica para agregar/quitar empresas miembro con `<select>` y campo de porcentaje.
- **Contador de participación en tiempo real** que cambia de color:
  - 🟠 Naranja: suma < 100%
  - 🟢 Verde: suma exactamente = 100%
  - 🔴 Rojo: suma > 100%
- Las operaciones de creación/edición usan **transacciones PDO** para garantizar integridad.
- Al eliminar un consorcio, los miembros se desvinculan automáticamente (ON DELETE CASCADE).

### 4.5 Módulo de Repositorio (`repositorio.php` + `api/repositorio.php`)
**Interfaz de dos columnas:**
- **Panel izquierdo:** Árbol jerárquico de carpetas con opciones de crear subcarpeta, renombrar y eliminar (inline, con botones al hover).
- **Panel derecho:** Tabla de documentos de la carpeta seleccionada.

**Funcionalidades:**
- Crear/renombrar/eliminar carpetas y subcarpetas sin límite de profundidad.
- **Subida de archivos con Drag & Drop** o selector de archivo.
- Tipos permitidos: `pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, jpeg, png, gif, zip, rar, txt, csv`.
- **Versionado documental:** Subir una nueva versión no sobreescribe; genera `v1.0 → v1.1 → v1.2`.
- Íconos visuales por tipo de archivo (PDF rojo, Word azul, Excel verde, etc.).
- Badges de estado coloreados (Borrador gris, En Revisión amarillo, Aprobado verde, Archivado violeta).
- Columna "Vinculado a" mostrando empresa o consorcio asignado.
- **Descarga segura:** Los archivos se sirven via PHP desde el storage externo, nunca por URL directa.
- **Asignación al consorcio → propagación automática:** Al vincular un documento a un Consorcio, el sistema crea automáticamente vínculos con todas las empresas miembro del consorcio.

### 4.6 Módulo de Licitaciones (`licitaciones.php` + `api/licitaciones.php`)
**Buscador avanzado con filtros:**
| Filtro | Descripción |
|---|---|
| Búsqueda libre | Texto en nombre o descripción del documento |
| Empresa | Selección de empresa por nombre y NIT |
| Consorcio | Selección directa de consorcio |
| Estado | Filtro por estado documental |
| Rango de fechas | Desde/hasta (por fecha de actualización) |

**Búsqueda cruzada (característica principal):**
> Al filtrar por **empresa**, el sistema devuelve:
> 1. Documentos asignados **directamente** a la empresa.
> 2. Documentos asignados a **consorcios donde esa empresa participa**.
> 3. Se muestra el **% de participación** en cada consorcio.

**Indicadores visuales:**
- Banner contextual al seleccionar empresa: muestra en cuántos consorcios participa.
- Filas de vínculos por consorcio resaltadas con fondo violeta suave.
- Badge de tipo de entidad (azul = Empresa / violeta = Consorcio).
- Descarga directa desde la tabla de resultados.

---

## 5. Estructura de Archivos

```
gesdoc/
├── api/
│   ├── empresas.php        # API CRUD Empresas
│   ├── consorcios.php      # API CRUD Consorcios + Miembros
│   ├── repositorio.php     # API Carpetas, Subida, Versiones, Descarga
│   └── licitaciones.php    # API Búsqueda cruzada
├── assets/
│   ├── css/
│   │   ├── style.css               # Hoja de estilos principal del sistema
│   │   ├── bootstrap.min.css       # Bootstrap 5.3 (local)
│   │   ├── all.min.css             # FontAwesome 6.4 (local)
│   │   ├── dataTables.bootstrap5.min.css
│   │   └── sweetalert2.min.css
│   ├── js/
│   │   ├── helpers.js              # Funciones comunes: dataTablesLang, formatCurrency, handleApiError
│   │   ├── jquery-3.7.0.min.js    # (local)
│   │   ├── bootstrap.bundle.min.js # (local)
│   │   ├── jquery.dataTables.min.js
│   │   ├── dataTables.bootstrap5.min.js
│   │   └── sweetalert2.all.min.js
│   └── webfonts/                   # Fuentes de FontAwesome (local)
├── docs/
│   └── CARACTERISTICAS.md          # Este archivo
├── auth.php                # Lógica de autenticación
├── config.php              # Configuración global (BD, JWT, Storage, Versión)
├── database.sql            # Script DDL completo de la base de datos
├── jwt_helper.php          # Clase JWT (encode/decode HMAC SHA256)
├── index.php               # Dashboard — Panel principal con tarjetas
├── login.php               # Página de inicio de sesión
├── logout.php              # Cierre de sesión (limpia cookie y localStorage)
├── empresas.php            # Módulo Empresas
├── consorcios.php          # Módulo Consorcios
├── repositorio.php         # Módulo Repositorio Documental
└── licitaciones.php        # Módulo Consulta de Licitaciones

D:\gesdoc_storage\          # Almacenamiento físico externo (FUERA del webroot)
```

---

## 6. Helpers JavaScript (`assets/js/helpers.js`)

El objeto global `GesDocHelpers` centraliza funciones reutilizables para evitar redundancia entre módulos:

| Función | Descripción |
|---|---|
| `GesDocHelpers.dataTablesLang` | Objeto de traducción al español para DataTables (evita llamadas AJAX externas con CORS) |
| `GesDocHelpers.formatCurrency(amount)` | Formato numérico con `,` para miles y `.` para decimales (ej: `1,234,567.89`) |
| `GesDocHelpers.handleApiError(xhr)` | Manejador centralizado de errores AJAX. Si es 401 → modal de sesión expirada + redirige a Login |

---

## 7. Configuración (`config.php`)

```php
define('JWT_SECRET',           'SuperSecretKey_GesDoc_2026_!@#$');  // Cambiar en producción
define('APP_VERSION',          '1.0.1');           // Incrementar para limpiar caché
define('DB_HOST',              'localhost');
define('DB_USER',              'root');
define('DB_PASS',              '');
define('DB_NAME',              'gesdoc');
define('EXTERNAL_STORAGE_PATH','D:\gesdoc_storage'); // Cambiar en producción
define('SMTP_HOST',            'smtp.hostinger.com');
define('BASE_URL',             'http://localhost/gesdoc');
```

---

## 8. Credenciales por Defecto

> ⚠️ Cambiar estas credenciales inmediatamente en producción.

| Campo | Valor |
|---|---|
| **Correo** | admin@gesdoc.com |
| **Contraseña** | admin123 |
| **Rol** | Administrador |

---

## 9. Requisitos del Servidor

- Servidor Web: **Apache 2.4+** con `mod_rewrite` habilitado.
- **PHP 8.0+** con extensiones: `pdo_mysql`, `fileinfo`, `json`.
- **MySQL 5.7+** / MariaDB 10.4+.
- Directorio de storage externo con **permisos de escritura** para el usuario del servidor web (en Windows: usuario de Apache/XAMPP; en Linux: `www-data`).
- Configuración SMTP activa para notificaciones (Hostinger u otro proveedor).

---

## 10. Hoja de Ruta — Próximas Funcionalidades

- [ ] **Alertas de vencimiento RUT/RUP:** Notificación automática cuando la fecha de actualización del RUT o RUP de una empresa está próxima a vencer (dashboard + correo).
- [ ] **Módulo de Reportes:** Exportación a Excel/PDF de resultados de búsqueda.
- [ ] **Gestión de Usuarios:** CRUD de usuarios y roles (RBAC) desde la interfaz.
- [ ] **Flujo de Aprobación:** Workflow de revisión y aprobación de documentos con notificaciones SMTP.
- [ ] **Log de Auditoría:** Vista en el panel de administración de la tabla `audit_logs`.
- [ ] **Buscador full-text:** Búsqueda dentro del contenido de PDFs y documentos.
- [ ] **Configuración SMTP:** Formulario de administración para configurar el servidor de correo desde la UI.
