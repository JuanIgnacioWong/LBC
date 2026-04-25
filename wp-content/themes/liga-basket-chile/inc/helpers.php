<?php
/**
 * Funciones helper reutilizables del tema.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Obtiene opcion del tema con fallback.
 *
 * @param string $key Clave de opcion.
 * @param mixed  $default Valor por defecto.
 * @return mixed
 */
function liga_get_option( $key, $default = '' ) {
	$options = get_option( 'liga_theme_options', array() );
	if ( isset( $options[ $key ] ) ) {
		return $options[ $key ];
	}
	return $default;
}

/**
 * Retorna temporada activa por defecto.
 *
 * @return string
 */
function liga_get_current_season_label() {
	$season = liga_get_option( 'current_season', gmdate( 'Y' ) );
	return sanitize_text_field( (string) $season );
}

/**
 * Links sociales base para top bar.
 *
 * @return array<string, string>
 */
function liga_get_social_links() {
	$links = array(
		'instagram' => liga_get_option( 'social_instagram', '#' ),
		'facebook'  => liga_get_option( 'social_facebook', '#' ),
		'youtube'   => liga_get_option( 'social_youtube', '#' ),
	);

	return array_map( 'esc_url', $links );
}

/**
 * Almacena alertas admin temporales por usuario.
 *
 * @param string $type Tipo de alerta: success, warning, error, info.
 * @param string $message Mensaje de alerta.
 * @return void
 */
function liga_add_admin_alert( $type, $message ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	$key    = 'liga_admin_alerts_' . $user_id;
	$alerts = get_transient( $key );
	if ( ! is_array( $alerts ) ) {
		$alerts = array();
	}

	$alerts[] = array(
		'type'    => sanitize_key( $type ),
		'message' => wp_kses_post( $message ),
	);

	set_transient( $key, $alerts, 15 * MINUTE_IN_SECONDS );
}

/**
 * Obtiene y elimina alertas temporales del usuario actual.
 *
 * @return array<int, array<string, string>>
 */
function liga_pull_admin_alerts() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return array();
	}

	$key    = 'liga_admin_alerts_' . $user_id;
	$alerts = get_transient( $key );

	delete_transient( $key );

	return is_array( $alerts ) ? $alerts : array();
}

/**
 * Sanitiza booleanos de formularios.
 *
 * @param mixed $value Valor original.
 * @return int
 */
function liga_sanitize_checkbox( $value ) {
	return ! empty( $value ) ? 1 : 0;
}

/**
 * Normaliza etiqueta de temporada con fallback.
 *
 * @param string $temporada Temporada recibida.
 * @param string $fallback Fallback opcional.
 * @return string
 */
function liga_normalize_temporada_label( $temporada, $fallback = '' ) {
	$temporada = trim( sanitize_text_field( (string) $temporada ) );
	if ( '' !== $temporada ) {
		return $temporada;
	}

	$fallback = trim( sanitize_text_field( (string) $fallback ) );
	if ( '' !== $fallback ) {
		return $fallback;
	}

	return liga_get_current_season_label();
}

/**
 * Verifica si un ID pertenece a un tipo de post valido.
 *
 * @param int    $post_id ID del post.
 * @param string $post_type Tipo esperado.
 * @return bool
 */
function liga_is_valid_post_type_id( $post_id, $post_type ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 ) {
		return false;
	}

	return $post_type === get_post_type( $post_id );
}

/**
 * Obtiene division de un equipo.
 *
 * @param int $equipo_id ID del equipo.
 * @return int
 */
function liga_get_equipo_division_id( $equipo_id ) {
	if ( ! liga_is_valid_post_type_id( $equipo_id, 'equipo' ) ) {
		return 0;
	}

	return (int) get_post_meta( $equipo_id, 'liga_division', true );
}

/**
 * Retorna nombre de equipo priorizando meta deportiva.
 *
 * @param int $equipo_id ID del equipo.
 * @return string
 */
function liga_get_equipo_nombre( $equipo_id ) {
	if ( ! liga_is_valid_post_type_id( $equipo_id, 'equipo' ) ) {
		return '';
	}

	$nombre = trim( sanitize_text_field( (string) get_post_meta( $equipo_id, 'liga_nombre_equipo', true ) ) );
	if ( '' !== $nombre ) {
		return $nombre;
	}

	return trim( sanitize_text_field( get_the_title( $equipo_id ) ) );
}

