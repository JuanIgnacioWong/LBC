<?php
/**
 * Public standings routing + data helpers.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers rewrite rules for public standings pages.
 *
 * URL pattern: /tabla/{division}/{temporada}/
 *
 * @return void
 */
function liga_register_public_standings_rewrite_rules() {
	add_rewrite_rule(
		'^tabla/([^/]+)/([0-9]{4})/?$',
		'index.php?liga_tabla_division=$matches[1]&liga_tabla_temporada=$matches[2]',
		'top'
	);
}
add_action( 'init', 'liga_register_public_standings_rewrite_rules', 20 );

/**
 * Registers query vars consumed by dynamic standings pages.
 *
 * @param array<int, string> $vars Query vars.
 * @return array<int, string>
 */
function liga_public_standings_query_vars( $vars ) {
	$vars[] = 'liga_tabla_division';
	$vars[] = 'liga_tabla_temporada';
	return $vars;
}
add_filter( 'query_vars', 'liga_public_standings_query_vars' );

/**
 * Flushes rewrite rules for standings routes.
 *
 * @return void
 */
function liga_flush_public_standings_rewrite_rules() {
	liga_register_public_standings_rewrite_rules();
	flush_rewrite_rules( false );
}

/**
 * Flushes rewrite rules when the theme is switched.
 *
 * @return void
 */
function liga_public_standings_flush_rewrite_on_theme_switch() {
	liga_flush_public_standings_rewrite_rules();
}
add_action( 'after_switch_theme', 'liga_public_standings_flush_rewrite_on_theme_switch' );

/**
 * Ensures rewrite schema is installed once after deployments.
 *
 * @return void
 */
function liga_maybe_upgrade_public_standings_rewrite_schema() {
	$target_version = 1;
	$current        = (int) get_option( 'liga_public_standings_rewrite_version', 0 );

	if ( $current >= $target_version ) {
		return;
	}

	liga_flush_public_standings_rewrite_rules();
	update_option( 'liga_public_standings_rewrite_version', $target_version );
}
add_action( 'init', 'liga_maybe_upgrade_public_standings_rewrite_schema', 99 );

/**
 * Resolves division ID from public URL slug.
 *
 * @param string $division_slug Division slug.
 * @return int
 */
function liga_get_division_id_from_public_slug( $division_slug ) {
	$division_slug = sanitize_title( (string) $division_slug );
	if ( '' === $division_slug ) {
		return 0;
	}

	static $lookup = null;
	if ( null === $lookup ) {
		$lookup    = array();
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
			$division_id      = (int) $division->ID;
			$post_slug        = sanitize_title( (string) $division->post_name );
			$title_slug       = sanitize_title( (string) $division->post_title );
			$custom_name      = trim( sanitize_text_field( (string) get_post_meta( $division_id, 'liga_nombre_division', true ) ) );
			$custom_name_slug = sanitize_title( $custom_name );

			if ( '' !== $post_slug && ! isset( $lookup[ $post_slug ] ) ) {
				$lookup[ $post_slug ] = $division_id;
			}

			if ( '' !== $title_slug && ! isset( $lookup[ $title_slug ] ) ) {
				$lookup[ $title_slug ] = $division_id;
			}

			if ( '' !== $custom_name_slug && ! isset( $lookup[ $custom_name_slug ] ) ) {
				$lookup[ $custom_name_slug ] = $division_id;
			}
		}
	}

	return isset( $lookup[ $division_slug ] ) ? (int) $lookup[ $division_slug ] : 0;
}

/**
 * Returns a human label for a division.
 *
 * @param int $division_id Division ID.
 * @return string
 */
function liga_get_division_public_label( $division_id ) {
	$division_id = absint( $division_id );
	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		return '';
	}

	$custom_label = trim( sanitize_text_field( (string) get_post_meta( $division_id, 'liga_nombre_division', true ) ) );
	if ( '' !== $custom_label ) {
		return $custom_label;
	}

	return trim( sanitize_text_field( get_the_title( $division_id ) ) );
}

/**
 * Returns the public standings URL for a division + season.
 *
 * @param int    $division_id Division ID.
 * @param string $temporada Season label (YYYY).
 * @return string
 */
