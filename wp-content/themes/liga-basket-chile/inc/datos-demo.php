<?php
/**
 * Carga de datos demo (fase 3).
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Crea o actualiza un post demo por slug.
 *
 * @param string $post_type Post type.
 * @param string $slug Slug.
 * @param string $title Titulo.
 * @param string $content Contenido.
 * @return int
 */
function liga_upsert_demo_post( $post_type, $slug, $title, $content = '' ) {
	$existing = get_page_by_path( $slug, OBJECT, $post_type );

	$postarr = array(
		'post_type'    => $post_type,
		'post_status'  => 'publish',
		'post_name'    => sanitize_title( $slug ),
		'post_title'   => wp_strip_all_tags( $title ),
		'post_content' => wp_kses_post( $content ),
	);

	if ( $existing instanceof WP_Post ) {
		$postarr['ID'] = (int) $existing->ID;
		return (int) wp_update_post( $postarr );
	}

	return (int) wp_insert_post( $postarr );
}

/**
 * Inserta dataset demo de liga.
 *
 * @param bool $force Fuerza recreacion.
 * @return array<string, mixed>
 */
function liga_seed_demo_data( $force = false ) {
	$already_seeded = (int) get_option( 'liga_demo_seeded', 0 );
	if ( $already_seeded > 0 && ! $force ) {
		return array(
			'status'  => 'skipped',
			'message' => __( 'Los datos demo ya fueron cargados.', 'liga-basket-chile' ),
		);
	}

	$divisions_config = array(
		array(
			'slug'      => 'adulto-masculino',
			'name'      => 'Adulto Masculino',
			'temporada' => liga_get_current_season_label(),
			'orden'     => 1,
			'activa'    => 1,
		),
		array(
			'slug'      => 'u17-mixto',
			'name'      => 'U17 Mixto',
			'temporada' => liga_get_current_season_label(),
			'orden'     => 2,
			'activa'    => 1,
		),
	);

	$division_ids = array();
	foreach ( $divisions_config as $division ) {
		$division_id = liga_upsert_demo_post( 'division', $division['slug'], $division['name'] );
		$division_ids[ $division['slug'] ] = $division_id;
		update_post_meta( $division_id, 'liga_nombre_division', $division['name'] );
		update_post_meta( $division_id, 'liga_temporada', $division['temporada'] );
		update_post_meta( $division_id, 'liga_orden_visual', $division['orden'] );
		update_post_meta( $division_id, 'liga_activa', $division['activa'] );
	}

	$teams_config = array(
		array( 'slug' => 'club-halcones', 'name' => 'Club Halcones', 'city' => 'Concepcion', 'fundacion' => 2006, 'division' => 'adulto-masculino', 'color' => '#2F57D7', 'coach' => 'Andres Herrera' ),
		array( 'slug' => 'deportivo-rio', 'name' => 'Deportivo Rio', 'city' => 'Talcahuano', 'fundacion' => 1998, 'division' => 'adulto-masculino', 'color' => '#FF9F1C', 'coach' => 'Jose Molina' ),
		array( 'slug' => 'academia-sur', 'name' => 'Academia Sur', 'city' => 'Chiguayante', 'fundacion' => 2010, 'division' => 'adulto-masculino', 'color' => '#22C55E', 'coach' => 'Bruno Cuevas' ),
		array( 'slug' => 'club-aurora', 'name' => 'Club Aurora', 'city' => 'San Pedro', 'fundacion' => 2013, 'division' => 'u17-mixto', 'color' => '#0EA5E9', 'coach' => 'Paula Olivares' ),
		array( 'slug' => 'escuela-andes', 'name' => 'Escuela Andes', 'city' => 'Hualpen', 'fundacion' => 2012, 'division' => 'u17-mixto', 'color' => '#EF4444', 'coach' => 'Marco Vidal' ),
		array( 'slug' => 'basket-concepcion', 'name' => 'Basket Concepcion', 'city' => 'Concepcion', 'fundacion' => 2004, 'division' => 'u17-mixto', 'color' => '#A855F7', 'coach' => 'Daniela Farias' ),
	);

	$team_ids = array();
	foreach ( $teams_config as $index => $team ) {
		$team_id = liga_upsert_demo_post( 'equipo', $team['slug'], $team['name'] );
		$team_ids[ $team['slug'] ] = $team_id;
		$team_division_id = (int) $division_ids[ $team['division'] ];
		$team_temporada   = liga_get_division_temporada_label( $team_division_id );
		if ( ! liga_is_valid_temporada_label( $team_temporada ) ) {
			$team_temporada = liga_get_current_season_label();
		}

		update_post_meta( $team_id, 'liga_nombre_equipo', $team['name'] );
		update_post_meta( $team_id, 'liga_ciudad', $team['city'] );
		update_post_meta( $team_id, 'liga_anio_fundacion', $team['fundacion'] );
		update_post_meta( $team_id, 'liga_division', $team_division_id );
		update_post_meta( $team_id, 'liga_temporada', $team_temporada );
		update_post_meta( $team_id, 'liga_color_principal', $team['color'] );
		update_post_meta( $team_id, 'liga_entrenador', $team['coach'] );
		update_post_meta( $team_id, 'liga_posicion_manual', $index + 1 );
		update_post_meta( $team_id, 'liga_activar_override', 0 );
		update_post_meta( $team_id, 'liga_equipo_competicion_key', liga_build_equipo_competicion_key( $team['name'], $team_division_id, $team_temporada ) );
	}

	$matches_config = array(
		array( 'slug' => 'j1-halcones-rio', 'local' => 'club-halcones', 'visita' => 'deportivo-rio', 'division' => 'adulto-masculino', 'fecha' => '2026-03-02', 'hora' => '19:30', 'cancha' => 'Gimnasio Municipal', 'estado' => 'jugado', 'pl' => 78, 'pv' => 65, 'inc' => 'ninguna' ),
		array( 'slug' => 'j1-academia-halcones', 'local' => 'academia-sur', 'visita' => 'club-halcones', 'division' => 'adulto-masculino', 'fecha' => '2026-03-05', 'hora' => '21:00', 'cancha' => 'Polideportivo Sur', 'estado' => 'jugado', 'pl' => 69, 'pv' => 72, 'inc' => 'ninguna' ),
		array( 'slug' => 'j1-rio-academia', 'local' => 'deportivo-rio', 'visita' => 'academia-sur', 'division' => 'adulto-masculino', 'fecha' => '2026-03-08', 'hora' => '18:00', 'cancha' => 'Arena Rio', 'estado' => 'jugado', 'pl' => 64, 'pv' => 58, 'inc' => 'ninguna' ),
		array( 'slug' => 'j2-halcones-academia', 'local' => 'club-halcones', 'visita' => 'academia-sur', 'division' => 'adulto-masculino', 'fecha' => '2026-03-15', 'hora' => '20:00', 'cancha' => 'Gimnasio Municipal', 'estado' => 'programado', 'pl' => 0, 'pv' => 0, 'inc' => 'ninguna' ),
		array( 'slug' => 'j2-rio-halcones', 'local' => 'deportivo-rio', 'visita' => 'club-halcones', 'division' => 'adulto-masculino', 'fecha' => '2026-03-21', 'hora' => '19:00', 'cancha' => 'Arena Rio', 'estado' => 'programado', 'pl' => 0, 'pv' => 0, 'inc' => 'ninguna' ),
		array( 'slug' => 'j2-academia-rio', 'local' => 'academia-sur', 'visita' => 'deportivo-rio', 'division' => 'adulto-masculino', 'fecha' => '2026-03-25', 'hora' => '20:30', 'cancha' => 'Polideportivo Sur', 'estado' => 'suspendido', 'pl' => 0, 'pv' => 0, 'inc' => 'ninguna' ),
		array( 'slug' => 'j1-aurora-andes', 'local' => 'club-aurora', 'visita' => 'escuela-andes', 'division' => 'u17-mixto', 'fecha' => '2026-03-01', 'hora' => '17:00', 'cancha' => 'Centro Deportivo Norte', 'estado' => 'jugado', 'pl' => 54, 'pv' => 49, 'inc' => 'ninguna' ),
		array( 'slug' => 'j1-concepcion-aurora', 'local' => 'basket-concepcion', 'visita' => 'club-aurora', 'division' => 'u17-mixto', 'fecha' => '2026-03-07', 'hora' => '16:30', 'cancha' => 'Coliseo Juvenil', 'estado' => 'jugado', 'pl' => 62, 'pv' => 62, 'inc' => 'visita_no_comparecio' ),
		array( 'slug' => 'j1-andes-concepcion', 'local' => 'escuela-andes', 'visita' => 'basket-concepcion', 'division' => 'u17-mixto', 'fecha' => '2026-03-10', 'hora' => '18:30', 'cancha' => 'Centro Deportivo Norte', 'estado' => 'jugado', 'pl' => 57, 'pv' => 60, 'inc' => 'ninguna' ),
		array( 'slug' => 'j2-aurora-concepcion', 'local' => 'club-aurora', 'visita' => 'basket-concepcion', 'division' => 'u17-mixto', 'fecha' => '2026-03-16', 'hora' => '17:30', 'cancha' => 'Centro Deportivo Norte', 'estado' => 'programado', 'pl' => 0, 'pv' => 0, 'inc' => 'ninguna' ),
		array( 'slug' => 'j2-andes-aurora', 'local' => 'escuela-andes', 'visita' => 'club-aurora', 'division' => 'u17-mixto', 'fecha' => '2026-03-22', 'hora' => '17:15', 'cancha' => 'Centro Deportivo Norte', 'estado' => 'programado', 'pl' => 0, 'pv' => 0, 'inc' => 'ninguna' ),
		array( 'slug' => 'j2-concepcion-andes', 'local' => 'basket-concepcion', 'visita' => 'escuela-andes', 'division' => 'u17-mixto', 'fecha' => '2026-03-29', 'hora' => '18:10', 'cancha' => 'Coliseo Juvenil', 'estado' => 'jugado', 'pl' => 58, 'pv' => 51, 'inc' => 'ninguna' ),
	);

	foreach ( $matches_config as $match ) {
		$match_title = get_the_title( $team_ids[ $match['local'] ] ) . ' vs ' . get_the_title( $team_ids[ $match['visita'] ] );
		$match_id    = liga_upsert_demo_post( 'partido', $match['slug'], $match_title );

		update_post_meta( $match_id, 'liga_equipo_local', (int) $team_ids[ $match['local'] ] );
		update_post_meta( $match_id, 'liga_equipo_visita', (int) $team_ids[ $match['visita'] ] );
		update_post_meta( $match_id, 'liga_division', (int) $division_ids[ $match['division'] ] );
		update_post_meta( $match_id, 'liga_temporada', liga_get_current_season_label() );
		update_post_meta( $match_id, 'liga_fecha_partido', $match['fecha'] );
		update_post_meta( $match_id, 'liga_hora_partido', $match['hora'] );
		update_post_meta( $match_id, 'liga_cancha', $match['cancha'] );
		update_post_meta( $match_id, 'liga_estado_partido', $match['estado'] );
		update_post_meta( $match_id, 'liga_puntos_local', $match['pl'] );
		update_post_meta( $match_id, 'liga_puntos_visita', $match['pv'] );
		update_post_meta( $match_id, 'liga_incomparecencia', $match['inc'] );
		update_post_meta( $match_id, 'liga_observaciones', '' );
	}

	$news_config = array(
		array( 'slug' => 'inicio-temporada', 'title' => 'Inicia la Temporada 2026 con record de equipos', 'content' => 'La liga arranca con una participacion historica de clubes del gran Concepcion.' ),
		array( 'slug' => 'nueva-cancha-oficial', 'title' => 'Se habilita nueva cancha oficial para semifinales', 'content' => 'La federacion confirma nuevo recinto para encuentros de alta convocatoria.' ),
		array( 'slug' => 'ranking-jornada-3', 'title' => 'Ranking actualizado tras la jornada 3', 'content' => 'La tabla presenta cambios en la parte alta en ambas divisiones.' ),
		array( 'slug' => 'charla-arbitros', 'title' => 'Capacitacion arbitral fortalece la competencia', 'content' => 'El panel tecnico reviso protocolos de juego y criterios disciplinarios.' ),
		array( 'slug' => 'sponsors-2026', 'title' => 'Nuevos sponsors se suman al proyecto 2026', 'content' => 'Marcas regionales y nacionales apoyaran el desarrollo de la liga.' ),
		array( 'slug' => 'final-four-u17', 'title' => 'Definido el formato Final Four para U17', 'content' => 'La division juvenil cerrara el torneo con formato concentrado.' ),
	);

	foreach ( $news_config as $news ) {
		liga_upsert_demo_post( 'post', $news['slug'], $news['title'], $news['content'] );
	}

	update_option(
		'liga_sponsors_demo',
		array(
			'BioSport',
			'Concepcion Motors',
			'Andes Nutrition',
			'Clinica Sur',
			'Logistica 360',
			'Energy Court',
		)
	);

	update_option( 'liga_demo_seeded', 1 );
	if ( function_exists( 'liga_flush_table_cache' ) ) {
		liga_flush_table_cache();
	}

	return array(
		'status'  => 'ok',
		'message' => __( 'Datos demo cargados correctamente.', 'liga-basket-chile' ),
	);
}

