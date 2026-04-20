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
