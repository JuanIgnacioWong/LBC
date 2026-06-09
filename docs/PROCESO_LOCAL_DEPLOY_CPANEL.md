# Proceso Profesional: Local + Deploy WordPress en cPanel con GitHub

Este documento define un flujo seguro y profesional para:

- desarrollo local con DDEV (Docker),
- revisión de cambios,
- despliegue continuo a cPanel desde GitHub,
- separación staging/producción,
- rollback operativo.

## 1) Arquitectura recomendada

- Local:
  - DDEV + Docker Desktop
  - WordPress + MariaDB + Redis (contenedor auxiliar)
  - Theme en `wp-content/themes/liga-basket-chile`
- Repositorio GitHub:
  - Versionar código (`wp-content/themes`, plugins propios, mu-plugins)
  - No versionar uploads, caché, secretos
- Servidor cPanel:
  - Producción: `lbcchile.com`, con WordPress ya instalado en `public_html/`
  - Staging opcional: subdominio dedicado, por ejemplo `staging.lbcchile.com`
- CI/CD:
  - Opción A: cPanel Git Version Control (`.cpanel.yml`)
  - Opción B: GitHub Actions (`.github/workflows/deploy-cpanel.yml`) por SSH + rsync

Importante: este repositorio despliega solo el tema `liga-basket-chile`. No despliega WordPress core, base de datos, uploads ni plugins instalados en producción.

## 2) Requisitos previos

Instalar en tu máquina:

1. Docker Desktop
2. DDEV
3. Git

Verificar:

```bash
docker --version
ddev version
git --version
```

## 3) Estructura del proyecto

Archivos clave agregados:

- `.ddev/config.yaml`
- `.ddev/docker-compose.redis.yaml`
- `.ddev/commands/web/wp-bootstrap`
- `.ddev/commands/web/wp-seed-demo`
- `.github/workflows/deploy-cpanel.yml`
- `.gitignore`

## 4) Levantar entorno local

Desde la raíz del proyecto:

```bash
ddev start
```

Luego bootstrap WordPress (si no está instalado):

```bash
ddev wp-bootstrap
```

Esto:

- descarga WordPress core localmente,
- crea `wp-config.php`,
- instala WordPress,
- activa el tema `liga-basket-chile`.

Acceso:

- URL principal DDEV: `https://lbc.ddev.site`
- Puerto solicitado: `http://127.0.0.1:8085`
- Usuario por defecto bootstrap: `admin`
- Clave por defecto bootstrap: `admin123!`

Recomendación inmediata:

- cambiar la clave apenas ingreses al admin.

## 5) Cargar datos demo para revisión visual

Opción CLI:

```bash
ddev wp-seed-demo
```

Opción Admin:

- `Apariencia > Opciones Liga Basket > Cargar datos demo`

Contenido que crea:

- 2 divisiones
- 6 equipos
- 12 partidos
- 6 noticias
- sponsors demo

## 6) Flujo de ramas recomendado

- `main`: producción
- `develop`: staging
- `feature/*`: trabajo de nuevas tareas

Flujo:

1. Crear rama `feature/...`
2. Push y Pull Request a `develop`
3. QA en staging
4. Merge `develop` -> `main`
5. Deploy automático a producción

## 7) Configuración de cPanel para deploy

### 7.1 Producción existente

En cPanel:

- Producción: `public_html/`
- Dominio principal: `lbcchile.com`
- WordPress debe existir ya en `public_html/`, incluyendo `wp-config.php`
- El tema quedará en `public_html/wp-content/themes/liga-basket-chile/`

Staging es opcional. Si se usa:

- crear subdominio, por ejemplo `staging.lbcchile.com`
- instalar WordPress en la carpeta del subdominio
- usar esa ruta como destino de staging

### 7.2 Opción A: cPanel Git Version Control

Esta opción usa el archivo `.cpanel.yml` incluido en la raíz del repositorio.

En cPanel:

1. Ir a `Git Version Control`.
2. Crear o clonar el repositorio desde GitHub.
3. Usar una carpeta de clonación fuera de `public_html`, por ejemplo:

```bash
/home/USUARIO/repositories/LBC
```

4. Activar deployment desde cPanel.
5. Ejecutar `Deploy HEAD Commit`.

El archivo `.cpanel.yml` copia:

```bash
wp-content/themes/liga-basket-chile/
```

hacia:

