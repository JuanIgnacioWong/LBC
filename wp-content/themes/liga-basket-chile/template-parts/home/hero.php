<?php
/**
 * Home hero section.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hero_data = liga_get_home_hero_banner_data();
$slides = isset( $hero_data['slides'] ) && is_array( $hero_data['slides'] ) ? $hero_data['slides'] : array();

if ( empty( $slides ) ) {
	return;
}

$is_slider         = ! empty( $hero_data['is_slider'] );
$show_controls     = ! empty( $hero_data['show_controls'] );
$autoplay_enabled  = ! empty( $hero_data['autoplay'] );
$autoplay_interval = isset( $hero_data['autoplay_interval'] ) ? absint( $hero_data['autoplay_interval'] ) : 5000;
if ( $autoplay_interval < 2500 ) {
	$autoplay_interval = 5000;
}
?>
<section class="liga-hero" aria-labelledby="liga-hero-title">
	<div class="liga-container">
		<div
			class="liga-hero-layout <?php echo $is_slider ? 'has-multiple-slides' : 'has-single-slide'; ?>"
			role="region"
			aria-label="<?php esc_attr_e( 'Banner principal', 'liga-basket-chile' ); ?>"
			data-liga-hero-slider="<?php echo $is_slider ? '1' : '0'; ?>"
			data-liga-autoplay="<?php echo $autoplay_enabled ? '1' : '0'; ?>"
			data-liga-autoplay-interval="<?php echo esc_attr( (string) $autoplay_interval ); ?>"
		>
			<div class="liga-hero-slider">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php
					$is_first     = 0 === $index;
					$slide_id     = 'liga-hero-slide-' . (string) ( $index + 1 );
					$slide_title_id = $is_first ? 'liga-hero-title' : $slide_id . '-title';
					$slide_classes = array(
						'liga-hero-slide',
						'is-align-' . sanitize_html_class( (string) $slide['text_align'] ),
						'is-height-' . sanitize_html_class( (string) $slide['height'] ),
					);
					if ( ! empty( $slide['overlay'] ) ) {
						$slide_classes[] = 'has-overlay';
					}
					if ( ! empty( $slide['gradient'] ) ) {
						$slide_classes[] = 'has-gradient';
					}
					if ( $is_first ) {
						$slide_classes[] = 'is-active';
					}

					$image_attrs = array(
						'class'    => 'liga-hero-image liga-hero-layout__image',
						'loading'  => $is_first ? 'eager' : 'lazy',
						'decoding' => 'async',
						'alt'      => sanitize_text_field( (string) $slide['image_alt'] ),
					);
					if ( $is_first ) {
						$image_attrs['fetchpriority'] = 'high';
					}
					?>
					<article
						id="<?php echo esc_attr( $slide_id ); ?>"
						class="<?php echo esc_attr( implode( ' ', $slide_classes ) ); ?>"
						aria-hidden="<?php echo $is_first ? 'false' : 'true'; ?>"
					>
						<div class="liga-hero-content liga-hero-layout__content">
							<?php if ( ! empty( $slide['eyebrow'] ) ) : ?>
								<p class="liga-hero-eyebrow liga-hero-layout__eyebrow"><?php echo esc_html( (string) $slide['eyebrow'] ); ?></p>
							<?php endif; ?>
							<h1 class="liga-hero-title liga-hero-layout__title" id="<?php echo esc_attr( $slide_title_id ); ?>">
								<span class="liga-hero-title-line"><?php echo esc_html( (string) $slide['title_line_one'] ); ?></span>
								<?php if ( ! empty( $slide['title_line_two'] ) ) : ?>
									<span class="liga-hero-title-line"><?php echo esc_html( (string) $slide['title_line_two'] ); ?></span>
								<?php endif; ?>
							</h1>
							<p class="liga-hero-description liga-hero-layout__text"><?php echo wp_kses_post( (string) $slide['description'] ); ?></p>
							<div class="liga-hero-actions liga-hero-layout__actions">
								<a class="liga-hero-cta-primary liga-btn liga-btn--primary" href="<?php echo esc_url( (string) $slide['cta_primary_url'] ); ?>"><?php echo esc_html( (string) $slide['cta_primary_label'] ); ?></a>
								<a class="liga-hero-cta-secondary liga-btn liga-btn--secondary" href="<?php echo esc_url( (string) $slide['cta_secondary_url'] ); ?>"><?php echo esc_html( (string) $slide['cta_secondary_label'] ); ?></a>
							</div>
						</div>

						<figure class="liga-hero-media liga-hero-layout__media">
							<?php if ( ! empty( $slide['image_id'] ) ) : ?>
								<?php
									echo wp_kses_post(
										wp_get_attachment_image(
											(int) $slide['image_id'],
											'large',
											false,
											$image_attrs
										)
									);
								?>
							<?php else : ?>
								<img
									class="liga-hero-image liga-hero-layout__image"
									src="<?php echo liga_escape_image_src( (string) $slide['image_src'] ); ?>"
									alt="<?php echo esc_attr( (string) $slide['image_alt'] ); ?>"
									loading="<?php echo $is_first ? 'eager' : 'lazy'; ?>"
									decoding="async"
									<?php if ( $is_first ) : ?>
										fetchpriority="high"
									<?php endif; ?>
								>
							<?php endif; ?>
						</figure>
					</article>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_controls ) : ?>
				<div class="liga-hero-slider__controls" aria-hidden="false">
					<button type="button" class="liga-hero-slider__arrow liga-hero-slider__arrow--prev" aria-label="<?php esc_attr_e( 'Banner anterior', 'liga-basket-chile' ); ?>">
						<span aria-hidden="true">&lsaquo;</span>
					</button>
					<button type="button" class="liga-hero-slider__arrow liga-hero-slider__arrow--next" aria-label="<?php esc_attr_e( 'Banner siguiente', 'liga-basket-chile' ); ?>">
						<span aria-hidden="true">&rsaquo;</span>
					</button>
				</div>
				<div class="liga-hero-slider__dots" aria-label="<?php esc_attr_e( 'Navegacion de banners', 'liga-basket-chile' ); ?>">
					<?php foreach ( $slides as $index => $slide ) : ?>
						<?php $is_first = 0 === $index; ?>
						<button
							type="button"
							class="liga-hero-slider__dot<?php echo $is_first ? ' is-active' : ''; ?>"
							data-liga-slide-to="<?php echo esc_attr( (string) $index ); ?>"
							aria-label="<?php echo esc_attr( sprintf( __( 'Ir al banner %d', 'liga-basket-chile' ), $index + 1 ) ); ?>"
							aria-current="<?php echo $is_first ? 'true' : 'false'; ?>"
						></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

	</div>
</section>
