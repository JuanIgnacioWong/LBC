<?php
/**
 * Home sponsors section.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sponsors = array(
	array( 'name' => 'CMPC', 'url' => '#' ),
	array( 'name' => 'Clinica Meds', 'url' => '#' ),
	array( 'name' => 'Powerade', 'url' => '#' ),
	array( 'name' => 'Macron', 'url' => '#' ),
	array( 'name' => 'Passline', 'url' => '#' ),
);
?>
<section class="liga-sponsors" aria-labelledby="liga-sponsors-title">
	<div class="liga-container">
		<div class="liga-section-head">
			<h2 class="liga-section-title" id="liga-sponsors-title"><?php esc_html_e( 'Patrocinadores', 'liga-basket-chile' ); ?></h2>
			<a class="liga-section-link" href="<?php echo esc_url( home_url( '/sponsors' ) ); ?>"><?php esc_html_e( 'Quiero ser sponsor', 'liga-basket-chile' ); ?></a>
		</div>

		<ul class="liga-sponsors-list" aria-label="<?php esc_attr_e( 'Listado de patrocinadores oficiales', 'liga-basket-chile' ); ?>">
			<?php foreach ( $sponsors as $sponsor ) : ?>
				<li class="liga-sponsors-item">
					<a class="liga-sponsors-link" href="<?php echo esc_url( $sponsor['url'] ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Ir al sitio de %s', $sponsor['name'] ) ); ?>">
						<figure class="liga-sponsors-figure">
							<img class="liga-sponsors-logo" src="<?php echo liga_escape_image_src( liga_svg_placeholder( $sponsor['name'], 320, 120, 'ffffff', '0b2a66' ) ); ?>" alt="<?php echo esc_attr( sprintf( 'Logo %s', $sponsor['name'] ) ); ?>">
						</figure>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
