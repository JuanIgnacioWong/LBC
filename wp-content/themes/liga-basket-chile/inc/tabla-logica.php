<?php
/**
 * Motor de tabla de posiciones (fase 2).
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna version actual de cache de tabla.
 *
 * @return int
 */
function liga_get_table_cache_version() {
	return (int) get_option( 'liga_table_cache_version', 1 );
}

/**
 * Invalida cache de tablas.
 *
 * @return void
 */
function liga_flush_table_cache() {
	update_option( 'liga_table_cache_version', liga_get_table_cache_version() + 1 );
}

/**
 * Fuerza invalidacion de cache cuando cambia el esquema de tabla.
 *
 * @return void
 */
function liga_maybe_upgrade_table_cache_schema() {
	$schema_version = (int) get_option( 'liga_table_schema_version', 1 );
	$target_version = 3;

	if ( $schema_version >= $target_version ) {
		return;
	}

	liga_flush_table_cache();
	update_option( 'liga_table_schema_version', $target_version );
}
add_action( 'init', 'liga_maybe_upgrade_table_cache_schema' );

/**
 * Arma clave de transient para tabla.
 *
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return string
 */
function liga_get_table_cache_key( $division_id, $temporada ) {
	$version = liga_get_table_cache_version();
	return 'liga_tabla_' . md5( $version . '|' . (string) $division_id . '|' . $temporada );
}

/**
 * Limpia cache de standings manteniendo compatibilidad con invalidacion global.
 *
 * @param int    $division Division.
 * @param string $temporada Temporada.
 * @return void
 */
function liga_clear_standings_cache( $division = 0, $temporada = '' ) {
	unset( $division, $temporada );
	liga_flush_table_cache();
}

/**
 * Normaliza estado de partido para calculo deportivo.
 *
 * @param mixed $status Estado crudo.
 * @return string
 */
function liga_normalize_match_status( $status ) {
	$status = trim( sanitize_text_field( (string) $status ) );
	if ( '' === $status ) {
		return 'programado';
	}

	$key = remove_accents( $status );
	$key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $key, 'UTF-8' ) : strtolower( $key );
	$key = sanitize_key( $key );

	$aliases = array(
		'programado' => 'programado',
		'jugado'     => 'jugado',
		'finalizado' => 'finalizado',
		'suspendido' => 'suspendido',
		'cancelado'  => 'cancelado',
	);

	return isset( $aliases[ $key ] ) ? $aliases[ $key ] : $key;
}

/**
 * Normaliza incomparecencia a valores semanticos.
 *
 * @param mixed $raw_forfeit Valor crudo.
 * @return string
 */
function liga_normalize_forfeit_status( $raw_forfeit ) {
	$value = trim( sanitize_text_field( (string) $raw_forfeit ) );
	if ( '' === $value ) {
		return 'none';
	}

	$key = remove_accents( $value );
	$key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $key, 'UTF-8' ) : strtolower( $key );
	$key = preg_replace( '/[^a-z0-9]+/u', '_', $key );
	$key = trim( (string) $key, '_' );

	if ( in_array( $key, array( 'ninguna', 'none', 'no', 'sin_incomparecencia' ), true ) ) {
		return 'none';
	}

	if ( in_array( $key, array( 'local', 'home', 'local_no_comparecio', 'home_forfeit' ), true ) ) {
		return 'home_forfeit';
	}

	if ( in_array( $key, array( 'visita', 'visitante', 'away', 'visita_no_comparecio', 'visitante_no_comparecio', 'away_forfeit' ), true ) ) {
		return 'away_forfeit';
	}

	return 'none';
}

/**
 * Normaliza incomparecencia al valor historico usado por metadatos del proyecto.
 *
 * @param mixed $raw_forfeit Valor crudo.
 * @return string
 */
function liga_normalize_match_forfeit_meta_value( $raw_forfeit ) {
	$normalized = liga_normalize_forfeit_status( $raw_forfeit );
	if ( 'home_forfeit' === $normalized ) {
		return 'local_no_comparecio';
	}

	if ( 'away_forfeit' === $normalized ) {
		return 'visita_no_comparecio';
	}

	return 'ninguna';
}

/**
 * Lee el primer meta no vacio entre varias llaves.
 *
 * @param int              $post_id Post.
 * @param array<int,string> $keys Llaves meta.
 * @return mixed
 */
function liga_get_first_match_meta_value( $post_id, $keys ) {
	foreach ( $keys as $key ) {
		$value = get_post_meta( $post_id, $key, true );
		if ( '' !== trim( (string) $value ) ) {
			return $value;
		}
	}

	return '';
}

function liga_get_match_home_team_id( $match_id ) {
	return absint( liga_get_first_match_meta_value( $match_id, array( 'liga_equipo_local', 'equipo_local' ) ) );
}

function liga_get_match_away_team_id( $match_id ) {
	return absint( liga_get_first_match_meta_value( $match_id, array( 'liga_equipo_visita', 'liga_equipo_visitante', 'equipo_visita', 'equipo_visitante' ) ) );
}

function liga_get_match_home_score( $match_id ) {
	return absint( liga_get_first_match_meta_value( $match_id, array( 'liga_puntos_local', 'puntos_local' ) ) );
}