```bash
$HOME/public_html/wp-content/themes/liga-basket-chile/
```

Esta opción es adecuada si se quiere desplegar manualmente desde cPanel después de hacer push a GitHub.

### 7.3 Opción B: GitHub Actions por SSH

Esta opción despliega automáticamente al hacer push.

Flujo:

- push a `develop`: despliega a staging, si `CPANEL_STAGING_PATH` existe
- push a `main`: despliega a producción en `public_html`

El workflow valida que exista `wp-config.php` en la ruta remota antes de copiar archivos. Esto reduce el riesgo de desplegar sobre una carpeta equivocada.

### 7.4 Habilitar SSH en cPanel

- Activar acceso SSH (si el plan lo permite).
- Crear o importar llave pública para despliegue.

### 7.5 Definir secretos en GitHub

En `Settings > Secrets and variables > Actions`, crear:

- `CPANEL_SSH_PRIVATE_KEY`
- `CPANEL_SSH_HOST`
- `CPANEL_SSH_PORT` (ejemplo `22`)
- `CPANEL_SSH_USER`
- `CPANEL_STAGING_PATH` (ruta absoluta staging)
- `CPANEL_PROD_PATH` (ruta absoluta producción)

Valores esperados para producción:

```bash
CPANEL_SSH_HOST=lbcchile.com
CPANEL_SSH_PORT=22
CPANEL_SSH_USER=USUARIO_CPANEL
CPANEL_PROD_PATH=/home/USUARIO_CPANEL/public_html
```

Si el proveedor entrega un host SSH distinto al dominio, usar ese host en `CPANEL_SSH_HOST`.

Ejemplo de staging, solo si existe:

```bash
CPANEL_STAGING_PATH=/home/USUARIO_CPANEL/staging.lbcchile.com
```

## 8) Cómo funciona el deploy

Workflow: `.github/workflows/deploy-cpanel.yml`

- push a `develop`:
  - despliega el tema a staging
- push a `main`:
  - despliega el tema a producción

Origen:

```bash
wp-content/themes/liga-basket-chile/
```

Destino producción:

```bash
public_html/wp-content/themes/liga-basket-chile/
```

El deploy usa `rsync --delete` solo dentro de la carpeta del tema. No borra plugins, uploads ni archivos del core de WordPress.

Post-deploy:

- intenta flush de caché vía WP-CLI si está disponible en servidor.

## 9) Seguridad recomendada (mínimo profesional)

Aplicar en `wp-config.php` de staging y prod:

```php
define('DISALLOW_FILE_EDIT', true);
define('WP_DEBUG', false);
define('FORCE_SSL_ADMIN', true);
```

Buenas prácticas:

1. Nunca subir `.env` ni llaves privadas al repo.
2. Usar solo secretos de GitHub Actions.
3. Forzar HTTPS en cPanel.
4. Limitar usuarios admin y habilitar 2FA.
5. Mantener plugins y core actualizados.

## 10) Rollback rápido

### Opción A: Git revert

1. Revertir commit problemático en `main`
2. Push
3. El workflow redeploya versión estable

### Opción B: tag estable

1. Mantener tags (`v1.0.0`, `v1.0.1`, etc.)
2. Crear branch temporal desde tag estable
3. Merge a `main` para restaurar

## 11) Checklist de salida a producción

1. QA completo en staging
2. Performance (Lighthouse, caché activa)
3. Backups de DB + archivos
4. Confirmar backup antes del primer deploy sobre `public_html`
5. Confirmar que `CPANEL_PROD_PATH` termina en `/public_html`
6. Confirmar menús, enlaces legales, sitemap
7. Revisar permisos y credenciales
8. Deploy a `main`

## 12) Troubleshooting

### No abre `127.0.0.1:8085`

- Verificar:

```bash
ddev describe
```

- Si hay conflicto de puerto, ajustar `router_http_port` en `.ddev/config.yaml`.

### Deploy falla por permisos en cPanel

- Validar usuario/ruta SSH
- Probar login manual SSH con la misma clave
- Revisar ownership de archivos en ruta destino

### Cambios no reflejados tras deploy

- Limpiar caché de plugin/CDN
- vaciar OPcache (si aplica)
- confirmar que el archivo llegó a la ruta correcta

---

Con esta base, el flujo queda listo para trabajo diario: desarrollo local seguro, revisión rápida, despliegue confiable y trazabilidad completa por GitHub.
