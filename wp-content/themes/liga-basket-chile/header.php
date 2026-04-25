<?php
/**
 * Site header.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$legacy_login_label   = get_theme_mod( 'liga_header_admin_label', 'Iniciar sesión' );
$legacy_login_url     = get_theme_mod( 'liga_header_admin_url', wp_login_url() );
$main_menu_location   = has_nav_menu( 'menu_principal' ) ? 'menu_principal' : 'primary';
$login_enabled        = (int) liga_get_option( 'header_login_enabled', 1 );
$login_label          = trim( (string) liga_get_option( 'header_login_label', $legacy_login_label ) );
$login_url_raw        = trim( (string) liga_get_option( 'header_login_url', $legacy_login_url ) );
$login_label          = '' !== $login_label ? $login_label : 'Iniciar sesión';
$login_url            = '' !== $login_url_raw ? $login_url_raw : wp_login_url();
$custom_logo_id       = get_theme_mod( 'custom_logo' );
$custom_logo_image    = $custom_logo_id ? wp_get_attachment_image_src( $custom_logo_id, 'full' ) : false;
$brand_logo_src       = ( $custom_logo_image && ! empty( $custom_logo_image[0] ) ) ? $custom_logo_image[0] : liga_svg_placeholder( 'LBC', 220, 220, '071c46', 'f7931e' );
$brand_logo_alt       = $custom_logo_image ? get_post_meta( $custom_logo_id, '_wp_attachment_image_alt', true ) : 'Logo Liga de Basquetbol Concepcion';
$brand_logo_alt       = $brand_logo_alt ? $brand_logo_alt : 'Logo Liga de Basquetbol Concepcion';
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
			<nav class="liga-topbar-social liga-topbar-nav" aria-label="<?php esc_attr_e( 'Menú superior', 'liga-basket-chile' ); ?>">
				<?php
				$topbar_menu_walker = class_exists( 'Liga_Topbar_Menu_Walker' ) ? new Liga_Topbar_Menu_Walker() : '';
				wp_nav_menu(
					array(
						'theme_location' => 'liga_topbar_menu',
						'container'      => false,
						'menu_class'     => 'liga-topbar-social-list liga-topbar__menu',
						'fallback_cb'    => 'liga_topbar_menu_fallback',
						'walker'         => $topbar_menu_walker,
					)
				);
				?>
			</nav>
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
						'theme_location' => $main_menu_location,
						'container'      => false,
						'menu_class'     => 'liga-header-nav-list',
						'fallback_cb'    => 'liga_primary_menu_fallback',
					)
				);
				?>
			</nav>

			<div class="liga-header-actions">
				<button class="liga-header-search-button" type="button" aria-label="<?php esc_attr_e( 'Abrir buscador del sitio', 'liga-basket-chile' ); ?>" aria-expanded="false" aria-controls="liga-header-search-panel">
					<svg class="liga-header-search-icon" viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false">
						<circle cx="11" cy="11" r="7"></circle>
						<line x1="16.65" y1="16.65" x2="21" y2="21"></line>
					</svg>
					<span class="screen-reader-text"><?php esc_html_e( 'Buscar', 'liga-basket-chile' ); ?></span>
				</button>
				<?php if ( $login_enabled ) : ?>
					<a class="liga-header-admin-cta" href="<?php echo esc_url( $login_url ); ?>"><?php echo esc_html( $login_label ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<div id="liga-header-search-panel" class="liga-header-search-panel" hidden>
			<form class="liga-header-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="screen-reader-text" for="liga-header-search-input"><?php esc_html_e( 'Buscar en la liga', 'liga-basket-chile' ); ?></label>
				<input id="liga-header-search-input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Buscar en la liga...', 'liga-basket-chile' ); ?>">
				<button class="liga-header-search-submit" type="submit"><?php esc_html_e( 'Buscar', 'liga-basket-chile' ); ?></button>
			</form>
		</div>
	</div>
</header>

<?php if ( ! is_front_page() ) : ?>
<main id="liga-main-content" class="liga-site-content">
<?php endif; ?>