function liga_get_standings_public_url( $division_id, $temporada = '' ) {
	$division_id = absint( $division_id );
	$temporada   = liga_normalize_temporada_label( (string) $temporada, liga_get_current_season_label() );

	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) || ! liga_is_valid_temporada_label( $temporada ) ) {
		return '';
	}

	$division_post = get_post( $division_id );
	if ( ! $division_post instanceof WP_Post ) {
		return '';
	}

	$division_slug = sanitize_title( (string) $division_post->post_name );
	if ( '' === $division_slug ) {
		$division_slug = sanitize_title( (string) $division_post->post_title );
	}

	if ( '' === $division_slug ) {
		return '';
	}

	$path = sprintf(
		'tabla/%s/%s/',
		rawurlencode( $division_slug ),
		rawurlencode( $temporada )
	);

	return home_url( user_trailingslashit( $path ) );
}

/**
 * Returns active context for public standings request.
 *
 * @return array<string, mixed>
 */
function liga_get_public_standings_request_context() {
	static $context = null;

	if ( null !== $context ) {
		return $context;
	}

	$division_slug = sanitize_title( (string) get_query_var( 'liga_tabla_division' ) );
	$temporada     = trim( sanitize_text_field( (string) get_query_var( 'liga_tabla_temporada' ) ) );

	$context = array(
		'is_public_standings' => false,
		'is_valid'            => false,
		'division_slug'       => $division_slug,
		'division_id'         => 0,
		'division_label'      => '',
		'temporada'           => $temporada,
		'page_title'          => '',
		'meta_description'    => '',
		'error'               => '',
	);

	if ( '' === $division_slug && '' === $temporada ) {
		return $context;
	}

	$context['is_public_standings'] = true;

	if ( ! liga_is_valid_temporada_label( $temporada ) ) {
		$context['error'] = __( 'Temporada invalida.', 'liga-basket-chile' );
		return $context;
	}

	$division_id = liga_get_division_id_from_public_slug( $division_slug );
	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		$context['error'] = __( 'Division no encontrada.', 'liga-basket-chile' );
		return $context;
	}

	$division_label = liga_get_division_public_label( $division_id );

	$context['is_valid']         = true;
	$context['division_id']      = $division_id;
	$context['division_label']   = $division_label;
	$context['page_title']       = sprintf(
		/* translators: 1: division label, 2: season */
		__( 'Tabla de Posiciones — %1$s %2$s', 'liga-basket-chile' ),
		$division_label,
		$temporada
	);
	$context['meta_description'] = sprintf(
		/* translators: 1: division label, 2: season */
		__( 'Tabla de posiciones oficial de %1$s temporada %2$s. Incluye clasificacion, ultimos resultados y proximos partidos.', 'liga-basket-chile' ),
		$division_label,
		$temporada
	);

	return $context;
}

/**
 * Forces 404 status for invalid standings context.
 *
 * @return void
 */
function liga_handle_public_standings_not_found() {
	$context = liga_get_public_standings_request_context();
	if ( empty( $context['is_public_standings'] ) || ! empty( $context['is_valid'] ) ) {
		return;
	}

	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->set_404();
	}

	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'liga_handle_public_standings_not_found', 9 );

/**
 * Routes standings context to the public template.
 *
 * @param string $template Current template.
 * @return string
 */
function liga_public_standings_template_include( $template ) {
	$context = liga_get_public_standings_request_context();
	if ( empty( $context['is_public_standings'] ) ) {
		return $template;
	}

	$custom_template = get_template_directory() . '/template-standings-public.php';
	if ( file_exists( $custom_template ) ) {
		return $custom_template;
	}

	return $template;
}
add_filter( 'template_include', 'liga_public_standings_template_include', 99 );

/**
 * Injects document title for public standings pages.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function liga_filter_public_standings_document_title( $parts ) {
	$context = liga_get_public_standings_request_context();
	if ( empty( $context['is_public_standings'] ) ) {
		return $parts;
	}

	if ( ! empty( $context['is_valid'] ) ) {
		$parts['title'] = (string) $context['page_title'];
	} else {
		$parts['title'] = __( 'Tabla de Posiciones', 'liga-basket-chile' );
	}

	return $parts;
}
add_filter( 'document_title_parts', 'liga_filter_public_standings_document_title' );

/**
 * Prints meta description for standings pages.
 *
 * @return void
 */
