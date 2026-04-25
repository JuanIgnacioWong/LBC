<?php
/**
 * Soporte de iconos por item para el menu superior.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta key para almacenar el icono del item de menu.
 */
const LIGA_TOPBAR_ICON_META_KEY = '_liga_topbar_icon';

/**
 * Retorna el catalogo de iconos disponibles para la topbar.
 *
 * @return array<int, string>
 */
function liga_get_topbar_icon_catalog() {
	return array(
		'sports_basketball',
		'emoji_events',
		'calendar_month',
		'event',
		'schedule',
		'leaderboard',
		'scoreboard',
		'groups',
		'group',
		'person',
		'stadium',
		'location_on',
		'map',
		'home',
		'newspaper',
		'campaign',
		'mail',
		'phone',
		'language',
		'public',
		'link',
		'info',
		'contact_support',
		'admin_panel_settings',
		'login',
		'logout',
		'search',
		'favorite',
		'star',
		'bolt',
		'local_fire_department',
		'notifications',
		'photo_camera',
		'videocam',
		'play_circle',
		'smart_display',
		'share',
		'alternate_email',
		'facebook',
		'chat_bubble',
		'chat_bubble_outline',
		'work',
		'music_note',
		'rss_feed',
		'sports',
		'sports_soccer',
		'sports_volleyball',
		'fitness_center',
		'military_tech',
		'workspace_premium',
		'flag',
		'verified',
		'check_circle',
		'radio_button_checked',
		'arrow_forward',
		'chevron_right',
		'open_in_new',
		'download',
		'upload',
		'edit',
		'settings',
		'dashboard',
		'analytics',
		'bar_chart',
		'insights',
		'timeline',
		'trending_up',
		'trending_down',
		'history',
		'update',
		'today',
		'date_range',
		'access_time',
		'timer',
		'place',
		'pin_drop',
		'near_me',
		'support_agent',
		'forum',
		'chat',
		'sms',
		'send',
		'person_add',
		'diversity_3',
		'handshake',
		'thumb_up',
		'celebration',
		'event_available',
		'event_note',
		'sports_score',
	);
}

/**
 * Genera etiqueta legible para el selector de iconos.
 *
 * @param string $icon Nombre del icono.
 * @return string
 */
function liga_get_topbar_icon_label( $icon ) {
	$icon = (string) $icon;

	$social_labels = array(
		'photo_camera'       => 'Instagram',
		'alternate_email'    => 'X / Twitter',
		'smart_display'      => 'YouTube',
		'facebook'           => 'Facebook',
		'chat'               => 'WhatsApp',
		'send'               => 'Telegram',
		'forum'              => 'Discord',
		'work'               => 'LinkedIn',
		'music_note'         => 'TikTok',
		'chat_bubble'        => 'Messenger',
		'chat_bubble_outline'=> 'Mensajes (outline)',
		'rss_feed'           => 'RSS',
	);

	if ( isset( $social_labels[ $icon ] ) ) {
		return $social_labels[ $icon ];
	}

	return ucwords( str_replace( '_', ' ', $icon ) );
}

/**
 * Retorna lista de redes sociales destacadas para acceso rapido.
 *
 * @return array<int, string>
 */
function liga_get_topbar_popular_social_icons() {
	return array(
		'photo_camera',
		'facebook',
		'smart_display',
		'alternate_email',
		'chat',
		'send',
		'work',
		'music_note',
		'forum',
		'chat_bubble',
	);
}

/**
 * Retorna datos SVG de marcas oficiales para redes sociales populares.
 *
 * SVGs basados en Bootstrap Icons (MIT), usando currentColor.
 *
 * @return array<string, array<string, mixed>>
 */