/**
 * Retorna los links referenciales para poblar el menu superior.
 *
 * @return array<int, array<string, string>>
 */
function liga_get_topbar_menu_demo_items() {
	return array(
		array(
			'title' => 'Temporada 2026',
			'url'   => '#',
			'icon'  => 'calendar_month',
		),
		array(
			'title' => 'Fixture',
			'url'   => home_url( '/partidos/' ),
			'icon'  => 'event',
		),
		array(
			'title' => 'Tabla de posiciones',
			'url'   => home_url( '/tabla/' ),
			'icon'  => 'leaderboard',
		),
		array(
			'title' => 'Últimos resultados',
			'url'   => home_url( '/resultados/' ),
			'icon'  => 'scoreboard',
		),
		array(
			'title' => 'Contacto',
			'url'   => home_url( '/contacto/' ),
			'icon'  => 'mail',
		),
		array(
			'title' => 'Instagram',
			'url'   => 'https://instagram.com/',
			'icon'  => 'photo_camera',
		),
		array(
			'title' => 'Facebook',
			'url'   => 'https://facebook.com/',
			'icon'  => 'facebook',
		),
		array(
			'title' => 'YouTube',
			'url'   => 'https://youtube.com/',
			'icon'  => 'smart_display',
		),
	);
}

/**
 * Retorna links referenciales para poblar el menu principal.
 *
 * @return array<int, array<string, string>>
 */
