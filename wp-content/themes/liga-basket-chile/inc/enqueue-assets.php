<?php
/**
 * Registro y carga de assets frontend.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna version por timestamp del archivo.
 *
 * @param string $relative_path Ruta relativa desde el tema.
 * @return string
 */
if ( ! function_exists( 'liga_asset_version' ) ) {
	function liga_asset_version( $relative_path ) {
		$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );
		return file_exists( $path ) ? (string) filemtime( $path ) : wp_get_theme()->get( 'Version' );
	}
}

/**
 * Encola estilos y scripts globales.
 *
 * @return void
 */
if ( ! function_exists( 'liga_enqueue_assets' ) ) {
	function liga_enqueue_assets() {
		wp_enqueue_style(
			'liga-google-fonts',
			'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;700;800&family=Inter:wght@400;500;600;700&display=swap',
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
			'liga-components',
			get_template_directory_uri() . '/assets/css/components.css',
			array( 'liga-main' ),
			liga_asset_version( 'assets/css/components.css' )
		);

		wp_enqueue_style(
			'liga-responsive',
			get_template_directory_uri() . '/assets/css/responsive.css',
			array( 'liga-components' ),
			liga_asset_version( 'assets/css/responsive.css' )
		);

		wp_enqueue_script(
			'liga-main',
			get_template_directory_uri() . '/assets/js/main.js',
			array(),
			liga_asset_version( 'assets/js/main.js' ),
			true
		);

		wp_enqueue_script(
			'liga-menu',
			get_template_directory_uri() . '/assets/js/menu.js',
			array( 'liga-main' ),
			liga_asset_version( 'assets/js/menu.js' ),
			true
		);

		wp_enqueue_script(
			'liga-interactions',
			get_template_directory_uri() . '/assets/js/interactions.js',
			array( 'liga-main' ),
			liga_asset_version( 'assets/js/interactions.js' ),
			true
		);
	}
}

if ( ! has_action( 'wp_enqueue_scripts', 'liga_enqueue_assets' ) ) {
	add_action( 'wp_enqueue_scripts', 'liga_enqueue_assets' );
}
