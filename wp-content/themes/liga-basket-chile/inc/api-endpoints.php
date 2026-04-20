<?php
/**
 * REST API custom (fase 3).
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra endpoints REST de la liga.
 *
 * @return void
 */
function liga_register_rest_endpoints() {
	register_rest_route(
		'liga/v1',
		'/tabla',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'liga_rest_get_tabla',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'liga/v1',
		'/partidos',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'liga_rest_get_partidos',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'liga/v1',
		'/equipos',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'liga_rest_get_equipos',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'liga/v1',
		'/noticias',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'liga_rest_get_noticias',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'liga_register_rest_endpoints' );

/**
 * Obtiene argumentos sanitizados de filtros API.
 *
 * @param WP_REST_Request $request Request.
 * @return array<string, mixed>
 */
function liga_rest_sanitize_filters( WP_REST_Request $request ) {
	$limit = absint( $request->get_param( 'limit' ) );
	if ( $limit <= 0 ) {
		$limit = 10;
	}
	if ( $limit > 50 ) {
		$limit = 50;
	}

	return array(
		'division'  => absint( $request->get_param( 'division' ) ),
		'temporada' => sanitize_text_field( (string) $request->get_param( 'temporada' ) ),
		'estado'    => sanitize_key( (string) $request->get_param( 'estado' ) ),
		'limit'     => $limit,
	);
}

/**
 * Endpoint: tabla de posiciones.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function liga_rest_get_tabla( WP_REST_Request $request ) {
	$filters = liga_rest_sanitize_filters( $request );
	$data    = function_exists( 'liga_get_standings_by_division_and_season' )
		? liga_get_standings_by_division_and_season( $filters['division'], $filters['temporada'] )
		: liga_calcular_tabla_posiciones( $filters['division'], $filters['temporada'] );

	return rest_ensure_response( $data );
}

/**
 * Endpoint: partidos.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function liga_rest_get_partidos( WP_REST_Request $request ) {
	$filters    = liga_rest_sanitize_filters( $request );
	$meta_query = array();

	if ( $filters['division'] > 0 ) {
		$meta_query[] = array(
			'key'   => 'liga_division',
			'value' => $filters['division'],
			'type'  => 'NUMERIC',
		);
	}

	if ( '' !== $filters['temporada'] ) {
		$meta_query[] = array(
			'key'   => 'liga_temporada',
			'value' => $filters['temporada'],
		);
	}

	if ( in_array( $filters['estado'], array( 'programado', 'jugado', 'finalizado', 'suspendido', 'cancelado' ), true ) ) {
		$meta_query[] = array(
			'key'   => 'liga_estado_partido',
			'value' => $filters['estado'],
		);
	}

	$matches = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => 'publish',
			'posts_per_page' => $filters['limit'],
			'meta_query'     => $meta_query,
			'meta_key'       => 'liga_fecha_partido',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);

	$data = array();
	foreach ( $matches as $match ) {
		$local_id   = (int) get_post_meta( $match->ID, 'liga_equipo_local', true );
		$visita_id  = (int) get_post_meta( $match->ID, 'liga_equipo_visita', true );
		$data[] = array(
			'id'              => (int) $match->ID,
			'titulo'          => $match->post_title,
			'local_id'        => $local_id,
			'local'           => $local_id ? get_the_title( $local_id ) : '',
			'visita_id'       => $visita_id,
			'visita'          => $visita_id ? get_the_title( $visita_id ) : '',
			'division_id'     => (int) get_post_meta( $match->ID, 'liga_division', true ),
			'division'        => get_the_title( (int) get_post_meta( $match->ID, 'liga_division', true ) ),
			'temporada'       => (string) get_post_meta( $match->ID, 'liga_temporada', true ),
			'fecha'           => (string) get_post_meta( $match->ID, 'liga_fecha_partido', true ),
			'hora'            => (string) get_post_meta( $match->ID, 'liga_hora_partido', true ),
			'cancha'          => (string) get_post_meta( $match->ID, 'liga_cancha', true ),
			'estado'          => (string) get_post_meta( $match->ID, 'liga_estado_partido', true ),
			'puntos_local'    => (int) get_post_meta( $match->ID, 'liga_puntos_local', true ),
			'puntos_visita'   => (int) get_post_meta( $match->ID, 'liga_puntos_visita', true ),
			'incomparecencia' => (string) get_post_meta( $match->ID, 'liga_incomparecencia', true ),
		);
	}

	return rest_ensure_response(
		array(
			'total' => count( $data ),
			'items' => $data,
		)
	);
}

/**
 * Endpoint: equipos.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function liga_rest_get_equipos( WP_REST_Request $request ) {
	$filters    = liga_rest_sanitize_filters( $request );
	$meta_query = array();
	$temporada  = liga_normalize_temporada_label( $filters['temporada'], '' );

	if ( $filters['division'] > 0 ) {
		$meta_query[] = array(
			'key'   => 'liga_division',
			'value' => $filters['division'],
			'type'  => 'NUMERIC',
		);
	}

	if ( liga_is_valid_temporada_label( $temporada ) ) {
		$meta_query[] = array(
			'key'   => 'liga_temporada',
			'value' => $temporada,
		);
	}

	$teams = get_posts(
		array(
			'post_type'      => 'equipo',
			'post_status'    => 'publish',
			'posts_per_page' => $filters['limit'],
			'meta_query'     => $meta_query,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$data = array();
	foreach ( $teams as $team ) {
		$division_id = (int) get_post_meta( $team->ID, 'liga_division', true );
		$logo_id     = (int) get_post_meta( $team->ID, 'liga_logo_equipo', true );
		$team_year   = liga_get_equipo_temporada_label( (int) $team->ID );
		$data[] = array(
			'id'             => (int) $team->ID,
			'nombre'         => liga_get_equipo_nombre( (int) $team->ID ),
			'titulo'         => $team->post_title,
			'ciudad'         => (string) get_post_meta( $team->ID, 'liga_ciudad', true ),
			'anio_fundacion' => (int) get_post_meta( $team->ID, 'liga_anio_fundacion', true ),
			'division_id'    => $division_id,
			'division'       => $division_id ? get_the_title( $division_id ) : '',
			'temporada'      => $team_year,
			'color'          => (string) get_post_meta( $team->ID, 'liga_color_principal', true ),
			'entrenador'     => (string) get_post_meta( $team->ID, 'liga_entrenador', true ),
			'logo_id'        => $logo_id,
			'logo_url'       => $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '',
		);
	}

	return rest_ensure_response(
		array(
			'total' => count( $data ),
			'items' => $data,
		)
	);
}

/**
 * Endpoint: noticias.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function liga_rest_get_noticias( WP_REST_Request $request ) {
	$filters = liga_rest_sanitize_filters( $request );

	$posts = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $filters['limit'],
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$data = array();
	foreach ( $posts as $news ) {
		$data[] = array(
			'id'      => (int) $news->ID,
			'titulo'  => $news->post_title,
			'fecha'   => get_the_date( 'c', $news ),
			'excerpt' => wp_trim_words( wp_strip_all_tags( $news->post_content ), 24 ),
			'url'     => get_permalink( $news ),
			'imagen'  => get_the_post_thumbnail_url( $news, 'large' ),
		);
	}

	return rest_ensure_response(
		array(
			'total' => count( $data ),
			'items' => $data,
		)
	);
}
