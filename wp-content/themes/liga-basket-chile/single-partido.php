<?php
/**
 * Plantilla single de partido.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main class="liga-match-single">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php
			the_post();

			$match_id = get_the_ID();

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
					return wp_date( 'd \d\e F \d\e Y', $timestamp );
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

				$permalink = '';
				if ( $team_id > 0 && 'equipo' === get_post_type( $team_id ) && 'publish' === get_post_status( $team_id ) ) {
					$permalink = (string) get_permalink( $team_id );
				}

				$team_cache[ $team_id ] = array(
					'id'        => $team_id,
					'name'      => '' !== $name ? $name : __( 'Equipo', 'liga-basket-chile' ),
					'permalink' => $permalink,
				);

				return $team_cache[ $team_id ];
			};

			$division_id = (int) get_post_meta( $match_id, 'liga_division', true );
			$temporada   = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_temporada', true ) ) );
			$status_key  = sanitize_key( (string) get_post_meta( $match_id, 'liga_estado_partido', true ) );
			$local_id    = (int) get_post_meta( $match_id, 'liga_equipo_local', true );
			$visita_id   = (int) get_post_meta( $match_id, 'liga_equipo_visita', true );
			$raw_date    = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_fecha_partido', true ) ) );
			$raw_time    = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_hora_partido', true ) ) );
			$venue       = trim( sanitize_text_field( (string) get_post_meta( $match_id, 'liga_cancha', true ) ) );
			$observations = trim( sanitize_textarea_field( (string) get_post_meta( $match_id, 'liga_observaciones', true ) ) );
			$walkover_key = sanitize_key( (string) get_post_meta( $match_id, 'liga_incomparecencia', true ) );

			if ( ! liga_is_valid_temporada_label( $temporada ) ) {
				$temporada = liga_normalize_temporada_label( '', liga_get_current_season_label() );
			}

			if ( function_exists( 'liga_validate_match_competition_context' ) ) {
				$context_validation = liga_validate_match_competition_context( $match_id );
				if ( ! is_wp_error( $context_validation ) ) {
					$division_id  = isset( $context_validation['division_id'] ) ? (int) $context_validation['division_id'] : $division_id;
					$temporada    = isset( $context_validation['temporada'] ) ? (string) $context_validation['temporada'] : $temporada;
					$local_id     = isset( $context_validation['local_id'] ) ? (int) $context_validation['local_id'] : $local_id;
					$visita_id    = isset( $context_validation['visita_id'] ) ? (int) $context_validation['visita_id'] : $visita_id;
					$walkover_key = isset( $context_validation['incomparecencia'] ) ? sanitize_key( (string) $context_validation['incomparecencia'] ) : $walkover_key;
				}
			}

			if ( ! in_array( $walkover_key, array( 'ninguna', 'local_no_comparecio', 'visita_no_comparecio' ), true ) ) {
				$walkover_key = 'ninguna';
			}

			$status_label   = $get_status_label( $status_key );
			$division_label = $get_division_label( $division_id );
			$match_title    = trim( sanitize_text_field( get_the_title() ) );

			$home_team = $get_team_data( $local_id );
			$away_team = $get_team_data( $visita_id );

			$score_local      = (int) get_post_meta( $match_id, 'liga_puntos_local', true );
			$score_visita     = (int) get_post_meta( $match_id, 'liga_puntos_visita', true );
			$has_score        = false;
			$score_evaluation = array();

			if ( function_exists( 'liga_is_match_countable_for_standings' ) ) {
				$score_evaluation = liga_is_match_countable_for_standings( $match_id, $division_id, $temporada );
				if ( ! empty( $score_evaluation['is_countable'] ) && ! empty( $score_evaluation['match'] ) && is_array( $score_evaluation['match'] ) ) {
					$score_local  = isset( $score_evaluation['match']['puntos_local'] ) ? (int) $score_evaluation['match']['puntos_local'] : $score_local;
					$score_visita = isset( $score_evaluation['match']['puntos_visita'] ) ? (int) $score_evaluation['match']['puntos_visita'] : $score_visita;
					$has_score    = true;
				}
			}

			if ( ! $has_score && in_array( $status_key, array( 'jugado', 'finalizado' ), true ) ) {
				if ( $score_local !== $score_visita && ( $score_local > 0 || $score_visita > 0 ) ) {
					$has_score = true;
				} elseif ( 'ninguna' !== $walkover_key && function_exists( 'liga_get_walkover_score' ) ) {
					$walkover    = liga_get_walkover_score( $walkover_key );
					$score_local = (int) $walkover['local'];
					$score_visita = (int) $walkover['visita'];
					$has_score   = true;
				}
			}

			$standings_url = '';
			if ( function_exists( 'liga_get_standings_public_url' ) && $division_id > 0 && liga_is_valid_temporada_label( $temporada ) ) {
				$standings_url = (string) liga_get_standings_public_url( $division_id, $temporada );
			}

			$matches_archive = get_post_type_archive_link( 'partido' );
			if ( ! $matches_archive ) {
				$matches_archive = home_url( '/partidos' );
			}

			$related_results = array();
			if ( function_exists( 'liga_get_recent_played_matches_by_division_and_season' ) && $division_id > 0 && liga_is_valid_temporada_label( $temporada ) ) {
				$result_rows = liga_get_recent_played_matches_by_division_and_season( $division_id, $temporada, 8 );
				foreach ( $result_rows as $row ) {
					$row_id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
					if ( $row_id <= 0 || $row_id === $match_id ) {
						continue;
					}

					$related_results[] = $row;
					if ( count( $related_results ) >= 4 ) {
						break;
					}
				}
			}

			$related_fixtures = array();
			if ( function_exists( 'liga_get_upcoming_matches_by_division_and_season' ) && $division_id > 0 && liga_is_valid_temporada_label( $temporada ) ) {
				$fixture_rows = liga_get_upcoming_matches_by_division_and_season( $division_id, $temporada, 8 );
				foreach ( $fixture_rows as $row ) {
					$row_id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
					if ( $row_id <= 0 || $row_id === $match_id ) {
						continue;
					}

					$related_fixtures[] = $row;
					if ( count( $related_fixtures ) >= 4 ) {
						break;
					}
				}
			}

			$hero_datetime = $format_datetime_attribute( $raw_date, $raw_time );
			$date_label    = $format_match_date( $raw_date );
			$time_label    = $format_match_time( $raw_time );
			$has_walkover  = 'ninguna' !== $walkover_key;
			?>
			<section class="liga-section liga-match-hero" aria-labelledby="liga-match-title-<?php echo esc_attr( (string) $match_id ); ?>">
				<div class="liga-container">
					<nav class="liga-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'liga-basket-chile' ); ?>">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Inicio', 'liga-basket-chile' ); ?></a>
						<span class="liga-breadcrumb__separator" aria-hidden="true">/</span>
						<a href="<?php echo esc_url( $matches_archive ); ?>"><?php esc_html_e( 'Partidos', 'liga-basket-chile' ); ?></a>
						<?php if ( '' !== $division_label ) : ?>
							<span class="liga-breadcrumb__separator" aria-hidden="true">/</span>
							<?php if ( '' !== $standings_url ) : ?>
								<a href="<?php echo esc_url( $standings_url ); ?>"><?php echo esc_html( $division_label ); ?></a>
							<?php else : ?>
								<span><?php echo esc_html( $division_label ); ?></span>
							<?php endif; ?>
						<?php endif; ?>
					</nav>

					<div class="liga-match-hero__meta" aria-label="<?php esc_attr_e( 'Contexto del partido', 'liga-basket-chile' ); ?>">
						<span class="liga-match-hero__chip"><?php echo esc_html( $division_label ); ?></span>
						<span class="liga-match-hero__chip"><?php echo esc_html( $temporada ); ?></span>
						<span class="liga-match-hero__chip liga-match-hero__chip--status"><?php echo esc_html( $status_label ); ?></span>
					</div>

					<h1 class="liga-match-title" id="liga-match-title-<?php echo esc_attr( (string) $match_id ); ?>">
						<?php echo esc_html( '' !== $match_title ? $match_title : sprintf( '%s vs %s', $home_team['name'], $away_team['name'] ) ); ?>
					</h1>

					<div class="liga-match-scoreboard" role="group" aria-label="<?php esc_attr_e( 'Marcador principal', 'liga-basket-chile' ); ?>">
						<div class="liga-match-team liga-match-team--home">
							<figure class="liga-match-team__logo">
								<?php echo wp_kses_post( liga_get_team_logo_html( $home_team['id'], array( 'class' => 'liga-team-logo liga-match-team__logo-image', 'size' => 'thumbnail' ) ) ); ?>
							</figure>
							<?php if ( '' !== $home_team['permalink'] ) : ?>
								<h2 class="liga-match-team__name"><a href="<?php echo esc_url( $home_team['permalink'] ); ?>"><?php echo esc_html( $home_team['name'] ); ?></a></h2>
							<?php else : ?>
								<h2 class="liga-match-team__name"><?php echo esc_html( $home_team['name'] ); ?></h2>
							<?php endif; ?>
						</div>

						<div class="liga-match-score" aria-live="polite">
							<?php if ( $has_score ) : ?>
								<span class="liga-match-score__number"><?php echo esc_html( (string) $score_local ); ?></span>
								<span class="liga-match-score__separator" aria-hidden="true">-</span>
								<span class="liga-match-score__number"><?php echo esc_html( (string) $score_visita ); ?></span>
							<?php else : ?>
								<span class="liga-match-score__vs"><?php esc_html_e( 'VS', 'liga-basket-chile' ); ?></span>
							<?php endif; ?>

							<?php if ( $has_walkover && isset( $walkover_labels[ $walkover_key ] ) ) : ?>
								<small class="liga-match-score__note"><?php echo esc_html( $walkover_labels[ $walkover_key ] ); ?></small>
							<?php endif; ?>
						</div>

						<div class="liga-match-team liga-match-team--away">
							<figure class="liga-match-team__logo">
								<?php echo wp_kses_post( liga_get_team_logo_html( $away_team['id'], array( 'class' => 'liga-team-logo liga-match-team__logo-image', 'size' => 'thumbnail' ) ) ); ?>
							</figure>
							<?php if ( '' !== $away_team['permalink'] ) : ?>
								<h2 class="liga-match-team__name"><a href="<?php echo esc_url( $away_team['permalink'] ); ?>"><?php echo esc_html( $away_team['name'] ); ?></a></h2>
							<?php else : ?>
								<h2 class="liga-match-team__name"><?php echo esc_html( $away_team['name'] ); ?></h2>
							<?php endif; ?>
						</div>
					</div>

					<div class="liga-match-hero__facts">
						<div class="liga-match-hero__fact">
							<span class="liga-match-hero__fact-label"><?php esc_html_e( 'Fecha', 'liga-basket-chile' ); ?></span>
							<?php if ( '' !== $hero_datetime ) : ?>
								<time datetime="<?php echo esc_attr( $hero_datetime ); ?>"><?php echo esc_html( '' !== $date_label ? $date_label : __( 'Por definir', 'liga-basket-chile' ) ); ?></time>
							<?php else : ?>
								<span><?php echo esc_html( '' !== $date_label ? $date_label : __( 'Por definir', 'liga-basket-chile' ) ); ?></span>
							<?php endif; ?>
						</div>
						<div class="liga-match-hero__fact">
							<span class="liga-match-hero__fact-label"><?php esc_html_e( 'Hora', 'liga-basket-chile' ); ?></span>
							<?php if ( '' !== $time_label && '' !== $hero_datetime ) : ?>
								<time datetime="<?php echo esc_attr( $hero_datetime ); ?>"><?php echo esc_html( $time_label ); ?> HRS</time>
							<?php else : ?>
								<span><?php echo esc_html( '' !== $time_label ? $time_label : __( 'Por definir', 'liga-basket-chile' ) ); ?></span>
							<?php endif; ?>
						</div>
						<div class="liga-match-hero__fact">
							<span class="liga-match-hero__fact-label"><?php esc_html_e( 'Recinto', 'liga-basket-chile' ); ?></span>
							<span><?php echo esc_html( '' !== $venue ? $venue : __( 'Recinto por definir', 'liga-basket-chile' ) ); ?></span>
						</div>
					</div>

					<?php if ( '' !== $standings_url ) : ?>
						<div class="liga-match-hero__actions">
							<a class="liga-btn liga-btn--ghost" href="<?php echo esc_url( $standings_url ); ?>"><?php esc_html_e( 'Ver tabla de posiciones', 'liga-basket-chile' ); ?></a>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<section class="liga-section liga-match-single__body">
				<div class="liga-container liga-match-single__layout">
					<article class="liga-match-single__main">
						<section class="liga-card liga-match-details" aria-labelledby="liga-match-details-title-<?php echo esc_attr( (string) $match_id ); ?>">
							<div class="liga-section-head">
								<h2 class="liga-section-title" id="liga-match-details-title-<?php echo esc_attr( (string) $match_id ); ?>"><?php esc_html_e( 'Detalles del partido', 'liga-basket-chile' ); ?></h2>
							</div>
							<dl class="liga-match-details__list">
								<div class="liga-match-details__row">
									<dt><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></dt>
									<dd><?php echo esc_html( $division_label ); ?></dd>
								</div>
								<div class="liga-match-details__row">
									<dt><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></dt>
									<dd><?php echo esc_html( $temporada ); ?></dd>
								</div>
								<div class="liga-match-details__row">
									<dt><?php esc_html_e( 'Estado', 'liga-basket-chile' ); ?></dt>
									<dd><?php echo esc_html( $status_label ); ?></dd>
								</div>
								<div class="liga-match-details__row">
									<dt><?php esc_html_e( 'Fecha', 'liga-basket-chile' ); ?></dt>
									<dd><?php echo esc_html( '' !== $date_label ? $date_label : __( 'Por definir', 'liga-basket-chile' ) ); ?></dd>
								</div>
								<div class="liga-match-details__row">
									<dt><?php esc_html_e( 'Hora', 'liga-basket-chile' ); ?></dt>
									<dd><?php echo esc_html( '' !== $time_label ? $time_label : __( 'Por definir', 'liga-basket-chile' ) ); ?></dd>
								</div>
								<div class="liga-match-details__row">
									<dt><?php esc_html_e( 'Recinto', 'liga-basket-chile' ); ?></dt>
									<dd><?php echo esc_html( '' !== $venue ? $venue : __( 'Recinto por definir', 'liga-basket-chile' ) ); ?></dd>
								</div>
								<?php if ( $has_walkover && isset( $walkover_labels[ $walkover_key ] ) ) : ?>
									<div class="liga-match-details__row liga-match-details__row--alert">
										<dt><?php esc_html_e( 'Incomparecencia', 'liga-basket-chile' ); ?></dt>
										<dd><?php echo esc_html( $walkover_labels[ $walkover_key ] ); ?></dd>
									</div>
								<?php endif; ?>
							</dl>
						</section>

						<?php if ( '' !== $observations ) : ?>
							<section class="liga-card liga-match-observations" aria-labelledby="liga-match-observations-title-<?php echo esc_attr( (string) $match_id ); ?>">
								<div class="liga-section-head">
									<h2 class="liga-section-title" id="liga-match-observations-title-<?php echo esc_attr( (string) $match_id ); ?>"><?php esc_html_e( 'Observaciones', 'liga-basket-chile' ); ?></h2>
								</div>
								<p><?php echo esc_html( $observations ); ?></p>
							</section>
						<?php endif; ?>
					</article>

					<aside class="liga-match-single__sidebar" aria-label="<?php esc_attr_e( 'Informacion relacionada', 'liga-basket-chile' ); ?>">
						<section class="liga-card liga-match-widget liga-match-widget--standings" aria-labelledby="liga-match-standings-widget-title-<?php echo esc_attr( (string) $match_id ); ?>">
							<div class="liga-section-head">
								<h2 class="liga-section-title" id="liga-match-standings-widget-title-<?php echo esc_attr( (string) $match_id ); ?>"><?php esc_html_e( 'Tabla relacionada', 'liga-basket-chile' ); ?></h2>
							</div>
							<?php if ( '' !== $standings_url ) : ?>
								<p class="liga-match-widget__text"><?php echo esc_html( sprintf( __( 'Sigue la clasificacion oficial de %1$s %2$s.', 'liga-basket-chile' ), $division_label, $temporada ) ); ?></p>
								<a class="liga-section-link" href="<?php echo esc_url( $standings_url ); ?>"><?php esc_html_e( 'Ir a tabla de posiciones', 'liga-basket-chile' ); ?></a>
							<?php else : ?>
								<p class="liga-match-widget__text"><?php esc_html_e( 'No hay una tabla publica disponible para este contexto.', 'liga-basket-chile' ); ?></p>
							<?php endif; ?>
						</section>

						<section class="liga-card liga-match-widget liga-match-widget--results" aria-labelledby="liga-match-results-widget-title-<?php echo esc_attr( (string) $match_id ); ?>">
							<div class="liga-section-head">
								<h2 class="liga-section-title" id="liga-match-results-widget-title-<?php echo esc_attr( (string) $match_id ); ?>"><?php esc_html_e( 'Ultimos resultados', 'liga-basket-chile' ); ?></h2>
								<a class="liga-section-link" href="<?php echo esc_url( add_query_arg( array( 'estado' => 'finalizado', 'division' => $division_id, 'temporada' => $temporada ), $matches_archive ) ); ?>"><?php esc_html_e( 'Ver todos', 'liga-basket-chile' ); ?></a>
							</div>

							<ul class="liga-results-list">
								<?php if ( empty( $related_results ) ) : ?>
									<li class="liga-results-item"><p class="liga-match-widget__text"><?php esc_html_e( 'Aun no hay resultados relacionados.', 'liga-basket-chile' ); ?></p></li>
								<?php endif; ?>

								<?php foreach ( $related_results as $result_row ) : ?>
									<?php
									$result_id      = isset( $result_row['id'] ) ? absint( $result_row['id'] ) : 0;
									$result_local   = isset( $result_row['local_id'] ) ? absint( $result_row['local_id'] ) : 0;
									$result_visita  = isset( $result_row['visita_id'] ) ? absint( $result_row['visita_id'] ) : 0;
									$result_home    = $get_team_data( $result_local );
									$result_away    = $get_team_data( $result_visita );
									$result_date_label = $format_match_date( isset( $result_row['fecha'] ) ? $result_row['fecha'] : '' );
									$result_date_attr  = $format_datetime_attribute( isset( $result_row['fecha'] ) ? $result_row['fecha'] : '', isset( $result_row['hora'] ) ? $result_row['hora'] : '' );
									?>
									<li class="liga-results-item">
										<a href="<?php echo esc_url( get_permalink( $result_id ) ); ?>" class="liga-match-widget__match-link">
											<article class="liga-card liga-result-card" aria-label="<?php echo esc_attr( sprintf( 'Resultado %s %d - %d %s', $result_home['name'], (int) $result_row['puntos_local'], (int) $result_row['puntos_visita'], $result_away['name'] ) ); ?>">
												<header class="liga-result-head">
													<span class="liga-result-division"><?php echo esc_html( $division_label ); ?></span>
													<time class="liga-result-date" datetime="<?php echo esc_attr( $result_date_attr ); ?>"><?php echo esc_html( $result_date_label ); ?></time>
												</header>
												<div class="liga-result-body">
													<figure class="liga-result-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $result_home['id'], array( 'class' => 'liga-team-logo liga-result-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
													<p class="liga-result-score"><?php echo esc_html( sprintf( '%d - %d', (int) $result_row['puntos_local'], (int) $result_row['puntos_visita'] ) ); ?></p>
													<figure class="liga-result-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $result_away['id'], array( 'class' => 'liga-team-logo liga-result-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
												</div>
												<footer class="liga-result-foot"><span class="liga-result-status"><?php echo esc_html( $get_status_label( isset( $result_row['estado'] ) ? $result_row['estado'] : '' ) ); ?></span></footer>
											</article>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>

						<section class="liga-card liga-match-widget liga-match-widget--fixtures" aria-labelledby="liga-match-fixtures-widget-title-<?php echo esc_attr( (string) $match_id ); ?>">
							<div class="liga-section-head">
								<h2 class="liga-section-title" id="liga-match-fixtures-widget-title-<?php echo esc_attr( (string) $match_id ); ?>"><?php esc_html_e( 'Proximos partidos', 'liga-basket-chile' ); ?></h2>
								<a class="liga-section-link" href="<?php echo esc_url( add_query_arg( array( 'estado' => 'programado', 'division' => $division_id, 'temporada' => $temporada ), $matches_archive ) ); ?>"><?php esc_html_e( 'Ver fixture', 'liga-basket-chile' ); ?></a>
							</div>

							<ul class="liga-fixture-list">
								<?php if ( empty( $related_fixtures ) ) : ?>
									<li class="liga-fixture-item"><p class="liga-match-widget__text"><?php esc_html_e( 'No hay partidos programados relacionados.', 'liga-basket-chile' ); ?></p></li>
								<?php endif; ?>

								<?php foreach ( $related_fixtures as $fixture_row ) : ?>
									<?php
									$fixture_id      = isset( $fixture_row['id'] ) ? absint( $fixture_row['id'] ) : 0;
									$fixture_local   = isset( $fixture_row['local_id'] ) ? absint( $fixture_row['local_id'] ) : 0;
									$fixture_visita  = isset( $fixture_row['visita_id'] ) ? absint( $fixture_row['visita_id'] ) : 0;
									$fixture_home    = $get_team_data( $fixture_local );
									$fixture_away    = $get_team_data( $fixture_visita );
									$fixture_date_label = $format_match_date( isset( $fixture_row['fecha'] ) ? $fixture_row['fecha'] : '' );
									$fixture_time_label = $format_match_time( isset( $fixture_row['hora'] ) ? $fixture_row['hora'] : '' );
									$fixture_datetime = $format_datetime_attribute( isset( $fixture_row['fecha'] ) ? $fixture_row['fecha'] : '', isset( $fixture_row['hora'] ) ? $fixture_row['hora'] : '' );
									$fixture_venue = isset( $fixture_row['recinto'] ) ? trim( sanitize_text_field( (string) $fixture_row['recinto'] ) ) : '';
									?>
									<li class="liga-fixture-item">
										<a href="<?php echo esc_url( get_permalink( $fixture_id ) ); ?>" class="liga-match-widget__match-link">
											<article class="liga-card liga-fixture-card">
												<header class="liga-fixture-head">
													<span class="liga-fixture-division"><?php echo esc_html( $division_label ); ?></span>
													<time class="liga-fixture-date" datetime="<?php echo esc_attr( $fixture_datetime ); ?>"><?php echo esc_html( $fixture_date_label ); ?></time>
												</header>
												<div class="liga-fixture-body">
													<?php if ( '' !== $fixture_time_label ) : ?>
														<p class="liga-fixture-time"><time datetime="<?php echo esc_attr( $fixture_datetime ); ?>"><?php echo esc_html( $fixture_time_label ); ?> HRS</time></p>
													<?php endif; ?>
													<div class="liga-fixture-teams">
														<figure class="liga-fixture-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $fixture_home['id'], array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
														<p class="liga-fixture-team-name"><?php echo esc_html( $fixture_home['name'] ); ?></p>
														<span class="liga-fixture-versus">vs</span>
														<p class="liga-fixture-team-name"><?php echo esc_html( $fixture_away['name'] ); ?></p>
														<figure class="liga-fixture-team-logo"><?php echo wp_kses_post( liga_get_team_logo_html( $fixture_away['id'], array( 'class' => 'liga-team-logo liga-fixture-team-logo__image', 'size' => 'thumbnail' ) ) ); ?></figure>
													</div>
													<p class="liga-fixture-venue"><?php echo esc_html( '' !== $fixture_venue ? $fixture_venue : __( 'Recinto por definir', 'liga-basket-chile' ) ); ?></p>
												</div>
											</article>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					</aside>
				</div>
			</section>
		<?php endwhile; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
