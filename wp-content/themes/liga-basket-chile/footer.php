<?php
/**
 * Site footer.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );
$site_name = $site_name ? $site_name : 'Liga de Basquetbol Concepcion';

$brand_title       = trim( sanitize_text_field( (string) liga_get_theme_option( 'footer_brand_title', $site_name ) ) );
$brand_description = trim( sanitize_textarea_field( (string) liga_get_theme_option( 'footer_brand_description', liga_get_option( 'footer_texto', '' ) ) ) );
$brand_logo_id     = absint( liga_get_theme_option( 'footer_logo_id', 0 ) );
$brand_logo_image  = $brand_logo_id ? wp_get_attachment_image_src( $brand_logo_id, 'full' ) : false;
$brand_logo_src    = ( $brand_logo_image && ! empty( $brand_logo_image[0] ) ) ? $brand_logo_image[0] : liga_svg_placeholder( 'LBC', 220, 220, '071c46', 'f7931e' );
$brand_logo_alt    = $brand_logo_id ? get_post_meta( $brand_logo_id, '_wp_attachment_image_alt', true ) : 'Logo Liga de Basquetbol Concepcion';
$brand_logo_alt    = $brand_logo_alt ? $brand_logo_alt : 'Logo Liga de Basquetbol Concepcion';

$social_links = array(
	'Instagram' => trim( (string) liga_get_theme_option( 'footer_social_instagram', '' ) ),
	'X'         => trim( (string) liga_get_theme_option( 'footer_social_x', '' ) ),
	'YouTube'   => trim( (string) liga_get_theme_option( 'footer_social_youtube', '' ) ),
	'TikTok'    => trim( (string) liga_get_theme_option( 'footer_social_tiktok', '' ) ),
);

$contact_address_1 = trim( sanitize_text_field( (string) liga_get_theme_option( 'footer_contact_address_1', 'Gimnasio Municipal de Concepcion' ) ) );
$contact_address_2 = trim( sanitize_text_field( (string) liga_get_theme_option( 'footer_contact_address_2', 'J. Campos 775, Concepcion' ) ) );
$contact_email     = trim( sanitize_email( (string) liga_get_theme_option( 'footer_contact_email', 'info@ligaconcep.cl' ) ) );
$contact_phone     = trim( sanitize_text_field( (string) liga_get_theme_option( 'footer_contact_phone', '+56 9 1234 5678' ) ) );

$show_brand_block       = 1 === (int) liga_get_theme_option( 'footer_show_brand_block', 1 );
$show_footer_menu       = 1 === (int) liga_get_theme_option( 'footer_show_footer_menu', 1 );
$show_secondary_menu    = 1 === (int) liga_get_theme_option( 'footer_show_secondary_menu', 1 );
$show_contact_block     = 1 === (int) liga_get_theme_option( 'footer_show_contact_block', 1 );
$show_logo              = 1 === (int) liga_get_theme_option( 'footer_show_logo', 1 );
$show_brand_title       = 1 === (int) liga_get_theme_option( 'footer_show_brand_title', 1 );
$show_brand_description = 1 === (int) liga_get_theme_option( 'footer_show_brand_description', 0 );
$show_social            = 1 === (int) liga_get_theme_option( 'footer_show_social', 1 );
$show_contact           = 1 === (int) liga_get_theme_option( 'footer_show_contact', 1 );
$show_legal_strip       = 1 === (int) liga_get_theme_option( 'footer_show_legal_strip', 1 );

$social_links = array_filter(
	$social_links,
	static function ( $url ) {
		return '' !== trim( (string) $url );
	}
);

$has_brand_content = ( $show_logo && '' !== $brand_logo_src )
	|| ( $show_brand_title && '' !== $brand_title )
	|| ( $show_brand_description && '' !== $brand_description )
	|| ( $show_social && ! empty( $social_links ) );

$has_contact_content = '' !== $contact_address_1 || '' !== $contact_address_2 || '' !== $contact_email || '' !== $contact_phone;

$render_brand_block    = $show_brand_block && $has_brand_content;
$render_footer_menu    = $show_footer_menu;
$render_secondary_menu = $show_secondary_menu;
$render_contact_block  = $show_contact_block && $show_contact && $has_contact_content;

$visible_blocks = count(
	array_filter(
		array(
			$render_brand_block,
			$render_footer_menu,
			$render_secondary_menu,
			$render_contact_block,
		)
	)
);

$footer_grid_classes = array( 'liga-grid', 'liga-footer-grid' );
if ( 3 === $visible_blocks ) {
	$footer_grid_classes[] = 'liga-footer-grid--columns-3';
} elseif ( 2 === $visible_blocks ) {
	$footer_grid_classes[] = 'liga-footer-grid--columns-2';
} elseif ( 1 === $visible_blocks ) {
	$footer_grid_classes[] = 'liga-footer-grid--columns-1';
}

$copyright_text = trim( sanitize_text_field( (string) liga_get_theme_option( 'footer_copyright_text', '' ) ) );
if ( '' === $copyright_text ) {
	$copyright_text = sprintf(
		/* translators: 1: current year, 2: site name */
		__( '© %1$s %2$s. Todos los derechos reservados.', 'liga-basket-chile' ),
		gmdate( 'Y' ),
		$site_name
	);
}

$legal_extra_text = trim( sanitize_textarea_field( (string) liga_get_theme_option( 'footer_legal_extra_text', '' ) ) );