function liga_get_match_away_score( $match_id ) {
	return absint( liga_get_first_match_meta_value( $match_id, array( 'liga_puntos_visita', 'liga_puntos_visitante', 'puntos_visita', 'puntos_visitante' ) ) );
}

function liga_get_match_division( $match_id ) {
	return absint( liga_get_first_match_meta_value( $match_id, array( 'liga_division', 'division' ) ) );
}

function liga_get_match_season( $match_id ) {
	return trim( sanitize_text_field( (string) liga_get_first_match_meta_value( $match_id, array( 'liga_temporada', 'temporada' ) ) ) );
}

function liga_get_match_status( $match_id ) {
	return liga_normalize_match_status( liga_get_first_match_meta_value( $match_id, array( 'liga_estado_partido', 'estado_partido', 'estado' ) ) );
}

function liga_get_match_forfeit_status( $match_id ) {
	return liga_normalize_match_forfeit_meta_value(
		liga_get_first_match_meta_value( $match_id, array( 'liga_incomparecencia', 'tipo_incomparecencia', 'equipo_incompareciente', 'incomparecencia' ) )
	);
}

/**
 * Reglas oficiales para puntos de tabla.
 *
 * Partido jugado: ganador +2, perdedor +1.
 * Incomparecencia: ganador +2, equipo ausente +0.
 *
 * @return array<string, int>
 */
function liga_get_standings_points_rules() {
	$rules = array(
		'victoria'                 => 2,
		'derrota'                  => 1,
		'victoria_incomparecencia' => 2,
		'incomparecencia'          => 0,
	);

	$filtered = apply_filters( 'liga_standings_points_rules', $rules );
	if ( ! is_array( $filtered ) ) {
		return $rules;
	}

	$filtered['victoria']                 = 2;
	$filtered['derrota']                  = 1;
	$filtered['victoria_incomparecencia'] = 2;
	$filtered['incomparecencia']          = 0;

	return $filtered;
}

/**
 * Obtiene equipos habilitados por division y temporada.
 *
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return array<int, WP_Post>
 */
function liga_get_available_teams_by_division_and_season( $division_id, $temporada ) {
	$division_id = absint( $division_id );
	$temporada   = trim( sanitize_text_field( (string) $temporada ) );

	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) || ! liga_is_valid_temporada_label( $temporada ) ) {
		return array();
	}

	$equipos = get_posts(
		array(
			'post_type'      => 'equipo',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => 'liga_division',
					'value' => $division_id,
					'type'  => 'NUMERIC',
				),
				array(
					'key'   => 'liga_temporada',
					'value' => $temporada,
				),
			),
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	return is_array( $equipos ) ? $equipos : array();
}

/**
 * Obtiene snapshot de contexto competitivo de un partido.
 *
 * @param int $partido_id Partido.
 * @return array<string, mixed>
 */
function liga_get_match_context_snapshot( $partido_id ) {
	$partido_id = absint( $partido_id );
	$snapshot   = array(
		'partido_id'      => $partido_id,
		'estado'          => '',
		'local_id'        => 0,
		'visita_id'       => 0,
		'division_id'     => 0,
		'temporada'       => '',
		'puntos_local'    => 0,
		'puntos_visita'   => 0,
		'incomparecencia' => 'ninguna',
	);

	if ( ! liga_is_valid_post_type_id( $partido_id, 'partido' ) ) {
		return $snapshot;
	}

	$snapshot['estado']          = liga_get_match_status( $partido_id );
	$snapshot['local_id']        = liga_get_match_home_team_id( $partido_id );
	$snapshot['visita_id']       = liga_get_match_away_team_id( $partido_id );
	$snapshot['division_id']     = liga_get_match_division( $partido_id );
	$snapshot['temporada']       = liga_get_match_season( $partido_id );
	$snapshot['puntos_local']    = liga_get_match_home_score( $partido_id );
	$snapshot['puntos_visita']   = liga_get_match_away_score( $partido_id );
	$snapshot['incomparecencia'] = liga_get_match_forfeit_status( $partido_id );

	if ( ! liga_is_valid_temporada_label( $snapshot['temporada'] ) ) {
		$division_temporada = liga_get_division_temporada_label( (int) $snapshot['division_id'] );
		if ( liga_is_valid_temporada_label( $division_temporada ) ) {
			$snapshot['temporada'] = $division_temporada;
		}
	}

	$snapshot['incomparecencia'] = liga_normalize_match_forfeit_meta_value( $snapshot['incomparecencia'] );

	return $snapshot;
}

/**
 * Valida que un cruce de basquetbol sea reglamentariamente valido.
 *
 * @param int    $local_id Equipo local.
 * @param int    $visita_id Equipo visita.
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return true|WP_Error
 */
