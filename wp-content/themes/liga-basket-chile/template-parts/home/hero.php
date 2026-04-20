<?php
/**
 * Home hero section.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hero_eyebrow     = get_theme_mod( 'liga_hero_eyebrow', 'Temporada Regular 2025' );
$hero_line_one    = get_theme_mod( 'liga_hero_line_one', 'Se vive el basquet' );
$hero_line_two    = get_theme_mod( 'liga_hero_line_two', 'Se vive Concepcion' );
$hero_description = get_theme_mod( 'liga_hero_description', 'La mejor liga del sur de Chile. Talento, esfuerzo y pasion en cada partido.' );
$hero_cta_one     = get_theme_mod( 'liga_hero_cta_one_label', 'Ver partidos' );
$hero_cta_one_url = get_theme_mod( 'liga_hero_cta_one_url', home_url( '/partidos' ) );
$hero_cta_two     = get_theme_mod( 'liga_hero_cta_two_label', 'Conoce la liga' );
$hero_cta_two_url = get_theme_mod( 'liga_hero_cta_two_url', home_url( '/la-liga' ) );
$hero_image       = get_theme_mod( 'liga_hero_image', '' );

if ( empty( $hero_image ) ) {
	$hero_image = liga_svg_placeholder( 'Liga Concepcion', 1440, 900, '071c46', 'f7931e' );
}
?>
<section class="liga-hero" aria-labelledby="liga-hero-title">
	<div class="liga-container">
		<div class="liga-hero-layout">
			<div class="liga-hero-content">
				<p class="liga-hero-eyebrow"><?php echo esc_html( $hero_eyebrow ); ?></p>
				<h1 class="liga-hero-title" id="liga-hero-title">
					<span class="liga-hero-title-line"><?php echo esc_html( $hero_line_one ); ?></span>
					<span class="liga-hero-title-line"><?php echo esc_html( $hero_line_two ); ?></span>
				</h1>
				<p class="liga-hero-description"><?php echo esc_html( $hero_description ); ?></p>
				<div class="liga-hero-actions">
					<a class="liga-hero-cta-primary" href="<?php echo esc_url( $hero_cta_one_url ); ?>"><?php echo esc_html( $hero_cta_one ); ?></a>
					<a class="liga-hero-cta-secondary" href="<?php echo esc_url( $hero_cta_two_url ); ?>"><?php echo esc_html( $hero_cta_two ); ?></a>
				</div>
			</div>

			<figure class="liga-hero-media">
				<img class="liga-hero-image" src="<?php echo liga_escape_image_src( $hero_image ); ?>" alt="<?php esc_attr_e( 'Jugador de basquetbol en accion durante un partido de liga', 'liga-basket-chile' ); ?>">
			</figure>
		</div>
	</div>
</section>
