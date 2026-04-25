# Contexto y Auditoria Maestra del Sitio (LBC)

## 1) Objetivo del documento
Este documento consolida el contexto tecnico y una auditoria maestra del sitio para reutilizarlo como base en prompts de ChatGPT o Claude.

Alcance de esta auditoria:
- Auditoria tecnica basada en codigo y configuracion del repositorio.
- No incluye medicion runtime real (Lighthouse, Core Web Vitals en produccion, logs reales, Search Console, GA4).

## 2) Snapshot ejecutivo
- Plataforma: WordPress con tema custom `liga-basket-chile`.
- Dominio funcional: liga de basquetbol (equipos, partidos, divisiones, tabla de posiciones, noticias).
- Backend deportivo propio: CPTs, metadatos, validaciones de reglamento, importadores CSV, calculo de standings, endpoints REST.
- Frontend: tema custom con homepage modular y templates deportivos.
- Entorno local: DDEV + Docker + MariaDB.
- Deploy: GitHub Actions a cPanel por SSH/rsync.

Estado general:
- Base funcional solida para operacion deportiva.
- Deuda tecnica media-alta por coexistencia de capas "legacy" y "nueva" (duplicidad de setup/assets/templates/opciones).
- Oportunidad inmediata en SEO tecnico, rendimiento de imagenes, coherencia de configuracion y robustez operativa de CI/CD.

## 3) Inventario tecnico
### 3.1 Infra y entorno
- DDEV (`.ddev/config.yaml`): WordPress, PHP 8.3, MariaDB 10.11, `WP_DEBUG=1` local.
- Docker Compose (`docker-compose.yml`): `wordpress:6.8.3-php8.3-apache` + MariaDB 10.11.
- Variables ejemplo (`.env.example`) con credenciales base local.

### 3.2 Tema
Ruta:
- `wp-content/themes/liga-basket-chile`

Volumen aproximado:
- 52 archivos en el tema.
- ~14.4k lineas (PHP + JS + CSS).

Modulos clave:
- `inc/cpt-registro.php`: CPT `equipo`, `partido`, `division`.
- `inc/metaboxes.php`: metadatos + validaciones de guardado.
- `inc/tabla-logica.php`: motor standings + desempates + cache.
- `inc/import-equipos.php` y `inc/import-partidos.php`: importacion CSV en 2 pasos.
- `inc/api-endpoints.php`: REST namespace `liga/v1`.
- `inc/public-standings.php`: rutas SEO-friendly `/tabla/{division}/{temporada}`.
- `inc/opciones-tema.php`: pantalla de opciones en admin.
- `inc/seguridad.php`: hardening basico.

### 3.3 Contenido funcional del negocio
- Equipos con contexto competitivo (division + temporada).
- Partidos con estado y reglas deportivas (incluye incomparecencia).
- Tabla calculada por reglas parametrizables.
- Noticias sobre post type nativo `post`.

## 4) Modelo de datos de negocio
### 4.1 CPTs
- `equipo`.
- `partido`.
- `division`.

### 4.2 Metacampos relevantes
Equipo:
- `liga_nombre_equipo`, `liga_logo_equipo`, `liga_ciudad`, `liga_anio_fundacion`, `liga_division`, `liga_temporada`, `liga_color_principal`, `liga_entrenador`, `liga_posicion_manual`, `liga_activar_override`, `liga_equipo_competicion_key`.

Partido:
- `liga_equipo_local`, `liga_equipo_visita`, `liga_division`, `liga_temporada`, `liga_fecha_partido`, `liga_hora_partido`, `liga_cancha`, `liga_estado_partido`, `liga_puntos_local`, `liga_puntos_visita`, `liga_incomparecencia`, `liga_observaciones`.

Division:
- `liga_nombre_division`, `liga_temporada`, `liga_orden_visual`, `liga_activa`.

### 4.3 REST API publica
Namespace:
- `/wp-json/liga/v1`

Endpoints:
- `/tabla`
- `/partidos`
- `/equipos`
- `/noticias`

Filtros:
- `division`, `temporada`, `estado`, `limit`.

## 5) Hallazgos de auditoria (priorizados)

## P0 (critico)
1. Inconsistencia de capa de configuracion (Theme Mod vs Option API).
- Evidencia:
  - Header/footer/home nuevo usan `get_theme_mod(...)`.
  - Panel de opciones guarda `liga_theme_options` y lee via `liga_get_option(...)`.
- Impacto:
  - El admin puede cambiar opciones que no se reflejan en el frontend principal.
  - Riesgo alto de confusion operativa y bugs de contenido.