function liga_validate_basketball_matchup( $local_id, $visita_id, $division_id, $temporada ) {
	$local_id    = absint( $local_id );
	$visita_id   = absint( $visita_id );
	$division_id = absint( $division_id );
	$temporada   = trim( sanitize_text_field( (string) $temporada ) );

	if ( ! liga_is_valid_post_type_id( $local_id, 'equipo' ) || ! liga_is_valid_post_type_id( $visita_id, 'equipo' ) ) {
		return new WP_Error( 'invalid_teams', __( 'Validacion: local y visita deben ser equipos validos.', 'liga-basket-chile' ) );
	}

	if ( $local_id === $visita_id ) {
		return new WP_Error( 'same_team', __( 'Validacion: el equipo local no puede ser igual al visita.', 'liga-basket-chile' ) );
	}

	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		return new WP_Error( 'invalid_division', __( 'Validacion: debes seleccionar una division valida para el partido.', 'liga-basket-chile' ) );
	}

	if ( ! liga_is_valid_temporada_label( $temporada ) ) {
		return new WP_Error( 'invalid_season', __( 'Validacion: la temporada del partido es obligatoria y debe tener formato YYYY.', 'liga-basket-chile' ) );
	}

	$division_local  = liga_get_equipo_division_id( $local_id );
	$division_visita = liga_get_equipo_division_id( $visita_id );
	if ( $division_local <= 0 || $division_visita <= 0 ) {
		return new WP_Error( 'missing_team_division', __( 'Validacion: ambos equipos deben tener division asignada.', 'liga-basket-chile' ) );
	}

	if ( $division_local !== $division_id || $division_visita !== $division_id ) {
		return new WP_Error( 'cross_division', __( 'Validacion: no se permiten cruces entre categorias. Ambos equipos deben pertenecer a la division del partido.', 'liga-basket-chile' ) );
	}

	$temporada_local  = liga_get_equipo_temporada_label( $local_id );
	$temporada_visita = liga_get_equipo_temporada_label( $visita_id );
	if ( ! liga_is_valid_temporada_label( $temporada_local ) || ! liga_is_valid_temporada_label( $temporada_visita ) ) {
		return new WP_Error( 'missing_team_season', __( 'Validacion: ambos equipos deben tener temporada/anio valido antes de programar partidos.', 'liga-basket-chile' ) );
	}

	if ( $temporada_local !== $temporada || $temporada_visita !== $temporada ) {
		return new WP_Error( 'cross_season', __( 'Validacion: local y visita deben pertenecer a la misma temporada del partido.', 'liga-basket-chile' ) );
	}

	return true;
}

/**
 * Valida que el contexto competitivo de un partido sea coherente.
 *
 * @param int         $partido_id Partido.
 * @param int         $division_id Division filtro opcional.
 * @param string|null $temporada Temporada filtro opcional.
 * @return array<string,mixed>|WP_Error
 */
function liga_validate_match_competition_context( $partido_id, $division_id = 0, $temporada = null ) {
	$partido_id  = absint( $partido_id );
	$division_id = absint( $division_id );
	$temporada   = null === $temporada ? '' : trim( sanitize_text_field( (string) $temporada ) );

	if ( ! liga_is_valid_post_type_id( $partido_id, 'partido' ) ) {
		return new WP_Error( 'invalid_match', __( 'partido inexistente', 'liga-basket-chile' ) );
	}

	$snapshot = liga_get_match_context_snapshot( $partido_id );
	if ( ! liga_is_valid_post_type_id( (int) $snapshot['division_id'], 'division' ) ) {
		return new WP_Error( 'invalid_division', __( 'division invalida', 'liga-basket-chile' ) );
	}

	if ( ! liga_is_valid_temporada_label( (string) $snapshot['temporada'] ) ) {
		return new WP_Error( 'invalid_season', __( 'temporada invalida', 'liga-basket-chile' ) );
	}

	if ( $division_id > 0 && $division_id !== (int) $snapshot['division_id'] ) {
		return new WP_Error( 'filtered_division', __( 'no pertenece a la division filtrada', 'liga-basket-chile' ) );
	}

	if ( liga_is_valid_temporada_label( $temporada ) && $temporada !== (string) $snapshot['temporada'] ) {
		return new WP_Error( 'filtered_season', __( 'temporada inconsistente', 'liga-basket-chile' ) );
	}

	$matchup_validation = liga_validate_basketball_matchup(
		(int) $snapshot['local_id'],
		(int) $snapshot['visita_id'],
		(int) $snapshot['division_id'],
		(string) $snapshot['temporada']
	);

	if ( is_wp_error( $matchup_validation ) ) {
		return $matchup_validation;
	}

	return $snapshot;
}

/**
 * Verifica si un partido ya existe por la misma llave competitiva.
 *
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @param int    $local_id Local.
 * @param int    $visita_id Visita.
 * @param string $fecha Fecha.
 * @param string $hora Hora.
 * @param int    $exclude_match_id Excluir ID.
 * @return bool
 */
