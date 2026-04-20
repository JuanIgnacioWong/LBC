<?php
/**
 * Card base de equipo.
 *
 * @package LigaBasketChile
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-article liga-equipo-card' ); ?>>
	<h2 class="liga-article__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<p><strong><?php esc_html_e( 'Ciudad:', 'liga-basket-chile' ); ?></strong> <?php echo esc_html( (string) get_post_meta( get_the_ID(), 'liga_ciudad', true ) ); ?></p>
	<p><strong><?php esc_html_e( 'Entrenador:', 'liga-basket-chile' ); ?></strong> <?php echo esc_html( (string) get_post_meta( get_the_ID(), 'liga_entrenador', true ) ); ?></p>
	<div class="liga-article__excerpt"><?php the_excerpt(); ?></div>
</article>
