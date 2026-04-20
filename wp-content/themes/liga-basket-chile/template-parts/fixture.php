<?php
/**
 * Bloque de fixture.
 *
 * @package LigaBasketChile
 */

$fixtures = get_posts(
	array(
		'post_type'      => 'partido',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'meta_key'       => 'liga_fecha_partido',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'   => 'liga_estado_partido',
				'value' => 'programado',
			),
		),
	)
);
?>
<section class="liga-card liga-home-fixture" data-liga-reveal>
	<header class="liga-block-head">
		<h2 class="liga-block-title"><?php esc_html_e( 'Proximos Partidos', 'liga-basket-chile' ); ?></h2>
		<a href="<?php echo esc_url( home_url( '/partido' ) ); ?>" class="liga-link-more"><?php esc_html_e( 'Ver Fixture', 'liga-basket-chile' ); ?></a>
	</header>
	<div class="liga-match-list">
		<?php if ( empty( $fixtures ) ) : ?>
			<p><?php esc_html_e( 'No hay partidos programados.', 'liga-basket-chile' ); ?></p>
		<?php endif; ?>
		<?php foreach ( $fixtures as $match ) : ?>
			<?php
			$local_id  = (int) get_post_meta( $match->ID, 'liga_equipo_local', true );
			$visita_id = (int) get_post_meta( $match->ID, 'liga_equipo_visita', true );
			$division  = (int) get_post_meta( $match->ID, 'liga_division', true );
			$fecha     = (string) get_post_meta( $match->ID, 'liga_fecha_partido', true );
			$hora      = (string) get_post_meta( $match->ID, 'liga_hora_partido', true );
			$cancha    = (string) get_post_meta( $match->ID, 'liga_cancha', true );
			?>
			<article class="liga-fixture-card">
				<p class="liga-fixture-teams"><?php echo esc_html( get_the_title( $local_id ) . ' vs ' . get_the_title( $visita_id ) ); ?></p>
				<div class="liga-match-meta">
					<span><?php echo esc_html( $fecha . ' ' . $hora ); ?></span>
					<span><?php echo esc_html( $cancha ); ?></span>
					<span class="liga-badge"><?php echo esc_html( get_the_title( $division ) ); ?></span>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