function liga_match_exists_in_competition_context( $division_id, $temporada, $local_id, $visita_id, $fecha, $hora, $exclude_match_id = 0 ) {
	$division_id       = absint( $division_id );
	$temporada         = trim( sanitize_text_field( (string) $temporada ) );
	$local_id          = absint( $local_id );
	$visita_id         = absint( $visita_id );
	$fecha             = trim( sanitize_text_field( (string) $fecha ) );
	$hora              = trim( sanitize_text_field( (string) $hora ) );
	$exclude_match_id  = absint( $exclude_match_id );

	if ( $division_id <= 0 || ! liga_is_valid_temporada_label( $temporada ) || $local_id <= 0 || $visita_id <= 0 || '' === $fecha ) {
		return false;
	}

	$args = array(
		'post_type'      => 'partido',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'   => 'liga_division',
				'value' => $division_id,
				'type'  => 'NUMERIC',
			),
			array(
				'key'   => 'liga_temporada',
				'value' => $temporada,
			),
			array(
				'key'   => 'liga_equipo_local',
				'value' => $local_id,
				'type'  => 'NUMERIC',
			),
			array(
				'key'   => 'liga_equipo_visita',
				'value' => $visita_id,
				'type'  => 'NUMERIC',
			),
			array(
				'key'   => 'liga_fecha_partido',
				'value' => $fecha,
			),
			array(
				'key'   => 'liga_hora_partido',
				'value' => $hora,
			),
		),
	);

	if ( $exclude_match_id > 0 ) {
		$args['post__not_in'] = array( $exclude_match_id );
	}

	$matches = get_posts( $args );
	return ! empty( $matches );
}

/**
 * Determina si un partido es computable para tabla y devuelve su snapshot normalizado.
 *
 * @param int         $partido_id Partido.
 * @param int         $division_id Division filtrada opcional.
 * @param string|null $temporada Temporada filtrada opcional.
 * @return array{is_countable:bool,reason:string,match:array<string,mixed>}
 */
function liga_is_match_countable_for_standings( $partido_id, $division_id = 0, $temporada = null ) {
	$partido_id   = absint( $partido_id );
	$division_id  = absint( $division_id );
	$temporada    = null === $temporada ? '' : trim( sanitize_text_field( (string) $temporada ) );
	$empty_result = array(
		'is_countable' => false,
		'reason'       => '',
		'match'        => array(),
	);

	if ( ! liga_is_valid_post_type_id( $partido_id, 'partido' ) ) {
		$empty_result['reason'] = __( 'partido inexistente', 'liga-basket-chile' );
		return $empty_result;
	}

	$estado = liga_get_match_status( $partido_id );
	if ( ! in_array( $estado, array( 'jugado', 'finalizado' ), true ) ) {
		$empty_result['reason'] = __( 'estado no computable', 'liga-basket-chile' );
		return $empty_result;
	}

	$context_validation = liga_validate_match_competition_context( $partido_id, $division_id, $temporada );
	if ( is_wp_error( $context_validation ) ) {
		$empty_result['reason'] = $context_validation->get_error_message();
		return $empty_result;
	}

	$partido_division  = (int) $context_validation['division_id'];
	$partido_temporada = (string) $context_validation['temporada'];
	$local_id          = (int) $context_validation['local_id'];
	$visita_id         = (int) $context_validation['visita_id'];
	$incomparecencia   = sanitize_key( (string) $context_validation['incomparecencia'] );
	if ( ! in_array( $incomparecencia, array( 'ninguna', 'local_no_comparecio', 'visita_no_comparecio' ), true ) ) {
		$incomparecencia = 'ninguna';
	}

	$puntos_local  = (int) $context_validation['puntos_local'];
	$puntos_visita = (int) $context_validation['puntos_visita'];

	if ( 'ninguna' !== $incomparecencia ) {
		$walkover_score = liga_get_walkover_score( $incomparecencia );
		$puntos_local   = (int) $walkover_score['local'];
		$puntos_visita  = (int) $walkover_score['visita'];
	} else {
		if ( $puntos_local === $puntos_visita ) {
			$empty_result['reason'] = __( 'empate detectado en estado computable', 'liga-basket-chile' );
			return $empty_result;
		}

		if ( 0 === $puntos_local && 0 === $puntos_visita ) {
			$empty_result['reason'] = __( 'marcador deportivo vacio', 'liga-basket-chile' );
			return $empty_result;
		}
	}

	return array(
		'is_countable' => true,
		'reason'       => '',
		'match'        => array(
			'partido_id'      => $partido_id,
			'division_id'     => $partido_division,
			'temporada'       => $partido_temporada,
			'estado'          => $estado,
			'local_id'        => $local_id,
			'visita_id'       => $visita_id,
			'puntos_local'    => $puntos_local,
			'puntos_visita'   => $puntos_visita,
			'incomparecencia' => $incomparecencia,
		),
	);
}

/**
 * Acumula estadisticas deportivas para dos equipos desde un partido computable.
 *
 * @param array<int, array<string, mixed>>           $tabla Tabla por equipo.
 * @param array<int, array<int, array<string, int>>> $head_to_head Acumulado H2H.
 * @param array<string, mixed>                       $match_data Snapshot validado.
 * @param array<string, int>                         $points_rules Reglas de puntos.
 * @return array<int, array<int, array<string, int>>>
 */