function liga_get_topbar_official_social_svg_data() {
	return array(
		'photo_camera'    => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334',
			),
		),
		'facebook'        => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951',
			),
		),
		'smart_display'   => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M8.051 1.999h.089c.822.003 4.987.033 6.11.335a2.01 2.01 0 0 1 1.415 1.42c.101.38.172.883.22 1.402l.01.104.022.26.008.104c.065.914.073 1.77.074 1.957v.075c-.001.194-.01 1.108-.082 2.06l-.008.105-.009.104c-.05.572-.124 1.14-.235 1.558a2.01 2.01 0 0 1-1.415 1.42c-1.16.312-5.569.334-6.18.335h-.142c-.309 0-1.587-.006-2.927-.052l-.17-.006-.087-.004-.171-.007-.171-.007c-1.11-.049-2.167-.128-2.654-.26a2.01 2.01 0 0 1-1.415-1.419c-.111-.417-.185-.986-.235-1.558L.09 9.82l-.008-.104A31 31 0 0 1 0 7.68v-.123c.002-.215.01-.958.064-1.778l.007-.103.003-.052.008-.104.022-.26.01-.104c.048-.519.119-1.023.22-1.402a2.01 2.01 0 0 1 1.415-1.42c.487-.13 1.544-.21 2.654-.26l.17-.007.172-.006.086-.003.171-.007A100 100 0 0 1 7.858 2z',
				'M6.4 5.209v4.818l4.157-2.408z',
			),
		),
		'alternate_email' => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z',
			),
		),
		'chat'            => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232',
			),
		),
		'send'            => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.287 5.906q-1.168.486-4.666 2.01-.567.225-.595.442c-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294q.39.01.868-.32 3.269-2.206 3.374-2.23c.05-.012.12-.026.166.016s.042.12.037.141c-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8 8 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629q.14.092.27.187c.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.4 1.4 0 0 0-.013-.315.34.34 0 0 0-.114-.217.53.53 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09',
			),
		),
		'work'            => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z',
			),
		),
		'music_note'      => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M9 0h1.98c.144.715.54 1.617 1.235 2.512C12.895 3.389 13.797 4 15 4v2c-1.753 0-3.07-.814-4-1.829V11a5 5 0 1 1-5-5v2a3 3 0 1 0 3 3z',
			),
		),
		'forum'           => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M13.545 2.907a13.2 13.2 0 0 0-3.257-1.011.05.05 0 0 0-.052.025c-.141.25-.297.577-.406.833a12.2 12.2 0 0 0-3.658 0 8 8 0 0 0-.412-.833.05.05 0 0 0-.052-.025c-1.125.194-2.22.534-3.257 1.011a.04.04 0 0 0-.021.018C.356 6.024-.213 9.047.066 12.032q.003.022.021.037a13.3 13.3 0 0 0 3.995 2.02.05.05 0 0 0 .056-.019q.463-.63.818-1.329a.05.05 0 0 0-.01-.059l-.018-.011a9 9 0 0 1-1.248-.595.05.05 0 0 1-.02-.066l.015-.019q.127-.095.248-.195a.05.05 0 0 1 .051-.007c2.619 1.196 5.454 1.196 8.041 0a.05.05 0 0 1 .053.007q.121.1.248.195a.05.05 0 0 1-.004.085 8 8 0 0 1-1.249.594.05.05 0 0 0-.03.03.05.05 0 0 0 .003.041c.24.465.515.909.817 1.329a.05.05 0 0 0 .056.019 13.2 13.2 0 0 0 4.001-2.02.05.05 0 0 0 .021-.037c.334-3.451-.559-6.449-2.366-9.106a.03.03 0 0 0-.02-.019m-8.198 7.307c-.789 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.45.73 1.438 1.613 0 .888-.637 1.612-1.438 1.612m5.316 0c-.788 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.451.73 1.438 1.613 0 .888-.631 1.612-1.438 1.612',
			),
		),
		'chat_bubble'     => array(
			'viewBox' => '0 0 16 16',
			'paths'   => array(
				'M0 7.76C0 3.301 3.493 0 8 0s8 3.301 8 7.76-3.493 7.76-8 7.76c-.81 0-1.586-.107-2.316-.307a.64.64 0 0 0-.427.03l-1.588.702a.64.64 0 0 1-.898-.566l-.044-1.423a.64.64 0 0 0-.215-.456C.956 12.108 0 10.092 0 7.76m5.546-1.459-2.35 3.728c-.225.358.214.761.551.506l2.525-1.916a.48.48 0 0 1 .578-.002l1.869 1.402a1.2 1.2 0 0 0 1.735-.32l2.35-3.728c.226-.358-.214-.761-.551-.506L9.728 7.381a.48.48 0 0 1-.578.002L7.281 5.98a1.2 1.2 0 0 0-1.735.32z',
			),
		),
	);
}

/**
 * Renderiza SVG oficial para redes sociales populares del topbar.
 *
 * @param string $icon       Icono guardado.
 * @param string $class_name Clases CSS adicionales.
 * @return string
 */