/**
 * Obtiene temporada configurada en division.
 *
 * @param int $division_id ID de la division.
 * @return string
 */
function liga_get_division_temporada_label( $division_id ) {
	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		return '';
	}

	return trim( sanitize_text_field( (string) get_post_meta( $division_id, 'liga_temporada', true ) ) );
}

/**
 * Determina si una temporada es valida (anio).
 *
 * @param string $temporada Temporada.
 * @return bool
 */
function liga_is_valid_temporada_label( $temporada ) {
	return 1 === preg_match( '/^\d{4}$/', trim( sanitize_text_field( (string) $temporada ) ) );
}

/**
 * Obtiene temporada efectiva de un equipo.
 *
 * @param int $equipo_id ID del equipo.
 * @return string
 */
function liga_get_equipo_temporada_label( $equipo_id ) {
	if ( ! liga_is_valid_post_type_id( $equipo_id, 'equipo' ) ) {
		return '';
	}

	$temporada = trim( sanitize_text_field( (string) get_post_meta( $equipo_id, 'liga_temporada', true ) ) );
	if ( liga_is_valid_temporada_label( $temporada ) ) {
		return $temporada;
	}

	$division_temporada = liga_get_division_temporada_label( liga_get_equipo_division_id( $equipo_id ) );
	if ( liga_is_valid_temporada_label( $division_temporada ) ) {
		return $division_temporada;
	}

	return '';
}

/**
 * Lista temporadas disponibles para selects estructurados.
 *
 * @return array<string, string>
 */