function liga_calculate_team_basketball_stats( &$tabla, $head_to_head, $match_data, $points_rules ) {
	$local_id        = (int) $match_data['local_id'];
	$visita_id       = (int) $match_data['visita_id'];
	$puntos_local    = (int) $match_data['puntos_local'];
	$puntos_visita   = (int) $match_data['puntos_visita'];
	$incomparecencia = (string) $match_data['incomparecencia'];

	if ( ! isset( $tabla[ $local_id ] ) ) {
		$tabla[ $local_id ] = liga_init_equipo_stats( $local_id );
	}

	if ( ! isset( $tabla[ $visita_id ] ) ) {
		$tabla[ $visita_id ] = liga_init_equipo_stats( $visita_id );
	}

	$tabla[ $local_id ]['pj']++;
	$tabla[ $visita_id ]['pj']++;
	$tabla[ $local_id ]['pf'] += $puntos_local;
	$tabla[ $local_id ]['pc'] += $puntos_visita;
	$tabla[ $visita_id ]['pf'] += $puntos_visita;
	$tabla[ $visita_id ]['pc'] += $puntos_local;
	$tabla[ $local_id ]['dif'] = (int) $tabla[ $local_id ]['pf'] - (int) $tabla[ $local_id ]['pc'];
	$tabla[ $visita_id ]['dif'] = (int) $tabla[ $visita_id ]['pf'] - (int) $tabla[ $visita_id ]['pc'];

	if ( 'local_no_comparecio' === $incomparecencia ) {
		$tabla[ $local_id ]['pp']++;
		$tabla[ $local_id ]['inc']++;
		$tabla[ $local_id ]['pts'] += (int) $points_rules['incomparecencia'];
		$tabla[ $visita_id ]['pg']++;
		$tabla[ $visita_id ]['pts'] += (int) $points_rules['victoria_incomparecencia'];
		$head_to_head = liga_register_head_to_head( $head_to_head, $local_id, $visita_id, $puntos_local, $puntos_visita, (int) $points_rules['incomparecencia'] );
		$head_to_head = liga_register_head_to_head( $head_to_head, $visita_id, $local_id, $puntos_visita, $puntos_local, (int) $points_rules['victoria_incomparecencia'] );
		return $head_to_head;
	}

	if ( 'visita_no_comparecio' === $incomparecencia ) {
		$tabla[ $visita_id ]['pp']++;
		$tabla[ $visita_id ]['inc']++;
		$tabla[ $visita_id ]['pts'] += (int) $points_rules['incomparecencia'];
		$tabla[ $local_id ]['pg']++;
		$tabla[ $local_id ]['pts'] += (int) $points_rules['victoria_incomparecencia'];
		$head_to_head = liga_register_head_to_head( $head_to_head, $local_id, $visita_id, $puntos_local, $puntos_visita, (int) $points_rules['victoria_incomparecencia'] );
		$head_to_head = liga_register_head_to_head( $head_to_head, $visita_id, $local_id, $puntos_visita, $puntos_local, (int) $points_rules['incomparecencia'] );
		return $head_to_head;
	}

	if ( $puntos_local > $puntos_visita ) {
		$tabla[ $local_id ]['pg']++;
		$tabla[ $local_id ]['pts'] += (int) $points_rules['victoria'];
		$tabla[ $visita_id ]['pp']++;
		$tabla[ $visita_id ]['pts'] += (int) $points_rules['derrota'];
		$head_to_head = liga_register_head_to_head( $head_to_head, $local_id, $visita_id, $puntos_local, $puntos_visita, (int) $points_rules['victoria'] );
		$head_to_head = liga_register_head_to_head( $head_to_head, $visita_id, $local_id, $puntos_visita, $puntos_local, (int) $points_rules['derrota'] );
		return $head_to_head;
	}

	$tabla[ $visita_id ]['pg']++;
	$tabla[ $visita_id ]['pts'] += (int) $points_rules['victoria'];
	$tabla[ $local_id ]['pp']++;
	$tabla[ $local_id ]['pts'] += (int) $points_rules['derrota'];
	$head_to_head = liga_register_head_to_head( $head_to_head, $local_id, $visita_id, $puntos_local, $puntos_visita, (int) $points_rules['derrota'] );
	$head_to_head = liga_register_head_to_head( $head_to_head, $visita_id, $local_id, $puntos_visita, $puntos_local, (int) $points_rules['victoria'] );

	return $head_to_head;
}

/**
 * Estructura inicial para estadisticas por equipo.
 *
 * @param int $equipo_id ID del equipo.
 * @return array<string, mixed>
 */
function liga_init_equipo_stats( $equipo_id ) {
	return array(
		'equipo_id'         => $equipo_id,
		'equipo'            => liga_get_equipo_nombre( $equipo_id ),
		'logo_id'           => (int) get_post_meta( $equipo_id, 'liga_logo_equipo', true ),
		'pj'                => 0,
		'pg'                => 0,
		'pp'                => 0,
		'inc'               => 0,
		'pts'               => 0,
		'pf'                => 0,
		'pc'                => 0,
		'dif'               => 0,
		'override_activo'   => (int) get_post_meta( $equipo_id, 'liga_activar_override', true ),
		'override_posicion' => (int) get_post_meta( $equipo_id, 'liga_posicion_manual', true ),
	);
}

