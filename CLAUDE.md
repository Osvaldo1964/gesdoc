# GesDoc — Configuración del Asistente

## Idioma
Responde **siempre en español** en todas las conversaciones, explicaciones, comentarios de código y mensajes.

## Proyecto
- **Nombre:** GesDoc
- **Descripción:** Aplicación web PHP/MySQL para gestión documental (ECM) de un pool de empresas.
- **Stack:** PHP 8.0+ vanilla, MySQL/MariaDB, PDO, Bootstrap 5.3, jQuery 3.7, DataTables 1.13, SweetAlert2, FontAwesome — todos locales (sin CDN).
- **Autenticación:** JWT HMAC SHA256 en cookie HttpOnly (`gesdoc_token`), 30 min de expiración.
- **Almacenamiento externo:** `D:\gesdoc_storage` (fuera del webroot).

## Convenciones de código
- Sanitización de strings: `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` — NO usar `FILTER_SANITIZE_STRING` (obsoleto en PHP 8).
- Validación de enums con `in_array()` y lista blanca explícita.
- `uploaded_by` y campos de usuario siempre desde el JWT, nunca hardcodeados.
- Toda acción de escritura registra en `audit_logs` mediante `logAudit()` de `api/auth_helper.php`.
- Upsert de estados financieros: `INSERT ... ON DUPLICATE KEY UPDATE` con clave única `(company_id, periodo)`.

## Módulos principales
| Archivo | Descripción |
|---|---|
| `index.php` | Dashboard / inicio |
| `empresas.php` + `api/empresas.php` | CRUD empresas + estados financieros por año |
| `consorcios.php` + `api/consorcios.php` | CRUD consorcios |
| `repositorio.php` + `api/repositorio.php` | Repositorio documental |
| `consulta_documental.php` + `api/consulta_documental.php` | Consulta documental (antes "Licitaciones") |
| `procesos.php` + `api/procesos.php` | Módulo Procesos Licitatorios |
| `proceso_detalle.php` | Detalle de proceso: indicadores, criterios, participantes y análisis |
| `api/auth_helper.php` | `getValidUser()` y `logAudit()` compartidos |
