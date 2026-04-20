# Liga Basket Chile - Tema WordPress Premium

Tema custom para liga/federacion de basquetbol con backend deportivo, frontend premium, panel administrativo, REST API y base escalable.

## Instalacion

1. Copiar carpeta `liga-basket-chile` en `wp-content/themes/`.
2. Activar tema desde `Apariencia > Temas`.
3. Ir a `Apariencia > Menus` y asignar:
- `Principal`
- `Secundario`
- `Footer`
- `Legal`
4. Ir a `Apariencia > Opciones Liga Basket` y configurar branding/base.
5. (Opcional) Cargar demo con boton `Cargar datos demo`.

## Uso admin deportivo

- `Liga Basquet > Dashboard`: resumen de proximos partidos, ultimos resultados, alertas y noticias.
- `Liga Basquet > Tabla Posiciones`: filtro por division/temporada, recalc, override manual por equipo.
- `Liga Basquet > Equipos`: gestion de clubes con columna logo y filtro por division.
- `Liga Basquet > Partidos`: gestion de encuentros con validaciones deportivas.
- `Liga Basquet > Configuracion`: acceso al panel de opciones del tema.

## Carga de equipos

Post type: `Equipo`.

Campos relevantes:
- nombre_equipo
- logo_equipo
- ciudad
- anio_fundacion
- division
- temporada
- color_principal
- entrenador
- posicion_manual
- activar_override

Importacion CSV:
- pantalla: `Liga Basquet > Importar Equipos`
- plantilla: `plantilla-equipos-liga.csv`
- encabezado: `nombre_equipo,division,temporada`
- flujo en 2 pasos: validar -> confirmar
- estrategia: importacion parcial (solo filas validas)
- usa validaciones centrales de contexto competitivo (`nombre + division + temporada`)

## Carga de partidos

Post type: `Partido`.

Campos relevantes:
- equipo_local
- equipo_visita
- division
- temporada
- fecha_partido
- hora_partido
- cancha
- estado_partido
- puntos_local
- puntos_visita
- incomparecencia
- observaciones

Importacion CSV:
- pantalla: `Liga Basquet > Importar Partidos`
- plantilla: `plantilla-partidos-liga.csv`
- encabezado: `division,temporada,equipo_local,equipo_visitante,fecha,hora,recinto,estado`
- flujo en 2 pasos: validar -> confirmar
- estrategia: importacion parcial (solo filas validas)
- no crea equipos; valida existencia y contexto antes de importar
- reutiliza validacion central de cruces (`liga_validate_basketball_matchup`)

Validaciones aplicadas:
- local diferente a visita
- equipos y partido deben compartir la misma division (sin cruces de categoria)
- temporada del partido normalizada segun temporada activa/division
- equipos obligatorios con nombre + division + temporada validos
- bloqueo de duplicados por `nombre + division + temporada`
- empate bloqueado para estado `jugado` sin incomparecencia
- incomparecencias normalizadas con marcador tecnico (20-0)
- orden admin por fecha

## Tabla de posiciones

Motor: `liga_calcular_tabla_posiciones($division, $temporada, $force_recalculate)`.

Reglas:
- Partido normal: ganador +2, perdedor +1
- Incomparecencia local: visita +2, local +0
- Incomparecencia visita: local +2, visita +0
- Empates o datos inconsistentes: partido descartado + alerta administrativa

Salida:
- `PJ`, `PG`, `PP`, `INC`, `PTS`, `PF`, `PC`, `DIF`
- orden: override manual > `PTS DESC` > H2H > `DIF DESC` > `PF DESC` > `PG DESC`
- override manual opcional por equipo
- cache por transient versionado
- recalculo inteligente por partido: `liga_maybe_recalculate_standings_for_match($match_id)`
  (solo recalcula contexto de standings cuando el partido es o fue computable)

Funciones centrales recomendadas para integraciones:
- Equipos:
  - `liga_get_equipo_division_id()`
  - `liga_get_equipo_temporada_label()`
  - `liga_find_team_by_name_division_and_season()`
  - `liga_team_exists_by_name_division_and_season()`
  - `liga_team_belongs_to_competition_context()`
- Partidos:
  - `liga_get_available_teams_by_division_and_season()`
  - `liga_validate_basketball_matchup()`
  - `liga_validate_match_competition_context()`
  - `liga_match_exists_in_competition_context()`
- Standings:
  - `liga_is_match_countable_for_standings()`
  - `liga_calcular_tabla_posiciones()`
  - `liga_get_standings_by_division_and_season()`
  - `liga_maybe_recalculate_standings_for_match()`

## API REST

Namespace: `liga/v1`

Endpoints:
- `/wp-json/liga/v1/tabla`
- `/wp-json/liga/v1/partidos`
- `/wp-json/liga/v1/equipos`
- `/wp-json/liga/v1/noticias`

Filtros:
- `division`
- `temporada`
- `estado`
- `limit`

## Seguridad base

- oculta version WordPress
- XML-RPC desactivado
- headers de seguridad
- sanitizacion en metaboxes/opciones/api
- nonces y `current_user_can()` en flujos sensibles

## Datos demo

Modulo: `inc/datos-demo.php`

Carga:
- 2 divisiones
- 6 equipos
- 12 partidos
- 6 noticias
- sponsors demo

Es idempotente y no duplica contenido si ya fue cargado.

## Despliegue produccion

Checklist recomendado:
1. Activar cache de pagina y objeto (Redis/Memcached).
2. Servir imagenes con WebP/AVIF y CDN.
3. Forzar HTTPS + HSTS en servidor.
4. Restringir usuarios admin y activar 2FA.
5. Monitorear endpoints REST y rate limiting.
6. Ejecutar Lighthouse y ajustar CLS/LCP sobre contenido real.
