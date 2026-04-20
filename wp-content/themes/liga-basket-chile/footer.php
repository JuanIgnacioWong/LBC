<?php
/**
 * Site footer.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$custom_logo_id    = get_theme_mod( 'custom_logo' );
$custom_logo_image = $custom_logo_id ? wp_get_attachment_image_src( $custom_logo_id, 'full' ) : false;
$brand_logo_src    = ( $custom_logo_image && ! empty( $custom_logo_image[0] ) ) ? $custom_logo_image[0] : liga_svg_placeholder( 'LBC', 220, 220, '071c46', 'f7931e' );
$brand_logo_alt    = $custom_logo_image ? get_post_meta( $custom_logo_id, '_wp_attachment_image_alt', true ) : 'Logo Liga de Basquetbol Concepcion';
$brand_logo_alt    = $brand_logo_alt ? $brand_logo_alt : 'Logo Liga de Basquetbol Concepcion';
$site_name         = get_bloginfo( 'name' );
$site_name         = $site_name ? $site_name : 'Liga de Basquetbol Concepcion';
$contact_address_1 = get_theme_mod( 'liga_contact_address_1', 'Gimnasio Municipal de Concepcion' );
$contact_address_2 = get_theme_mod( 'liga_contact_address_2', 'J. Campos 775, Concepcion' );
$contact_email     = get_theme_mod( 'liga_contact_email', 'info@ligaconcep.cl' );
$contact_phone     = get_theme_mod( 'liga_contact_phone', '+56 9 1234 5678' );
?>
<?php if ( ! is_front_page() ) : ?>
</main>
<?php endif; ?>

<footer class="liga-footer" aria-labelledby="liga-footer-brand-title">
	<div class="liga-container">
		<div class="liga-grid liga-footer-grid">
			<section class="liga-footer-block liga-footer-brand">
				<h2 class="liga-footer-brand-title" id="liga-footer-brand-title"><?php echo esc_html( $site_name ); ?></h2>
				<a class="liga-footer-brand-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Inicio Liga de Basquetbol Concepcion', 'liga-basket-chile' ); ?>">
					<figure class="liga-footer-brand-figure">
						<img class="liga-footer-brand-logo" src="<?php echo liga_escape_image_src( $brand_logo_src ); ?>" alt="<?php echo esc_attr( $brand_logo_alt ); ?>">
					</figure>
				</a>
				<ul class="liga-footer-social-list">
					<li class="liga-footer-social-item"><a class="liga-footer-social-link" href="<?php echo esc_url( get_theme_mod( 'liga_social_instagram', '#' ) ); ?>" target="_blank" rel="noopener noreferrer">Instagram</a></li>
					<li class="liga-footer-social-item"><a class="liga-footer-social-link" href="<?php echo esc_url( get_theme_mod( 'liga_social_x', '#' ) ); ?>" target="_blank" rel="noopener noreferrer">X</a></li>
					<li class="liga-footer-social-item"><a class="liga-footer-social-link" href="<?php echo esc_url( get_theme_mod( 'liga_social_youtube', '#' ) ); ?>" target="_blank" rel="noopener noreferrer">YouTube</a></li>
					<li class="liga-footer-social-item"><a class="liga-footer-social-link" href="<?php echo esc_url( get_theme_mod( 'liga_social_tiktok', '#' ) ); ?>" target="_blank" rel="noopener noreferrer">TikTok</a></li>
				</ul>
			</section>

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

			<section class="liga-footer-block liga-footer-contact" aria-label="<?php esc_attr_e( 'Informacion de contacto', 'liga-basket-chile' ); ?>">
				<h2 class="liga-footer-block-title"><?php esc_html_e( 'Contacto', 'liga-basket-chile' ); ?></h2>
				<ul class="liga-footer-contact-list">
					<li class="liga-footer-contact-item"><?php echo esc_html( $contact_address_1 ); ?></li>
					<li class="liga-footer-contact-item"><?php echo esc_html( $contact_address_2 ); ?></li>
					<li class="liga-footer-contact-item"><a class="liga-footer-contact-link" href="mailto:<?php echo antispambot( sanitize_email( $contact_email ) ); ?>"><?php echo esc_html( $contact_email ); ?></a></li>
					<li class="liga-footer-contact-item"><a class="liga-footer-contact-link" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $contact_phone ) ); ?>"><?php echo esc_html( $contact_phone ); ?></a></li>
				</ul>
			</section>
		</div>
	</div>

	<div class="liga-footer-legal-strip">
		<div class="liga-container">
			<div class="liga-footer-legal-inner">
				<p class="liga-footer-legal-copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $site_name ); ?>. <?php esc_html_e( 'Todos los derechos reservados.', 'liga-basket-chile' ); ?></p>
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
</footer>
<?php wp_footer(); ?>
</body>
</html>