- Accion:
  - Unificar fuente de verdad (recomendado: Option API o Customizer, no ambos mezclados).

2. Duplicidad de bootstrap de tema y assets con riesgo de drift.
- Evidencia:
  - `functions.php` define setup/assets y luego carga `inc/setup-theme.php` + `inc/enqueue-assets.php`.
  - `inc/enqueue-assets.php` queda parcialmente "anulado" por `function_exists` + `has_action`.
- Impacto:
  - Parte de CSS/JS en `inc/enqueue-assets.php` no se carga realmente.
  - Mantenimiento mas dificil y errores silenciosos.
- Accion:
  - Consolidar en una sola implementacion de setup/assets.

## P1 (alto)
3. Estrategia de versionado de assets anulada por filtro de seguridad.
- Evidencia:
  - Se genera version por `filemtime` pero luego se elimina `?ver=` de scripts y styles.
- Impacto:
  - Cache-busting poco confiable; riesgo de servir assets desactualizados post-deploy.
- Accion:
  - Mantener versionado de assets y retirar eliminacion global de `ver` para assets propios.

4. SEO tecnico incompleto fuera de tabla publica.
- Evidencia:
  - Meta description custom solo en standings publicas.
  - No hay evidencia de OG/Twitter/JSON-LD/canonical en templates principales.
- Impacto:
  - Menor CTR y menor contexto semantico para buscadores/redes.
- Accion:
  - Implementar capa SEO transversal (plugin SEO o capa propia centralizada).

5. Rendimiento de homepage sensible al crecimiento de datos.
- Evidencia:
  - En home se itera cada division y se llama `liga_calcular_tabla_posiciones(...)` por division.
  - Multiples consultas/meta lookups por render.
- Impacto:
  - Escalamiento deficiente con mas divisiones/partidos.
- Accion:
  - Precalculo/cache por contexto y optimizacion de consultas (y/o warmup programado).

6. CI/CD sin gates de calidad.
- Evidencia:
  - Workflow despliega directo en push a `develop`/`main` sin pasos de pruebas/lint.
- Impacto:
  - Mayor probabilidad de degradaciones en staging/produccion.
- Accion:
  - Agregar job de validacion previa (PHP lint, tests, checks minimos de integridad).

## P2 (medio)
7. Activos y templates legacy coexistentes.
- Evidencia:
  - Existen templates/JS/CSS de la version anterior no usados por front principal.
- Impacto:
  - Mayor complejidad cognitiva, deuda tecnica y riesgo de cambios inconsistentes.
- Accion:
  - Catalogar y retirar legado no usado o encapsularlo explicitamente.

8. Imagenes sin optimizaciones de carga sistematicas.
- Evidencia:
  - Varias etiquetas `<img>` sin `loading="lazy"`, `decoding="async"`, `width/height`.
- Impacto:
  - Peor LCP/CLS en escenarios reales.
- Accion:
  - Estandarizar helper de imagen responsive con atributos de rendimiento.

9. Endpoints REST publicos sin capa de control de consumo.
- Evidencia:
  - `permission_callback => __return_true` en endpoints.
- Impacto:
  - Exposicion valida por negocio, pero sin rate limiting/caching puede degradar performance.
- Accion:
  - Cache de respuestas + rate limiting a nivel edge/server.

10. Fallback de noticias con fechas fijas futuras.
- Evidencia:
  - Fechas hardcodeadas `2026-06-xx` en fallback.
- Impacto:
  - Puede verse artificial/inconsistente con fecha actual si no hay contenido.
- Accion:
  - Fallback dinamico relativo a fecha actual o bloque editorial vacio controlado.

## P3 (bajo)
11. Hardening correcto pero basico.
- Evidencia:
  - Headers de seguridad basicos, XML-RPC off, ocultar generator.
- Impacto:
  - Buen baseline, falta CSP/HSTS/estrategia completa server-side.
- Accion:
  - Completar hardening en servidor/proxy + plugin de seguridad si aplica.

## 6) Riesgos de negocio
- Riesgo editorial: cambios en panel de opciones no reflejados en frontend principal.
- Riesgo SEO: bajo enriquecimiento semantico del sitio principal.
- Riesgo operativo: despliegues automaticos sin validaciones suficientes.
- Riesgo de mantenimiento: duplicidades + legado no podado.

## 7) Plan recomendado de remediacion

## Fase 1 (0-7 dias)
- Unificar configuracion (theme mods vs option API).
- Consolidar un solo flujo de encolado de assets.
- Corregir cache-busting de assets.
- Agregar validacion CI minima antes de deploy.

