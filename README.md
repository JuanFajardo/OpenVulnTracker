# OpenVulnTracker

Dashboard centralizado para la gestión y visualización de vulnerabilidades de seguridad en proyectos de software. Integra resultados de múltiples herramientas de análisis: **OWASP Dependency-Check (SCA)**, **Trivy (contenedores)** y **Semgrep (SAST)**.

---

## Tabla de contenidos

1. [Requisitos](#requisitos)
2. [Instalación](#instalación)
3. [Acceso inicial](#acceso-inicial)
4. [Estructura del proyecto](#estructura-del-proyecto)
5. [Importar reportes](#importar-reportes)
6. [Convención de nombres de archivo](#convención-de-nombres-de-archivo)
7. [Módulos de análisis](#módulos-de-análisis)
8. [Gestión de usuarios](#gestión-de-usuarios)
9. [Autenticación API (tokens)](#autenticación-api-tokens)
10. [Importación vía API (curl)](#importación-vía-api-curl)
11. [Roles y permisos](#roles-y-permisos)
12. [Comandos Artisan](#comandos-artisan)
13. [Generación de reportes externos](#generación-de-reportes-externos)

---

## Requisitos

| Componente | Versión mínima |
|---|---|
| PHP | 8.2 |
| MySQL | 5.7 / MariaDB 10.4 |
| Node.js | 18 |
| Composer | 2.x |
| XAMPP (Windows) | 8.x |

---

## Instalación
### 0. Montar en un contendor
```
docker-compose up -d --build

docker-compose exec app npm install && docker-compose exec app npm run build

docker-compose exec app composer install

docker-compose exec app php artisan migrate

docker-compose exec app php artisan db:seed

```

### 1. Clonar o copiar el proyecto

Coloca la carpeta `OpenAppSec` dentro de `C:\xampp\htdocs\`:

```
C:\xampp\htdocs\OpenAppSec\
    vulntracker\      ← aplicación Laravel
    scan\             ← reportes JSON de ejemplo
```

### 2. Instalar dependencias PHP

```bash
cd C:\xampp\htdocs\OpenAppSec\vulntracker
composer install
```

### 3. Instalar dependencias Node.js y compilar assets

```bash
npm install
npm run build
```

### 4. Configurar variables de entorno

El archivo `.env` ya viene preconfigurado para XAMPP. Verifica que los valores sean correctos:

```env
APP_NAME=OpenVulnTracker
APP_URL=http://localhost/OpenAppSec/vulntracker/public

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vulntracker
DB_USERNAME=root
DB_PASSWORD=
```

Si tu MySQL tiene contraseña, actualiza `DB_PASSWORD`.

### 5. Ejecutar el setup automático (Windows)

Asegúrate de que **XAMPP MySQL esté corriendo**, luego ejecuta desde `vulntracker\`:

```bash
setup.bat
```

Este script realiza automáticamente:
- Crea la base de datos `vulntracker`
- Ejecuta todas las migraciones
- Crea el usuario administrador inicial

### 6. Setup manual (alternativo)

Si prefieres hacerlo paso a paso:

```bash
# Crear base de datos
mysql -u root -e "CREATE DATABASE IF NOT EXISTS vulntracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Ejecutar migraciones
php artisan migrate --force

# Crear usuario administrador
php artisan db:seed --force
```

---

## Acceso inicial

Una vez completada la instalación, abre tu navegador en:

```
http://localhost/OpenAppSec/vulntracker/public
```

**Credenciales por defecto:**

| Campo | Valor |
|---|---|
| Email | `admin@vulntracker.local` |
| Contraseña | `Admin@1234` |

> **Importante:** Cambia la contraseña del administrador después del primer acceso.

---

## Estructura del proyecto

```
├── openvulntracker/               ← Aplicación Laravel
│   ├── app/
│   │   ├── Console/Commands/      ← Comandos Artisan de importación
│   │   ├── Http/Controllers/      ← Controladores web y API
│   │   └── Models/                ← Project, Scan, Dependency, Vulnerability...
│   ├── resources/views/           ← Vistas Blade
│   ├── routes/
│   │   ├── web.php                ← Rutas web
│   │   └── api.php                ← Rutas API
│   ├── public/                    ← Archivos públicos (assets compilados)
│   ├── .env                       ← Configuración del entorno
│   └── setup.bat                  ← Script de instalación para 
```

### Base de datos (tablas principales)

| Tabla | Descripción |
|---|---|
| `projects` | Un registro por proyecto escaneado |
| `scans` | Historial de escaneos SCA por proyecto y rama |
| `dependencies` | Dependencias vulnerables detectadas por SCA |
| `vulnerabilities` | CVEs/GHSAs con métricas CVSS v2 y v3 |
| `trivy_scans` | Escaneos de imágenes/contenedores (Trivy) |
| `trivy_vulnerabilities` | Vulnerabilidades detectadas por Trivy |
| `sast_scans` | Escaneos de código estático (Semgrep) |
| `sast_findings` | Hallazgos SAST con severidad y ubicación en código |
| `api_tokens` | Tokens de autenticación para la API |
| `vulnerability_states` | Estados de gestión por vulnerabilidad (mitigado, aceptado, etc.) |

---

## Importar reportes

Hay tres formas de importar un reporte:

### Opción A — Interfaz web

1. Inicia sesión en la aplicación
2. Haz clic en **"Import Report"** en el menú lateral
3. Selecciona el tipo de reporte (o usa **Auto-detectar**)
4. Arrastra o selecciona tu archivo JSON
5. El sistema muestra automáticamente el **proyecto** y la **rama** extraídos del nombre del archivo
6. Haz clic en **"Importar"**

### Opción B — Comando Artisan (CLI)

```bash
# SCA — OWASP Dependency-Check
php artisan vuln:import ruta/al/reporte.json

# Trivy — contenedor / imagen
php artisan trivy:import ruta/al/proyecto_rama.json

# SAST — Semgrep
php artisan sast:import ruta/al/proyecto_rama.json
```

Con los reportes de ejemplo incluidos:

```bash
php artisan vuln:import ../scan/ferre/dependency-check-report.json
php artisan vuln:import ../scan/juice/dependency-check-report.json
```

### Opción C — API REST (curl)

Ver sección [Importación vía API](#importación-vía-api-curl).

---

## Convención de nombres de archivo

El nombre del archivo JSON determina el **proyecto** y la **rama** en el sistema.

**Formato:** `<nombre-proyecto>_<rama>.json`

El separador es el **último guion bajo** (`_`) del nombre del archivo (sin extensión).

| Nombre de archivo | Proyecto | Rama |
|---|---|---|
| `proyecto-dos_main.json` | `proyecto-dos` | `main` |
| `juice_develop.json` | `juice` | `develop` |
| `mi_app_v2_staging.json` | `mi_app_v2` | `staging` |
| `dvna_main.json` | `dvna` | `main` |

> **Nota para SCA (Dependency-Check):** El sistema usa el nombre del archivo para determinar proyecto y rama, ignorando el campo `projectInfo.name` del JSON.

---

## Módulos de análisis

### SCA — Software Composition Analysis

Analiza las dependencias de tu proyecto usando **OWASP Dependency-Check**.

- Detecta CVEs y GHSAs en librerías de terceros
- Muestra severidad CVSS v2 y v3
- Distingue dependencias directas y transitivas
- Permite gestionar el estado de cada vulnerabilidad (sin revisión / mitigado / aceptado)

**Generar reporte:**

```bash
# Para proyectos Node.js / npm
dependency-check --project "mi-proyecto" --scan . --format JSON --out dependency-check-report.json

# Para proyectos PHP / Composer
dependency-check --project "mi-proyecto" --scan . --enableExperimental --format JSON --out dependency-check-report.json
```

Renombra el archivo generado con el formato `proyecto_rama.json` antes de importarlo.

---

### Trivy — Escaneo de contenedores e imágenes

Analiza imágenes Docker y sistemas de archivos con **Trivy**.

**Generar reporte:**

```bash
# Imagen Docker
trivy image --format json --output mi-app_main.json mi-imagen:latest

# Sistema de archivos
trivy fs --format json --output mi-app_main.json .

# Imagen desde un archivo tar
trivy image --input imagen.tar --format json --output mi-app_main.json
```

**Importar:**

```bash
php artisan trivy:import mi-app_main.json
```

---

### SAST — Análisis estático de código (Semgrep)

Detecta vulnerabilidades en el código fuente usando **Semgrep**.

**Generar reporte:**

```bash
# Análisis completo con reglas de seguridad
semgrep --config=auto --json --output proyecto_rama.json .

# Solo reglas de seguridad OWASP
semgrep --config=p/owasp-top-ten --json --output proyecto_rama.json .
```

**Importar:**

```bash
php artisan sast:import proyecto_rama.json
```

**Severidades:**

| Semgrep | OpenVulnTracker | Color |
|---|---|---|
| ERROR | Alta | Rojo |
| WARNING | Media | Amarillo |
| INFO | Informativa | Azul |

---

## Gestión de usuarios

Accesible desde el menú lateral **"User Management"** (solo administradores).

### Roles disponibles

| Rol | Puede ver | Puede importar | Gestiona usuarios |
|---|---|---|---|
| `admin` | Sí | Sí | Sí |
| `analyst` | Sí | Sí | No |
| `viewer` | Sí | No | No |

### Crear usuarios

1. Ve a **Admin → User Management**
2. Haz clic en **"Nuevo Usuario"**
3. Completa nombre, correo, contraseña y rol
4. Guarda

---

## Autenticación API (tokens)

Los tokens permiten importar reportes de forma automática desde pipelines CI/CD sin necesidad de sesión web.

### Crear un token

1. Inicia sesión como administrador
2. Ve a **Admin → Autenticación**
3. Haz clic en **"Nuevo Token"**
4. Asigna un nombre descriptivo y duración (días)
5. Copia el token generado — **solo se muestra una vez**

El token tiene el formato:

```
vt_a1b2c3d4e5f6...  (68 caracteres en total)
```

### Gestión de tokens

- Los tokens próximos a vencer (≤ 7 días) se muestran en **amarillo**
- Los tokens vencidos se muestran en **rojo** y son rechazados por la API
- Puedes editar la duración o eliminar tokens desde la tabla

---

## Importación vía API (curl)

Ideal para integraciones con **GitHub Actions**, **GitLab CI**, **Jenkins** u otros pipelines.

### Endpoint

```
POST /api/import
```

### Headers requeridos

```
Authorization: Bearer <tu-token>
```

### Parámetros (form-data)

| Parámetro | Tipo | Requerido | Descripción |
|---|---|---|---|
| `file` | archivo | Sí | Archivo JSON del reporte |
| `scan_type` | string | No | `auto` (por defecto), `sca`, `trivy`, `sast` |

### Ejemplos

**Auto-detectar formato:**

```bash
curl -X POST \
  -H "Authorization: Bearer vt_xxxx..." \
  -F "file=@proyecto_main.json" \
  http://localhost/OpenAppSec/vulntracker/public/api/import
```

**Forzar tipo SCA:**

```bash
curl -X POST \
  -H "Authorization: Bearer vt_xxxx..." \
  -F "file=@proyecto_main.json" \
  -F "scan_type=sca" \
  http://localhost/OpenAppSec/vulntracker/public/api/import
```

**Forzar tipo Trivy:**

```bash
curl -X POST \
  -H "Authorization: Bearer vt_xxxx..." \
  -F "file=@mi-imagen_main.json" \
  -F "scan_type=trivy" \
  http://localhost/OpenAppSec/vulntracker/public/api/import
```

### Respuestas

**Éxito (`200`):**

```json
{
  "success": true,
  "message": "Reporte importado exitosamente."
}
```

**Error de validación (`422`):**

```json
{
  "success": false,
  "error": "Selecciona un archivo JSON."
}
```

**Token inválido o vencido (`401`):**

```json
{
  "error": "Token inválido o vencido."
}
```

### Integración con GitHub Actions

```yaml
- name: Importar reporte a OpenVulnTracker
  run: |
    curl -X POST \
      -H "Authorization: Bearer ${{ secrets.OPENVULNTRACKER_TOKEN }}" \
      -F "file=@dependency-check-report.json" \
      -F "scan_type=sca" \
      http://tu-servidor/api/import
```

---

## Roles y permisos

| Acción | admin | analyst | viewer |
|---|---|---|---|
| Ver dashboard y proyectos | ✅ | ✅ | ✅ |
| Ver detalle de vulnerabilidades | ✅ | ✅ | ✅ |
| Importar reportes (web) | ✅ | ✅ | ❌ |
| Importar reportes (API) | ✅ | ✅ | ❌ |
| Gestionar estados de vulnerabilidades | ✅ | ✅ | ❌ |
| Eliminar proyectos y escaneos | ✅ | ❌ | ❌ |
| Gestionar usuarios | ✅ | ❌ | ❌ |
| Crear y eliminar tokens API | ✅ | ❌ | ❌ |

---

## Comandos Artisan

Ejecuta todos los comandos desde la carpeta `vulntracker/`:

```bash
# Importar reporte SCA (Dependency-Check)
php artisan vuln:import ruta/al/reporte.json

# Importar con proyecto y rama explícitos
php artisan vuln:import reporte.json --project="mi-proyecto" --branch="main"

# Forzar reimportación aunque ya exista un escaneo de esa fecha
php artisan vuln:import reporte.json --force

# Importar reporte Trivy
php artisan trivy:import proyecto_rama.json

# Importar reporte SAST (Semgrep)
php artisan sast:import proyecto_rama.json

# Ejecutar migraciones
php artisan migrate

# Recrear usuario administrador
php artisan db:seed

# Iniciar servidor de desarrollo
php artisan serve

# Limpiar caché de configuración
php artisan config:clear
php artisan cache:clear
```

---

## Generación de reportes externos

### OWASP Dependency-Check

Descarga: https://owasp.org/www-project-dependency-check/

```bash
# Windows
dependency-check.bat --project "nombre" --scan . --format JSON --out reporte.json

# Linux/macOS
./dependency-check.sh --project "nombre" --scan . --format JSON --out reporte.json
```

### Trivy

Descarga: https://aquasecurity.github.io/trivy/

```bash
# Instalar en Linux
curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/main/contrib/install.sh | sh

# Instalar en Windows (via Chocolatey)
choco install trivy

# Escanear imagen
trivy image --format json --output mi-app_main.json nginx:latest
```

### Semgrep

Descarga: https://semgrep.dev/docs/getting-started/

```bash
# Instalar
pip install semgrep

# Generar reporte
semgrep --config=auto --json --output mi-proyecto_main.json .
```

---

## Solución de problemas comunes

**Error de conexión a MySQL**
Verifica que XAMPP MySQL esté corriendo y que las credenciales en `.env` sean correctas.

**Error 500 al acceder a la aplicación**
Ejecuta `php artisan config:clear` y verifica que el archivo `.env` exista y tenga `APP_KEY` definido.

**Los assets (CSS/JS) no cargan**
Ejecuta `npm run build` desde la carpeta `vulntracker/` para compilar los archivos frontend.

**El archivo JSON es rechazado**
Verifica que el archivo no supere 200 MB y que sea un JSON válido generado por una de las herramientas soportadas.

**Token API rechazado**
El token puede estar vencido. Verifica la fecha de expiración en **Admin → Autenticación** y crea uno nuevo si es necesario.

---

*OpenVulnTracker — Powered by OWASP Dependency-Check, Trivy y Semgrep*
