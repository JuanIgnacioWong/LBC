<?php
/**
 * Configuracion y soportes del tema.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra soportes y menus del tema.
 *
 * @return void
 */
function liga_setup_theme() {
	load_theme_textdomain( 'liga-basket-chile', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 320,
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
			'primary'   => __( 'Principal', 'liga-basket-chile' ),
			'menu_principal' => __( 'Menú Principal', 'liga-basket-chile' ),
			'liga_topbar_menu' => __( 'Menú Topbar', 'liga-basket-chile' ),
			'secondary' => __( 'Secundario', 'liga-basket-chile' ),
			'footer'    => __( 'Footer', 'liga-basket-chile' ),
			'legal'     => __( 'Legal', 'liga-basket-chile' ),
		)
	);
}
add_action( 'after_setup_theme', 'liga_setup_theme' );

/**
 * Registra sidebars base para footer.
 *
 * @return void
 */
function liga_register_sidebars() {
	$sidebars = array(
		'footer-col-1' => __( 'Footer Columna 1', 'liga-basket-chile' ),
		'footer-col-2' => __( 'Footer Columna 2', 'liga-basket-chile' ),
		'footer-col-3' => __( 'Footer Columna 3', 'liga-basket-chile' ),
		'footer-col-4' => __( 'Footer Columna 4', 'liga-basket-chile' ),
	);

	foreach ( $sidebars as $id => $name ) {
		register_sidebar(
			array(
				'name'          => $name,
				'id'            => $id,
				'description'   => __( 'Area de widgets del footer.', 'liga-basket-chile' ),
				'before_widget' => '<section id="%1$s" class="widget liga-widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="liga-widget__title">',
				'after_title'   => '</h3>',
			)
		);
	}
}
add_action( 'widgets_init', 'liga_register_sidebars' );
