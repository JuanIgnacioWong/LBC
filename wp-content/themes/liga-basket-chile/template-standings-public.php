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

$build_team_short_name = static function ( $team_name ) {
	$normalized = strtoupper( remove_accents( wp_strip_all_tags( (string) $team_name ) ) );
	$tokens     = preg_split( '/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY );
	$abbr       = '';

	foreach ( (array) $tokens as $token ) {
		$clean_token = preg_replace( '/[^A-Z0-9]/', '', (string) $token );
		if ( '' === $clean_token ) {
			continue;
		}

		$abbr .= substr( $clean_token, 0, 1 );
		if ( strlen( $abbr ) >= 3 ) {
			break;
		}
	}

	if ( strlen( $abbr ) < 2 ) {
		$fallback = preg_replace( '/[^A-Z0-9]/', '', $normalized );
		$abbr     = substr( (string) $fallback, 0, 3 );
	}

	return '' !== $abbr ? $abbr : 'LB';
};

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

$get_team_data = static function ( $team_id ) use ( &$team_cache, $build_team_short_name ) {
	$team_id = absint( $team_id );
	if ( isset( $team_cache[ $team_id ] ) ) {
		return $team_cache[ $team_id ];
	}

	$name = '';
	if ( $team_id > 0 ) {
		$name_meta = trim( (string) get_post_meta( $team_id, 'liga_nombre_equipo', true ) );
		$name      = '' !== $name_meta ? $name_meta : get_the_title( $team_id );
	}

	$logo_src = '';
	$logo_id  = $team_id > 0 ? (int) get_post_meta( $team_id, 'liga_logo_equipo', true ) : 0;
	if ( $logo_id > 0 ) {
		$logo_src = (string) wp_get_attachment_image_url( $logo_id, 'thumbnail' );
		if ( '' === $logo_src ) {
			$logo_src = (string) wp_get_attachment_image_url( $logo_id, 'full' );
		}
	}

	$team_cache[ $team_id ] = array(
		'name' => $name,
		'abbr' => $build_team_short_name( $name ),
		'logo' => $logo_src,
	);

	return $team_cache[ $team_id ];
};

$division_id       = $is_valid ? (int) $context['division_id'] : 0;
$division_label    = $is_valid ? (string) $context['division_label'] : '';
$temporada         = $is_valid ? (string) $context['temporada'] : '';
$standings_data    = $is_valid ? liga_get_standings_by_division_and_season( $division_id, $temporada ) : array( 'tabla' => array() );
$standings_rows    = ( isset( $standings_data['tabla'] ) && is_array( $standings_data['tabla'] ) ) ? $standings_data['tabla'] : array();
$recent_results    = $is_valid ? liga_get_recent_played_matches_by_division_and_season( $division_id, $temporada, 6 ) : array();
$upcoming_matches  = $is_valid ? liga_get_upcoming_matches_by_division_and_season( $division_id, $temporada, 6 ) : array();
$related_divisions = $is_valid ? liga_get_related_division_links_for_season( $temporada, $division_id, 6 ) : array();
$related_seasons   = $is_valid ? liga_get_related_season_links_for_division( $division_id, $temporada, 6 ) : array();

$matches_archive = get_post_type_archive_link( 'partido' );
if ( ! $matches_archive ) {
	$matches_archive = home_url( '/partido' );
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

get_header();
?>
<section class="liga-section liga-standings-page-hero" aria-labelledby="liga-standings-page-title">
	<div class="liga-container">
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
						__( 'Clasificacion oficial de %1$s para la temporada %2$s, con resultados recientes y proximos encuentros del mismo contexto competitivo.', 'liga-basket-chile' ),
						$division_label,
						$temporada
					)
				);
				?>
			</p>

			<?php if ( ! empty( $related_divisions ) || ! empty( $related_seasons ) ) : ?>
				<div class="liga-standings-related-nav" aria-label="<?php esc_attr_e( 'Navegacion relacionada', 'liga-basket-chile' ); ?>">
					<?php if ( ! empty( $related_divisions ) ) : ?>
						<div class="liga-standings-related-block">
							<h2><?php esc_html_e( 'Otras divisiones', 'liga-basket-chile' ); ?></h2>
							<ul>
								<?php foreach ( $related_divisions as $related_link ) : ?>
									<li>
										<a href="<?php echo esc_url( (string) $related_link['url'] ); ?>"><?php echo esc_html( (string) $related_link['label'] ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $related_seasons ) ) : ?>
						<div class="liga-standings-related-block">
							<h2><?php esc_html_e( 'Otras temporadas', 'liga-basket-chile' ); ?></h2>
							<ul>
								<?php foreach ( $related_seasons as $related_link ) : ?>
									<li>
										<a href="<?php echo esc_url( (string) $related_link['url'] ); ?>"><?php echo esc_html( (string) $related_link['label'] ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<h1 class="liga-article__title liga-standings-page-title" id="liga-standings-page-title"><?php esc_html_e( 'Tabla no disponible', 'liga-basket-chile' ); ?></h1>
			<p class="liga-standings-page-subtitle"><?php esc_html_e( 'La division o temporada solicitada no existe o no es valida.', 'liga-basket-chile' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<?php if ( $is_valid ) : ?>
	<section class="liga-home-main-panels liga-standings-page-panels" aria-labelledby="liga-standings-page-title">
		<div class="liga-container">
			<div class="liga-grid liga-home-main-panels-grid">
				<section class="liga-card liga-standings-panel" aria-labelledby="liga-standings-title">
					<div class="liga-section-head">
						<h2 class="liga-section-title" id="liga-standings-title"><?php esc_html_e( 'Tabla de posiciones', 'liga-basket-chile' ); ?></h2>
					</div>

					<div class="liga-table-wrap">
						<table class="liga-table liga-standings-table">
							<caption class="liga-table-caption"><?php echo esc_html( sprintf( __( 'Tabla %1$s %2$s', 'liga-basket-chile' ), $division_label, $temporada ) ); ?></caption>
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
								<?php if ( empty( $standings_rows ) ) : ?>
									<tr>
										<td colspan="11"><?php esc_html_e( 'Sin resultados cargados para esta division y temporada.', 'liga-basket-chile' ); ?></td>
									</tr>
								<?php endif; ?>

								<?php foreach ( $standings_rows as $row_index => $row ) : ?>
									<?php
									$row_team_name = isset( $row['equipo'] ) ? (string) $row['equipo'] : '';
									$row_short     = $build_team_short_name( $row_team_name );
									$row_logo_id   = isset( $row['logo_id'] ) ? (int) $row['logo_id'] : 0;
									$row_logo_src  = '';

									if ( $row_logo_id > 0 ) {
										$row_logo_src = (string) wp_get_attachment_image_url( $row_logo_id, 'thumbnail' );
										if ( '' === $row_logo_src ) {
											$row_logo_src = (string) wp_get_attachment_image_url( $row_logo_id, 'full' );
										}
									}

									if ( '' === $row_logo_src ) {
										$row_logo_src = liga_svg_placeholder( $row_short, 64, 64, '0b2a66', 'ffffff' );
									}
									?>
									<tr class="<?php echo 0 === $row_index ? 'liga-row--leader' : ''; ?>">
										<th scope="row"><?php echo esc_html( isset( $row['pos'] ) ? (int) $row['pos'] : $row_index + 1 ); ?></th>
										<td>
											<figure class="liga-table-team-logo">
												<img src="<?php echo liga_escape_image_src( $row_logo_src ); ?>" alt="<?php echo esc_attr( sprintf( 'Logo %s', $row_team_name ) ); ?>">
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
				</section>

				<section class="liga-card liga-results-panel" aria-labelledby="liga-results-title">
					<div class="liga-section-head">
						<h2 class="liga-section-title" id="liga-results-title"><?php esc_html_e( 'Ultimos resultados', 'liga-basket-chile' ); ?></h2>
						<a class="liga-section-link" href="<?php echo esc_url( $results_link ); ?>"><?php esc_html_e( 'Ver todos', 'liga-basket-chile' ); ?></a>
					</div>

					<ul class="liga-results-list">
						<?php if ( empty( $recent_results ) ) : ?>
							<li class="liga-results-item">
								<p><?php esc_html_e( 'Aun no hay partidos jugados para este contexto.', 'liga-basket-chile' ); ?></p>
							</li>
						<?php endif; ?>

						<?php foreach ( $recent_results as $result ) : ?>
							<?php
							$home_team = $get_team_data( (int) $result['local_id'] );
							$away_team = $get_team_data( (int) $result['visita_id'] );
							$home_logo = '' !== $home_team['logo'] ? $home_team['logo'] : liga_svg_placeholder( $home_team['abbr'], 64, 64, '0b2a66', 'ffffff' );
							$away_logo = '' !== $away_team['logo'] ? $away_team['logo'] : liga_svg_placeholder( $away_team['abbr'], 64, 64, '071c46', 'f7931e' );
							?>
							<li class="liga-results-item">
								<article class="liga-card liga-result-card" aria-label="<?php echo esc_attr( sprintf( 'Resultado %s %d-%d %s', $home_team['name'], (int) $result['puntos_local'], (int) $result['puntos_visita'], $away_team['name'] ) ); ?>">
									<header class="liga-result-head">
										<span class="liga-result-division"><?php echo esc_html( $division_label ); ?></span>
										<time class="liga-result-date" datetime="<?php echo esc_attr( $format_datetime_attribute( $result['fecha'], $result['hora'] ) ); ?>"><?php echo esc_html( $format_date( $result['fecha'], 'd M Y' ) ); ?></time>
									</header>
									<div class="liga-result-body">
										<figure class="liga-result-team-logo">
											<img src="<?php echo liga_escape_image_src( $home_logo ); ?>" alt="<?php echo esc_attr( sprintf( 'Logo %s', $home_team['name'] ) ); ?>">
										</figure>
										<p class="liga-result-score"><?php echo esc_html( sprintf( '%d - %d', (int) $result['puntos_local'], (int) $result['puntos_visita'] ) ); ?></p>
										<figure class="liga-result-team-logo">
											<img src="<?php echo liga_escape_image_src( $away_logo ); ?>" alt="<?php echo esc_attr( sprintf( 'Logo %s', $away_team['name'] ) ); ?>">
										</figure>
									</div>
									<footer class="liga-result-foot">
										<span class="liga-result-status"><?php echo esc_html( $get_match_status_label( $result['estado'], $result['incomparecencia'] ) ); ?></span>
									</footer>
								</article>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>

				<section class="liga-card liga-fixture-panel" aria-labelledby="liga-fixture-title">
					<div class="liga-section-head">
						<h2 class="liga-section-title" id="liga-fixture-title"><?php esc_html_e( 'Proximos partidos', 'liga-basket-chile' ); ?></h2>
						<a class="liga-section-link" href="<?php echo esc_url( $fixture_link ); ?>"><?php esc_html_e( 'Ver fixture', 'liga-basket-chile' ); ?></a>
					</div>

					<ul class="liga-fixture-list">
						<?php if ( empty( $upcoming_matches ) ) : ?>
							<li class="liga-fixture-item">
								<p><?php esc_html_e( 'No hay partidos programados para este contexto.', 'liga-basket-chile' ); ?></p>
							</li>
						<?php endif; ?>

						<?php foreach ( $upcoming_matches as $fixture ) : ?>
							<?php
							$home_team = $get_team_data( (int) $fixture['local_id'] );
							$away_team = $get_team_data( (int) $fixture['visita_id'] );
							$home_logo = '' !== $home_team['logo'] ? $home_team['logo'] : liga_svg_placeholder( $home_team['abbr'], 64, 64, '0b2a66', 'ffffff' );
							$away_logo = '' !== $away_team['logo'] ? $away_team['logo'] : liga_svg_placeholder( $away_team['abbr'], 64, 64, '071c46', 'f7931e' );
							?>
							<li class="liga-fixture-item">
								<article class="liga-card liga-fixture-card">
									<header class="liga-fixture-head">
										<span class="liga-fixture-division"><?php echo esc_html( $division_label ); ?></span>
										<time class="liga-fixture-date" datetime="<?php echo esc_attr( $format_datetime_attribute( $fixture['fecha'], $fixture['hora'] ) ); ?>"><?php echo esc_html( $format_date( $fixture['fecha'], 'D d M Y' ) ); ?></time>
									</header>
									<div class="liga-fixture-body">
										<p class="liga-fixture-time"><time datetime="<?php echo esc_attr( $format_datetime_attribute( $fixture['fecha'], $fixture['hora'] ) ); ?>"><?php echo esc_html( $format_time( $fixture['hora'] ) ); ?> HRS</time></p>
										<div class="liga-fixture-teams">
											<figure class="liga-fixture-team-logo">
												<img src="<?php echo liga_escape_image_src( $home_logo ); ?>" alt="<?php echo esc_attr( sprintf( 'Logo %s', $home_team['name'] ) ); ?>">
											</figure>
											<p class="liga-fixture-team-name"><?php echo esc_html( $home_team['name'] ); ?></p>
											<span class="liga-fixture-versus">vs</span>
											<p class="liga-fixture-team-name"><?php echo esc_html( $away_team['name'] ); ?></p>
											<figure class="liga-fixture-team-logo">
												<img src="<?php echo liga_escape_image_src( $away_logo ); ?>" alt="<?php echo esc_attr( sprintf( 'Logo %s', $away_team['name'] ) ); ?>">
											</figure>
										</div>
										<p class="liga-fixture-venue"><?php echo esc_html( '' !== $fixture['recinto'] ? (string) $fixture['recinto'] : __( 'Cancha por definir', 'liga-basket-chile' ) ); ?></p>
									</div>
								</article>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			</div>
		</div>
	</section>
<?php endif; ?>
<?php
get_footer();
