<?php
/**
 * Public dynamic standings page by division + season.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$context = function_exists( 'liga_get_public_standings_request_context' )
	? liga_get_public_standings_request_context()
	: array();

$is_valid = ! empty( $context['is_valid'] );

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

	return '' !== trim( (string) $raw_time ) ? wp_date( 'Y-m-d\\TH:i', $timestamp ) : wp_date( 'Y-m-d', $timestamp );
};

$get_match_status_label = static function ( $status_key, $incomparecencia = 'ninguna' ) {
	$status_labels = array(
		'jugado'     => __( 'Jugado', 'liga-basket-chile' ),
		'finalizado' => __( 'Finalizado', 'liga-basket-chile' ),
		'programado' => __( 'Programado', 'liga-basket-chile' ),
		'suspendido' => __( 'Suspendido', 'liga-basket-chile' ),
		'cancelado'  => __( 'Cancelado', 'liga-basket-chile' ),
	);

	$status_key      = sanitize_key( (string) $status_key );
	$incomparecencia = sanitize_key( (string) $incomparecencia );

	if ( 'local_no_comparecio' === $incomparecencia ) {
		return __( 'Incomparecencia local', 'liga-basket-chile' );
	}

	if ( 'visita_no_comparecio' === $incomparecencia ) {
		return __( 'Incomparecencia visita', 'liga-basket-chile' );
	}

	if ( isset( $status_labels[ $status_key ] ) ) {
		return $status_labels[ $status_key ];
	}

	return '' !== $status_key ? ucwords( str_replace( '_', ' ', $status_key ) ) : '';
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

$division_id       = $is_valid ? (int) $context['division_id'] : 0;
$division_label    = $is_valid ? (string) $context['division_label'] : '';
$temporada         = $is_valid ? (string) $context['temporada'] : '';
$standings_data    = $is_valid ? liga_get_standings_by_division_and_season( $division_id, $temporada ) : array( 'tabla' => array() );
$standings_rows    = ( isset( $standings_data['tabla'] ) && is_array( $standings_data['tabla'] ) ) ? $standings_data['tabla'] : array();
$recent_results    = $is_valid ? liga_get_recent_played_matches_by_division_and_season( $division_id, $temporada, 4 ) : array();
$upcoming_matches  = $is_valid ? liga_get_upcoming_matches_by_division_and_season( $division_id, $temporada, 4 ) : array();
$related_divisions = $is_valid ? liga_get_related_division_links_for_season( $temporada, $division_id, 8 ) : array();
$related_seasons   = $is_valid ? liga_get_related_season_links_for_division( $division_id, $temporada, 8 ) : array();
$related_news      = $is_valid && function_exists( 'liga_get_related_news_for_standings_context' )
	? liga_get_related_news_for_standings_context( $division_id, $temporada, 4 )
	: array();

$current_division_link = $is_valid
	? array(
		'label' => $division_label,
		'url'   => liga_get_standings_public_url( $division_id, $temporada ),
	)
	: array();

$current_season_link = $is_valid
	? array(
		'label' => $temporada,
		'url'   => liga_get_standings_public_url( $division_id, $temporada ),
	)
	: array();

$division_links = $is_valid ? array_merge( array( $current_division_link ), $related_divisions ) : array();
$season_links   = $is_valid ? array_merge( array( $current_season_link ), $related_seasons ) : array();

$matches_archive = get_post_type_archive_link( 'partido' );
if ( ! $matches_archive ) {
	$matches_archive = home_url( '/partidos' );
}

$results_link = add_query_arg(
	array(
		'estado'    => 'finalizado',
		'division'  => $division_id,
		'temporada' => $temporada,
	),
	$matches_archive
);

$fixture_link = add_query_arg(
	array(
		'estado'    => 'programado',
		'division'  => $division_id,
		'temporada' => $temporada,
	),
	$matches_archive
);

$news_archive = get_permalink( get_option( 'page_for_posts' ) );
if ( ! $news_archive ) {
	$news_archive = home_url( '/noticias' );
}

get_header();
?>
<section class="liga-section liga-standings-page-hero" aria-labelledby="liga-standings-page-title">
	<div class="liga-container liga-standings-page__container">
		<nav class="liga-standings-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'liga-basket-chile' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'liga-basket-chile' ); ?></a>
			<span aria-hidden="true">/</span>
			<?php if ( $is_valid ) : ?>
				<a href="<?php echo esc_url( liga_get_default_public_standings_url( $temporada ) ); ?>"><?php esc_html_e( 'Tablas', 'liga-basket-chile' ); ?></a>
				<span aria-hidden="true">/</span>
				<span><?php echo esc_html( $division_label ); ?></span>
				<span aria-hidden="true">/</span>
				<span><?php echo esc_html( $temporada ); ?></span>
			<?php else : ?>
				<span><?php esc_html_e( 'Tabla de Posiciones', 'liga-basket-chile' ); ?></span>
			<?php endif; ?>
		</nav>

		<?php if ( $is_valid ) : ?>
			<h1 class="liga-article__title liga-standings-page-title" id="liga-standings-page-title"><?php echo esc_html( (string) $context['page_title'] ); ?></h1>
			<p class="liga-standings-page-subtitle">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: division label, 2: season */
						__( 'Clasificacion oficial de %1$s para la temporada %2$s.', 'liga-basket-chile' ),
						$division_label,
						$temporada
					)
				);
				?>
			</p>
		<?php else : ?>
			<h1 class="liga-article__title liga-standings-page-title" id="liga-standings-page-title"><?php esc_html_e( 'Tabla no disponible', 'liga-basket-chile' ); ?></h1>
			<p class="liga-standings-page-subtitle"><?php esc_html_e( 'La division o temporada solicitada no existe o no es valida.', 'liga-basket-chile' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php if ( $is_valid ) : ?>
	<main class="liga-standings-page" aria-labelledby="liga-standings-page-title">
		<div class="liga-container liga-standings-page__container">
			<section class="liga-standings-page__layout">
				<div class="liga-standings-page__main">
					<section class="liga-card liga-standings-panel" aria-labelledby="liga-standings-title">
						<div class="liga-section-head liga-standings-page__header-controls">
							<h2 class="liga-section-title" id="liga-standings-title"><?php esc_html_e( 'Tabla de posiciones', 'liga-basket-chile' ); ?></h2>
							<a class="liga-section-link" href="<?php echo esc_url( $results_link ); ?>"><?php esc_html_e( 'Resultados', 'liga-basket-chile' ); ?></a>
						</div>

						<div class="liga-standings-context-nav" aria-label="<?php esc_attr_e( 'Filtros de tabla', 'liga-basket-chile' ); ?>">
							<?php if ( ! empty( $division_links ) ) : ?>
								<div class="liga-standings-context-nav__group">
									<span class="liga-standings-context-nav__label"><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></span>
									<ul class="liga-standings-context-nav__list">
										<?php foreach ( $division_links as $division_link ) : ?>
											<?php
											$is_current_division = $division_link['label'] === $division_label;
											$link_class          = $is_current_division ? ' is-active' : '';
											?>
											<li>
												<a class="liga-standings-context-nav__link<?php echo esc_attr( $link_class ); ?>" href="<?php echo esc_url( (string) $division_link['url'] ); ?>"><?php echo esc_html( (string) $division_link['label'] ); ?></a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $season_links ) ) : ?>
								<div class="liga-standings-context-nav__group">
									<span class="liga-standings-context-nav__label"><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></span>
									<ul class="liga-standings-context-nav__list">
										<?php foreach ( $season_links as $season_link ) : ?>
											<?php
											$is_current_season = $season_link['label'] === $temporada;
											$link_class        = $is_current_season ? ' is-active' : '';
											?>
											<li>
												<a class="liga-standings-context-nav__link<?php echo esc_attr( $link_class ); ?>" href="<?php echo esc_url( (string) $season_link['url'] ); ?>"><?php echo esc_html( (string) $season_link['label'] ); ?></a>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>

						<div class="liga-table-wrap liga-standings-page__table">
							<table class="liga-table liga-standings-table">
								<caption class="liga-table-caption"><?php echo esc_html( sprintf( __( 'Tabla %1$s %2$s', 'liga-basket-chile' ), $division_label, $temporada ) ); ?></caption>
								<thead>
									<tr>
										<th scope="col"><?php esc_html_e( 'Pos', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'Equipo', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PJ', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PG', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PP', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'INC', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PF', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PC', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'DIF', 'liga-basket-chile' ); ?></th>
										<th scope="col"><?php esc_html_e( 'PTS', 'liga-basket-chile' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( empty( $standings_rows ) ) : ?>
										<tr>
											<td colspan="10"><?php esc_html_e( 'Sin resultados cargados para esta division y temporada.', 'liga-basket-chile' ); ?></td>
										</tr>
									<?php endif; ?>

									<?php foreach ( $standings_rows as $row_index => $row ) : ?>
										<?php
										$row_team_name = isset( $row['equipo'] ) ? (string) $row['equipo'] : '';
										$row_team_id   = isset( $row['equipo_id'] ) ? (int) $row['equipo_id'] : 0;
										?>
										<tr class="<?php echo 0 === $row_index ? 'liga-row--leader' : ''; ?>">
											<th scope="row"><?php echo esc_html( isset( $row['pos'] ) ? (int) $row['pos'] : $row_index + 1 ); ?></th>
											<td>
												<div class="liga-standings-team">
													<figure class="liga-table-team-logo">
														<?php echo wp_kses_post( liga_get_team_logo_html( $row_team_id, array( 'class' => 'liga-team-logo liga-table-team-logo__image', 'size' => 'thumbnail' ) ) ); ?>
													</figure>
													<span class="liga-standings-team__name"><?php echo esc_html( $row_team_name ); ?></span>
												</div>
											</td>
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
											<td><?php echo esc_html( isset( $row['pf'] ) ? (int) $row['pf'] : 0 ); ?></td>
											<td><?php echo esc_html( isset( $row['pc'] ) ? (int) $row['pc'] : 0 ); ?></td>
											<td><?php echo esc_html( isset( $row['dif'] ) ? (int) $row['dif'] : 0 ); ?></td>
											<td class="liga-standings-table__pts"><?php echo esc_html( isset( $row['pts'] ) ? (int) $row['pts'] : 0 ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</section>
				</div>

				<aside class="liga-standings-page__sidebar" aria-label="<?php esc_attr_e( 'Informacion complementaria', 'liga-basket-chile' ); ?>">
					<section class="liga-card liga-results-panel liga-standings-sidebar-widget liga-standings-page__widget" aria-labelledby="liga-sidebar-results-title">
						<div class="liga-section-head">
							<h2 class="liga-section-title" id="liga-sidebar-results-title"><?php esc_html_e( 'Ultimos resultados', 'liga-basket-chile' ); ?></h2>
							<a class="liga-section-link" href="<?php echo esc_url( $results_link ); ?>"><?php esc_html_e( 'Ver todos', 'liga-basket-chile' ); ?></a>
						</div>
						<ul class="liga-results-list">
							<?php if ( empty( $recent_results ) ) : ?>
								<li class="liga-results-item"><p><?php esc_html_e( 'Aun no hay partidos jugados para este contexto.', 'liga-basket-chile' ); ?></p></li>
							<?php endif; ?>
							<?php foreach ( $recent_results as $result ) : ?>
								<?php
								$home_team = $get_team_data( (int) $result['local_id'] );
								$away_team = $get_team_data( (int) $result['visita_id'] );
								?>
								<li class="liga-results-item">
									<article class="liga-card liga-result-card" aria-label="<?php echo esc_attr( sprintf( 'Resultado %s %d-%d %s', $home_team['name'], (int) $result['puntos_local'], (int) $result['puntos_visita'], $away_team['name'] ) ); ?>">
										<header class="liga-result-head">
											<span class="liga-result-division"><?php echo esc_html( $division_label ); ?></span>
											<time class="liga-result-date" datetime="<?php echo esc_attr( $format_datetime_attribute( $result['fecha'], $result['hora'] ) ); ?>"><?php echo esc_html( $format_date( $result['fecha'], 'd M Y' ) ); ?></time>
										</header>
										<div class="liga-result-body">
											<figure class="liga-result-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $home_team['id'], array( 'class' => 'liga-team-logo liga-result-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
											<strong class="liga-result-score"><?php echo esc_html( sprintf( '%d - %d', (int) $result['puntos_local'], (int) $result['puntos_visita'] ) ); ?></strong>
											<figure class="liga-result-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $away_team['id'], array( 'class' => 'liga-team-logo liga-result-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
										</div>
										<footer class="liga-result-foot">
											<span class="liga-result-status"><?php echo esc_html( $get_match_status_label( $result['estado'], $result['incomparecencia'] ) ); ?></span>
										</footer>
									</article>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>

					<section class="liga-card liga-fixture-panel liga-standings-sidebar-widget liga-standings-page__widget" aria-labelledby="liga-sidebar-fixture-title">
						<div class="liga-section-head">
							<h2 class="liga-section-title" id="liga-sidebar-fixture-title"><?php esc_html_e( 'Proximos partidos', 'liga-basket-chile' ); ?></h2>
							<a class="liga-section-link" href="<?php echo esc_url( $fixture_link ); ?>"><?php esc_html_e( 'Ver fixture', 'liga-basket-chile' ); ?></a>
						</div>
						<ul class="liga-fixture-list">
							<?php if ( empty( $upcoming_matches ) ) : ?>
								<li class="liga-fixture-item"><p><?php esc_html_e( 'No hay partidos programados para este contexto.', 'liga-basket-chile' ); ?></p></li>
							<?php endif; ?>
							<?php foreach ( $upcoming_matches as $fixture ) : ?>
								<?php
								$home_team = $get_team_data( (int) $fixture['local_id'] );
								$away_team = $get_team_data( (int) $fixture['visita_id'] );
								?>
								<li class="liga-fixture-item">
									<article class="liga-card liga-fixture-card">
										<header class="liga-fixture-head">
											<span class="liga-fixture-division"><?php echo esc_html( $division_label ); ?></span>
											<time class="liga-fixture-date" datetime="<?php echo esc_attr( $format_datetime_attribute( $fixture['fecha'], $fixture['hora'] ) ); ?>"><?php echo esc_html( $format_date( $fixture['fecha'], 'D d M Y' ) ); ?></time>
										</header>
										<div class="liga-fixture-body">
											<?php if ( '' !== $format_time( $fixture['hora'] ) ) : ?>
												<p class="liga-fixture-time"><time datetime="<?php echo esc_attr( $format_datetime_attribute( $fixture['fecha'], $fixture['hora'] ) ); ?>"><?php echo esc_html( $format_time( $fixture['hora'] ) ); ?> HRS</time></p>
											<?php endif; ?>
											<div class="liga-fixture-teams">
												<figure class="liga-fixture-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $home_team['id'], array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
												<p class="liga-fixture-team-name"><?php echo esc_html( $home_team['name'] ); ?></p>
												<span class="liga-fixture-versus">vs</span>
												<p class="liga-fixture-team-name"><?php echo esc_html( $away_team['name'] ); ?></p>
												<figure class="liga-fixture-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $away_team['id'], array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
											</div>
											<p class="liga-fixture-venue"><?php echo esc_html( '' !== $fixture['recinto'] ? (string) $fixture['recinto'] : __( 'Cancha por definir', 'liga-basket-chile' ) ); ?></p>
										</div>
									</article>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>

					<?php if ( ! empty( $related_divisions ) ) : ?>
						<section class="liga-card liga-standings-sidebar-widget liga-standings-sidebar-links liga-standings-page__widget" aria-labelledby="liga-sidebar-divisions-title">
							<div class="liga-section-head">
								<h2 class="liga-section-title" id="liga-sidebar-divisions-title"><?php esc_html_e( 'Otras divisiones', 'liga-basket-chile' ); ?></h2>
							</div>
							<ul class="liga-standings-sidebar-links__list">
								<?php foreach ( $related_divisions as $related_link ) : ?>
									<li><a href="<?php echo esc_url( (string) $related_link['url'] ); ?>"><?php echo esc_html( (string) $related_link['label'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<?php if ( ! empty( $related_news ) ) : ?>
						<section class="liga-card liga-standings-sidebar-widget liga-standings-sidebar-news liga-standings-page__widget" aria-labelledby="liga-sidebar-news-title">
							<div class="liga-section-head">
								<h2 class="liga-section-title" id="liga-sidebar-news-title"><?php esc_html_e( 'Ultimas noticias', 'liga-basket-chile' ); ?></h2>
								<a class="liga-section-link" href="<?php echo esc_url( $news_archive ); ?>"><?php esc_html_e( 'Ver noticias', 'liga-basket-chile' ); ?></a>
							</div>
							<ul class="liga-standings-sidebar-news__list">
								<?php foreach ( $related_news as $news_item ) : ?>
									<li>
										<a class="liga-standings-sidebar-news__item" href="<?php echo esc_url( (string) $news_item['url'] ); ?>">
											<strong><?php echo esc_html( (string) $news_item['title'] ); ?></strong>
											<time datetime="<?php echo esc_attr( (string) $news_item['date_iso'] ); ?>"><?php echo esc_html( (string) $news_item['date_label'] ); ?></time>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endif; ?>

					<section class="liga-card liga-standings-sidebar-widget liga-standings-legend liga-standings-page__widget" aria-labelledby="liga-sidebar-legend-title">
						<div class="liga-section-head">
							<h2 class="liga-section-title" id="liga-sidebar-legend-title"><?php esc_html_e( 'Criterios de tabla', 'liga-basket-chile' ); ?></h2>
						</div>
						<ul class="liga-standings-legend__list">
							<li><strong>PJ:</strong> <?php esc_html_e( 'Partidos jugados', 'liga-basket-chile' ); ?></li>
							<li><strong>PG:</strong> <?php esc_html_e( 'Partidos ganados', 'liga-basket-chile' ); ?></li>
							<li><strong>PP:</strong> <?php esc_html_e( 'Partidos perdidos', 'liga-basket-chile' ); ?></li>
							<li><strong>INC:</strong> <?php esc_html_e( 'Incomparecencias', 'liga-basket-chile' ); ?></li>
							<li><strong>PF:</strong> <?php esc_html_e( 'Puntos a favor', 'liga-basket-chile' ); ?></li>
							<li><strong>PC:</strong> <?php esc_html_e( 'Puntos en contra', 'liga-basket-chile' ); ?></li>
							<li><strong>DIF:</strong> <?php esc_html_e( 'Diferencia PF - PC', 'liga-basket-chile' ); ?></li>
							<li><strong>PTS:</strong> <?php esc_html_e( 'Puntaje reglamentario', 'liga-basket-chile' ); ?></li>
						</ul>
					</section>
				</aside>
			</section>
		</div>
	</main>
<?php endif; ?>
<?php
get_footer();
