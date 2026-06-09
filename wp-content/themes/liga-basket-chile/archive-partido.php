<?php
/**
 * Archivo publico de partidos.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$get_match_timestamp = static function ( $raw_date, $raw_time = '' ) {
	$date = trim( sanitize_text_field( (string) $raw_date ) );
	$time = trim( sanitize_text_field( (string) $raw_time ) );

	if ( '' === $date ) {
		return 0;
	}

	$combined = '' !== $time ? $date . ' ' . $time : $date;
	$parsed   = strtotime( $combined );

	return false !== $parsed ? (int) $parsed : 0;
};

$format_match_date = static function ( $raw_date ) use ( $get_match_timestamp ) {
	$timestamp = $get_match_timestamp( $raw_date );
	if ( $timestamp > 0 ) {
		return wp_date( 'd M Y', $timestamp );
	}

	return trim( sanitize_text_field( (string) $raw_date ) );
};

$format_match_time = static function ( $raw_time ) {
	$time = trim( sanitize_text_field( (string) $raw_time ) );
	if ( '' === $time ) {
		return '';
	}

	$parsed = strtotime( $time );
	if ( false !== $parsed ) {
		return wp_date( 'H:i', $parsed );
	}

	return $time;
};

$format_datetime_attribute = static function ( $raw_date, $raw_time = '' ) use ( $get_match_timestamp ) {
	$timestamp = $get_match_timestamp( $raw_date, $raw_time );
	if ( $timestamp <= 0 ) {
		return '';
	}

	if ( '' !== trim( sanitize_text_field( (string) $raw_time ) ) ) {
		return wp_date( 'Y-m-d\\TH:i', $timestamp );
	}

	return wp_date( 'Y-m-d', $timestamp );
};

$status_labels = array(
	'programado' => __( 'Programado', 'liga-basket-chile' ),
	'jugado'     => __( 'Jugado', 'liga-basket-chile' ),
	'finalizado' => __( 'Finalizado', 'liga-basket-chile' ),
	'suspendido' => __( 'Suspendido', 'liga-basket-chile' ),
	'cancelado'  => __( 'Cancelado', 'liga-basket-chile' ),
);

$walkover_labels = array(
	'local_no_comparecio'  => __( 'Local no comparecio', 'liga-basket-chile' ),
	'visita_no_comparecio' => __( 'Visita no comparecio', 'liga-basket-chile' ),
);

$get_status_label = static function ( $status_key ) use ( $status_labels ) {
	$key = sanitize_key( (string) $status_key );
	if ( isset( $status_labels[ $key ] ) ) {
		return $status_labels[ $key ];
	}

	return '' !== $key ? ucwords( str_replace( '_', ' ', $key ) ) : __( 'Sin estado', 'liga-basket-chile' );
};

$get_division_label = static function ( $division_id ) {
	$division_id = absint( $division_id );
	if ( $division_id <= 0 ) {
		return __( 'Sin division', 'liga-basket-chile' );
	}

	if ( function_exists( 'liga_get_division_public_label' ) ) {
		$label = trim( sanitize_text_field( (string) liga_get_division_public_label( $division_id ) ) );
		if ( '' !== $label ) {
			return $label;
		}
	}

	$fallback = trim( sanitize_text_field( get_the_title( $division_id ) ) );
	return '' !== $fallback ? $fallback : __( 'Sin division', 'liga-basket-chile' );
};

$team_cache = array();
$get_team_data = static function ( $team_id ) use ( &$team_cache ) {
	$team_id = absint( $team_id );
	if ( isset( $team_cache[ $team_id ] ) ) {
		return $team_cache[ $team_id ];
	}

	$name = '';
	if ( $team_id > 0 ) {
		if ( function_exists( 'liga_get_equipo_nombre' ) ) {
			$name = trim( sanitize_text_field( (string) liga_get_equipo_nombre( $team_id ) ) );
		}

		if ( '' === $name ) {
			$name = trim( sanitize_text_field( get_the_title( $team_id ) ) );
		}
	}

	$team_cache[ $team_id ] = array(
		'id'   => $team_id,
		'name' => '' !== $name ? $name : __( 'Equipo', 'liga-basket-chile' ),
	);

	return $team_cache[ $team_id ];
};

$selected_division  = isset( $_GET['division'] ) ? absint( wp_unslash( $_GET['division'] ) ) : 0;
$selected_temporada = isset( $_GET['temporada'] ) ? trim( sanitize_text_field( wp_unslash( $_GET['temporada'] ) ) ) : '';
$selected_estado    = isset( $_GET['estado'] ) ? sanitize_key( wp_unslash( $_GET['estado'] ) ) : '';

$divisions = get_posts(
	array(
		'post_type'      => 'division',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'liga_orden_visual',
		'orderby'        => array(
			'meta_value_num' => 'ASC',
			'title'          => 'ASC',
		),
	)
);

$season_options = function_exists( 'liga_get_available_temporadas' ) ? liga_get_available_temporadas() : array();
if ( '' !== $selected_temporada && ! isset( $season_options[ $selected_temporada ] ) ) {
	$season_options[ $selected_temporada ] = $selected_temporada;
}

$archive_url = get_post_type_archive_link( 'partido' );
if ( ! $archive_url ) {
	$archive_url = home_url( '/partidos/' );
}

$pagination_args = array();
if ( $selected_division > 0 ) {
	$pagination_args['division'] = $selected_division;
}
if ( '' !== $selected_temporada ) {
	$pagination_args['temporada'] = $selected_temporada;
}
if ( '' !== $selected_estado ) {
	$pagination_args['estado'] = $selected_estado;
}

get_header();
?>
<section class="liga-partidos-archive">
	<div class="liga-container">
		<header class="liga-partidos-archive__header">
			<h1 class="liga-partidos-archive__title"><?php post_type_archive_title(); ?></h1>
			<p class="liga-partidos-archive__subtitle"><?php esc_html_e( 'Listado oficial de encuentros con filtros por division, temporada y estado.', 'liga-basket-chile' ); ?></p>
		</header>

		<form class="liga-partidos-archive__filters liga-card" method="get" action="<?php echo esc_url( $archive_url ); ?>" aria-label="<?php esc_attr_e( 'Filtros de partidos', 'liga-basket-chile' ); ?>">
			<div class="liga-partidos-archive__filter">
				<label for="liga-filter-division"><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></label>
				<select id="liga-filter-division" name="division">
					<option value="0"><?php esc_html_e( 'Todas', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $divisions as $division ) : ?>
						<?php $division_id = (int) $division->ID; ?>
						<option value="<?php echo esc_attr( (string) $division_id ); ?>" <?php selected( $selected_division, $division_id ); ?>>
							<?php echo esc_html( $get_division_label( $division_id ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="liga-partidos-archive__filter">
				<label for="liga-filter-temporada"><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></label>
				<select id="liga-filter-temporada" name="temporada">
					<option value=""><?php esc_html_e( 'Todas', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $season_options as $season_key => $season_label ) : ?>
						<option value="<?php echo esc_attr( (string) $season_key ); ?>" <?php selected( $selected_temporada, (string) $season_key ); ?>>
							<?php echo esc_html( (string) $season_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="liga-partidos-archive__filter">
				<label for="liga-filter-estado"><?php esc_html_e( 'Estado', 'liga-basket-chile' ); ?></label>
				<select id="liga-filter-estado" name="estado">
					<option value=""><?php esc_html_e( 'Todos', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $status_labels as $status_key => $status_label ) : ?>
						<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $selected_estado, $status_key ); ?>>
							<?php echo esc_html( $status_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="liga-partidos-archive__actions">
				<button type="submit" class="liga-btn liga-btn--primary"><?php esc_html_e( 'Filtrar', 'liga-basket-chile' ); ?></button>
				<a href="<?php echo esc_url( $archive_url ); ?>" class="liga-btn liga-btn--ghost"><?php esc_html_e( 'Limpiar', 'liga-basket-chile' ); ?></a>
			</div>
		</form>

		<?php if ( have_posts() ) : ?>
			<div class="liga-partidos-archive__list" role="list">
				<?php while ( have_posts() ) : ?>
					<?php
					the_post();
					$match_id       = get_the_ID();
					$division_id    = (int) get_post_meta( $match_id, 'liga_division', true );
					$status_key     = sanitize_key( (string) get_post_meta( $match_id, 'liga_estado_partido', true ) );
					$walkover_key   = sanitize_key( (string) get_post_meta( $match_id, 'liga_incomparecencia', true ) );
					$raw_date       = (string) get_post_meta( $match_id, 'liga_fecha_partido', true );
					$raw_time       = (string) get_post_meta( $match_id, 'liga_hora_partido', true );
					$raw_venue      = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_cancha', true ) ) );
					$local_id       = (int) get_post_meta( $match_id, 'liga_equipo_local', true );
					$visita_id      = (int) get_post_meta( $match_id, 'liga_equipo_visita', true );
					$local_pts      = (int) get_post_meta( $match_id, 'liga_puntos_local', true );
					$visita_pts     = (int) get_post_meta( $match_id, 'liga_puntos_visita', true );

					$home_team      = $get_team_data( $local_id );
					$away_team      = $get_team_data( $visita_id );

					$score_label    = __( 'VS', 'liga-basket-chile' );
					$has_real_score = false;

					if ( function_exists( 'liga_is_match_countable_for_standings' ) ) {
						$score_eval = liga_is_match_countable_for_standings( $match_id, $division_id, null );
						if ( ! empty( $score_eval['is_countable'] ) && ! empty( $score_eval['match'] ) && is_array( $score_eval['match'] ) ) {
							$local_pts      = isset( $score_eval['match']['puntos_local'] ) ? (int) $score_eval['match']['puntos_local'] : $local_pts;
							$visita_pts     = isset( $score_eval['match']['puntos_visita'] ) ? (int) $score_eval['match']['puntos_visita'] : $visita_pts;
							$has_real_score = true;
						}
					}

					if ( ! $has_real_score && in_array( $status_key, array( 'jugado', 'finalizado' ), true ) ) {
						if ( $local_pts !== $visita_pts && ( $local_pts > 0 || $visita_pts > 0 ) ) {
							$has_real_score = true;
						} elseif ( in_array( $walkover_key, array( 'local_no_comparecio', 'visita_no_comparecio' ), true ) && function_exists( 'liga_get_walkover_score' ) ) {
							$walkover       = liga_get_walkover_score( $walkover_key );
							$local_pts      = (int) $walkover['local'];
							$visita_pts     = (int) $walkover['visita'];
							$has_real_score = true;
						}
					}

					if ( $has_real_score ) {
						$score_label = sprintf( '%d - %d', $local_pts, $visita_pts );
					}

					$date_label     = $format_match_date( $raw_date );
					$time_label     = $format_match_time( $raw_time );
					$datetime_attr  = $format_datetime_attribute( $raw_date, $raw_time );
					$division_label = $get_division_label( $division_id );
					$status_label   = $get_status_label( $status_key );
					?>
					<article class="liga-card liga-partidos-archive__item" role="listitem" aria-labelledby="liga-match-item-title-<?php echo esc_attr( (string) $match_id ); ?>">
						<div class="liga-partidos-archive__meta">
							<span class="liga-partidos-archive__division"><?php echo esc_html( $division_label ); ?></span>
							<span class="liga-partidos-archive__status liga-partidos-archive__status--<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></span>
						</div>

						<h2 class="liga-partidos-archive__match-title" id="liga-match-item-title-<?php echo esc_attr( (string) $match_id ); ?>">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

						<div class="liga-partidos-archive__scoreboard">
							<div class="liga-partidos-archive__team liga-partidos-archive__team--home">
								<figure class="liga-partidos-archive__team-logo">
									<?php echo wp_kses_post( liga_get_team_logo_html( $home_team['id'], array( 'class' => 'liga-team-logo liga-partidos-archive__team-logo-image', 'size' => 'thumbnail' ) ) ); ?>
								</figure>
								<span class="liga-partidos-archive__team-name"><?php echo esc_html( $home_team['name'] ); ?></span>
							</div>

							<div class="liga-partidos-archive__score<?php echo $has_real_score ? ' is-score' : ' is-vs'; ?>">
								<?php echo esc_html( $score_label ); ?>
							</div>

							<div class="liga-partidos-archive__team liga-partidos-archive__team--away">
								<figure class="liga-partidos-archive__team-logo">
									<?php echo wp_kses_post( liga_get_team_logo_html( $away_team['id'], array( 'class' => 'liga-team-logo liga-partidos-archive__team-logo-image', 'size' => 'thumbnail' ) ) ); ?>
								</figure>
								<span class="liga-partidos-archive__team-name"><?php echo esc_html( $away_team['name'] ); ?></span>
							</div>
						</div>

						<ul class="liga-partidos-archive__facts">
							<li>
								<strong><?php esc_html_e( 'Fecha', 'liga-basket-chile' ); ?>:</strong>
								<?php if ( '' !== $datetime_attr ) : ?>
									<time datetime="<?php echo esc_attr( $datetime_attr ); ?>"><?php echo esc_html( '' !== $date_label ? $date_label : __( 'Por definir', 'liga-basket-chile' ) ); ?></time>
								<?php else : ?>
									<?php echo esc_html( '' !== $date_label ? $date_label : __( 'Por definir', 'liga-basket-chile' ) ); ?>
								<?php endif; ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Hora', 'liga-basket-chile' ); ?>:</strong>
								<?php echo esc_html( '' !== $time_label ? $time_label : __( 'Por definir', 'liga-basket-chile' ) ); ?>
							</li>
							<li>
								<strong><?php esc_html_e( 'Recinto', 'liga-basket-chile' ); ?>:</strong>
								<?php echo esc_html( '' !== $raw_venue ? $raw_venue : __( 'Recinto por definir', 'liga-basket-chile' ) ); ?>
							</li>
						</ul>

						<?php if ( isset( $walkover_labels[ $walkover_key ] ) ) : ?>
							<p class="liga-partidos-archive__walkover"><?php echo esc_html( $walkover_labels[ $walkover_key ] ); ?></p>
						<?php endif; ?>

						<footer class="liga-partidos-archive__footer">
							<a class="liga-section-link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Ver detalle del partido', 'liga-basket-chile' ); ?></a>
						</footer>
					</article>
				<?php endwhile; ?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => __( 'Anterior', 'liga-basket-chile' ),
					'next_text' => __( 'Siguiente', 'liga-basket-chile' ),
					'add_args'  => $pagination_args,
				)
			);
			?>
		<?php else : ?>
			<p class="liga-partidos-archive__empty"><?php esc_html_e( 'No hay partidos disponibles para los filtros seleccionados.', 'liga-basket-chile' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