function liga_get_topbar_official_social_icon_svg( $icon, $class_name = '' ) {
	$icon      = liga_sanitize_topbar_icon( $icon );
	$icon_data = liga_get_topbar_official_social_svg_data();

	if ( '' === $icon || ! isset( $icon_data[ $icon ] ) ) {
		return '';
	}

	$data   = $icon_data[ $icon ];
	$viewbox = isset( $data['viewBox'] ) ? (string) $data['viewBox'] : '0 0 16 16';
	$paths   = isset( $data['paths'] ) && is_array( $data['paths'] ) ? $data['paths'] : array();

	if ( empty( $paths ) ) {
		return '';
	}

	$svg_classes = trim( 'liga-brand-icon ' . (string) $class_name );
	$svg         = '<svg xmlns="http://www.w3.org/2000/svg" class="' . esc_attr( $svg_classes ) . '" viewBox="' . esc_attr( $viewbox ) . '" aria-hidden="true" focusable="false">';

	foreach ( $paths as $path_d ) {
		$svg .= '<path d="' . esc_attr( (string) $path_d ) . '" />';
	}

	$svg .= '</svg>';

	return $svg;
}

/**
 * Sanitiza el icono elegido contra el catalogo permitido.
 *
 * @param string $icon Valor recibido.
 * @return string
 */
function liga_sanitize_topbar_icon( $icon ) {
	$icon = strtolower( trim( (string) $icon ) );
	$icon = str_replace( '-', '_', $icon );
	$icon = preg_replace( '/[^a-z0-9_]/', '', $icon );
	$icon = is_string( $icon ) ? $icon : '';

	if ( '' === $icon ) {
		return '';
	}

	static $allowed = null;
	if ( null === $allowed ) {
		$allowed = array_fill_keys( liga_get_topbar_icon_catalog(), true );
	}

	return isset( $allowed[ $icon ] ) ? $icon : '';
}

/**
 * Obtiene el ID del menu asignado a la ubicacion topbar.
 *
 * @return int
 */
function liga_get_topbar_menu_id() {
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	return isset( $locations['liga_topbar_menu'] ) ? (int) $locations['liga_topbar_menu'] : 0;
}

/**
 * Obtiene el ID del menu "Menú Topbar" por nombre (si existe).
 *
 * @return int
 */
function liga_get_topbar_named_menu_id() {
	$menu_obj = wp_get_nav_menu_object( 'Menú Topbar' );
	return ( $menu_obj instanceof WP_Term ) ? (int) $menu_obj->term_id : 0;
}

/**
 * Verifica si el menu parece ser de topbar por nombre o slug.
 *
 * @param int $menu_id ID del menu.
 * @return bool
 */
function liga_menu_looks_like_topbar( $menu_id ) {
	$menu_id = (int) $menu_id;
	if ( $menu_id <= 0 ) {
		return false;
	}

	$menu_obj = wp_get_nav_menu_object( $menu_id );
	if ( ! ( $menu_obj instanceof WP_Term ) ) {
		return false;
	}

	$name = sanitize_title( (string) $menu_obj->name );
	$slug = sanitize_title( (string) $menu_obj->slug );

	if ( false !== strpos( $name, 'topbar' ) || false !== strpos( $slug, 'topbar' ) ) {
		return true;
	}

	return false !== strpos( $name, 'barra-superior' ) || false !== strpos( $slug, 'barra-superior' );
}

/**
 * Obtiene el ID del menu abierto en Apariencia > Menus.
 *
 * @return int
 */
function liga_get_current_admin_menu_id() {
	$menu_id = isset( $_REQUEST['menu'] ) ? absint( wp_unslash( $_REQUEST['menu'] ) ) : 0;

	if ( $menu_id <= 0 && isset( $GLOBALS['_nav_menu_selected_id'] ) ) {
		$menu_id = absint( $GLOBALS['_nav_menu_selected_id'] );
	}

	if ( $menu_id <= 0 && isset( $GLOBALS['nav_menu_selected_id'] ) ) {
		$menu_id = absint( $GLOBALS['nav_menu_selected_id'] );
	}

	if ( $menu_id <= 0 && function_exists( 'get_user_option' ) ) {
		$menu_id = absint( get_user_option( 'nav_menu_recently_edited' ) );
	}

	return $menu_id;
}

