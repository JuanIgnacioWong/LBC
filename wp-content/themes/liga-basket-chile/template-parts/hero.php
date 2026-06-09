<?php
/**
 * Hero principal.
 *
 * @package LigaBasketChile
 */

$hero_title    = liga_get_option( 'hero_title', 'SE VIVE EL BASQUET SE VIVE CONCEPCION' );
$hero_subtitle = liga_get_option( 'hero_subtitle', 'La competencia mas intensa del sur de Chile. Talento, esfuerzo y pasion en cada jornada.' );
$cta_primary   = liga_get_option( 'header_cta_label', 'Ver Partidos' );
$cta_primary_u = home_url( '/partidos' );
$cta_secondary = __( 'Conoce la Liga', 'liga-basket-chile' );
$cta_second_u  = home_url( '/la-liga' );
$hero_image    = liga_get_option( 'hero_image_url', '' );
?>
<section class="liga-section liga-hero" data-liga-reveal>
	<div class="liga-hero-overlay"></div>
	<div class="liga-container liga-hero-grid">
		<div class="liga-hero-copy">
			<p class="liga-eyebrow"><?php echo esc_html( sprintf( 'Temporada %s', liga_get_current_season_label() ) ); ?></p>
			<h1 class="liga-hero-title"><?php echo esc_html( $hero_title ); ?></h1>
			<p class="liga-hero-subtitle"><?php echo esc_html( $hero_subtitle ); ?></p>
			<div class="liga-hero-actions">
				<a class="liga-btn liga-btn--hero" href="<?php echo esc_url( $cta_primary_u ); ?>"><?php echo esc_html( $cta_primary ); ?></a>
				<a class="liga-btn liga-btn--ghost" href="<?php echo esc_url( $cta_second_u ); ?>"><?php echo esc_html( $cta_secondary ); ?></a>
			</div>
		</div>
		<div class="liga-hero-media">
			<?php if ( ! empty( $hero_image ) ) : ?>
				<img src="<?php echo liga_escape_image_src( $hero_image ); ?>" alt="<?php esc_attr_e( 'Jugador en accion', 'liga-basket-chile' ); ?>" loading="lazy" decoding="async">
			<?php else : ?>
				<div class="liga-hero-ball" aria-hidden="true"></div>
				<div class="liga-hero-silhouette" aria-hidden="true"></div>
			<?php endif; ?>
		</div>
	</div>
</section>
