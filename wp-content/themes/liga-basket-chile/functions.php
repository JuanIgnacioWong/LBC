<?php
/**
 * Core functions for Liga de Basquetbol Concepcion theme.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset version helper based on filemtime.
 *
 * @param string $relative_path Relative path from theme root.
 * @return string
 */
function liga_asset_version( $relative_path ) {
	$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );
	return file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );
}

/**
 * Theme setup.
 *
 * @return void
 */
function liga_theme_setup() {
	load_theme_textdomain( 'liga-basket-chile', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 180,
			'width'       => 180,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	register_nav_menus(
		array(
			'primary'   => __( 'Menu principal', 'liga-basket-chile' ),
			'menu_principal' => __( 'Menú Principal', 'liga-basket-chile' ),
			'liga_topbar_menu' => __( 'Menú Topbar', 'liga-basket-chile' ),
			'footer'    => __( 'Menu footer', 'liga-basket-chile' ),
			'secondary' => __( 'Menu La Liga', 'liga-basket-chile' ),
			'legal'     => __( 'Menu legal', 'liga-basket-chile' ),
		)
	);
}
add_action( 'after_setup_theme', 'liga_theme_setup' );

/**
 * Migra asignacion legacy `primary` hacia `menu_principal` una sola vez.
 *
 * @return void
 */
function liga_migrate_primary_menu_location_once() {
	if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$migrated = (int) get_option( 'liga_menu_principal_migrated', 0 );
	if ( $migrated > 0 ) {
		return;
	}

	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	if ( empty( $locations['menu_principal'] ) && ! empty( $locations['primary'] ) ) {
		$locations['menu_principal'] = (int) $locations['primary'];
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	update_option( 'liga_menu_principal_migrated', 1 );
}
add_action( 'admin_init', 'liga_migrate_primary_menu_location_once' );

/**
 * Enqueue frontend assets.
 *
 * @return void
 */
function liga_enqueue_assets() {
	wp_enqueue_style(
		'liga-google-fonts',
		'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'liga-main',
		get_template_directory_uri() . '/assets/css/main.css',
		array( 'liga-google-fonts' ),
		liga_asset_version( 'assets/css/main.css' )
	);

	wp_enqueue_style(
		'liga-responsive',
		get_template_directory_uri() . '/assets/css/responsive.css',
		array( 'liga-main' ),
		liga_asset_version( 'assets/css/responsive.css' )
	);

	wp_enqueue_script(
		'liga-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		liga_asset_version( 'assets/js/main.js' ),
		true
	);
	wp_script_add_data( 'liga-main', 'defer', true );
}
add_action( 'wp_enqueue_scripts', 'liga_enqueue_assets' );

/**
 * Registra y carga el controlador por defecto del pop-up Swish.
 *
 * @return void
 */
function liga_enqueue_swish_popup_script() {
	wp_register_script(
		'swish-popup',
		get_template_directory_uri() . '/assets/js/popup.js',
		array(),
		'1.0.0',
		true
	);

	if ( function_exists( 'liga_should_enqueue_default_popup_script' ) && liga_should_enqueue_default_popup_script() ) {
		wp_enqueue_script( 'swish-popup' );
	}
}
add_action( 'wp_enqueue_scripts', 'liga_enqueue_swish_popup_script' );

/**
 * SVG placeholder URI helper for fallback images.
 *
 * @param string $label Text label.
 * @param int    $width Image width.
 * @param int    $height Image height.
 * @param string $bg Background hex without #.
 * @param string $fg Foreground hex without #.
 * @return string
 */
function liga_svg_placeholder( $label, $width = 1200, $height = 675, $bg = '0b2a66', $fg = 'ffffff' ) {
	$clean_label = sanitize_text_field( $label );
	$svg         = sprintf(
		"<svg xmlns='http://www.w3.org/2000/svg' width='%d' height='%d' viewBox='0 0 %d %d'><rect width='100%%' height='100%%' fill='#%s'/><text x='50%%' y='50%%' dominant-baseline='middle' text-anchor='middle' font-family='Arial, sans-serif' font-size='%d' fill='#%s'>%s</text></svg>",
		(int) $width,
		(int) $height,
		(int) $width,
		(int) $height,
		sanitize_hex_color_no_hash( $bg ) ? $bg : '0b2a66',
		max( 20, (int) floor( $width / 18 ) ),
		sanitize_hex_color_no_hash( $fg ) ? $fg : 'ffffff',
		esc_html( $clean_label )
	);

	return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode( $svg );
}

/**
 * Escape image sources including inline data placeholders.
 *
 * @param string $src Image source.
 * @return string
 */
function liga_escape_image_src( $src ) {
	return esc_url( $src, array( 'http', 'https', 'data' ) );
}

/**
 * Fallback menu for header.
 *
 * @return void
 */
function liga_primary_menu_fallback() {
	echo '<ul class="liga-header-nav-list">';
	echo '<li class="liga-header-nav-item"><a class="liga-header-nav-link" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Inicio', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-header-nav-item"><a class="liga-header-nav-link" href="' . esc_url( home_url( '/tabla' ) ) . '">' . esc_html__( 'Tabla de Posiciones', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-header-nav-item"><a class="liga-header-nav-link" href="' . esc_url( home_url( '/partidos' ) ) . '">' . esc_html__( 'Partidos', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-header-nav-item"><a class="liga-header-nav-link" href="' . esc_url( home_url( '/equipos' ) ) . '">' . esc_html__( 'Equipos', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-header-nav-item"><a class="liga-header-nav-link" href="' . esc_url( home_url( '/noticias' ) ) . '">' . esc_html__( 'Noticias', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-header-nav-item"><a class="liga-header-nav-link" href="' . esc_url( home_url( '/la-liga' ) ) . '">' . esc_html__( 'La Liga', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-header-nav-item"><a class="liga-header-nav-link" href="' . esc_url( home_url( '/contacto' ) ) . '">' . esc_html__( 'Contacto', 'liga-basket-chile' ) . '</a></li>';
	echo '</ul>';
}

/**
 * Fallback menu for topbar navigation.
 *
 * @return void
 */
function liga_topbar_menu_fallback() {
	echo '<ul class="liga-topbar-social-list liga-topbar__menu liga-topbar__menu--fallback"></ul>';
}

/**
 * Fallback menu for footer navigation.
 *
 * @return void
 */
function liga_footer_menu_fallback() {
	echo '<ul class="liga-footer-nav-list">';
	echo '<li class="liga-footer-nav-item"><a class="liga-footer-nav-link" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Inicio', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-footer-nav-item"><a class="liga-footer-nav-link" href="' . esc_url( home_url( '/posiciones' ) ) . '">' . esc_html__( 'Posiciones', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-footer-nav-item"><a class="liga-footer-nav-link" href="' . esc_url( home_url( '/partidos' ) ) . '">' . esc_html__( 'Partidos', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-footer-nav-item"><a class="liga-footer-nav-link" href="' . esc_url( home_url( '/equipos' ) ) . '">' . esc_html__( 'Equipos', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-footer-nav-item"><a class="liga-footer-nav-link" href="' . esc_url( home_url( '/noticias' ) ) . '">' . esc_html__( 'Noticias', 'liga-basket-chile' ) . '</a></li>';
	echo '</ul>';
}

/**
 * Fallback menu for La Liga block.
 *
 * @return void
 */
function liga_secondary_menu_fallback() {
	echo '<ul class="liga-footer-league-list">';
	echo '<li class="liga-footer-league-item"><a class="liga-footer-league-link" href="' . esc_url( home_url( '/la-liga/reglamento' ) ) . '">' . esc_html__( 'Reglamento', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-footer-league-item"><a class="liga-footer-league-link" href="' . esc_url( home_url( '/la-liga/historia' ) ) . '">' . esc_html__( 'Historia', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-footer-league-item"><a class="liga-footer-league-link" href="' . esc_url( home_url( '/la-liga/galeria' ) ) . '">' . esc_html__( 'Galeria', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-footer-league-item"><a class="liga-footer-league-link" href="' . esc_url( home_url( '/contacto' ) ) . '">' . esc_html__( 'Contacto', 'liga-basket-chile' ) . '</a></li>';
	echo '</ul>';
}

/**
 * Fallback legal menu.
 *
 * @return void
 */
function liga_legal_menu_fallback() {
	echo '<ul class="liga-footer-legal-list">';
	echo '<li class="liga-footer-legal-item"><a class="liga-footer-legal-link" href="' . esc_url( home_url( '/terminos-y-condiciones' ) ) . '">' . esc_html__( 'Terminos y Condiciones', 'liga-basket-chile' ) . '</a></li>';
	echo '<li class="liga-footer-legal-item"><a class="liga-footer-legal-link" href="' . esc_url( home_url( '/politica-de-privacidad' ) ) . '">' . esc_html__( 'Politica de Privacidad', 'liga-basket-chile' ) . '</a></li>';
	echo '</ul>';
}

/**
 * Add theme-specific classes to nav menu links.
 *
 * @param array    $atts Link attributes.
 * @param WP_Post  $item Menu item object.
 * @param stdClass $args Menu arguments.
 * @return array
 */
function liga_nav_menu_link_attributes( $atts, $item, $args ) {
	if ( empty( $args->theme_location ) ) {
		return $atts;
	}

	$classes = array();

	switch ( $args->theme_location ) {
		case 'primary':
		case 'menu_principal':
			$classes[] = 'liga-header-nav-link';
			break;
		case 'liga_topbar_menu':
			$classes[] = 'liga-topbar-social-link';
			$classes[] = 'liga-topbar__link';
			break;
		case 'footer':
			$classes[] = 'liga-footer-nav-link';
			break;
		case 'secondary':
			$classes[] = 'liga-footer-league-link';
			break;
		case 'legal':
			$classes[] = 'liga-footer-legal-link';
			break;
	}

	if ( ! empty( $classes ) ) {
		$existing      = isset( $atts['class'] ) ? explode( ' ', (string) $atts['class'] ) : array();
		$atts['class'] = trim( implode( ' ', array_unique( array_merge( $existing, $classes ) ) ) );
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'liga_nav_menu_link_attributes', 10, 3 );

/**
 * Detecta si la solicitud actual apunta al listado publico de noticias.
 *
 * @return bool
 */
function liga_is_news_archive_request() {
	if ( is_admin() ) {
		return false;
	}

	$request_path = trim( (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ), '/' );

	return 1 === preg_match( '#^noticias(?:/page/[0-9]+)?/?$#', $request_path );
}

/**
 * Fuerza template de noticias para la ruta publica /noticias.
 *
 * @param string $template Template resuelto por WordPress.
 * @return string
 */
function liga_force_news_archive_template( $template ) {
	if ( ! liga_is_news_archive_request() ) {
		return $template;
	}

	$news_template = locate_template( array( 'home.php', 'archive-noticia.php', 'index.php' ) );

	return '' !== $news_template ? $news_template : $template;
}
add_filter( 'template_include', 'liga_force_news_archive_template', 50 );

/**
 * Fuerza template single de partido para evitar overrides de terceros.
 *
 * @param string $template Template resuelto por WordPress.
 * @return string
 */
function liga_force_partido_single_template( $template ) {
	if ( ! is_singular( 'partido' ) ) {
		return $template;
	}

	$single_partido_template = locate_template( array( 'single-partido.php' ) );
	return '' !== $single_partido_template ? $single_partido_template : $template;
}
add_filter( 'template_include', 'liga_force_partido_single_template', 99 );

/**
 * Evita que WordPress marque /noticias como 404 cuando se forza template.
 *
 * @param bool     $preempt  Valor previo para cortar manejo de 404.
 * @param WP_Query $wp_query Query principal actual.
 * @return bool
 */
function liga_prevent_news_archive_404( $preempt, $wp_query ) {
	if ( liga_is_news_archive_request() ) {
		return true;
	}

	return $preempt;
}
add_filter( 'pre_handle_404', 'liga_prevent_news_archive_404', 10, 2 );

/**
 * Add theme-specific classes to nav menu list items.
 *
 * @param array    $classes Current classes.
 * @param WP_Post  $item Menu item object.
 * @param stdClass $args Menu arguments.
 * @return array
 */
function liga_nav_menu_item_classes( $classes, $item, $args ) {
	if ( empty( $args->theme_location ) ) {
		return $classes;
	}

	switch ( $args->theme_location ) {
		case 'primary':
		case 'menu_principal':
			$classes[] = 'liga-header-nav-item';
			break;
		case 'liga_topbar_menu':
			$classes[] = 'liga-topbar-social-item';
			$classes[] = 'liga-topbar__item';
			break;
		case 'footer':
			$classes[] = 'liga-footer-nav-item';
			break;
		case 'secondary':
			$classes[] = 'liga-footer-league-item';
			break;
		case 'legal':
			$classes[] = 'liga-footer-legal-item';
			break;
	}

	return array_unique( $classes );
}
add_filter( 'nav_menu_css_class', 'liga_nav_menu_item_classes', 10, 3 );

/**
 * Bootstrap theme modules from /inc.
 *
 * @return void
 */
function liga_bootstrap_inc_modules() {
		$modules = array(
			'inc/helpers.php',
			'inc/seguridad.php',
			'inc/setup-theme.php',
			'inc/enqueue-assets.php',
			'inc/taxonomias.php',
			'inc/cpt-registro.php',
			'inc/cpt-popup.php',
			'inc/metaboxes.php',
			'inc/tabla-logica.php',
			'inc/public-standings.php',
			'inc/popup-render.php',
			'inc/opciones-tema.php',
			'inc/admin-footer-options.php',
			'inc/admin-tabla.php',
			'inc/import-equipos.php',
			'inc/import-partidos.php',
			'inc/api-endpoints.php',
			'inc/topbar-menu-icons.php',
			'inc/datos-demo.php',
		);

	foreach ( $modules as $module ) {
		$module_path = get_template_directory() . '/' . $module;
		if ( file_exists( $module_path ) ) {
			require_once $module_path;
		}
	}
}

liga_bootstrap_inc_modules();