function liga_output_public_standings_meta_description() {
	$context = liga_get_public_standings_request_context();
	if ( empty( $context['is_public_standings'] ) || empty( $context['is_valid'] ) ) {
		return;
	}

	echo '<meta name="description" content="' . esc_attr( (string) $context['meta_description'] ) . '">' . "\n";
}
add_action( 'wp_head', 'liga_output_public_standings_meta_description', 5 );

/**
 * Adds body classes for standings pages.
 *
 * @param array<int, string> $classes Body classes.
 * @return array<int, string>
 */
function liga_public_standings_body_class( $classes ) {
	$context = liga_get_public_standings_request_context();
	if ( empty( $context['is_public_standings'] ) ) {
		return $classes;
	}

	$classes[] = 'liga-public-standings-page';
	if ( ! empty( $context['is_valid'] ) ) {
		$classes[] = 'liga-public-standings-page--valid';
	}

	return array_unique( $classes );
}
add_filter( 'body_class', 'liga_public_standings_body_class' );

/**
 * Returns played/finalized matches for a division + season.
 *
 * @param int    $division_id Division ID.
 * @param string $temporada Season label.
 * @param int    $limit Maximum rows.
 * @return array<int, array<string, mixed>>
 */
function liga_get_recent_played_matches_by_division_and_season( $division_id, $temporada, $limit = 6 ) {
	$division_id = absint( $division_id );
	$temporada   = trim( sanitize_text_field( (string) $temporada ) );
	$limit       = max( 1, min( 20, absint( $limit ) ) );

	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) || ! liga_is_valid_temporada_label( $temporada ) ) {
		return array();
	}

	$query_limit = max( 12, $limit * 4 );
	$matches     = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => 'publish',
			'posts_per_page' => $query_limit,
			'meta_key'       => 'liga_fecha_partido',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
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
					'key'     => 'liga_estado_partido',
					'value'   => array( 'jugado', 'finalizado' ),
					'compare' => 'IN',
				),
			),
		)
	);

	$rows = array();

	foreach ( $matches as $match ) {
		$match_id      = (int) $match->ID;
		$match_context = liga_is_match_countable_for_standings( $match_id, $division_id, $temporada );
		if ( empty( $match_context['is_countable'] ) || empty( $match_context['match'] ) || ! is_array( $match_context['match'] ) ) {
			continue;
		}

		$data        = $match_context['match'];
		$local_id    = isset( $data['local_id'] ) ? (int) $data['local_id'] : 0;
		$visita_id   = isset( $data['visita_id'] ) ? (int) $data['visita_id'] : 0;
		$raw_date    = (string) get_post_meta( $match_id, 'liga_fecha_partido', true );
		$raw_time    = (string) get_post_meta( $match_id, 'liga_hora_partido', true );
		$raw_venue   = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_cancha', true ) ) );
		$match_state = sanitize_key( (string) get_post_meta( $match_id, 'liga_estado_partido', true ) );

		if ( $local_id <= 0 || $visita_id <= 0 || $local_id === $visita_id ) {
			continue;
		}

		$rows[] = array(
			'id'             => $match_id,
			'division_id'    => $division_id,
			'temporada'      => $temporada,
			'local_id'       => $local_id,
			'visita_id'      => $visita_id,
			'puntos_local'   => isset( $data['puntos_local'] ) ? (int) $data['puntos_local'] : 0,
			'puntos_visita'  => isset( $data['puntos_visita'] ) ? (int) $data['puntos_visita'] : 0,
			'incomparecencia'=> isset( $data['incomparecencia'] ) ? sanitize_key( (string) $data['incomparecencia'] ) : 'ninguna',
			'estado'         => $match_state,
			'fecha'          => trim( sanitize_text_field( $raw_date ) ),
			'hora'           => trim( sanitize_text_field( $raw_time ) ),
			'recinto'        => $raw_venue,
		);

		if ( count( $rows ) >= $limit ) {
			break;
		}
	}

	return $rows;
}

/**
 * Returns upcoming programmed matches for a division + season.
 *
 * @param int    $division_id Division ID.
 * @param string $temporada Season label.
 * @param int    $limit Maximum rows.
 * @return array<int, array<string, mixed>>
 */