function liga_get_main_menu_demo_items() {
	return array(
		array(
			'title' => 'Inicio',
			'url'   => home_url( '/' ),
		),
		array(
			'title' => 'Tabla de Posiciones',
			'url'   => home_url( '/tabla/' ),
		),
		array(
			'title' => 'Partidos',
			'url'   => home_url( '/partidos/' ),
		),
		array(
			'title' => 'Equipos',
			'url'   => home_url( '/equipos/' ),
		),
		array(
			'title' => 'Noticias',
			'url'   => home_url( '/noticias/' ),
		),
		array(
			'title' => 'La Liga',
			'url'   => home_url( '/la-liga/' ),
		),
		array(
			'title' => 'Contacto',
			'url'   => home_url( '/contacto/' ),
		),
	);
}

/**
 * Indica si existen banners principales (en cualquier estado relevante).
 *
 * @return bool
 */
function liga_has_any_banner_principal_posts() {
	if ( ! post_type_exists( 'banner-principal' ) ) {
		return false;
	}

	$existing = get_posts(
		array(
			'post_type'              => 'banner-principal',
			'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private', 'trash' ),
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	return ! empty( $existing );
}

/**
 * Crea banners demo iniciales para orientar al usuario final.
 *
 * Reglas:
 * - Solo se ejecuta una vez.
 * - Si ya existen banners (reales o demo), no crea nada.
 * - No sobrescribe contenido existente.
 *
 * @return void
 */
function liga_seed_banner_principal_demo_if_empty() {
	$already_seeded = (int) get_option( 'liga_banner_principal_demo_seeded', 0 );
	if ( $already_seeded > 0 ) {
		return;
	}

	if ( liga_has_any_banner_principal_posts() ) {
		update_option( 'liga_banner_principal_demo_seeded', 1 );
		return;
	}

	$demo_banners = array(
		array(
			'slug'              => 'banner-principal-demo-1',
			'eyebrow'           => 'Temporada 2026',
			'title'             => 'Vive la pasion del basquetbol en Concepcion',
			'description'       => 'Resultados, programacion, equipos y tabla de posiciones en un solo lugar para seguir cada fecha de la liga.',
			'cta_primary_text'  => 'Ver partidos',
			'cta_primary_url'   => home_url( '/partidos/' ),
			'cta_secondary_text'=> 'Tabla de posiciones',
			'cta_secondary_url' => home_url( '/tabla/' ),
			'order'             => 1,
		),
		array(
			'slug'              => 'banner-principal-demo-2',
			'eyebrow'           => 'Competencia oficial',
			'title'             => 'Equipos, talento y comunidad en la cancha',
			'description'       => 'Conoce a los clubes participantes, revisa sus proximos encuentros y sigue el rendimiento de cada division.',
			'cta_primary_text'  => 'Ver equipos',
			'cta_primary_url'   => home_url( '/equipos/' ),
			'cta_secondary_text'=> 'Ultimos resultados',
			'cta_secondary_url' => home_url( '/resultados/' ),
			'order'             => 2,
		),
		array(
			'slug'              => 'banner-principal-demo-3',
			'eyebrow'           => 'Liga Basket Chile',
			'title'             => 'Toda la informacion del torneo actualizada',
			'description'       => 'Noticias, fixture, resultados y posiciones disponibles para jugadores, clubes, familias y publico general.',
			'cta_primary_text'  => 'Noticias',
			'cta_primary_url'   => home_url( '/noticias/' ),
			'cta_secondary_text'=> 'Conoce la liga',
			'cta_secondary_url' => home_url( '/la-liga/' ),
			'order'             => 3,
		),
	);

	foreach ( $demo_banners as $banner ) {
		$banner_id = liga_upsert_demo_post( 'banner-principal', $banner['slug'], $banner['title'] );

		update_post_meta( $banner_id, 'liga_banner_eyebrow', $banner['eyebrow'] );
		update_post_meta( $banner_id, 'liga_banner_titulo', $banner['title'] );
		update_post_meta( $banner_id, 'liga_banner_bajada', $banner['description'] );
		update_post_meta( $banner_id, 'liga_banner_cta_principal_texto', $banner['cta_primary_text'] );
		update_post_meta( $banner_id, 'liga_banner_cta_principal_url', esc_url_raw( $banner['cta_primary_url'] ) );
		update_post_meta( $banner_id, 'liga_banner_cta_secundario_texto', $banner['cta_secondary_text'] );
		update_post_meta( $banner_id, 'liga_banner_cta_secundario_url', esc_url_raw( $banner['cta_secondary_url'] ) );
		update_post_meta( $banner_id, '_liga_banner_image_id', 0 );
		update_post_meta( $banner_id, 'liga_banner_imagen_id', 0 );
		update_post_meta( $banner_id, 'liga_banner_activo', 1 );
		update_post_meta( $banner_id, 'liga_banner_orden_visual', (int) $banner['order'] );
		update_post_meta( $banner_id, 'liga_banner_alineacion_texto', 'izquierda' );
		update_post_meta( $banner_id, 'liga_banner_altura', 'normal' );
		update_post_meta( $banner_id, 'liga_banner_overlay', 1 );
		update_post_meta( $banner_id, 'liga_banner_fondo_degradado', 1 );
		update_post_meta( $banner_id, 'liga_banner_autoplay', 1 );
	}

	update_option( 'liga_banner_principal_demo_seeded', 1 );
	if ( function_exists( 'liga_flush_home_banner_cache' ) ) {
		liga_flush_home_banner_cache();
	}
}
add_action( 'init', 'liga_seed_banner_principal_demo_if_empty', 35 );

/**
 * Normaliza una URL para comparar items del menu sin duplicar.
 *
 * @param string $url URL a normalizar.
 * @return string
 */
function liga_normalize_menu_item_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url || '#' === $url ) {
		return '#';
	}

	if ( 0 === strpos( $url, '/' ) ) {
		$url = home_url( $url );
	}

	return untrailingslashit( strtolower( $url ) );
}