$footer_has_labelledby = $render_brand_block && $show_brand_title && '' !== $brand_title;
?>
<?php if ( ! is_front_page() ) : ?>
</main>
<?php endif; ?>

<footer class="liga-footer" <?php if ( $footer_has_labelledby ) : ?>aria-labelledby="liga-footer-brand-title"<?php else : ?>aria-label="<?php esc_attr_e( 'Footer del sitio', 'liga-basket-chile' ); ?>"<?php endif; ?>>
	<?php if ( $visible_blocks > 0 ) : ?>
	<div class="liga-container">
		<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $footer_grid_classes ) ) ); ?>">
			<?php if ( $render_brand_block ) : ?>
			<section class="liga-footer-block liga-footer-brand">
				<?php if ( $show_brand_title && '' !== $brand_title ) : ?>
				<h2 class="liga-footer-brand-title" id="liga-footer-brand-title"><?php echo esc_html( $brand_title ); ?></h2>
				<?php endif; ?>
				<?php if ( $show_logo ) : ?>
				<a class="liga-footer-brand-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Inicio Liga de Basquetbol Concepcion', 'liga-basket-chile' ); ?>">
					<figure class="liga-footer-brand-figure">
						<img class="liga-footer-brand-logo" src="<?php echo liga_escape_image_src( $brand_logo_src ); ?>" alt="<?php echo esc_attr( $brand_logo_alt ); ?>">
					</figure>
				</a>
				<?php endif; ?>
				<?php if ( $show_brand_description && '' !== $brand_description ) : ?>
				<p class="liga-footer-brand-description"><?php echo esc_html( $brand_description ); ?></p>
				<?php endif; ?>
				<?php if ( $show_social && ! empty( $social_links ) ) : ?>
				<ul class="liga-footer-social-list">
					<?php foreach ( $social_links as $label => $url ) : ?>
					<li class="liga-footer-social-item"><a class="liga-footer-social-link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</section>
			<?php endif; ?>

			<?php if ( $render_footer_menu ) : ?>
			<nav class="liga-footer-block liga-footer-nav" aria-label="<?php esc_attr_e( 'Navegacion footer', 'liga-basket-chile' ); ?>">
				<h2 class="liga-footer-block-title"><?php esc_html_e( 'Navegacion', 'liga-basket-chile' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'liga-footer-nav-list',
						'fallback_cb'    => 'liga_footer_menu_fallback',
					)
				);
				?>
			</nav>
			<?php endif; ?>

			<?php if ( $render_secondary_menu ) : ?>
			<section class="liga-footer-block liga-footer-league" aria-label="<?php esc_attr_e( 'Informacion de la liga', 'liga-basket-chile' ); ?>">
				<h2 class="liga-footer-block-title"><?php esc_html_e( 'La Liga', 'liga-basket-chile' ); ?></h2>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'secondary',
						'container'      => false,
						'menu_class'     => 'liga-footer-league-list',
						'fallback_cb'    => 'liga_secondary_menu_fallback',
					)
				);
				?>
			</section>
			<?php endif; ?>

			<?php if ( $render_contact_block ) : ?>
			<section class="liga-footer-block liga-footer-contact" aria-label="<?php esc_attr_e( 'Informacion de contacto', 'liga-basket-chile' ); ?>">
				<h2 class="liga-footer-block-title"><?php esc_html_e( 'Contacto', 'liga-basket-chile' ); ?></h2>
				<ul class="liga-footer-contact-list">
					<?php if ( '' !== $contact_address_1 ) : ?>
					<li class="liga-footer-contact-item"><?php echo esc_html( $contact_address_1 ); ?></li>
					<?php endif; ?>
					<?php if ( '' !== $contact_address_2 ) : ?>
					<li class="liga-footer-contact-item"><?php echo esc_html( $contact_address_2 ); ?></li>
					<?php endif; ?>
					<?php if ( '' !== $contact_email ) : ?>
					<li class="liga-footer-contact-item"><a class="liga-footer-contact-link" href="mailto:<?php echo esc_attr( antispambot( $contact_email ) ); ?>"><?php echo esc_html( antispambot( $contact_email ) ); ?></a></li>
					<?php endif; ?>
					<?php if ( '' !== $contact_phone ) : ?>
					<li class="liga-footer-contact-item"><a class="liga-footer-contact-link" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $contact_phone ) ); ?>"><?php echo esc_html( $contact_phone ); ?></a></li>
					<?php endif; ?>
				</ul>
			</section>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( $show_legal_strip ) : ?>
	<div class="liga-footer-legal-strip">
		<div class="liga-container">
			<div class="liga-footer-legal-inner">
				<div class="liga-footer-legal-copy-wrap">
					<p class="liga-footer-legal-copy"><?php echo esc_html( $copyright_text ); ?></p>
					<?php if ( '' !== $legal_extra_text ) : ?>
					<p class="liga-footer-legal-extra"><?php echo esc_html( $legal_extra_text ); ?></p>
					<?php endif; ?>
				</div>
				<nav class="liga-footer-legal-nav" aria-label="<?php esc_attr_e( 'Enlaces legales', 'liga-basket-chile' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'legal',
							'container'      => false,
							'menu_class'     => 'liga-footer-legal-list',
							'fallback_cb'    => 'liga_legal_menu_fallback',
						)
					);
					?>
				</nav>
			</div>
		</div>
	</div>
	<?php endif; ?>
</footer>
<?php wp_footer(); ?>
</body>
</html>
