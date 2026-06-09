<?php
/**
 * Home sponsors section.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sponsors = function_exists( 'liga_get_home_sponsors' ) ? liga_get_home_sponsors( 12 ) : array();
$section_title = trim( (string) liga_get_option( 'sponsors_section_title', __( 'Participantes', 'liga-basket-chile' ) ) );
$section_title = '' !== $section_title ? $section_title : __( 'Participantes', 'liga-basket-chile' );
$cta_label     = trim( (string) liga_get_option( 'sponsors_cta_label', __( 'Participar en la competición', 'liga-basket-chile' ) ) );
$cta_label     = '' !== $cta_label ? $cta_label : __( 'Participar en la competición', 'liga-basket-chile' );
?>
<section class="liga-sponsors" aria-labelledby="liga-sponsors-title">
	<div class="liga-container">
		<div class="liga-section-head">
			<h2 class="liga-section-title" id="liga-sponsors-title"><?php echo esc_html( $section_title ); ?></h2>
			<a class="liga-section-link" href="<?php echo esc_url( home_url( '/sponsors' ) ); ?>"><?php echo esc_html( $cta_label ); ?></a>
		</div>

			<?php if ( ! empty( $sponsors ) ) : ?>
				<div class="liga-sponsors-carousel" data-liga-sponsors-carousel>
					<button class="liga-sponsors-nav liga-sponsors-nav--prev" type="button" aria-label="<?php esc_attr_e( 'Ver sponsors anteriores', 'liga-basket-chile' ); ?>">
						<span aria-hidden="true">&lsaquo;</span>
					</button>
					<ul class="liga-sponsors-list" aria-label="<?php esc_attr_e( 'Listado de patrocinadores oficiales', 'liga-basket-chile' ); ?>" data-liga-sponsors-track>
						<?php foreach ( $sponsors as $sponsor ) : ?>
							<?php
							$sponsor_id     = isset( $sponsor['id'] ) ? (int) $sponsor['id'] : 0;
							$sponsor_name   = isset( $sponsor['name'] ) ? (string) $sponsor['name'] : '';
							$sponsor_url    = isset( $sponsor['url'] ) ? (string) $sponsor['url'] : '';
							$sponsor_markup = function_exists( 'liga_render_sponsor_logo' ) ? liga_render_sponsor_logo( $sponsor_id, 'medium' ) : '';

							if ( '' === $sponsor_markup ) {
								continue;
							}
							?>
							<li class="liga-sponsors-item">
								<?php if ( '' !== $sponsor_url ) : ?>
									<a class="liga-sponsors-link" href="<?php echo esc_url( $sponsor_url ); ?>" target="_blank" rel="noopener noreferrer sponsored" aria-label="<?php echo esc_attr( sprintf( 'Ir al sitio de %s', $sponsor_name ) ); ?>">
										<figure class="liga-sponsors-figure">
											<?php echo wp_kses_post( $sponsor_markup ); ?>
										</figure>
									</a>
								<?php else : ?>
									<span class="liga-sponsors-link" aria-label="<?php echo esc_attr( sprintf( 'Sponsor %s', $sponsor_name ) ); ?>">
										<figure class="liga-sponsors-figure">
											<?php echo wp_kses_post( $sponsor_markup ); ?>
										</figure>
									</span>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<button class="liga-sponsors-nav liga-sponsors-nav--next" type="button" aria-label="<?php esc_attr_e( 'Ver sponsors siguientes', 'liga-basket-chile' ); ?>">
						<span aria-hidden="true">&rsaquo;</span>
					</button>
				</div>
			<?php endif; ?>
	</div>
</section>