function liga_get_upcoming_matches_by_division_and_season( $division_id, $temporada, $limit = 6 ) {
	$division_id = absint( $division_id );
	$temporada   = trim( sanitize_text_field( (string) $temporada ) );
	$limit       = max( 1, min( 20, absint( $limit ) ) );

	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) || ! liga_is_valid_temporada_label( $temporada ) ) {
		return array();
	}

	$query_limit = max( 12, $limit * 4 );
	$matches     = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => 'publish',
			'posts_per_page' => $query_limit,
			'meta_key'       => 'liga_fecha_partido',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
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
					'key'   => 'liga_estado_partido',
					'value' => 'programado',
				),
			),
		)
	);

	$rows = array();

	foreach ( $matches as $match ) {
		$match_id    = (int) $match->ID;
		$raw_date    = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_fecha_partido', true ) ) );
		$raw_time    = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_hora_partido', true ) ) );
		$raw_venue   = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_cancha', true ) ) );
		$match_state = sanitize_key( (string) get_post_meta( $match_id, 'liga_estado_partido', true ) );

		if ( '' === $raw_date || false === strtotime( $raw_date ) ) {
			continue;
		}

		$context_validation = liga_validate_match_competition_context( $match_id, $division_id, $temporada );
		if ( is_wp_error( $context_validation ) ) {
			continue;
		}

		$local_id  = isset( $context_validation['local_id'] ) ? (int) $context_validation['local_id'] : 0;
		$visita_id = isset( $context_validation['visita_id'] ) ? (int) $context_validation['visita_id'] : 0;

		if ( $local_id <= 0 || $visita_id <= 0 || $local_id === $visita_id ) {
			continue;
		}

		$rows[] = array(
			'id'            => $match_id,
			'division_id'   => $division_id,
			'temporada'     => $temporada,
			'local_id'      => $local_id,
			'visita_id'     => $visita_id,
			'estado'        => $match_state,
			'fecha'         => $raw_date,
			'hora'          => $raw_time,
			'recinto'       => $raw_venue,
		);

		if ( count( $rows ) >= $limit ) {
			break;
		}
	}

	return $rows;
}

/**
 * Returns division IDs that have activity in a season.
 *
 * @param string $temporada Season label.
 * @return array<int, int>
 */
function liga_get_active_division_ids_by_season( $temporada ) {
	$temporada = trim( sanitize_text_field( (string) $temporada ) );
	if ( ! liga_is_valid_temporada_label( $temporada ) ) {
		return array();
	}

	$ids = array();

	$team_ids = get_posts(
		array(
			'post_type'      => 'equipo',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'liga_temporada',
					'value' => $temporada,
				),
			),
		)
	);

	foreach ( $team_ids as $team_id ) {
		$division_id = liga_get_equipo_division_id( (int) $team_id );
		if ( liga_is_valid_post_type_id( $division_id, 'division' ) ) {
			$ids[ $division_id ] = $division_id;
		}
	}

	$match_ids = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'liga_temporada',
					'value' => $temporada,
				),
			),
		)
	);

	foreach ( $match_ids as $match_id ) {
		$division_id = (int) get_post_meta( (int) $match_id, 'liga_division', true );
		if ( liga_is_valid_post_type_id( $division_id, 'division' ) ) {
			$ids[ $division_id ] = $division_id;
		}
	}

	if ( empty( $ids ) ) {
		return array();
	}

	$division_posts = get_posts(
		array(
			'post_type'      => 'division',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post__in'       => array_values( $ids ),
			'meta_key'       => 'liga_orden_visual',
			'orderby'        => array(
				'meta_value_num' => 'ASC',
				'title'          => 'ASC',
			),
		)
	);

	$ordered = array();
	foreach ( $division_posts as $division_post ) {
		$ordered[] = (int) $division_post->ID;
	}

	return $ordered;
}

/**
 * Returns available seasons for one division.
 *
 * @param int $division_id Division ID.
 * @return array<int, string>
 */
function liga_get_available_seasons_by_division( $division_id ) {
	$division_id = absint( $division_id );
	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		return array();
	}

	$seasons = array();

	$team_ids = get_posts(
		array(
			'post_type'      => 'equipo',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'liga_division',
					'value' => $division_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	foreach ( $team_ids as $team_id ) {
		$season = liga_get_equipo_temporada_label( (int) $team_id );
		if ( liga_is_valid_temporada_label( $season ) ) {
			$seasons[ $season ] = $season;
		}
	}

	$match_ids = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => 'liga_division',
					'value' => $division_id,
					'type'  => 'NUMERIC',
				),
			),
		)
	);

	foreach ( $match_ids as $match_id ) {
		$season = trim( sanitize_text_field( (string) get_post_meta( (int) $match_id, 'liga_temporada', true ) ) );
		if ( liga_is_valid_temporada_label( $season ) ) {
			$seasons[ $season ] = $season;
		}
	}

	if ( empty( $seasons ) ) {
		$current = trim( sanitize_text_field( liga_get_current_season_label() ) );
		if ( liga_is_valid_temporada_label( $current ) ) {
			$seasons[ $current ] = $current;
		}
	}

	krsort( $seasons, SORT_NUMERIC );
	return array_values( $seasons );
}

