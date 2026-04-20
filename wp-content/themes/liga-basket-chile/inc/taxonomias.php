<?php
/**
 * Registro de taxonomias (fase 2).
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra taxonomia editorial para noticias.
 *
 * @return void
 */
function liga_register_taxonomies() {
	register_taxonomy(
		'categoria_noticia_liga',
		'post',
		array(
			'labels'       => array(
				'name'          => __( 'Categorias de Noticias', 'liga-basket-chile' ),
				'singular_name' => __( 'Categoria de Noticia', 'liga-basket-chile' ),
			),
			'public'       => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'rewrite'      => array( 'slug' => 'categoria-noticia' ),
		)
	);
}
add_action( 'init', 'liga_register_taxonomies' );
