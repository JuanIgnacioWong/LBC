<?php
/**
 * Site header.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$season_label         = get_theme_mod( 'liga_season_label', gmdate( 'Y' ) );
$institutional_claim  = get_theme_mod( 'liga_institutional_claim', 'Pasion, Competencia y Comunidad.' );
$contact_label        = get_theme_mod( 'liga_top_contact_label', 'Contacto' );
$contact_url          = get_theme_mod( 'liga_top_contact_url', home_url( '/contacto' ) );
$admin_label          = get_theme_mod( 'liga_header_admin_label', 'Area Admin' );
$admin_url            = get_theme_mod( 'liga_header_admin_url', admin_url() );
$search_url           = add_query_arg( 's', '', home_url( '/' ) );
$site_name            = get_bloginfo( 'name' );
$site_name            = $site_name ? $site_name : 'Liga de Basquetbol Concepcion';
$custom_logo_id       = get_theme_mod( 'custom_logo' );
$custom_logo_image    = $custom_logo_id ? wp_get_attachment_image_src( $custom_logo_id, 'full' ) : false;
$brand_logo_src       = ( $custom_logo_image && ! empty( $custom_logo_image[0] ) ) ? $custom_logo_image[0] : liga_svg_placeholder( 'LBC', 220, 220, '071c46', 'f7931e' );
$brand_logo_alt       = $custom_logo_image ? get_post_meta( $custom_logo_id, '_wp_attachment_image_alt', true ) : 'Logo Liga de Basquetbol Concepcion';
$brand_logo_alt       = $brand_logo_alt ? $brand_logo_alt : 'Logo Liga de Basquetbol Concepcion';
$social_links         = array(
	'Instagram' => get_theme_mod( 'liga_social_instagram', '#' ),
	'X'         => get_theme_mod( 'liga_social_x', '#' ),
	'YouTube'   => get_theme_mod( 'liga_social_youtube', '#' ),
	'TikTok'    => get_theme_mod( 'liga_social_tiktok', '#' ),
);
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'liga-body' ); ?>>
<?php wp_body_open(); ?>
<a class="liga-skip-link screen-reader-text" href="#liga-main-content"><?php esc_html_e( 'Saltar al contenido principal', 'liga-basket-chile' ); ?></a>

<header class="liga-topbar" aria-label="Barra superior institucional">
	<div class="liga-container">
		<div class="liga-topbar-inner">
			<nav class="liga-topbar-social" aria-label="<?php esc_attr_e( 'Redes sociales', 'liga-basket-chile' ); ?>">
				<ul class="liga-topbar-social-list">
					<?php foreach ( $social_links as $social_label => $social_url ) : ?>
						<li class="liga-topbar-social-item">
							<a class="liga-topbar-social-link" href="<?php echo esc_url( $social_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( sprintf( 'Ir a %s', $social_label ) ); ?>">
								<?php echo esc_html( $social_label ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
			<p class="liga-topbar-season">
				<strong class="liga-topbar-season-label"><?php echo esc_html( sprintf( 'Temporada %s', $season_label ) ); ?></strong>
			</p>
			<p class="liga-topbar-claim"><?php echo esc_html( $institutional_claim ); ?></p>
			<a class="liga-topbar-contact-link" href="<?php echo esc_url( $contact_url ); ?>"><?php echo esc_html( $contact_label ); ?></a>
		</div>
	</div>
</header>

<header class="liga-header" id="site-header" aria-label="Cabecera principal">
	<div class="liga-container">
		<div class="liga-header-inner">
			<a class="liga-header-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Ir al inicio', 'liga-basket-chile' ); ?>">
				<figure class="liga-header-brand-figure">
					<img class="liga-header-brand-logo" src="<?php echo liga_escape_image_src( $brand_logo_src ); ?>" alt="<?php echo esc_attr( $brand_logo_alt ); ?>">
				</figure>
				<span class="liga-header-brand-name"><?php echo esc_html( $site_name ); ?></span>
			</a>

			<button class="liga-header-menu-button" type="button" aria-label="<?php esc_attr_e( 'Abrir menu principal', 'liga-basket-chile' ); ?>" aria-expanded="false" aria-controls="liga-primary-navigation">
				<span class="liga-header-menu-line" aria-hidden="true"></span>
				<span class="liga-header-menu-line" aria-hidden="true"></span>
				<span class="liga-header-menu-line" aria-hidden="true"></span>
			</button>

			<nav class="liga-header-nav" id="liga-primary-navigation" aria-label="<?php esc_attr_e( 'Navegacion principal', 'liga-basket-chile' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'liga-header-nav-list',
						'fallback_cb'    => 'liga_primary_menu_fallback',
					)
				);
				?>
			</nav>

			<div class="liga-header-actions">
				<a class="liga-header-search-button" href="<?php echo esc_url( $search_url ); ?>" aria-label="<?php esc_attr_e( 'Buscar en el sitio', 'liga-basket-chile' ); ?>">
					<span class="liga-header-search-icon" aria-hidden="true"><?php esc_html_e( 'Buscar', 'liga-basket-chile' ); ?></span>
				</a>
				<a class="liga-header-admin-cta" href="<?php echo esc_url( $admin_url ); ?>"><?php echo esc_html( $admin_label ); ?></a>
			</div>
		</div>
	</div>
</header>

<?php if ( ! is_front_page() ) : ?>
<main id="liga-main-content" class="liga-site-content">
<?php endif; ?>