function liga_get_available_temporadas() {
	$temporadas = array();
	$current    = trim( sanitize_text_field( liga_get_current_season_label() ) );

	if ( liga_is_valid_temporada_label( $current ) ) {
		$temporadas[ $current ] = $current;
	}

	$divisions = get_posts(
		array(
			'post_type'      => 'division',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	foreach ( $divisions as $division ) {
		$temporada = liga_get_division_temporada_label( (int) $division->ID );
		if ( liga_is_valid_temporada_label( $temporada ) ) {
			$temporadas[ $temporada ] = $temporada;
		}
	}

	if ( empty( $temporadas ) ) {
		$year = gmdate( 'Y' );
		$temporadas[ $year ] = $year;
	}

	krsort( $temporadas, SORT_NUMERIC );

	return $temporadas;
}

/**
 * Construye clave unica logica para equipo por contexto competitivo.
 *
 * @param string $nombre Nombre de equipo.
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return string
 */
function liga_build_equipo_competicion_key( $nombre, $division_id, $temporada ) {
	$normalized_name = sanitize_title( remove_accents( sanitize_text_field( (string) $nombre ) ) );
	$normalized_year = trim( sanitize_text_field( (string) $temporada ) );
	$division_id     = absint( $division_id );

	return $normalized_name . '|' . (string) $division_id . '|' . $normalized_year;
}

/**
 * Normaliza texto para comparaciones de contexto competitivo.
 *
 * @param mixed $value Valor a normalizar.
 * @return string
 */
function liga_normalize_competition_string_for_compare( $value ) {
	$normalized = trim( sanitize_text_field( (string) $value ) );
	if ( '' === $normalized ) {
		return '';
	}

	$normalized = remove_accents( $normalized );
	if ( function_exists( 'mb_strtolower' ) ) {
		$normalized = mb_strtolower( $normalized, 'UTF-8' );
	} else {
		$normalized = strtolower( $normalized );
	}

	$normalized = preg_replace( '/\s+/u', ' ', $normalized );
	return trim( (string) $normalized );
}

/**
 * Resuelve ID de division por nombre.
 *
 * @param string $division_name Nombre de division.
 * @return int
 */
function liga_get_division_id_by_name( $division_name ) {
	$division_key = liga_normalize_competition_string_for_compare( $division_name );
	if ( '' === $division_key ) {
		return 0;
	}

	static $lookup = null;
	if ( null === $lookup ) {
		$lookup = array();
		$divisions = get_posts(
			array(
				'post_type'      => 'division',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $divisions as $division ) {
			$division_id  = (int) $division->ID;
			$title_key    = liga_normalize_competition_string_for_compare( get_the_title( $division_id ) );
			$meta_name    = trim( sanitize_text_field( (string) get_post_meta( $division_id, 'liga_nombre_division', true ) ) );
			$meta_name_key = liga_normalize_competition_string_for_compare( $meta_name );

			if ( '' !== $title_key && ! isset( $lookup[ $title_key ] ) ) {
				$lookup[ $title_key ] = $division_id;
			}

			if ( '' !== $meta_name_key && ! isset( $lookup[ $meta_name_key ] ) ) {
				$lookup[ $meta_name_key ] = $division_id;
			}
		}
	}

	return isset( $lookup[ $division_key ] ) ? (int) $lookup[ $division_key ] : 0;
}

/**
 * Obtiene IDs de equipos por nombre dentro de una division/temporada.
 *
 * @param string $nombre Nombre equipo.
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return array<int, int>
 */
function liga_get_team_ids_by_name_division_and_season( $nombre, $division_id, $temporada ) {
	$division_id = absint( $division_id );
	$temporada   = trim( sanitize_text_field( (string) $temporada ) );
	$nombre_key  = liga_normalize_competition_string_for_compare( $nombre );

	if ( '' === $nombre_key || $division_id <= 0 || ! liga_is_valid_temporada_label( $temporada ) ) {
		return array();
	}

	$teams = array();
	if ( function_exists( 'liga_get_available_teams_by_division_and_season' ) ) {
		$teams = liga_get_available_teams_by_division_and_season( $division_id, $temporada );
	} else {
		$teams = get_posts(
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
			)
		);
	}

	$matches = array();
	foreach ( $teams as $team ) {
		$team_id   = (int) $team->ID;
		$team_name = liga_get_equipo_nombre( $team_id );
		if ( liga_normalize_competition_string_for_compare( $team_name ) === $nombre_key ) {
			$matches[] = $team_id;
		}
	}

	return $matches;
}

/**
 * Busca un equipo por nombre dentro de division/temporada.
 *
 * @param string $nombre Nombre equipo.
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return int
 */
function liga_find_team_by_name_division_and_season( $nombre, $division_id, $temporada ) {
	$matches = liga_get_team_ids_by_name_division_and_season( $nombre, $division_id, $temporada );
	return 1 === count( $matches ) ? (int) $matches[0] : 0;
}

/**
 * Verifica existencia de equipo por nombre + division + temporada.
 *
 * @param string $nombre Nombre equipo.
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @param int    $exclude_team_id Excluir ID.
 * @return bool
 */
function liga_team_exists_by_name_division_and_season( $nombre, $division_id, $temporada, $exclude_team_id = 0 ) {
	$division_id      = absint( $division_id );
	$temporada        = trim( sanitize_text_field( (string) $temporada ) );
	$exclude_team_id  = absint( $exclude_team_id );
	$nombre           = trim( sanitize_text_field( (string) $nombre ) );

	if ( '' === $nombre || $division_id <= 0 || ! liga_is_valid_temporada_label( $temporada ) ) {
		return false;
	}

	$context_key = liga_build_equipo_competicion_key( $nombre, $division_id, $temporada );
	$query_args  = array(
		'post_type'      => 'equipo',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => array(
			array(
				'key'   => 'liga_equipo_competicion_key',
				'value' => $context_key,
			),
		),
	);

	if ( $exclude_team_id > 0 ) {
		$query_args['post__not_in'] = array( $exclude_team_id );
	}

	$duplicate_by_key = get_posts( $query_args );
	if ( ! empty( $duplicate_by_key ) ) {
		return true;
	}

	$matches = liga_get_team_ids_by_name_division_and_season( $nombre, $division_id, $temporada );
	foreach ( $matches as $team_id ) {
		if ( $exclude_team_id > 0 && $exclude_team_id === (int) $team_id ) {
			continue;
		}
		return true;
	}

	return false;
}

/**
 * Verifica que un equipo pertenezca al contexto competitivo indicado.
 *
 * @param int    $equipo_id ID equipo.
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return bool
 */
function liga_team_belongs_to_competition_context( $equipo_id, $division_id, $temporada ) {
	$equipo_id   = absint( $equipo_id );
	$division_id = absint( $division_id );
	$temporada   = trim( sanitize_text_field( (string) $temporada ) );

	if ( ! liga_is_valid_post_type_id( $equipo_id, 'equipo' ) || ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		return false;
	}

	if ( ! liga_is_valid_temporada_label( $temporada ) ) {
		return false;
	}

	$team_division  = liga_get_equipo_division_id( $equipo_id );
	$team_temporada = liga_get_equipo_temporada_label( $equipo_id );

	return $team_division === $division_id && $team_temporada === $temporada;
}

/**
 * Rellena contexto deportivo faltante en equipos existentes.
 *
 * @return void
 */
function liga_maybe_backfill_equipo_context() {
	$schema_version = (int) get_option( 'liga_equipo_context_schema_version', 1 );
	$target_version = 2;

	if ( $schema_version >= $target_version ) {
		return;
	}

	$equipos = get_posts(
		array(
			'post_type'      => 'equipo',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	foreach ( $equipos as $equipo ) {
		$equipo_id   = (int) $equipo->ID;
		$division_id = liga_get_equipo_division_id( $equipo_id );
		$nombre      = liga_get_equipo_nombre( $equipo_id );
		$temporada   = trim( sanitize_text_field( (string) get_post_meta( $equipo_id, 'liga_temporada', true ) ) );

		if ( ! liga_is_valid_temporada_label( $temporada ) ) {
			$division_temporada = liga_get_division_temporada_label( $division_id );
			if ( liga_is_valid_temporada_label( $division_temporada ) ) {
				$temporada = $division_temporada;
			} else {
				$temporada = trim( sanitize_text_field( liga_get_current_season_label() ) );
			}
		}

		if ( '' !== $nombre ) {
			update_post_meta( $equipo_id, 'liga_nombre_equipo', $nombre );
		}

		if ( liga_is_valid_temporada_label( $temporada ) ) {
			update_post_meta( $equipo_id, 'liga_temporada', $temporada );
		}

		if ( $division_id > 0 && '' !== $nombre && liga_is_valid_temporada_label( $temporada ) ) {
			update_post_meta( $equipo_id, 'liga_equipo_competicion_key', liga_build_equipo_competicion_key( $nombre, $division_id, $temporada ) );
		}
	}

	update_option( 'liga_equipo_context_schema_version', $target_version );
}
add_action( 'init', 'liga_maybe_backfill_equipo_context', 30 );

/**
 * Puntaje tecnico oficial para incomparecencia.
 *
 * @param string $incomparecencia Valor de incomparecencia.
 * @return array{local:int,visita:int}
 */
function liga_get_walkover_score( $incomparecencia ) {
	if ( 'local_no_comparecio' === $incomparecencia ) {
		return array(
			'local'  => 0,
			'visita' => 20,
		);
	}

	if ( 'visita_no_comparecio' === $incomparecencia ) {
		return array(
			'local'  => 20,
			'visita' => 0,
		);
	}

	return array(
		'local'  => 0,
		'visita' => 0,
	);
}

/**
 * Fallback de menu legal en footer.
 *
 * @return void
 */
function liga_fallback_legal_menu() {
	echo '<ul class="liga-legal-menu">';
	echo '<li><a href="' . esc_url( home_url( '/politica-privacidad' ) ) . '">' . esc_html__( 'Privacidad', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/terminos' ) ) . '">' . esc_html__( 'Terminos', 'liga-basket-chile' ) . '</a></li>';
	echo '</ul>';
}

/**
 * Fallback de menu principal.
 *
 * @return void
 */
function liga_fallback_primary_menu() {
	echo '<ul id="primary-menu" class="liga-menu">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Inicio', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/partido' ) ) . '">' . esc_html__( 'Partidos', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/equipo' ) ) . '">' . esc_html__( 'Equipos', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/noticias' ) ) . '">' . esc_html__( 'Noticias', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/contacto' ) ) . '">' . esc_html__( 'Contacto', 'liga-basket-chile' ) . '</a></li>';
	echo '</ul>';
}

/**
 * Fallback de menu footer.
 *
 * @return void
 */
function liga_fallback_footer_menu() {
	echo '<ul class="liga-footer-menu">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Inicio', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/la-liga' ) ) . '">' . esc_html__( 'La Liga', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/contacto' ) ) . '">' . esc_html__( 'Contacto', 'liga-basket-chile' ) . '</a></li>';
	echo '</ul>';
}

/**
 * Fallback de menu secundario.
 *
 * @return void
 */
function liga_fallback_secondary_menu() {
	echo '<ul class="liga-footer-menu">';
	echo '<li><a href="' . esc_url( home_url( '/historia' ) ) . '">' . esc_html__( 'Historia', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/reglamentos' ) ) . '">' . esc_html__( 'Reglamentos', 'liga-basket-chile' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/sponsors' ) ) . '">' . esc_html__( 'Sponsors', 'liga-basket-chile' ) . '</a></li>';
	echo '</ul>';
}

/**
 * Limpia cache del hero principal.
 *
 * @return void
 */
function liga_flush_home_banner_cache() {
	delete_transient( 'liga_home_active_banner_ids_v1' );
}

/**
 * Resuelve IDs de banners activos ordenados por prioridad.
 *
 * Orden:
 * - mayor orden visual
 * - mas reciente
 *
 * @return array<int, int>
 */
function liga_get_home_active_banner_ids() {
	$cached = get_transient( 'liga_home_active_banner_ids_v1' );
	if ( is_array( $cached ) ) {
		return array_map( 'absint', $cached );
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'banner-principal',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => 'liga_banner_activo',
					'value'   => 1,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			),
			'meta_key'               => 'liga_banner_orden_visual',
			'orderby'                => array(
				'meta_value_num' => 'DESC',
				'date'           => 'DESC',
			),
		)
	);

	$ids = array_map( 'absint', (array) $query->posts );
	set_transient( 'liga_home_active_banner_ids_v1', $ids, 10 * MINUTE_IN_SECONDS );

	return $ids;
}

/**
 * Divide titulo en dos lineas opcionales.
 *
 * @param string $title Titulo de entrada.
 * @return array{line_one:string,line_two:string}
 */
function liga_split_hero_title_lines( $title ) {
	$title = trim( sanitize_text_field( (string) $title ) );
	if ( '' === $title ) {
		return array(
			'line_one' => '',
			'line_two' => '',
		);
	}

	if ( false !== strpos( $title, '|' ) ) {
		$parts = array_map( 'trim', explode( '|', $title, 2 ) );
		return array(
			'line_one' => sanitize_text_field( (string) $parts[0] ),
			'line_two' => isset( $parts[1] ) ? sanitize_text_field( (string) $parts[1] ) : '',
		);
	}

	return array(
		'line_one' => $title,
		'line_two' => '',
	);
}

/**
 * Obtiene ID de imagen del banner con prioridad de meta nueva.
 *
 * @param int $banner_id ID del banner principal.
 * @return int
 */
function liga_get_banner_principal_image_id( $banner_id ) {
	$banner_id = absint( $banner_id );
	if ( $banner_id <= 0 ) {
		return 0;
	}

	$primary_image_id = (int) get_post_meta( $banner_id, '_liga_banner_image_id', true );
	$legacy_image_id  = (int) get_post_meta( $banner_id, 'liga_banner_imagen_id', true );
	$image_id         = $primary_image_id > 0 ? $primary_image_id : $legacy_image_id;

	if ( $image_id <= 0 || ! wp_attachment_is_image( $image_id ) ) {
		return 0;
	}

	return $image_id;
}

/**
 * Recupera una URL legacy de imagen si el banner no tiene attachment ID.
 *
 * @param int $banner_id ID del banner principal.
 * @return string
 */
function liga_get_banner_principal_legacy_image_url( $banner_id ) {
	$banner_id = absint( $banner_id );
	if ( $banner_id <= 0 ) {
		return '';
	}

	$candidate_keys = array(
		'liga_banner_imagen_url',
		'liga_banner_image_url',
		'_liga_banner_image_url',
	);

	foreach ( $candidate_keys as $meta_key ) {
		$meta_value = trim( (string) get_post_meta( $banner_id, $meta_key, true ) );
		if ( '' === $meta_value ) {
			continue;
		}

		$url = esc_url_raw( $meta_value );
		if ( '' !== $url ) {
			return $url;
		}
	}

	return '';
}

/**
 * Retorna payload del hero principal con soporte para slider multiple.
 *
 * @return array<string, mixed>
 */
function liga_get_home_hero_banner_data() {
	$fallback_title_line_one = sanitize_text_field( (string) get_theme_mod( 'liga_hero_line_one', 'Se vive el basquet' ) );
	$fallback_title_line_two = sanitize_text_field( (string) get_theme_mod( 'liga_hero_line_two', 'Se vive Concepcion' ) );
	$fallback_title          = trim( $fallback_title_line_one . ' ' . $fallback_title_line_two );
	$fallback_image          = get_theme_mod( 'liga_hero_image', '' );
	if ( empty( $fallback_image ) ) {
		$fallback_image = liga_svg_placeholder( 'Liga Concepcion', 1440, 900, '071c46', 'f7931e' );
	}

	$fallback_slide = array(
		'eyebrow'             => sanitize_text_field( (string) get_theme_mod( 'liga_hero_eyebrow', 'Temporada Regular 2025' ) ),
		'title'               => sanitize_text_field( '' !== $fallback_title ? $fallback_title : 'Se vive el basquet Se vive Concepcion' ),
		'title_line_one'      => '' !== $fallback_title_line_one ? $fallback_title_line_one : 'Se vive el basquet',
		'title_line_two'      => $fallback_title_line_two,
		'description'         => sanitize_textarea_field( (string) get_theme_mod( 'liga_hero_description', 'La mejor liga del sur de Chile. Talento, esfuerzo y pasion en cada partido.' ) ),
		'cta_primary_label'   => sanitize_text_field( (string) get_theme_mod( 'liga_hero_cta_one_label', 'Ver partidos' ) ),
		'cta_primary_url'     => esc_url_raw( (string) get_theme_mod( 'liga_hero_cta_one_url', home_url( '/partidos/' ) ) ),
		'cta_secondary_label' => sanitize_text_field( (string) get_theme_mod( 'liga_hero_cta_two_label', 'Conoce la liga' ) ),
		'cta_secondary_url'   => esc_url_raw( (string) get_theme_mod( 'liga_hero_cta_two_url', home_url( '/la-liga/' ) ) ),
		'image_id'            => 0,
		'image_src'           => $fallback_image,
		'image_alt'           => __( 'Jugador de basquetbol en accion durante un partido de liga', 'liga-basket-chile' ),
		'text_align'          => 'left',
		'height'              => 'normal',
		'overlay'             => 1,
		'gradient'            => 1,
		'autoplay'            => 1,
	);

	$active_banner_ids = liga_get_home_active_banner_ids();
	if ( empty( $active_banner_ids ) ) {
		return array(
			'slides'            => array( $fallback_slide ),
			'is_slider'         => false,
			'show_controls'     => false,
			'autoplay'          => true,
			'autoplay_interval' => 5000,
		);
	}

	$align_map = array(
		'izquierda' => 'left',
		'centro'    => 'center',
		'derecha'   => 'right',
	);
	$height_map = array(
		'compacta' => 'compact',
		'normal'   => 'normal',
		'amplia'   => 'wide',
	);

	$slides = array();
	foreach ( $active_banner_ids as $banner_id ) {
		$raw_title = trim( sanitize_text_field( (string) get_post_meta( $banner_id, 'liga_banner_titulo', true ) ) );
		if ( '' === $raw_title ) {
			$raw_title = trim( sanitize_text_field( get_the_title( $banner_id ) ) );
		}
		if ( '' === $raw_title ) {
			$raw_title = (string) $fallback_slide['title'];
		}

		$title_lines = liga_split_hero_title_lines( $raw_title );
		if ( '' === $title_lines['line_one'] ) {
			$title_lines['line_one'] = (string) $fallback_slide['title_line_one'];
		}

		$align_raw = sanitize_key( (string) get_post_meta( $banner_id, 'liga_banner_alineacion_texto', true ) );
		$height_raw = sanitize_key( (string) get_post_meta( $banner_id, 'liga_banner_altura', true ) );
		$overlay_raw = get_post_meta( $banner_id, 'liga_banner_overlay', true );
		$gradient_raw = get_post_meta( $banner_id, 'liga_banner_fondo_degradado', true );
		$autoplay_raw = get_post_meta( $banner_id, 'liga_banner_autoplay', true );

		$image_id = liga_get_banner_principal_image_id( $banner_id );
		$image_src = (string) $fallback_slide['image_src'];
		if ( $image_id <= 0 ) {
			$legacy_image_url = liga_get_banner_principal_legacy_image_url( $banner_id );
			if ( '' !== $legacy_image_url ) {
				$image_src = $legacy_image_url;
			}
		}

		$image_alt = trim( sanitize_text_field( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) );
		if ( '' === $image_alt ) {
			$image_alt = $raw_title;
		}
		if ( '' === $image_alt ) {
			$image_alt = (string) $fallback_slide['image_alt'];
		}

		$slides[] = array(
			'eyebrow'             => trim( sanitize_text_field( (string) get_post_meta( $banner_id, 'liga_banner_eyebrow', true ) ) ),
			'title'               => $raw_title,
			'title_line_one'      => $title_lines['line_one'],
			'title_line_two'      => $title_lines['line_two'],
			'description'         => trim( sanitize_textarea_field( (string) get_post_meta( $banner_id, 'liga_banner_bajada', true ) ) ),
			'cta_primary_label'   => trim( sanitize_text_field( (string) get_post_meta( $banner_id, 'liga_banner_cta_principal_texto', true ) ) ),
			'cta_primary_url'     => trim( esc_url_raw( (string) get_post_meta( $banner_id, 'liga_banner_cta_principal_url', true ) ) ),
			'cta_secondary_label' => trim( sanitize_text_field( (string) get_post_meta( $banner_id, 'liga_banner_cta_secundario_texto', true ) ) ),
			'cta_secondary_url'   => trim( esc_url_raw( (string) get_post_meta( $banner_id, 'liga_banner_cta_secundario_url', true ) ) ),
			'image_id'            => $image_id,
			'image_src'           => $image_src,
			'image_alt'           => $image_alt,
			'text_align'          => isset( $align_map[ $align_raw ] ) ? $align_map[ $align_raw ] : 'left',
			'height'              => isset( $height_map[ $height_raw ] ) ? $height_map[ $height_raw ] : 'normal',
			'overlay'             => '' === (string) $overlay_raw ? true : 1 === (int) $overlay_raw,
			'gradient'            => '' === (string) $gradient_raw ? true : 1 === (int) $gradient_raw,
			'autoplay'            => '' === (string) $autoplay_raw ? true : 1 === (int) $autoplay_raw,
		);
	}

	foreach ( $slides as $index => $slide ) {
		if ( '' === $slide['eyebrow'] ) {
			$slides[ $index ]['eyebrow'] = (string) $fallback_slide['eyebrow'];
		}
		if ( '' === $slide['description'] ) {
			$slides[ $index ]['description'] = (string) $fallback_slide['description'];
		}
		if ( '' === $slide['cta_primary_label'] ) {
			$slides[ $index ]['cta_primary_label'] = (string) $fallback_slide['cta_primary_label'];
		}
		if ( '' === $slide['cta_primary_url'] ) {
			$slides[ $index ]['cta_primary_url'] = (string) $fallback_slide['cta_primary_url'];
		}
		if ( '' === $slide['cta_secondary_label'] ) {
			$slides[ $index ]['cta_secondary_label'] = (string) $fallback_slide['cta_secondary_label'];
		}
		if ( '' === $slide['cta_secondary_url'] ) {
			$slides[ $index ]['cta_secondary_url'] = (string) $fallback_slide['cta_secondary_url'];
		}
	}

	$slide_count = count( $slides );
	$is_slider   = $slide_count > 1;

	return array(
		'slides'            => $slides,
		'is_slider'         => $is_slider,
		'show_controls'     => $is_slider,
		'autoplay'          => $is_slider ? (bool) $slides[0]['autoplay'] : false,
		'autoplay_interval' => 5000,
	);
}