/**
 * Agrega links demo a un menu sin repetir items existentes.
 *
 * @param int $menu_id ID del menu.
 * @return void
 */
function liga_populate_topbar_demo_menu_items( $menu_id ) {
	$menu_id        = (int) $menu_id;
	$existing_items = wp_get_nav_menu_items( $menu_id );
	$existing_keys  = array();

	if ( is_array( $existing_items ) ) {
		foreach ( $existing_items as $existing_item ) {
			$item_title = isset( $existing_item->title ) ? (string) $existing_item->title : '';
			$item_url   = isset( $existing_item->url ) ? (string) $existing_item->url : '';
			$item_key   = sanitize_title( $item_title ) . '|' . liga_normalize_menu_item_url( $item_url );
			$existing_keys[ $item_key ] = true;
		}
	}

	foreach ( liga_get_topbar_menu_demo_items() as $demo_item ) {
		$item_title = isset( $demo_item['title'] ) ? (string) $demo_item['title'] : '';
		$item_url   = isset( $demo_item['url'] ) ? (string) $demo_item['url'] : '';
		$item_icon  = isset( $demo_item['icon'] ) ? liga_sanitize_topbar_icon( (string) $demo_item['icon'] ) : '';
		$item_key   = sanitize_title( $item_title ) . '|' . liga_normalize_menu_item_url( $item_url );
		$item_url_safe = '#' === trim( $item_url ) ? '#' : esc_url_raw( $item_url );

		if ( isset( $existing_keys[ $item_key ] ) ) {
			continue;
		}

		$menu_item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => wp_strip_all_tags( $item_title ),
				'menu-item-url'    => $item_url_safe,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			)
		);

		$menu_item_id = (int) $menu_item_id;
		if ( $menu_item_id > 0 && '' !== $item_icon ) {
			update_post_meta( $menu_item_id, LIGA_TOPBAR_ICON_META_KEY, $item_icon );
		}
	}
}

