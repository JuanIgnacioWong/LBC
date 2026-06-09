<?php
/**
 * Home main panels section.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$season = liga_get_current_season_label();

$get_timestamp = static function ( $raw_date, $raw_time = '' ) {
	$date = trim( (string) $raw_date );
	$time = trim( (string) $raw_time );

	if ( '' === $date ) {
		return 0;
	}

	$combined  = '' !== $time ? $date . ' ' . $time : $date;
	$timestamp = strtotime( $combined );

	return false !== $timestamp ? (int) $timestamp : 0;
};

$format_date = static function ( $raw_date, $format ) use ( $get_timestamp ) {
	$timestamp = $get_timestamp( $raw_date );
	if ( $timestamp > 0 ) {
		return wp_date( $format, $timestamp );
	}

	return sanitize_text_field( (string) $raw_date );
};

$format_time = static function ( $raw_time ) {
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

$format_datetime_attribute = static function ( $raw_date, $raw_time = '' ) use ( $get_timestamp ) {
	$timestamp = $get_timestamp( $raw_date, $raw_time );
	if ( $timestamp <= 0 ) {
		return '';
	}

	return '' !== trim( (string) $raw_time ) ? wp_date( 'Y-m-d\TH:i', $timestamp ) : wp_date( 'Y-m-d', $timestamp );
};

$get_match_status_label = static function ( $status_key ) {
	$status_labels = array(
		'jugado'     => __( 'Jugado', 'liga-basket-chile' ),
		'finalizado' => __( 'Finalizado', 'liga-basket-chile' ),
		'programado' => __( 'Programado', 'liga-basket-chile' ),
		'suspendido' => __( 'Suspendido', 'liga-basket-chile' ),
		'cancelado'  => __( 'Cancelado', 'liga-basket-chile' ),
	);

	$key = sanitize_key( (string) $status_key );
	if ( isset( $status_labels[ $key ] ) ) {
		return $status_labels[ $key ];
	}

	return '' !== $key ? ucwords( str_replace( '_', ' ', $key ) ) : '';
};

$get_division_label = static function ( $division_id ) {
	$label = $division_id > 0 ? get_the_title( (int) $division_id ) : '';
	return '' !== $label ? $label : __( 'Sin division', 'liga-basket-chile' );
};

$team_cache = array();

$get_team_data = static function ( $team_id ) use ( &$team_cache ) {
	$team_id = absint( $team_id );
	if ( isset( $team_cache[ $team_id ] ) ) {
		return $team_cache[ $team_id ];
	}

	$name = '';
	if ( $team_id > 0 ) {
		$name_meta = trim( (string) get_post_meta( $team_id, 'liga_nombre_equipo', true ) );
		$name      = '' !== $name_meta ? $name_meta : get_the_title( $team_id );
	}

	$team_cache[ $team_id ] = array(
		'id'   => $team_id,
		'name' => $name,
	);

	return $team_cache[ $team_id ];
};

$division_posts = get_posts(
	array(
		'post_type'      => 'division',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'liga_orden_visual',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
	)
);

$divisions = array();
$max_visible_teams = 12;

foreach ( $division_posts as $division_post ) {
	$division_id    = (int) $division_post->ID;
	$table_data     = function_exists( 'liga_get_standings_by_division_and_season' )
		? liga_get_standings_by_division_and_season( $division_id, $season )
		: liga_calcular_tabla_posiciones( $division_id, $season );
	$division_label = trim( (string) get_post_meta( $division_id, 'liga_nombre_division', true ) );
	$rows           = array();

	if ( ( ! isset( $table_data['tabla'] ) || ! is_array( $table_data['tabla'] ) || empty( $table_data['tabla'] ) ) && function_exists( 'liga_get_standings_by_division_and_season' ) ) {
		$division_season = function_exists( 'liga_get_division_temporada_label' ) ? liga_get_division_temporada_label( $division_id ) : '';
		if ( ! liga_is_valid_temporada_label( $division_season ) ) {
			$division_season = gmdate( 'Y' );
		}

		$table_data = liga_get_standings_by_division_and_season( $division_id, $division_season );
	}

	if ( isset( $table_data['tabla'] ) && is_array( $table_data['tabla'] ) ) {
		$rows = array_slice( $table_data['tabla'], 0, $max_visible_teams );
	}

	$divisions[] = array(
		'key'   => 'division-' . $division_id,
		'label' => '' !== $division_label ? $division_label : $division_post->post_title,
		'rows'  => $rows,
	);
}

$results_posts = get_posts(
	array(
		'post_type'      => 'partido',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
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

$results = array();

foreach ( $results_posts as $result_post ) {
	$local_id    = (int) get_post_meta( $result_post->ID, 'liga_equipo_local', true );
	$visita_id   = (int) get_post_meta( $result_post->ID, 'liga_equipo_visita', true );
	$division_id = (int) get_post_meta( $result_post->ID, 'liga_division', true );
	$local_pts   = (int) get_post_meta( $result_post->ID, 'liga_puntos_local', true );
	$visita_pts  = (int) get_post_meta( $result_post->ID, 'liga_puntos_visita', true );
	$raw_date    = (string) get_post_meta( $result_post->ID, 'liga_fecha_partido', true );
	$status      = (string) get_post_meta( $result_post->ID, 'liga_estado_partido', true );

	if ( $local_id <= 0 || $visita_id <= 0 || $local_id === $visita_id ) {
		continue;
	}

	$home_team = $get_team_data( $local_id );
	$away_team = $get_team_data( $visita_id );

	$results[] = array(
		'division'  => $get_division_label( $division_id ),
		'date'      => $format_datetime_attribute( $raw_date ),
		'date_h'    => $format_date( $raw_date, 'd M Y' ),
		'home_id'   => $home_team['id'],
		'home'      => $home_team['name'],
		'away_id'   => $away_team['id'],
		'away'      => $away_team['name'],
		'score'     => sprintf( '%d - %d', $local_pts, $visita_pts ),
		'status'    => $get_match_status_label( $status ),
	);
}

$fixtures_posts = get_posts(
	array(
		'post_type'      => 'partido',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
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

$fixtures = array();

foreach ( $fixtures_posts as $fixture_post ) {
	$local_id    = (int) get_post_meta( $fixture_post->ID, 'liga_equipo_local', true );
	$visita_id   = (int) get_post_meta( $fixture_post->ID, 'liga_equipo_visita', true );
	$division_id = (int) get_post_meta( $fixture_post->ID, 'liga_division', true );
	$raw_date    = (string) get_post_meta( $fixture_post->ID, 'liga_fecha_partido', true );
	$raw_time    = (string) get_post_meta( $fixture_post->ID, 'liga_hora_partido', true );
	$venue       = trim( (string) get_post_meta( $fixture_post->ID, 'liga_cancha', true ) );

	if ( $local_id <= 0 || $visita_id <= 0 || $local_id === $visita_id ) {
		continue;
	}

	$home_team = $get_team_data( $local_id );
	$away_team = $get_team_data( $visita_id );

	$fixtures[] = array(
		'division'  => $get_division_label( $division_id ),
		'date'      => $format_datetime_attribute( $raw_date ),
		'date_h'    => $format_date( $raw_date, 'D d M Y' ),
		'time'      => $format_time( $raw_time ),
		'datetime'  => $format_datetime_attribute( $raw_date, $raw_time ),
		'home_id'   => $home_team['id'],
		'home'      => $home_team['name'],
		'away_id'   => $away_team['id'],
		'away'      => $away_team['name'],
		'venue'     => '' !== $venue ? $venue : __( 'Cancha por definir', 'liga-basket-chile' ),
	);
}

$matches_archive = get_post_type_archive_link( 'partido' );
if ( ! $matches_archive ) {
	$matches_archive = home_url( '/partidos' );
}

$results_link = add_query_arg( 'estado', 'finalizado', $matches_archive );
$fixture_link = add_query_arg( 'estado', 'programado', $matches_archive );

$standings_link = function_exists( 'liga_get_default_public_standings_url' ) ? liga_get_default_public_standings_url( $season ) : '';
if ( '' === $standings_link ) {
	$standings_link = home_url( '/posiciones' );
}
?>
<section class="liga-home-main-panels" aria-labelledby="liga-home-main-panels-title">
	<div class="liga-container">
		<h2 class="liga-home-main-panels-title" id="liga-home-main-panels-title"><?php esc_html_e( 'Centro Deportivo', 'liga-basket-chile' ); ?></h2>

		<div class="liga-grid liga-home-main-panels-grid">
				<section class="liga-card liga-standings-panel" aria-labelledby="liga-standings-title">
					<div class="liga-section-head">
						<h3 class="liga-section-title" id="liga-standings-title"><?php esc_html_e( 'Tabla de posiciones', 'liga-basket-chile' ); ?></h3>
						<a class="liga-section-link" href="<?php echo esc_url( $standings_link ); ?>"><?php esc_html_e( 'Ver tabla completa', 'liga-basket-chile' ); ?></a>
					</div>

				<?php if ( empty( $divisions ) ) : ?>
					<p><?php esc_html_e( 'No hay divisiones cargadas.', 'liga-basket-chile' ); ?></p>
				<?php else : ?>
					<div class="liga-division-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Divisiones de tabla de posiciones', 'liga-basket-chile' ); ?>">
						<?php foreach ( $divisions as $index => $division ) : ?>
							<?php
							$tab_id    = 'liga-division-tab-' . $division['key'];
							$panel_id  = 'liga-division-panel-' . $division['key'];
							$is_active = 0 === $index;
							?>
							<button class="liga-division-tab liga-tab <?php echo $is_active ? 'liga-tab-active liga-tab--active' : ''; ?>" id="<?php echo esc_attr( $tab_id ); ?>" type="button" role="tab" aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $panel_id ); ?>">
								<?php echo esc_html( $division['label'] ); ?>
							</button>
						<?php endforeach; ?>
					</div>

					<?php foreach ( $divisions as $index => $division ) : ?>
						<?php
						$panel_id  = 'liga-division-panel-' . $division['key'];
						$tab_id    = 'liga-division-tab-' . $division['key'];
						$is_active = 0 === $index;
						?>
						<div class="liga-table-wrap" id="<?php echo esc_attr( $panel_id ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $tab_id ); ?>" <?php echo $is_active ? '' : 'hidden'; ?>>
							<table class="liga-table liga-standings-table">
								<caption class="liga-table-caption"><?php echo esc_html( sprintf( __( 'Tabla %s', 'liga-basket-chile' ), $division['label'] ) ); ?></caption>
								<thead>
									<tr>
										<th scope="col"><?php esc_html_e( 'Pos', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Logo', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Equipo', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PJ', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PG', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PP', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'INC', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PTS', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PF', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PC', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'DIF', 'liga-basket-chile' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $division['rows'] ) ) : ?>
										<tr>
											<td colspan="11"><?php esc_html_e( 'Sin resultados cargados.', 'liga-basket-chile' ); ?></td>
										</tr>
									<?php endif; ?>

									<?php foreach ( $division['rows'] as $row_index => $row ) : ?>
										<?php
										$row_team_name = isset( $row['equipo'] ) ? (string) $row['equipo'] : '';
										$row_team_id   = isset( $row['equipo_id'] ) ? (int) $row['equipo_id'] : 0;
										?>
										<tr class="<?php echo 0 === $row_index ? 'liga-row--leader' : ''; ?>">
											<th scope="row"><?php echo esc_html( isset( $row['pos'] ) ? (int) $row['pos'] : $row_index + 1 ); ?></th>
											<td>
												<figure class="liga-table-team-logo">
													<?php echo wp_kses_post( liga_get_team_logo_html( $row_team_id, array( 'class' => 'liga-team-logo liga-table-team-logo__image', 'size' => 'thumbnail' ) ) ); ?>
												</figure>
											</td>
											<td><?php echo esc_html( $row_team_name ); ?></td>
											<td><?php echo esc_html( isset( $row['pj'] ) ? (int) $row['pj'] : 0 ); ?></td>
											<td><?php echo esc_html( isset( $row['pg'] ) ? (int) $row['pg'] : 0 ); ?></td>
											<td><?php echo esc_html( isset( $row['pp'] ) ? (int) $row['pp'] : 0 ); ?></td>
											<td>
												<?php if ( isset( $row['inc'] ) && (int) $row['inc'] > 0 ) : ?>
													<span class="liga-inc-badge"><?php echo esc_html( (int) $row['inc'] ); ?></span>
												<?php else : ?>
													<?php echo esc_html( isset( $row['inc'] ) ? (int) $row['inc'] : 0 ); ?>
												<?php endif; ?>
											</td>
											<td><?php echo esc_html( isset( $row['pts'] ) ? (int) $row['pts'] : 0 ); ?></td>
											<td><?php echo esc_html( isset( $row['pf'] ) ? (int) $row['pf'] : 0 ); ?></td>
											<td><?php echo esc_html( isset( $row['pc'] ) ? (int) $row['pc'] : 0 ); ?></td>
											<td><?php echo esc_html( isset( $row['dif'] ) ? (int) $row['dif'] : 0 ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>

			<section class="liga-card liga-results-panel" aria-labelledby="liga-results-title">
				<div class="liga-section-head">
					<h3 class="liga-section-title" id="liga-results-title"><?php esc_html_e( 'Ultimos resultados', 'liga-basket-chile' ); ?></h3>
					<a class="liga-section-link" href="<?php echo esc_url( $results_link ); ?>"><?php esc_html_e( 'Ver todos', 'liga-basket-chile' ); ?></a>
				</div>

				<ul class="liga-results-list">
					<?php if ( empty( $results ) ) : ?>
						<li class="liga-results-item">
							<p><?php esc_html_e( 'Aun no hay partidos computables.', 'liga-basket-chile' ); ?></p>
						</li>
					<?php endif; ?>

					<?php foreach ( $results as $result ) : ?>
						<li class="liga-results-item">
							<article class="liga-card liga-result-card" aria-label="<?php echo esc_attr( sprintf( 'Resultado %s %s, %s', $result['home'], $result['score'], $result['away'] ) ); ?>">
								<header class="liga-result-head">
									<span class="liga-result-division"><?php echo esc_html( $result['division'] ); ?></span>
									<time class="liga-result-date" datetime="<?php echo esc_attr( $result['date'] ); ?>"><?php echo esc_html( $result['date_h'] ); ?></time>
								</header>
								<div class="liga-result-body">
									<figure class="liga-result-team-logo">
										<?php echo wp_kses_post( liga_get_team_logo_html( $result['home_id'], array( 'class' => 'liga-team-logo liga-result-team-logo__image', 'size' => 'thumbnail' ) ) ); ?>
									</figure>
									<p class="liga-result-score"><?php echo esc_html( $result['score'] ); ?></p>
									<figure class="liga-result-team-logo">
										<?php echo wp_kses_post( liga_get_team_logo_html( $result['away_id'], array( 'class' => 'liga-team-logo liga-result-team-logo__image', 'size' => 'thumbnail' ) ) ); ?>
									</figure>
								</div>
								<footer class="liga-result-foot">
									<span class="liga-result-status"><?php echo esc_html( $result['status'] ); ?></span>
								</footer>
							</article>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>

			<section class="liga-card liga-fixture-panel" aria-labelledby="liga-fixture-title">
				<div class="liga-section-head">
					<h3 class="liga-section-title" id="liga-fixture-title"><?php esc_html_e( 'Proximos partidos', 'liga-basket-chile' ); ?></h3>
					<a class="liga-section-link" href="<?php echo esc_url( $fixture_link ); ?>"><?php esc_html_e( 'Ver fixture', 'liga-basket-chile' ); ?></a>
				</div>

				<ul class="liga-fixture-list">
					<?php if ( empty( $fixtures ) ) : ?>
						<li class="liga-fixture-item">
							<p><?php esc_html_e( 'No hay partidos programados.', 'liga-basket-chile' ); ?></p>
						</li>
					<?php endif; ?>

					<?php foreach ( $fixtures as $fixture ) : ?>
						<li class="liga-fixture-item">
							<article class="liga-card liga-fixture-card">
								<header class="liga-fixture-head">
									<span class="liga-fixture-division"><?php echo esc_html( $fixture['division'] ); ?></span>
									<time class="liga-fixture-date" datetime="<?php echo esc_attr( $fixture['date'] ); ?>"><?php echo esc_html( $fixture['date_h'] ); ?></time>
								</header>
								<div class="liga-fixture-body">
									<p class="liga-fixture-time"><time datetime="<?php echo esc_attr( $fixture['datetime'] ); ?>"><?php echo esc_html( $fixture['time'] ); ?> HRS</time></p>
									<div class="liga-fixture-teams">
										<figure class="liga-fixture-team-logo">
											<?php echo wp_kses_post( liga_get_team_logo_html( $fixture['home_id'], array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?>
										</figure>
										<p class="liga-fixture-team-name"><?php echo esc_html( $fixture['home'] ); ?></p>
										<span class="liga-fixture-versus">vs</span>
										<p class="liga-fixture-team-name"><?php echo esc_html( $fixture['away'] ); ?></p>
										<figure class="liga-fixture-team-logo">
											<?php echo wp_kses_post( liga_get_team_logo_html( $fixture['away_id'], array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?>
										</figure>
									</div>
									<p class="liga-fixture-venue"><?php echo esc_html( $fixture['venue'] ); ?></p>
								</div>
							</article>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		</div>
	</div>
</section>
