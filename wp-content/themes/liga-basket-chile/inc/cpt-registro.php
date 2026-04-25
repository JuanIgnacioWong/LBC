<?php
/**
 * Registro de CPT (fase 2).
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra los CPT del ecosistema deportivo.
 *
 * @return void
 */
function liga_register_cpts() {
	register_post_type(
		'equipo',
		array(
			'labels'       => array(
				'name'               => __( 'Equipos', 'liga-basket-chile' ),
				'singular_name'      => __( 'Equipo', 'liga-basket-chile' ),
				'add_new_item'       => __( 'Agregar equipo', 'liga-basket-chile' ),
				'edit_item'          => __( 'Editar equipo', 'liga-basket-chile' ),
				'new_item'           => __( 'Nuevo equipo', 'liga-basket-chile' ),
				'view_item'          => __( 'Ver equipo', 'liga-basket-chile' ),
				'search_items'       => __( 'Buscar equipos', 'liga-basket-chile' ),
				'not_found'          => __( 'No se encontraron equipos', 'liga-basket-chile' ),
				'not_found_in_trash' => __( 'No hay equipos en papelera', 'liga-basket-chile' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-groups',
			'rewrite'      => array( 'slug' => 'equipo' ),
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		)
	);

	register_post_type(
		'partido',
		array(
			'labels'       => array(
				'name'               => __( 'Partidos', 'liga-basket-chile' ),
				'singular_name'      => __( 'Partido', 'liga-basket-chile' ),
				'add_new_item'       => __( 'Agregar partido', 'liga-basket-chile' ),
				'edit_item'          => __( 'Editar partido', 'liga-basket-chile' ),
				'new_item'           => __( 'Nuevo partido', 'liga-basket-chile' ),
				'view_item'          => __( 'Ver partido', 'liga-basket-chile' ),
				'search_items'       => __( 'Buscar partidos', 'liga-basket-chile' ),
				'not_found'          => __( 'No se encontraron partidos', 'liga-basket-chile' ),
				'not_found_in_trash' => __( 'No hay partidos en papelera', 'liga-basket-chile' ),
			),
			'public'       => true,
			'has_archive'  => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-calendar-alt',
			'rewrite'      => array( 'slug' => 'partido' ),
			'supports'     => array( 'title', 'editor' ),
		)
	);

	register_post_type(
		'division',
		array(
			'labels'       => array(
				'name'               => __( 'Divisiones', 'liga-basket-chile' ),
				'singular_name'      => __( 'Division', 'liga-basket-chile' ),
				'add_new_item'       => __( 'Agregar division', 'liga-basket-chile' ),
				'edit_item'          => __( 'Editar division', 'liga-basket-chile' ),
				'new_item'           => __( 'Nueva division', 'liga-basket-chile' ),
				'view_item'          => __( 'Ver division', 'liga-basket-chile' ),
				'search_items'       => __( 'Buscar divisiones', 'liga-basket-chile' ),
				'not_found'          => __( 'No se encontraron divisiones', 'liga-basket-chile' ),
				'not_found_in_trash' => __( 'No hay divisiones en papelera', 'liga-basket-chile' ),
			),
			'public'       => true,
			'has_archive'  => false,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-networking',
			'rewrite'      => array( 'slug' => 'division' ),
			'supports'     => array( 'title' ),
		)
	);

	register_post_type(
		'banner-principal',
		array(
			'labels'              => array(
				'name'               => __( 'Banners Principales', 'liga-basket-chile' ),
				'singular_name'      => __( 'Banner Principal', 'liga-basket-chile' ),
				'add_new_item'       => __( 'Agregar banner principal', 'liga-basket-chile' ),
				'edit_item'          => __( 'Editar banner principal', 'liga-basket-chile' ),
				'new_item'           => __( 'Nuevo banner principal', 'liga-basket-chile' ),
				'view_item'          => __( 'Ver banner principal', 'liga-basket-chile' ),
				'search_items'       => __( 'Buscar banners principales', 'liga-basket-chile' ),
				'not_found'          => __( 'No se encontraron banners principales', 'liga-basket-chile' ),
				'not_found_in_trash' => __( 'No hay banners principales en papelera', 'liga-basket-chile' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'menu_icon'           => 'dashicons-images-alt2',
			'supports'            => array( 'title' ),
		)
	);
}
add_action( 'init', 'liga_register_cpts' );

/**
 * Mejora el post type nativo para noticias.
 *
 * @return void
 */
function liga_enhance_native_news_post_type() {
	add_post_type_support( 'post', 'thumbnail' );
	add_post_type_support( 'post', 'excerpt' );
}
add_action( 'init', 'liga_enhance_native_news_post_type', 20 );

/**
 * Columna de logo y metadatos en admin de equipos.
 *
 * @param array<string, string> $columns Columnas actuales.
 * @return array<string, string>
 */
function liga_equipo_admin_columns( $columns ) {
	$new_columns = array();

	foreach ( $columns as $key => $value ) {
		if ( 'title' === $key ) {
			$new_columns['liga_logo']     = __( 'Logo', 'liga-basket-chile' );
			$new_columns['liga_division'] = __( 'Division', 'liga-basket-chile' );
			$new_columns['liga_temporada'] = __( 'Temporada', 'liga-basket-chile' );
			$new_columns['liga_ciudad']   = __( 'Ciudad', 'liga-basket-chile' );
		}
		$new_columns[ $key ] = $value;
	}

	return $new_columns;
}
add_filter( 'manage_equipo_posts_columns', 'liga_equipo_admin_columns' );

/**
 * Renderiza columnas custom de equipos.
 *
 * @param string $column Nombre de columna.
 * @param int    $post_id ID del post.
 * @return void
 */
function liga_render_equipo_admin_columns( $column, $post_id ) {
	if ( 'liga_logo' === $column ) {
		$logo_id = (int) get_post_meta( $post_id, 'liga_logo_equipo', true );
		if ( $logo_id > 0 ) {
			echo wp_kses_post( wp_get_attachment_image( $logo_id, array( 36, 36 ) ) );
		} else {
			echo '<span aria-hidden="true">-</span>';
		}
	}

	if ( 'liga_division' === $column ) {
		$division_id = (int) get_post_meta( $post_id, 'liga_division', true );
		echo $division_id ? esc_html( get_the_title( $division_id ) ) : '<span aria-hidden="true">-</span>';
	}

	if ( 'liga_temporada' === $column ) {
		$temporada = liga_get_equipo_temporada_label( (int) $post_id );
		echo '' !== $temporada ? esc_html( $temporada ) : '<span aria-hidden="true">-</span>';
	}

	if ( 'liga_ciudad' === $column ) {
		$ciudad = get_post_meta( $post_id, 'liga_ciudad', true );
		echo $ciudad ? esc_html( $ciudad ) : '<span aria-hidden="true">-</span>';
	}
}
add_action( 'manage_equipo_posts_custom_column', 'liga_render_equipo_admin_columns', 10, 2 );

/**
 * Agrega filtro por division en admin para equipos y partidos.
 *
 * @param string $post_type Post type actual.
 * @return void
 */
function liga_admin_filter_by_division( $post_type ) {
	if ( ! in_array( $post_type, array( 'equipo', 'partido' ), true ) ) {
		return;
	}

	$selected_division = isset( $_GET['liga_division_filter'] ) ? absint( wp_unslash( $_GET['liga_division_filter'] ) ) : 0;
	$divisions         = get_posts(
		array(
			'post_type'      => 'division',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	echo '<label class="screen-reader-text" for="liga_division_filter">' . esc_html__( 'Filtrar por division', 'liga-basket-chile' ) . '</label>';
	echo '<select id="liga_division_filter" name="liga_division_filter">';
	echo '<option value="0">' . esc_html__( 'Todas las divisiones', 'liga-basket-chile' ) . '</option>';

	foreach ( $divisions as $division ) {
		echo '<option value="' . esc_attr( (string) $division->ID ) . '" ' . selected( $selected_division, $division->ID, false ) . '>' . esc_html( $division->post_title ) . '</option>';
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'liga_admin_filter_by_division' );

/**
 * Aplica filtro por division a la consulta admin.
 *
 * @param WP_Query $query Consulta.
 * @return void
 */
function liga_apply_admin_division_filter( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( ! in_array( $post_type, array( 'equipo', 'partido' ), true ) ) {
		return;
	}

	$division_filter = isset( $_GET['liga_division_filter'] ) ? absint( wp_unslash( $_GET['liga_division_filter'] ) ) : 0;
	if ( $division_filter <= 0 ) {
		return;
	}

	$meta_query = (array) $query->get( 'meta_query' );
	$meta_query[] = array(
		'key'   => 'liga_division',
		'value' => $division_filter,
		'type'  => 'NUMERIC',
	);

	$query->set( 'meta_query', $meta_query );
}
add_action( 'pre_get_posts', 'liga_apply_admin_division_filter' );

/**
 * Ordena partidos por fecha y hora en admin.
 *
 * @param WP_Query $query Consulta.
 * @return void
 */
function liga_sort_partidos_admin_by_date( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'partido' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( $query->get( 'orderby' ) ) {
		return;
	}

	$query->set( 'meta_key', 'liga_fecha_partido' );
	$query->set( 'orderby', 'meta_value' );
	$query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'liga_sort_partidos_admin_by_date' );
