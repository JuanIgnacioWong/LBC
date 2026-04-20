<?php
/**
 * Card base de partido.
 *
 * @package LigaBasketChile
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'liga-article liga-partido-card' ); ?>>
	<h2 class="liga-article__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
	<p>
		<strong><?php esc_html_e( 'Fecha:', 'liga-basket-chile' ); ?></strong>
		<?php echo esc_html( (string) get_post_meta( get_the_ID(), 'liga_fecha_partido', true ) ); ?>
		<?php echo esc_html( (string) get_post_meta( get_the_ID(), 'liga_hora_partido', true ) ); ?>
	</p>
	<p>
		<strong><?php esc_html_e( 'Cancha:', 'liga-basket-chile' ); ?></strong>
		<?php echo esc_html( (string) get_post_meta( get_the_ID(), 'liga_cancha', true ) ); ?>
	</p>
	<div class="liga-article__excerpt"><?php the_excerpt(); ?></div>
</article>