/**
 * Agrega links demo al menu principal sin repetir items existentes.
 *
 * @param int $menu_id ID del menu.
 * @return void
 */
function liga_populate_main_menu_demo_items( $menu_id ) {
	$menu_id        = (int) $menu_id;
	$existing_items = wp_get_nav_menu_items( $menu_id );
	$existing_keys  = array();

	if ( is_array( $existing_items ) ) {
		foreach ( $existing_items as $existing_item ) {
			$item_title = isset( $existing_item->title ) ? (string) $existing_item->title : '';
			$item_url   = isset( $existing_item->url ) ? (string) $existing_item->url : '';
			$item_key   = sanitize_title( $item_title ) . '|' . liga_normalize_menu_item_url( $item_url );
			$existing_keys[ $item_key ] = true;
		}
	}

	foreach ( liga_get_main_menu_demo_items() as $demo_item ) {
		$item_title = isset( $demo_item['title'] ) ? (string) $demo_item['title'] : '';
		$item_url   = isset( $demo_item['url'] ) ? (string) $demo_item['url'] : '';
		$item_key   = sanitize_title( $item_title ) . '|' . liga_normalize_menu_item_url( $item_url );
		$item_url_safe = '#' === trim( $item_url ) ? '#' : esc_url_raw( $item_url );

		if ( isset( $existing_keys[ $item_key ] ) ) {
			continue;
		}

		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => wp_strip_all_tags( $item_title ),
				'menu-item-url'    => $item_url_safe,
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			)
		);
	}
}

