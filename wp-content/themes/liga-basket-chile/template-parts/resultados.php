<?php
/**
 * Bloque de resultados recientes.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$matches_archive = get_post_type_archive_link( 'partido' );
if ( ! $matches_archive ) {
	$matches_archive = home_url( '/partidos' );
}

$defaults = array(
	'posts_per_page' => 5,
	'title'          => __( 'Ultimos resultados', 'liga-basket-chile' ),
	'show_link'      => true,
	'link_label'     => __( 'Ver todos', 'liga-basket-chile' ),
	'link_url'       => add_query_arg( 'estado', 'finalizado', $matches_archive ),
	'empty_message'  => __( 'Aun no hay partidos computables.', 'liga-basket-chile' ),
);

$widget_args = wp_parse_args( is_array( $args ) ? $args : array(), $defaults );
$limit       = absint( $widget_args['posts_per_page'] );

if ( $limit <= 0 ) {
	$limit = 5;
}

$results_query = new WP_Query(
	array(
		'post_type'      => 'partido',
		'post_status'    => 'publish',
		'posts_per_page' => $limit,
		'meta_key'       => 'liga_fecha_partido',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
		'no_found_rows'  => true,
		'meta_query'     => array(
			array(
				'key'     => 'liga_estado_partido',
				'value'   => array( 'jugado', 'finalizado' ),
				'compare' => 'IN',
			),
		),
	)
);

$format_match_date = static function ( $raw_date ) {
	$date = trim( (string) $raw_date );
	if ( '' === $date ) {
		return '';
	}

	$timestamp = strtotime( $date );
	if ( false !== $timestamp ) {
		return wp_date( 'd M Y', $timestamp );
	}

	return sanitize_text_field( $date );
};

$format_status = static function ( $raw_status ) {
	$status = sanitize_key( (string) $raw_status );

	if ( 'finalizado' === $status ) {
		return __( 'Finalizado', 'liga-basket-chile' );
	}

	if ( 'jugado' === $status ) {
		return __( 'Jugado', 'liga-basket-chile' );
	}

	return '' !== $status ? ucwords( str_replace( '_', ' ', $status ) ) : __( 'Sin estado', 'liga-basket-chile' );
};

$widget_title_id = wp_unique_id( 'liga-results-widget-title-' );
?>
<section class="liga-card liga-results-panel liga-single-post__widget" aria-labelledby="<?php echo esc_attr( $widget_title_id ); ?>">
	<div class="liga-section-head">
		<h2 class="liga-section-title" id="<?php echo esc_attr( $widget_title_id ); ?>"><?php echo esc_html( $widget_args['title'] ); ?></h2>
		<?php if ( ! empty( $widget_args['show_link'] ) && ! empty( $widget_args['link_url'] ) ) : ?>
			<a class="liga-section-link" href="<?php echo esc_url( $widget_args['link_url'] ); ?>"><?php echo esc_html( $widget_args['link_label'] ); ?></a>
		<?php endif; ?>
	</div>

	<ul class="liga-results-list">
		<?php if ( $results_query->have_posts() ) : ?>
			<?php while ( $results_query->have_posts() ) : ?>
				<?php
				$results_query->the_post();
				$match_id      = get_the_ID();
				$local_id      = (int) get_post_meta( $match_id, 'liga_equipo_local', true );
				$visita_id     = (int) get_post_meta( $match_id, 'liga_equipo_visita', true );
				$division_id   = (int) get_post_meta( $match_id, 'liga_division', true );
				$local_pts     = (int) get_post_meta( $match_id, 'liga_puntos_local', true );
				$visita_pts    = (int) get_post_meta( $match_id, 'liga_puntos_visita', true );
				$raw_date      = (string) get_post_meta( $match_id, 'liga_fecha_partido', true );
				$raw_status    = (string) get_post_meta( $match_id, 'liga_estado_partido', true );
				$local_name    = $local_id > 0 ? get_the_title( $local_id ) : '';
				$visita_name   = $visita_id > 0 ? get_the_title( $visita_id ) : '';
				$division_name = $division_id > 0 ? get_the_title( $division_id ) : '';

				$local_name    = '' !== $local_name ? $local_name : __( 'Local', 'liga-basket-chile' );
				$visita_name   = '' !== $visita_name ? $visita_name : __( 'Visita', 'liga-basket-chile' );
				$division_name = '' !== $division_name ? $division_name : __( 'Sin division', 'liga-basket-chile' );
				?>
				<li class="liga-results-item">
					<article class="liga-card liga-result-card">
						<header class="liga-result-head">
							<span class="liga-result-division"><?php echo esc_html( $division_name ); ?></span>
							<time class="liga-result-date"><?php echo esc_html( $format_match_date( $raw_date ) ); ?></time>
						</header>
						<div class="liga-single-post__result-scoreline">
							<span class="liga-single-post__result-team">
								<?php echo wp_kses_post( liga_get_team_logo_html( $local_id, array( 'class' => 'liga-team-logo liga-single-post__result-team-logo', 'size' => 'thumbnail' ) ) ); ?>
								<?php echo esc_html( $local_name ); ?>
							</span>
							<strong class="liga-result-score"><?php echo esc_html( sprintf( '%d - %d', $local_pts, $visita_pts ) ); ?></strong>
							<span class="liga-single-post__result-team liga-single-post__result-team--away">
								<?php echo esc_html( $visita_name ); ?>
								<?php echo wp_kses_post( liga_get_team_logo_html( $visita_id, array( 'class' => 'liga-team-logo liga-single-post__result-team-logo', 'size' => 'thumbnail' ) ) ); ?>
							</span>
						</div>
						<footer class="liga-result-foot">
							<span class="liga-result-status"><?php echo esc_html( $format_status( $raw_status ) ); ?></span>
						</footer>
					</article>
				</li>
			<?php endwhile; ?>
		<?php else : ?>
			<li class="liga-results-item">
				<p><?php echo esc_html( $widget_args['empty_message'] ); ?></p>
			</li>
		<?php endif; ?>
	</ul>
</section>
<?php wp_reset_postdata(); ?>