/**
 * Determina si estamos editando exactamente el menu topbar en admin.
 *
 * @return bool
 */
function liga_is_topbar_menu_admin_context() {
	if ( ! is_admin() ) {
		return false;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'nav-menus' !== $screen->id ) {
		return false;
	}

	$current_menu_id = liga_get_current_admin_menu_id();
	if ( $current_menu_id <= 0 ) {
		return false;
	}

	return liga_is_topbar_icon_target_menu( $current_menu_id );
}

/**
 * Define si un menu dado admite el meta de iconos topbar.
 *
 * @param int $menu_id ID del menu.
 * @return bool
 */
function liga_is_topbar_icon_target_menu( $menu_id ) {
	$menu_id = (int) $menu_id;
	if ( $menu_id <= 0 ) {
		return false;
	}

	$assigned_topbar_id = liga_get_topbar_menu_id();
	if ( $assigned_topbar_id > 0 && $menu_id === $assigned_topbar_id ) {
		return true;
	}

	if ( $assigned_topbar_id <= 0 && is_admin() ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'nav-menus' === $screen->id ) {
			$current_menu_id = liga_get_current_admin_menu_id();
			if ( $current_menu_id > 0 && $menu_id === $current_menu_id ) {
				return true;
			}
		}
	}

	$named_topbar_id = liga_get_topbar_named_menu_id();
	if ( $named_topbar_id > 0 && $menu_id === $named_topbar_id ) {
		return true;
	}

	return liga_menu_looks_like_topbar( $menu_id );
}

/**
 * Encola fuente Material Symbols sin duplicarla.
 *
 * @return void
 */
function liga_enqueue_material_symbols_font() {
	if ( wp_style_is( 'liga-material-symbols-outlined', 'enqueued' ) ) {
		return;
	}

	wp_enqueue_style(
		'liga-material-symbols-outlined',
		'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap',
		array(),
		null
	);
}

/**
 * Encola assets del selector visual solo en la pantalla de menus.
 *
 * @param string $hook_suffix Hook actual del admin.
 * @return void
 */