/**
 * Guarda estadisticas H2H para criterios de desempate.
 *
 * @param array<int, array<int, array<string, int>>> $head_to_head Tabla H2H.
 * @param int                                        $team_id Equipo.
 * @param int                                        $opponent_id Rival.
 * @param int                                        $pf Puntos a favor.
 * @param int                                        $pc Puntos en contra.
 * @param int                                        $pts_tabla Puntos de tabla.
 * @return array<int, array<int, array<string, int>>>
 */
function liga_register_head_to_head( $head_to_head, $team_id, $opponent_id, $pf, $pc, $pts_tabla ) {
	if ( ! isset( $head_to_head[ $team_id ] ) ) {
		$head_to_head[ $team_id ] = array();
	}

	if ( ! isset( $head_to_head[ $team_id ][ $opponent_id ] ) ) {
		$head_to_head[ $team_id ][ $opponent_id ] = array(
			'pj'  => 0,
			'pts' => 0,
			'pf'  => 0,
			'pc'  => 0,
			'dif' => 0,
		);
	}

	$head_to_head[ $team_id ][ $opponent_id ]['pj']++;
	$head_to_head[ $team_id ][ $opponent_id ]['pts'] += $pts_tabla;
	$head_to_head[ $team_id ][ $opponent_id ]['pf'] += $pf;
	$head_to_head[ $team_id ][ $opponent_id ]['pc'] += $pc;
	$head_to_head[ $team_id ][ $opponent_id ]['dif'] = $head_to_head[ $team_id ][ $opponent_id ]['pf'] - $head_to_head[ $team_id ][ $opponent_id ]['pc'];

	return $head_to_head;
}

/**
 * Obtiene snapshot H2H entre dos equipos.
 *
 * @param array<int, array<int, array<string, int>>> $head_to_head Tabla H2H.
 * @param int                                        $team_id Equipo.
 * @param int                                        $opponent_id Rival.
 * @return array<string, int>
 */
function liga_get_h2h_snapshot( $head_to_head, $team_id, $opponent_id ) {
	if ( isset( $head_to_head[ $team_id ], $head_to_head[ $team_id ][ $opponent_id ] ) ) {
		return $head_to_head[ $team_id ][ $opponent_id ];
	}

	return array(
		'pj'  => 0,
		'pts' => 0,
		'pf'  => 0,
		'pc'  => 0,
		'dif' => 0,
	);
}

/**
 * Ordena la tabla segun reglas y override manual.
 *
 * @param array<int, array<string, mixed>>           $tabla Tabla.
 * @param array<int, array<int, array<string, int>>> $head_to_head Tabla H2H.
 * @return void
 */
function liga_sort_table( &$tabla, $head_to_head = array() ) {
	usort(
		$tabla,
		static function ( $a, $b ) use ( $head_to_head ) {
			$a_override = ! empty( $a['override_activo'] ) && ! empty( $a['override_posicion'] );
			$b_override = ! empty( $b['override_activo'] ) && ! empty( $b['override_posicion'] );

			if ( $a_override && $b_override && (int) $a['override_posicion'] !== (int) $b['override_posicion'] ) {
				return (int) $a['override_posicion'] <=> (int) $b['override_posicion'];
			}

			if ( $a_override && ! $b_override ) {
				return -1;
			}

			if ( $b_override && ! $a_override ) {
				return 1;
			}

			if ( (int) $a['pts'] !== (int) $b['pts'] ) {
				return (int) $b['pts'] <=> (int) $a['pts'];
			}

			$a_h2h = liga_get_h2h_snapshot( $head_to_head, (int) $a['equipo_id'], (int) $b['equipo_id'] );
			$b_h2h = liga_get_h2h_snapshot( $head_to_head, (int) $b['equipo_id'], (int) $a['equipo_id'] );

			if ( $a_h2h['pj'] > 0 || $b_h2h['pj'] > 0 ) {
				if ( (int) $a_h2h['pts'] !== (int) $b_h2h['pts'] ) {
					return (int) $b_h2h['pts'] <=> (int) $a_h2h['pts'];
				}

				if ( (int) $a_h2h['dif'] !== (int) $b_h2h['dif'] ) {
					return (int) $b_h2h['dif'] <=> (int) $a_h2h['dif'];
				}

				if ( (int) $a_h2h['pf'] !== (int) $b_h2h['pf'] ) {
					return (int) $b_h2h['pf'] <=> (int) $a_h2h['pf'];
				}
			}

			if ( (int) $a['dif'] !== (int) $b['dif'] ) {
				return (int) $b['dif'] <=> (int) $a['dif'];
			}

			if ( (int) $a['pf'] !== (int) $b['pf'] ) {
				return (int) $b['pf'] <=> (int) $a['pf'];
			}

			if ( (int) $a['inc'] !== (int) $b['inc'] ) {
				return (int) $a['inc'] <=> (int) $b['inc'];
			}

			if ( (int) $a['pg'] !== (int) $b['pg'] ) {
				return (int) $b['pg'] <=> (int) $a['pg'];
			}

			if ( (int) $a['pc'] !== (int) $b['pc'] ) {
				return (int) $a['pc'] <=> (int) $b['pc'];
			}

			return strcasecmp( (string) $a['equipo'], (string) $b['equipo'] );
		}
	);
}