/**
 * Garantiza estado minimo del menu principal demo sin sobrescribir contenido real.
 *
 * @return bool
 */
function liga_ensure_main_menu_demo_state() {
	$locations        = (array) get_theme_mod( 'nav_menu_locations', array() );
	$assigned_menu_id = isset( $locations['menu_principal'] ) ? (int) $locations['menu_principal'] : 0;

	if ( $assigned_menu_id > 0 ) {
		$assigned_menu_obj = wp_get_nav_menu_object( $assigned_menu_id );
		if ( $assigned_menu_obj instanceof WP_Term ) {
			$assigned_items = wp_get_nav_menu_items( $assigned_menu_id );
			if ( empty( $assigned_items ) || ! is_array( $assigned_items ) ) {
				liga_populate_main_menu_demo_items( $assigned_menu_id );
			}
			return true;
		}

		unset( $locations['menu_principal'] );
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	$legacy_primary_id = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	if ( $legacy_primary_id > 0 ) {
		$legacy_menu_obj = wp_get_nav_menu_object( $legacy_primary_id );
		if ( $legacy_menu_obj instanceof WP_Term ) {
			$legacy_items = wp_get_nav_menu_items( $legacy_primary_id );
			if ( empty( $legacy_items ) || ! is_array( $legacy_items ) ) {
				liga_populate_main_menu_demo_items( $legacy_primary_id );
			}

			$locations['menu_principal'] = $legacy_primary_id;
			set_theme_mod( 'nav_menu_locations', $locations );
			return true;
		}
	}

	$menu_name = 'Menú Principal';
	$menu_obj  = wp_get_nav_menu_object( $menu_name );
	$menu_id   = ( $menu_obj instanceof WP_Term ) ? (int) $menu_obj->term_id : 0;

	if ( $menu_id <= 0 ) {
		$created_menu = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $created_menu ) ) {
			return false;
		}
		$menu_id = (int) $created_menu;
	}

	if ( $menu_id <= 0 ) {
		return false;
	}

	$existing_items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $existing_items ) || ! is_array( $existing_items ) ) {
		liga_populate_main_menu_demo_items( $menu_id );
	}

	$locations['menu_principal'] = $menu_id;
	if ( empty( $locations['primary'] ) ) {
		$locations['primary'] = $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );

	return true;
}

