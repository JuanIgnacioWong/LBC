<?php
/**
 * Bloque de resultados recientes.
 *
 * @package LigaBasketChile
 */

$results = get_posts(
	array(
		'post_type'      => 'partido',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'meta_key'       => 'liga_fecha_partido',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'     => 'liga_estado_partido',
				'value'   => array( 'jugado', 'finalizado' ),
				'compare' => 'IN',
			),
		),
	)
);
?>
<section class="liga-card liga-home-results" data-liga-reveal>
	<header class="liga-block-head">
		<h2 class="liga-block-title"><?php esc_html_e( 'Ultimos Resultados', 'liga-basket-chile' ); ?></h2>
	</header>
	<div class="liga-match-list">
		<?php if ( empty( $results ) ) : ?>
			<p><?php esc_html_e( 'Aun no hay partidos computables.', 'liga-basket-chile' ); ?></p>
		<?php endif; ?>
		<?php foreach ( $results as $match ) : ?>
			<?php
			$local_id   = (int) get_post_meta( $match->ID, 'liga_equipo_local', true );
			$visita_id  = (int) get_post_meta( $match->ID, 'liga_equipo_visita', true );
			$local_pts  = (int) get_post_meta( $match->ID, 'liga_puntos_local', true );
			$visita_pts = (int) get_post_meta( $match->ID, 'liga_puntos_visita', true );
			$division   = (int) get_post_meta( $match->ID, 'liga_division', true );
			$fecha      = (string) get_post_meta( $match->ID, 'liga_fecha_partido', true );
			$estado     = sanitize_key( (string) get_post_meta( $match->ID, 'liga_estado_partido', true ) );
			$estado_txt = 'finalizado' === $estado ? __( 'Finalizado', 'liga-basket-chile' ) : __( 'Jugado', 'liga-basket-chile' );
			?>
			<article class="liga-match-card">
				<div class="liga-match-score">
					<span class="liga-match-team"><?php echo esc_html( get_the_title( $local_id ) ); ?></span>
					<strong><?php echo esc_html( sprintf( '%d - %d', $local_pts, $visita_pts ) ); ?></strong>
					<span class="liga-match-team"><?php echo esc_html( get_the_title( $visita_id ) ); ?></span>
				</div>
				<div class="liga-match-meta">
					<span><?php echo esc_html( get_the_title( $division ) ); ?></span>
					<span><?php echo esc_html( $fecha ); ?></span>
					<span class="liga-badge"><?php echo esc_html( $estado_txt ); ?></span>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</section>