/**
 * Calcula tabla de posiciones por division y temporada.
 *
 * @param int|string $division Division ID.
 * @param string     $temporada Temporada.
 * @param bool       $force_recalculate Fuerza recache.
 * @return array<string, mixed>
 */
function liga_calcular_tabla_posiciones( $division, $temporada, $force_recalculate = false ) {
	$division_id = absint( $division );
	$temporada   = liga_normalize_temporada_label( (string) $temporada, liga_get_current_season_label() );
	$cache_key   = liga_get_table_cache_key( $division_id, $temporada );

	if ( ! $force_recalculate ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	$alerts      = array();
	$alerts_seen = array();
	$add_alert   = static function ( $message ) use ( &$alerts, &$alerts_seen ) {
		$message = sanitize_text_field( (string) $message );
		if ( '' === $message || isset( $alerts_seen[ $message ] ) ) {
			return;
		}

		$alerts_seen[ $message ] = true;
		$alerts[]                = $message;
	};

	if ( $division_id > 0 && ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		$add_alert( __( 'La division solicitada no existe. No se pudo calcular la tabla.', 'liga-basket-chile' ) );
		return array(
			'division_id'          => $division_id,
			'division'             => '',
			'temporada'            => $temporada,
			'total_equipos'        => 0,
			'tabla'                => array(),
			'alerts'               => $alerts,
			'partidos_computados'  => 0,
			'partidos_descartados' => 0,
			'generated_at'         => gmdate( 'c' ),
		);
	}

	$meta_query = array();
	$points_rules = liga_get_standings_points_rules();

	if ( $division_id > 0 ) {
		$meta_query[] = array(
			'key'   => 'liga_division',
			'value' => $division_id,
			'type'  => 'NUMERIC',
		);
	}

	$meta_query[] = array(
		'key'   => 'liga_temporada',
		'value' => $temporada,
	);

	$partidos = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => $meta_query,
			'meta_key'       => 'liga_fecha_partido',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		)
	);

	$tabla        = array();
	$head_to_head = array();

	if ( $division_id > 0 ) {
		$equipos_division = liga_get_available_teams_by_division_and_season( $division_id, $temporada );

		foreach ( $equipos_division as $equipo ) {
			$equipo_id = (int) $equipo->ID;
			if ( $equipo_id > 0 ) {
				$tabla[ $equipo_id ] = liga_init_equipo_stats( $equipo_id );
			}
		}
	}

	if ( $division_id > 0 && empty( $tabla ) ) {
		$add_alert( __( 'No hay equipos inscritos para esta division y temporada.', 'liga-basket-chile' ) );
	}

	$partidos_computados  = 0;
	$partidos_descartados = 0;
	$partidos_no_computables_por_estado = 0;
	$incomparecencias_detectadas = 0;

	foreach ( $partidos as $partido ) {
		$partido_id  = (int) $partido->ID;
		$evaluation  = liga_is_match_countable_for_standings( $partido_id, $division_id, $temporada );
		$match_data  = isset( $evaluation['match'] ) && is_array( $evaluation['match'] ) ? $evaluation['match'] : array();
		$local_id    = isset( $match_data['local_id'] ) ? (int) $match_data['local_id'] : 0;
		$visita_id   = isset( $match_data['visita_id'] ) ? (int) $match_data['visita_id'] : 0;

		if ( empty( $evaluation['is_countable'] ) ) {
			$partidos_descartados++;
			$reason = isset( $evaluation['reason'] ) ? trim( (string) $evaluation['reason'] ) : '';
			if ( __( 'estado no computable', 'liga-basket-chile' ) === $reason ) {
				$partidos_no_computables_por_estado++;
				continue;
			}

			$add_alert(
				sprintf(
					/* translators: 1: partido ID, 2: motivo de descarte */
					__( 'Partido #%1$d descartado: %2$s.', 'liga-basket-chile' ),
					$partido_id,
					'' !== $reason ? $reason : __( 'inconsistencia deportiva', 'liga-basket-chile' )
				)
			);
			continue;
		}

		if ( isset( $match_data['incomparecencia'] ) && 'ninguna' !== (string) $match_data['incomparecencia'] ) {
			$incomparecencias_detectadas++;
		}

		$head_to_head = liga_calculate_team_basketball_stats( $tabla, $head_to_head, $match_data, $points_rules );
		$partidos_computados++;
	}

	if ( $partidos_no_computables_por_estado > 0 ) {
		$add_alert(
			sprintf(
				/* translators: %d: cantidad de partidos */
				__( '%d partidos programados/suspendidos/cancelados no afectan la tabla.', 'liga-basket-chile' ),
				$partidos_no_computables_por_estado
			)
		);
	}

	if ( $incomparecencias_detectadas > 0 ) {
		$add_alert(
			sprintf(
				/* translators: %d: cantidad de incomparecencias */
				__( '%d incomparecencias computadas: el ausente suma 0 pts e INC +1.', 'liga-basket-chile' ),
				$incomparecencias_detectadas
			)
		);
	}

	$tabla_values = array_values( $tabla );
	liga_sort_table( $tabla_values, $head_to_head );

	foreach ( $tabla_values as $index => &$row ) {
		$row['dif'] = (int) $row['pf'] - (int) $row['pc'];
		$row['prom_pf'] = (int) $row['pj'] > 0 ? round( (float) $row['pf'] / (int) $row['pj'], 2 ) : 0;
		$row['prom_pc'] = (int) $row['pj'] > 0 ? round( (float) $row['pc'] / (int) $row['pj'], 2 ) : 0;
		$row['prom_dif'] = (int) $row['pj'] > 0 ? round( (float) $row['dif'] / (int) $row['pj'], 2 ) : 0;
		$row['pos'] = $index + 1;
	}
	unset( $row );

	$result = array(
		'division_id'          => $division_id,
		'division'             => $division_id ? get_the_title( $division_id ) : '',
		'temporada'            => $temporada,
		'total_equipos'        => count( $tabla_values ),
		'tabla'                => $tabla_values,
		'alerts'               => $alerts,
		'partidos_computados'  => $partidos_computados,
		'partidos_descartados' => $partidos_descartados,
		'puntos_reglamento'    => $points_rules,
		'generated_at'         => gmdate( 'c' ),
	);

	set_transient( $cache_key, $result, 15 * MINUTE_IN_SECONDS );

	return $result;
}

