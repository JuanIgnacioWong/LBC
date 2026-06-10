<?php
/**
 * Bloque de fixture.
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
	'title'          => __( 'Proximos partidos', 'liga-basket-chile' ),
	'show_link'      => true,
	'link_label'     => __( 'Ver fixture', 'liga-basket-chile' ),
	'link_url'       => add_query_arg( 'estado', 'programado', $matches_archive ),
	'empty_message'  => __( 'No hay partidos programados.', 'liga-basket-chile' ),
);

$widget_args = wp_parse_args( is_array( $args ) ? $args : array(), $defaults );
$limit       = absint( $widget_args['posts_per_page'] );

if ( $limit <= 0 ) {
	$limit = 5;
}

$fixtures_posts = function_exists( 'liga_get_upcoming_match_posts' ) ? liga_get_upcoming_match_posts( $limit ) : array();

$get_match_timestamp = static function ( $raw_date, $raw_time = '' ) {
	$date = trim( (string) $raw_date );
	$time = trim( (string) $raw_time );

	if ( '' === $date ) {
		return 0;
	}

	$combined  = '' !== $time ? $date . ' ' . $time : $date;
	$timestamp = strtotime( $combined );

	return false !== $timestamp ? (int) $timestamp : 0;
};

$format_match_date = static function ( $raw_date ) use ( $get_match_timestamp ) {
	$timestamp = $get_match_timestamp( $raw_date );
	if ( $timestamp > 0 ) {
		return wp_date( 'j \d\e F \d\e Y', $timestamp );
	}

	return sanitize_text_field( (string) $raw_date );
};

$format_match_time = static function ( $raw_time ) {
	$time = trim( (string) $raw_time );
	if ( '' === $time ) {
		return '';
	}

	$timestamp = strtotime( $time );
	if ( false !== $timestamp ) {
		return wp_date( 'H:i', $timestamp );
	}

	return sanitize_text_field( $time );
};

$format_datetime_attribute = static function ( $raw_date, $raw_time = '' ) use ( $get_match_timestamp ) {
	$timestamp = $get_match_timestamp( $raw_date, $raw_time );
	if ( $timestamp <= 0 ) {
		return '';
	}

	return '' !== trim( (string) $raw_time ) ? wp_date( 'Y-m-d\TH:i', $timestamp ) : wp_date( 'Y-m-d', $timestamp );
};

$widget_title_id = wp_unique_id( 'liga-fixture-widget-title-' );
?>
<section class="liga-card liga-fixture-panel liga-single-post__widget" aria-labelledby="<?php echo esc_attr( $widget_title_id ); ?>">
	<div class="liga-section-head">
		<h2 class="liga-section-title" id="<?php echo esc_attr( $widget_title_id ); ?>"><?php echo esc_html( $widget_args['title'] ); ?></h2>
		<?php if ( ! empty( $widget_args['show_link'] ) && ! empty( $widget_args['link_url'] ) ) : ?>
			<a class="liga-section-link" href="<?php echo esc_url( $widget_args['link_url'] ); ?>"><?php echo esc_html( $widget_args['link_label'] ); ?></a>
		<?php endif; ?>
	</div>

	<ul class="liga-fixture-list">
		<?php if ( ! empty( $fixtures_posts ) ) : ?>
			<?php foreach ( $fixtures_posts as $fixture_post ) : ?>
				<?php
				$match_id       = (int) $fixture_post->ID;
				$local_id       = (int) get_post_meta( $match_id, 'liga_equipo_local', true );
				$visita_id      = (int) get_post_meta( $match_id, 'liga_equipo_visita', true );
				$division_id    = (int) get_post_meta( $match_id, 'liga_division', true );
				$raw_date       = function_exists( 'liga_get_match_date' ) ? liga_get_match_date( $match_id ) : (string) get_post_meta( $match_id, 'liga_fecha_partido', true );
				$raw_time       = function_exists( 'liga_get_match_time' ) ? liga_get_match_time( $match_id ) : (string) get_post_meta( $match_id, 'liga_hora_partido', true );
				$venue          = function_exists( 'liga_get_match_venue' ) ? liga_get_match_venue( $match_id ) : trim( (string) get_post_meta( $match_id, 'liga_cancha', true ) );
				$local_name     = $local_id > 0 ? get_the_title( $local_id ) : '';
				$visita_name    = $visita_id > 0 ? get_the_title( $visita_id ) : '';
				$division_name  = $division_id > 0 ? get_the_title( $division_id ) : '';
				$match_datetime = $format_datetime_attribute( $raw_date, $raw_time );
				$match_time     = function_exists( 'liga_format_match_time_label' ) ? liga_format_match_time_label( $raw_time ) : $format_match_time( $raw_time );
				$match_date     = function_exists( 'liga_format_match_date_label' ) ? liga_format_match_date_label( $raw_date ) : $format_match_date( $raw_date );
				$has_time       = '' !== $raw_time;

				$local_name    = '' !== $local_name ? $local_name : __( 'Local', 'liga-basket-chile' );
				$visita_name   = '' !== $visita_name ? $visita_name : __( 'Visita', 'liga-basket-chile' );
				$division_name = '' !== $division_name ? $division_name : __( 'Sin division', 'liga-basket-chile' );
				$venue         = '' !== $venue ? $venue : __( 'Recinto por definir', 'liga-basket-chile' );
				?>
				<li class="liga-fixture-item">
					<article class="liga-card liga-fixture-card">
						<header class="liga-fixture-head">
							<span class="liga-fixture-division"><?php echo esc_html( $division_name ); ?></span>
							<time class="liga-fixture-date" datetime="<?php echo esc_attr( $format_datetime_attribute( $raw_date ) ); ?>"><?php echo esc_html( $match_date ); ?></time>
						</header>
						<div class="liga-fixture-body">
							<p class="liga-fixture-time">
								<?php if ( $has_time ) : ?>
									<time datetime="<?php echo esc_attr( $match_datetime ); ?>"><?php echo esc_html( $match_time ); ?></time>
								<?php else : ?>
									<span><?php echo esc_html( $match_time ); ?></span>
								<?php endif; ?>
							</p>
							<div class="liga-fixture-teams">
								<figure class="liga-fixture-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $local_id, array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
								<p class="liga-fixture-team-name"><?php echo esc_html( $local_name ); ?></p>
								<span class="liga-fixture-versus">vs</span>
								<p class="liga-fixture-team-name"><?php echo esc_html( $visita_name ); ?></p>
								<figure class="liga-fixture-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $visita_id, array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
							</div>
							<p class="liga-fixture-venue"><?php echo esc_html( $venue ); ?></p>
						</div>
					</article>
				</li>
			<?php endforeach; ?>
		<?php else : ?>
			<li class="liga-fixture-item">
				<p><?php echo esc_html( $widget_args['empty_message'] ); ?></p>
			</li>
		<?php endif; ?>
	</ul>
</section>