/**
 * Inicializa menu principal demo una sola vez al activar tema.
 *
 * @return void
 */
function liga_seed_main_menu_demo_once() {
	if ( is_admin() && is_user_logged_in() && ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$already_seeded = (int) get_option( 'liga_main_menu_demo_seeded', 0 );
	if ( $already_seeded > 0 ) {
		return;
	}

	if ( liga_ensure_main_menu_demo_state() ) {
		update_option( 'liga_main_menu_demo_seeded', 1 );
	}
}
add_action( 'after_switch_theme', 'liga_seed_main_menu_demo_once' );

/**
 * Backfill one-shot para instalaciones existentes del menu principal.
 *
 * @return void
 */
function liga_seed_main_menu_demo_admin_backfill() {
	if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$already_backfilled = (int) get_option( 'liga_main_menu_demo_backfill_v1', 0 );
	if ( $already_backfilled > 0 ) {
		return;
	}

	if ( liga_ensure_main_menu_demo_state() ) {
		update_option( 'liga_main_menu_demo_seeded', 1 );
	}

	update_option( 'liga_main_menu_demo_backfill_v1', 1 );
}
add_action( 'admin_init', 'liga_seed_main_menu_demo_admin_backfill' );

/**
 * Garantiza estado minimo del menu topbar demo sin sobrescribir contenido real.
 *
 * - Si la ubicacion tiene menu valido con items, no altera items.
 * - Si la ubicacion tiene menu valido vacio, agrega items demo.
 * - Si la ubicacion apunta a un menu invalido, limpia y recrea asignacion.
 * - Si no hay ubicacion asignada, crea/usa "Menú Topbar" y lo asigna.
 *
 * @return bool True si pudo garantizar el estado; false si no pudo crear menu.
 */
function liga_ensure_topbar_menu_demo_state() {
	$locations        = (array) get_theme_mod( 'nav_menu_locations', array() );
	$assigned_menu_id = isset( $locations['liga_topbar_menu'] ) ? (int) $locations['liga_topbar_menu'] : 0;

	if ( $assigned_menu_id > 0 ) {
		$assigned_menu_obj = wp_get_nav_menu_object( $assigned_menu_id );
		if ( $assigned_menu_obj instanceof WP_Term ) {
			$assigned_items = wp_get_nav_menu_items( $assigned_menu_id );
			if ( empty( $assigned_items ) || ! is_array( $assigned_items ) ) {
				liga_populate_topbar_demo_menu_items( $assigned_menu_id );
			}

			return true;
		}

		// Limpia asignacion invalida (menu eliminado) para permitir recreacion segura.
		unset( $locations['liga_topbar_menu'] );
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	if ( ! empty( $locations['liga_topbar_menu'] ) ) {
		return true;
	}

	$menu_name = 'Menú Topbar';
	$menu_obj  = wp_get_nav_menu_object( $menu_name );
	$menu_id   = ( $menu_obj instanceof WP_Term ) ? (int) $menu_obj->term_id : 0;

	if ( $menu_id <= 0 ) {
		$created_menu = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $created_menu ) ) {
			return false;
		}
		$menu_id = (int) $created_menu;
	}

	if ( $menu_id <= 0 ) {
		return false;
	}

	$existing_items = wp_get_nav_menu_items( $menu_id );
	if ( empty( $existing_items ) || ! is_array( $existing_items ) ) {
		liga_populate_topbar_demo_menu_items( $menu_id );
	}

	$locations['liga_topbar_menu'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	return true;
}

/**
 * Crea y asigna menu demo para topbar una sola vez de forma segura.
 *
 * Reglas:
 * - Si la ubicacion ya tiene menu asignado, no modifica nada.
 * - Si existe un menu "Menú Topbar" con items, lo respeta y solo lo asigna.
 * - Si no existe, lo crea y agrega links referenciales.
 *
 * @return void
 */
function liga_seed_topbar_menu_demo_once() {
	if ( is_admin() && is_user_logged_in() && ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$already_seeded = (int) get_option( 'liga_topbar_menu_demo_seeded', 0 );
	if ( $already_seeded > 0 ) {
		return;
	}

	if ( liga_ensure_topbar_menu_demo_state() ) {
		update_option( 'liga_topbar_menu_demo_seeded', 1 );
	}
}
add_action( 'after_switch_theme', 'liga_seed_topbar_menu_demo_once' );

/**
 * Backfill one-shot para instalaciones existentes:
 * pobla topbar con contenido demo solo si aun no se inicializo.
 *
 * @return void
 */
function liga_seed_topbar_menu_demo_admin_backfill() {
	if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$already_backfilled = (int) get_option( 'liga_topbar_menu_demo_backfill_v2', 0 );
	if ( $already_backfilled > 0 ) {
		return;
	}

	if ( liga_ensure_topbar_menu_demo_state() ) {
		update_option( 'liga_topbar_menu_demo_seeded', 1 );
	}

	update_option( 'liga_topbar_menu_demo_backfill_v2', 1 );
}
add_action( 'admin_init', 'liga_seed_topbar_menu_demo_admin_backfill' );

/**
 * Procesa accion manual para poblar menu principal demo.
 *
 * @return void
 */
function liga_handle_main_menu_seed_request() {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_GET['liga_main_menu_seed'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'liga_main_menu_seed' ) ) {
		liga_add_admin_alert( 'error', __( 'Nonce invalido al poblar el menú principal.', 'liga-basket-chile' ) );
		return;
	}

	$ok = liga_ensure_main_menu_demo_state();
	if ( $ok ) {
		update_option( 'liga_main_menu_demo_seeded', 1 );
		update_option( 'liga_main_menu_demo_backfill_v1', 1 );
		liga_add_admin_alert( 'success', __( 'Menú Principal poblado correctamente.', 'liga-basket-chile' ) );
	} else {
		liga_add_admin_alert( 'warning', __( 'No fue posible poblar el Menú Principal.', 'liga-basket-chile' ) );
	}

	wp_safe_redirect( remove_query_arg( array( 'liga_main_menu_seed', '_wpnonce' ) ) );
	exit;
}
add_action( 'admin_init', 'liga_handle_main_menu_seed_request' );

/**
 * Procesa accion manual para poblar menu topbar demo.
 *
 * @return void
 */
function liga_handle_topbar_seed_request() {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_GET['liga_topbar_seed'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'liga_topbar_seed' ) ) {
		liga_add_admin_alert( 'error', __( 'Nonce invalido al poblar el menú topbar.', 'liga-basket-chile' ) );
		return;
	}

	$ok = liga_ensure_topbar_menu_demo_state();
	if ( $ok ) {
		update_option( 'liga_topbar_menu_demo_seeded', 1 );
		update_option( 'liga_topbar_menu_demo_backfill_v2', 1 );
		liga_add_admin_alert( 'success', __( 'Menú Topbar poblado correctamente.', 'liga-basket-chile' ) );
	} else {
		liga_add_admin_alert( 'warning', __( 'No fue posible poblar el Menú Topbar.', 'liga-basket-chile' ) );
	}

	wp_safe_redirect( remove_query_arg( array( 'liga_topbar_seed', '_wpnonce' ) ) );
	exit;
}
add_action( 'admin_init', 'liga_handle_topbar_seed_request' );

/**
 * Procesa accion manual para carga demo desde admin.
 *
 * @return void
 */
function liga_handle_demo_seed_request() {
	if ( ! is_admin() ) {
		return;
	}

	if ( ! isset( $_GET['liga_demo_seed'] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'liga_demo_seed' ) ) {
		liga_add_admin_alert( 'error', __( 'Nonce invalido al cargar demo.', 'liga-basket-chile' ) );
		return;
	}

	$force  = isset( $_GET['force'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['force'] ) );
	$result = liga_seed_demo_data( $force );

	$type = 'ok' === $result['status'] ? 'success' : 'warning';
	liga_add_admin_alert( $type, $result['message'] );

	wp_safe_redirect( remove_query_arg( array( 'liga_demo_seed', '_wpnonce', 'force' ) ) );
	exit;
}
add_action( 'admin_init', 'liga_handle_demo_seed_request' );