/**
 * Alias semantico para obtener tabla por division y temporada.
 *
 * @param int|string $division Division.
 * @param string     $temporada Temporada.
 * @param bool       $force_recalculate Fuerza recache.
 * @return array<string, mixed>
 */
function liga_get_standings_by_division_and_season( $division, $temporada, $force_recalculate = false ) {
	return liga_calcular_tabla_posiciones( $division, $temporada, $force_recalculate );
}

/**
 * Decide si corresponde recalcular standings para un partido.
 *
 * @param int $match_id Partido.
 * @return bool
 */
function liga_maybe_recalculate_standings_for_match( $match_id ) {
	$match_id = absint( $match_id );
	if ( $match_id <= 0 || ! liga_is_valid_post_type_id( $match_id, 'partido' ) ) {
		return false;
	}

	$current_context_validation = liga_validate_match_competition_context( $match_id );
	$current_division_id        = 0;
	$current_temporada          = '';
	$current_countable          = false;

	if ( ! is_wp_error( $current_context_validation ) ) {
		$current_division_id = (int) $current_context_validation['division_id'];
		$current_temporada   = (string) $current_context_validation['temporada'];
		$countable_eval      = liga_is_match_countable_for_standings( $match_id, $current_division_id, $current_temporada );
		$current_countable   = ! empty( $countable_eval['is_countable'] );
	}

	$previous_countable   = 1 === (int) get_post_meta( $match_id, '_liga_last_standings_countable', true );
	$previous_division_id = (int) get_post_meta( $match_id, '_liga_last_standings_division', true );
	$previous_temporada   = trim( sanitize_text_field( (string) get_post_meta( $match_id, '_liga_last_standings_temporada', true ) ) );

	$contexts_to_recalc = array();
	if ( $previous_countable && liga_is_valid_post_type_id( $previous_division_id, 'division' ) && liga_is_valid_temporada_label( $previous_temporada ) ) {
		$contexts_to_recalc[] = array(
			'division_id' => $previous_division_id,
			'temporada'   => $previous_temporada,
		);
	}

	if ( $current_countable && liga_is_valid_post_type_id( $current_division_id, 'division' ) && liga_is_valid_temporada_label( $current_temporada ) ) {
		$contexts_to_recalc[] = array(
			'division_id' => $current_division_id,
			'temporada'   => $current_temporada,
		);
	}

	update_post_meta( $match_id, '_liga_last_standings_countable', $current_countable ? 1 : 0 );
	update_post_meta( $match_id, '_liga_last_standings_division', $current_division_id );
	update_post_meta( $match_id, '_liga_last_standings_temporada', $current_temporada );

	if ( empty( $contexts_to_recalc ) ) {
		return false;
	}

	$unique_contexts = array();
	foreach ( $contexts_to_recalc as $context ) {
		$key = (string) (int) $context['division_id'] . '|' . (string) $context['temporada'];
		$unique_contexts[ $key ] = $context;
	}

	liga_flush_table_cache();
	foreach ( $unique_contexts as $context ) {
		liga_calcular_tabla_posiciones( (int) $context['division_id'], (string) $context['temporada'], true );
	}

	return true;
}

/**
 * Recalcula cache de tabla cuando se guarda un partido.
 *
 * @param int          $post_id ID del partido.
 * @param WP_Post|null $post Post.
 * @param bool         $update Si es update.
 * @return void
 */
function liga_recalculate_standings_after_match_save( $post_id, $post = null, $update = true ) {
	static $processed = array();

	$post_id = absint( $post_id );
	if ( $post_id <= 0 || isset( $processed[ $post_id ] ) ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	if ( 'partido' !== get_post_type( $post_id ) ) {
		return;
	}

	$processed[ $post_id ] = true;
	liga_maybe_recalculate_standings_for_match( $post_id );
}
add_action( 'save_post_partido', 'liga_recalculate_standings_after_match_save', 30, 3 );