function liga_enqueue_topbar_icon_admin_assets( $hook_suffix ) {
	if ( 'nav-menus.php' !== $hook_suffix ) {
		return;
	}

	if ( ! liga_is_topbar_menu_admin_context() ) {
		return;
	}

	liga_enqueue_material_symbols_font();

	wp_enqueue_style(
		'liga-admin-menu-icons',
		get_template_directory_uri() . '/assets/css/admin-menu-icons.css',
		array(),
		liga_asset_version( 'assets/css/admin-menu-icons.css' )
	);

	wp_enqueue_script(
		'liga-admin-menu-icons',
		get_template_directory_uri() . '/assets/js/admin-menu-icons.js',
		array(),
		liga_asset_version( 'assets/js/admin-menu-icons.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'liga_enqueue_topbar_icon_admin_assets' );

/**
 * Determina si el menu topbar tiene al menos un icono configurado.
 *
 * @return bool
 */
function liga_topbar_menu_has_icons() {
	static $has_icons = null;

	if ( null !== $has_icons ) {
		return $has_icons;
	}

	$has_icons = false;
	$menu_id   = liga_get_topbar_menu_id();
	if ( $menu_id <= 0 ) {
		return $has_icons;
	}

	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) ) {
		return $has_icons;
	}

	foreach ( $items as $item ) {
		$item_id = isset( $item->ID ) ? (int) $item->ID : 0;
		if ( $item_id <= 0 ) {
			continue;
		}

		$icon = liga_sanitize_topbar_icon( (string) get_post_meta( $item_id, LIGA_TOPBAR_ICON_META_KEY, true ) );
		if ( '' !== $icon ) {
			$has_icons = true;
			break;
		}
	}

	return $has_icons;
}

/**
 * Encola fuente de iconos en frontend solo si la topbar usa iconos.
 *
 * @return void
 */
function liga_enqueue_topbar_icon_front_assets() {
	if ( ! liga_topbar_menu_has_icons() ) {
		return;
	}

	liga_enqueue_material_symbols_font();
}
add_action( 'wp_enqueue_scripts', 'liga_enqueue_topbar_icon_front_assets', 20 );

/**
 * Sugiere un icono ejemplo segun titulo o URL del item.
 *
 * @param WP_Post $item Item de menu.
 * @return string
 */
function liga_get_topbar_example_icon_for_menu_item( $item ) {
	$title = isset( $item->title ) ? sanitize_title( (string) $item->title ) : '';
	$url   = isset( $item->url ) ? strtolower( (string) $item->url ) : '';
	$path  = (string) wp_parse_url( $url, PHP_URL_PATH );
	$path  = untrailingslashit( strtolower( $path ) );

	if ( '#' === trim( $url ) || false !== strpos( $title, 'temporada' ) ) {
		return 'calendar_month';
	}

	if ( false !== strpos( $path, '/partidos' ) || false !== strpos( $title, 'fixture' ) ) {
		return 'event';
	}

	if ( false !== strpos( $path, '/tabla' ) || false !== strpos( $title, 'posiciones' ) ) {
		return 'leaderboard';
	}

	if ( false !== strpos( $path, '/resultados' ) || false !== strpos( $title, 'resultados' ) ) {
		return 'scoreboard';
	}

	if ( false !== strpos( $path, '/contacto' ) || false !== strpos( $title, 'contacto' ) ) {
		return 'mail';
	}

	if ( false !== strpos( $url, 'instagram.com' ) || false !== strpos( $title, 'instagram' ) ) {
		return 'photo_camera';
	}

	if ( false !== strpos( $url, 'facebook.com' ) || false !== strpos( $title, 'facebook' ) ) {
		return 'facebook';
	}

	if ( false !== strpos( $url, 'youtube.com' ) || false !== strpos( $title, 'youtube' ) ) {
		return 'smart_display';
	}

	return '';
}

/**
 * Asigna iconos de ejemplo al topbar existente una sola vez.
 *
 * Solo completa items sin icono para no sobrescribir configuracion del admin.
 *
 * @return void
 */
function liga_seed_topbar_example_icons_once() {
	if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$already_seeded = (int) get_option( 'liga_topbar_example_icons_seeded', 0 );
	if ( $already_seeded > 0 ) {
		return;
	}

	$menu_id = liga_get_topbar_menu_id();
	if ( $menu_id <= 0 ) {
		return;
	}

	$items = wp_get_nav_menu_items( $menu_id );
	if ( ! is_array( $items ) || empty( $items ) ) {
		return;
	}

	foreach ( $items as $item ) {
		$item_id = isset( $item->ID ) ? (int) $item->ID : 0;
		if ( $item_id <= 0 ) {
			continue;
		}

		$current_icon = liga_sanitize_topbar_icon( (string) get_post_meta( $item_id, LIGA_TOPBAR_ICON_META_KEY, true ) );
		if ( '' !== $current_icon ) {
			continue;
		}

		$suggested_icon = liga_sanitize_topbar_icon( liga_get_topbar_example_icon_for_menu_item( $item ) );
		if ( '' === $suggested_icon ) {
			continue;
		}

		update_post_meta( $item_id, LIGA_TOPBAR_ICON_META_KEY, $suggested_icon );
	}

	update_option( 'liga_topbar_example_icons_seeded', 1 );
}
add_action( 'admin_init', 'liga_seed_topbar_example_icons_once' );

/**
 * Adjunta icono guardado al objeto item del menu.
 *
 * @param WP_Post $menu_item Item del menu.
 * @return WP_Post
 */
function liga_setup_topbar_icon_menu_item( $menu_item ) {
	$item_id = isset( $menu_item->ID ) ? (int) $menu_item->ID : 0;
	if ( $item_id <= 0 ) {
		$menu_item->liga_topbar_icon = '';
		return $menu_item;
	}

	$icon = get_post_meta( $item_id, LIGA_TOPBAR_ICON_META_KEY, true );
	$menu_item->liga_topbar_icon = liga_sanitize_topbar_icon( $icon );

	return $menu_item;
}
add_filter( 'wp_setup_nav_menu_item', 'liga_setup_topbar_icon_menu_item' );

/**
 * Renderiza el campo custom "Icono Topbar" por item.
 *
 * @param int      $item_id ID del item.
 * @param WP_Post  $item Objeto item.
 * @param int      $depth Nivel del item.
 * @param stdClass $args Argumentos de walker admin.
 * @param int      $id ID del menu nav.
 * @return void
 */
function liga_render_topbar_icon_menu_item_custom_field( $item_id, $item, $depth, $args, $id ) {
	unset( $depth, $args, $id );

	if ( ! liga_is_topbar_menu_admin_context() ) {
		return;
	}

	$selected_icon = isset( $item->liga_topbar_icon ) ? liga_sanitize_topbar_icon( $item->liga_topbar_icon ) : '';
	$preview_label = '' !== $selected_icon ? liga_get_topbar_icon_label( $selected_icon ) : __( 'Sin icono', 'liga-basket-chile' );
	$preview_glyph = '';

	if ( '' !== $selected_icon ) {
		$preview_glyph = liga_get_topbar_official_social_icon_svg( $selected_icon, 'liga-icon-picker__glyph liga-icon-picker__glyph--brand' );
		if ( '' === $preview_glyph ) {
			$preview_glyph = '<span class="material-symbols-outlined liga-icon-picker__glyph" aria-hidden="true">' . esc_html( $selected_icon ) . '</span>';
		}
	} else {
		$preview_glyph = '<span class="material-symbols-outlined liga-icon-picker__glyph" aria-hidden="true">hide_source</span>';
	}
	?>
	<div class="field-liga-topbar-icon description description-wide">
		<p class="description description-wide">
			<label for="edit-menu-item-liga-topbar-icon-<?php echo esc_attr( $item_id ); ?>">
				<?php esc_html_e( 'Icono Topbar', 'liga-basket-chile' ); ?>
			</label>
		</p>
		<div class="liga-icon-picker" data-item-id="<?php echo esc_attr( $item_id ); ?>">
			<input
				type="hidden"
				id="edit-menu-item-liga-topbar-icon-<?php echo esc_attr( $item_id ); ?>"
				class="liga-icon-picker__value"
				name="menu-item-liga-topbar-icon[<?php echo esc_attr( $item_id ); ?>]"
				value="<?php echo esc_attr( $selected_icon ); ?>"
			/>
			<div class="liga-icon-picker__toolbar">
				<input type="search" class="liga-icon-picker__search" placeholder="<?php esc_attr_e( 'Buscar icono...', 'liga-basket-chile' ); ?>" />
				<button type="button" class="button button-small liga-icon-picker__option liga-icon-picker__option--none" data-icon="" aria-pressed="<?php echo '' === $selected_icon ? 'true' : 'false'; ?>">
					<?php esc_html_e( 'Sin icono', 'liga-basket-chile' ); ?>
				</button>
			</div>
			<div class="liga-icon-picker__preview">
				<span class="liga-icon-picker__preview-icon<?php echo '' === $selected_icon ? ' is-empty' : ''; ?>" aria-hidden="true"><?php echo $preview_glyph; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="liga-icon-picker__preview-text"><?php echo esc_html( $preview_label ); ?></span>
			</div>
			<div class="liga-icon-picker__popular-wrap">
				<p class="liga-icon-picker__popular-title"><?php esc_html_e( 'Redes sociales populares', 'liga-basket-chile' ); ?></p>
				<div class="liga-icon-picker__popular">
					<?php foreach ( liga_get_topbar_popular_social_icons() as $popular_icon ) : ?>
						<?php
						$popular_is_selected = ( $selected_icon === $popular_icon );
						$popular_label       = liga_get_topbar_icon_label( $popular_icon );
						$popular_glyph       = liga_get_topbar_official_social_icon_svg( $popular_icon, 'liga-icon-picker__glyph liga-icon-picker__glyph--brand' );

						if ( '' === $popular_glyph ) {
							$popular_glyph = '<span class="material-symbols-outlined liga-icon-picker__glyph" aria-hidden="true">' . esc_html( $popular_icon ) . '</span>';
						}
						?>
						<button
							type="button"
							class="liga-icon-picker__option liga-icon-picker__option--popular<?php echo $popular_is_selected ? ' is-selected' : ''; ?>"
							data-icon="<?php echo esc_attr( $popular_icon ); ?>"
							data-label="<?php echo esc_attr( $popular_label ); ?>"
							aria-label="<?php echo esc_attr( $popular_label ); ?>"
							aria-pressed="<?php echo $popular_is_selected ? 'true' : 'false'; ?>"
						>
							<?php echo $popular_glyph; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="liga-icon-picker__popular-label"><?php echo esc_html( $popular_label ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="liga-icon-picker__grid" role="listbox" aria-label="<?php esc_attr_e( 'Selector de iconos', 'liga-basket-chile' ); ?>">
				<?php foreach ( liga_get_topbar_icon_catalog() as $icon_name ) : ?>
					<?php
					$is_selected = ( $selected_icon === $icon_name );
					$icon_label  = liga_get_topbar_icon_label( $icon_name );
					?>
					<button
						type="button"
						class="liga-icon-picker__option liga-icon-picker__option--icon<?php echo $is_selected ? ' is-selected' : ''; ?>"
						data-icon="<?php echo esc_attr( $icon_name ); ?>"
						data-label="<?php echo esc_attr( $icon_label ); ?>"
						aria-label="<?php echo esc_attr( $icon_label ); ?>"
						aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>"
					>
						<span class="material-symbols-outlined liga-icon-picker__glyph" aria-hidden="true"><?php echo esc_html( $icon_name ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'liga_render_topbar_icon_menu_item_custom_field', 10, 5 );

/**
 * Guarda icono por item de menu topbar.
 *
 * @param int   $menu_id ID del menu editado.
 * @param int   $menu_item_db_id ID del item editado.
 * @param array $args Datos del item.
 * @return void
 */
function liga_save_topbar_icon_menu_item_meta( $menu_id, $menu_item_db_id, $args ) {
	unset( $args );

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( ! liga_is_topbar_icon_target_menu( $menu_id ) ) {
		return;
	}

	if ( ! isset( $_POST['menu-item-liga-topbar-icon'][ $menu_item_db_id ] ) ) {
		return;
	}

	$raw_icon = wp_unslash( $_POST['menu-item-liga-topbar-icon'][ $menu_item_db_id ] );
	$icon     = liga_sanitize_topbar_icon( $raw_icon );

	if ( '' === $icon ) {
		delete_post_meta( $menu_item_db_id, LIGA_TOPBAR_ICON_META_KEY );
		return;
	}

	update_post_meta( $menu_item_db_id, LIGA_TOPBAR_ICON_META_KEY, $icon );
}
add_action( 'wp_update_nav_menu_item', 'liga_save_topbar_icon_menu_item_meta', 10, 3 );

if ( ! class_exists( 'Liga_Topbar_Menu_Walker' ) ) {
	/**
	 * Walker para imprimir icono antes del texto en menu topbar.
	 */
	class Liga_Topbar_Menu_Walker extends Walker_Nav_Menu {
		/**
		 * Inicia el elemento.
		 *
		 * @param string   $output HTML acumulado.
		 * @param WP_Post  $item Item actual.
		 * @param int      $depth Profundidad.
		 * @param stdClass $args Argumentos.
		 * @param int      $id ID actual.
		 * @return void
		 */
		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
			$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

			$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;

			$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

			$item_id = apply_filters( 'nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth );
			$item_id = $item_id ? ' id="' . esc_attr( $item_id ) . '"' : '';

			$output .= $indent . '<li' . $item_id . $class_names . '>';

			$atts = array(
				'title'        => ! empty( $item->attr_title ) ? $item->attr_title : '',
				'target'       => ! empty( $item->target ) ? $item->target : '',
				'rel'          => ! empty( $item->xfn ) ? $item->xfn : '',
				'href'         => ! empty( $item->url ) ? $item->url : '',
				'aria-current' => $item->current ? 'page' : '',
			);

			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( empty( $value ) ) {
					continue;
				}
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}

			$title = apply_filters( 'the_title', $item->title, $item->ID );
			$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

				$icon_value  = isset( $item->liga_topbar_icon ) ? liga_sanitize_topbar_icon( $item->liga_topbar_icon ) : '';
				$icon_markup = '';
				if ( '' !== $icon_value ) {
					$icon_markup = liga_get_topbar_official_social_icon_svg( $icon_value, 'liga-topbar__icon liga-topbar__icon--brand' );
					if ( '' === $icon_markup ) {
						$icon_markup = '<span class="material-symbols-outlined liga-topbar__icon" aria-hidden="true">' . esc_html( $icon_value ) . '</span>';
					}
				}

			$item_output  = $args->before;
			$item_output .= '<a' . $attributes . '>';
			$item_output .= $args->link_before . $icon_markup . '<span class="liga-topbar__label">' . $title . '</span>' . $args->link_after;
			$item_output .= '</a>';
			$item_output .= $args->after;

			$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
	}
}