## Fase 2 (1-3 semanas)
- Implementar capa SEO tecnica base (title/meta/canonical/og/twitter/schema).
- Estandarizar imagenes responsive y lazy-loading.
- Optimizar queries de home y aplicar cache por contexto.

## Fase 3 (1-2 meses)
- Podar codigo legacy no usado.
- Añadir observabilidad (errores, tiempos de respuesta, dashboards).
- Definir objetivos cuantitativos de CWV, indexacion y conversion.

## 8) Paquete de contexto minimo para IA (copiar/pegar)
Usa este bloque al inicio de cada prompt en ChatGPT/Claude:

```text
Proyecto: Liga Basket Chile (WordPress custom theme)
Objetivo: [describe objetivo puntual]
Stack: WordPress + PHP 8.3 + MariaDB + DDEV + deploy GitHub Actions -> cPanel
Tema: wp-content/themes/liga-basket-chile
Modelo dominio: divisiones, equipos, partidos, tabla de posiciones, noticias
Backend clave: inc/metaboxes.php, inc/tabla-logica.php, inc/import-*.php, inc/api-endpoints.php, inc/public-standings.php
Riesgos conocidos: configuracion duplicada (theme_mod vs options), assets duplicados, SEO tecnico incompleto, CI sin gates, codigo legacy coexistente
Restricciones: no romper reglas deportivas ni estructura de datos existente
Respuesta requerida: analisis + plan + cambios concretos por archivo + criterios de aceptacion + tests/checklist
```

## 9) Prompt maestro universal (ChatGPT/Claude)
```text
Actua como arquitecto senior WordPress (producto + SEO + performance + seguridad + DX).

Contexto del proyecto:
[PEGAR BLOQUE "Paquete de contexto minimo para IA"]

Tu tarea:
1) Auditar el objetivo solicitado con enfoque de impacto de negocio + riesgo tecnico.
2) Proponer una solucion por fases (quick wins, mediano plazo, largo plazo).
3) Entregar cambios concretos por archivo (que editar, que eliminar, que crear).
4) Definir criterios de aceptacion verificables y checklist de QA.
5) Incluir riesgos, trade-offs y plan de rollback.

Formato de salida obligatorio:
- Resumen ejecutivo (max 10 lineas)
- Hallazgos priorizados (P0/P1/P2/P3)
- Plan de implementacion por fases
- Tabla "Archivo -> Cambio -> Riesgo -> Test"
- Checklist final de validacion

Reglas:
- No des recomendaciones genericas: aterriza a WordPress + este contexto.
- Si falta informacion, enumera supuestos explicitos y continua.
- Prioriza mantener compatibilidad con los metadatos deportivos existentes.
```

## 10) Prompt especializado para ejecutar una auditoria completa automatizada
```text
Quiero una auditoria maestra completa y accionable de este sitio WordPress.

Contexto:
[PEGAR BLOQUE "Paquete de contexto minimo para IA"]

Incluye obligatoriamente estos frentes:
- Arquitectura de tema y deuda tecnica
- Performance backend/frontend
- SEO tecnico/on-page
- Accesibilidad
- Seguridad aplicativa y de despliegue
- Calidad de CI/CD y observabilidad
- Riesgos de negocio y continuidad operativa

Entrega:
1) Score por frente (0-100) con justificacion.
2) 20 hallazgos priorizados con impacto y esfuerzo.
3) Roadmap 30-60-90 dias.
4) Backlog en formato tabla: prioridad, tarea, owner sugerido, estimacion, dependencia.
5) Top 5 quick wins que se puedan ejecutar en 1 semana.
```

## 11) Evidencias tecnicas principales (archivos)
- `wp-content/themes/liga-basket-chile/functions.php`
- `wp-content/themes/liga-basket-chile/inc/enqueue-assets.php`
- `wp-content/themes/liga-basket-chile/inc/seguridad.php`
- `wp-content/themes/liga-basket-chile/inc/opciones-tema.php`
- `wp-content/themes/liga-basket-chile/header.php`
- `wp-content/themes/liga-basket-chile/footer.php`
- `wp-content/themes/liga-basket-chile/template-parts/home/main-panels.php`
- `wp-content/themes/liga-basket-chile/template-parts/home/news.php`
- `wp-content/themes/liga-basket-chile/inc/api-endpoints.php`
- `wp-content/themes/liga-basket-chile/inc/public-standings.php`
- `.github/workflows/deploy-cpanel.yml`

## 12) Nota final
Este contexto ya esta listo para usar como "base de sistema" en prompts de ChatGPT o Claude. Solo necesitas agregar el objetivo puntual de cada sesion.
