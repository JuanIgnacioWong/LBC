<?php
/**
 * Bloque de sponsors.
 *
 * @package LigaBasketChile
 */

$sponsors = get_option(
	'liga_sponsors_demo',
	array(
		'BioSport',
		'Concepcion Motors',
		'Andes Nutrition',
		'Clinica Sur',
		'Logistica 360',
		'Energy Court',
	)
);
?>
<section class="liga-section liga-sponsors" data-liga-reveal>
	<div class="liga-container liga-sponsors-wrap">
		<div class="liga-sponsors-head">
			<h2 class="liga-block-title"><?php esc_html_e( 'Sponsors Oficiales', 'liga-basket-chile' ); ?></h2>
			<a class="liga-btn liga-btn--ghost liga-btn--small" href="<?php echo esc_url( home_url( '/contacto' ) ); ?>">
				<?php esc_html_e( 'QUIERO SER SPONSOR', 'liga-basket-chile' ); ?>
			</a>
		</div>
		<div class="liga-sponsors-track" data-liga-marquee>
			<?php foreach ( $sponsors as $sponsor ) : ?>
				<div class="liga-sponsor-item"><?php echo esc_html( $sponsor ); ?></div>
			<?php endforeach; ?>
			<?php foreach ( $sponsors as $sponsor ) : ?>
				<div class="liga-sponsor-item" aria-hidden="true"><?php echo esc_html( $sponsor ); ?></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