/**
 * Returns related division links for same season.
 *
 * @param string $temporada Current season.
 * @param int    $exclude_division_id Excluded division.
 * @param int    $max Max links.
 * @return array<int, array<string, string|bool>>
 */
function liga_get_related_division_links_for_season( $temporada, $exclude_division_id = 0, $max = 8 ) {
	$temporada           = trim( sanitize_text_field( (string) $temporada ) );
	$exclude_division_id = absint( $exclude_division_id );
	$max                 = max( 1, min( 20, absint( $max ) ) );

	if ( ! liga_is_valid_temporada_label( $temporada ) ) {
		return array();
	}

	$division_ids = liga_get_active_division_ids_by_season( $temporada );
	if ( empty( $division_ids ) ) {
		$fallback = get_posts(
			array(
				'post_type'      => 'division',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => 'liga_orden_visual',
				'orderby'        => array(
					'meta_value_num' => 'ASC',
					'title'          => 'ASC',
				),
			)
		);

		$division_ids = array_map(
			static function ( $division_post ) {
				return (int) $division_post->ID;
			},
			$fallback
		);
	}

	$links = array();
	foreach ( $division_ids as $division_id ) {
		$division_id = absint( $division_id );
		if ( $division_id <= 0 || $division_id === $exclude_division_id ) {
			continue;
		}

		$url = liga_get_standings_public_url( $division_id, $temporada );
		if ( '' === $url ) {
			continue;
		}

		$links[] = array(
			'label' => liga_get_division_public_label( $division_id ),
			'url'   => $url,
		);

		if ( count( $links ) >= $max ) {
			break;
		}
	}

	return $links;
}

/**
 * Returns related season links for same division.
 *
 * @param int    $division_id Division ID.
 * @param string $exclude_temporada Current season.
 * @param int    $max Max links.
 * @return array<int, array<string, string|bool>>
 */
function liga_get_related_season_links_for_division( $division_id, $exclude_temporada = '', $max = 6 ) {
	$division_id        = absint( $division_id );
	$exclude_temporada  = trim( sanitize_text_field( (string) $exclude_temporada ) );
	$max                = max( 1, min( 20, absint( $max ) ) );

	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		return array();
	}

	$seasons = liga_get_available_seasons_by_division( $division_id );
	$links   = array();

	foreach ( $seasons as $season ) {
		if ( ! liga_is_valid_temporada_label( $season ) || $season === $exclude_temporada ) {
			continue;
		}

		$url = liga_get_standings_public_url( $division_id, $season );
		if ( '' === $url ) {
			continue;
		}

		$links[] = array(
			'label' => $season,
			'url'   => $url,
		);

		if ( count( $links ) >= $max ) {
			break;
		}
	}

	return $links;
}

/**
 * Returns the first public standings URL available for a season.
 *
 * @param string $temporada Season label.
 * @return string
 */
function liga_get_default_public_standings_url( $temporada = '' ) {
	$temporada = liga_normalize_temporada_label( (string) $temporada, liga_get_current_season_label() );
	if ( ! liga_is_valid_temporada_label( $temporada ) ) {
		return '';
	}

	$division_ids = liga_get_active_division_ids_by_season( $temporada );
	if ( empty( $division_ids ) ) {
		$all_divisions = get_posts(
			array(
				'post_type'      => 'division',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => 'liga_orden_visual',
				'orderby'        => array(
					'meta_value_num' => 'ASC',
					'title'          => 'ASC',
				),
			)
		);

		if ( ! empty( $all_divisions ) ) {
			$division_ids[] = (int) $all_divisions[0]->ID;
		}
	}

	if ( empty( $division_ids ) ) {
		return '';
	}

	return liga_get_standings_public_url( (int) $division_ids[0], $temporada );
}
