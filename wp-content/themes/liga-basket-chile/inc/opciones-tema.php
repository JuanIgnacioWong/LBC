<?php
/**
 * Opciones de tema (fase 2).
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Campos soportados por opciones de tema.
 *
 * @return array<string, string>
 */
function liga_get_theme_option_fields() {
	return array(
		'header_slogan'      => 'text',
		'header_cta_label'   => 'text',
		'header_cta_url'     => 'url',
		'social_instagram'   => 'url',
		'social_facebook'    => 'url',
		'social_youtube'     => 'url',
		'home_modulos_activos' => 'checkbox',
		'featured_division'  => 'int',
		'hero_title'         => 'text',
		'hero_subtitle'      => 'textarea',
		'news_count'         => 'int',
		'sponsors_section_title' => 'text',
		'sponsors_cta_label' => 'text',
		'footer_texto'       => 'textarea',
		'contact_email'      => 'email',
		'contact_phone'      => 'text',
		'footer_columns'     => 'int',
		'api_key'            => 'text',
		'branding_nombre'    => 'text',
		'current_season'     => 'text',
		'institutional_phrase'=> 'text',
		'top_contact_label'  => 'text',
		'top_contact_url'    => 'url',
		'header_login_enabled' => 'checkbox',
		'header_login_label' => 'text',
		'header_login_url'   => 'url',
			'standings_points_win' => 'int',
			'standings_points_loss' => 'int',
			'standings_points_walkover_win' => 'int',
			'standings_points_walkover_loss' => 'int',
			'footer_brand_title'   => 'text',
			'footer_brand_description' => 'textarea',
			'footer_logo_id'       => 'int',
			'footer_show_logo'     => 'checkbox',
			'footer_show_brand_title' => 'checkbox',
			'footer_show_brand_description' => 'checkbox',
			'footer_social_instagram' => 'url',
			'footer_social_x'      => 'url',
			'footer_social_youtube' => 'url',
			'footer_social_tiktok' => 'url',
			'footer_show_social'   => 'checkbox',
			'footer_contact_address_1' => 'text',
			'footer_contact_address_2' => 'text',
			'footer_contact_email' => 'email',
			'footer_contact_phone' => 'text',
			'footer_show_contact'  => 'checkbox',
			'footer_copyright_text' => 'text',
			'footer_legal_extra_text' => 'textarea',
			'footer_show_legal_strip' => 'checkbox',
			'footer_show_brand_block' => 'checkbox',
			'footer_show_footer_menu' => 'checkbox',
			'footer_show_secondary_menu' => 'checkbox',
			'footer_show_contact_block' => 'checkbox',
		);
	}

/**
 * Sanitiza opciones del tema.
 *
 * @param array<string, mixed> $input Input.
 * @return array<string, mixed>
 */
function liga_sanitize_theme_options( $input ) {
	$existing  = get_option( 'liga_theme_options', array() );
	$sanitized = is_array( $existing ) ? $existing : array();
	$fields    = liga_get_theme_option_fields();
	$input     = is_array( $input ) ? $input : array();
	$active    = array();

	if ( isset( $input['_active_fields'] ) && is_array( $input['_active_fields'] ) ) {
		$active = array_map( 'sanitize_key', wp_unslash( $input['_active_fields'] ) );
	}

	unset( $input['_active_fields'] );

	if ( empty( $active ) ) {
		$active = array_keys( $input );
	}

	foreach ( $fields as $key => $type ) {
		if ( ! in_array( $key, $active, true ) ) {
			continue;
		}

		$value = isset( $input[ $key ] ) ? $input[ $key ] : '';

		switch ( $type ) {
			case 'url':
				$sanitized[ $key ] = esc_url_raw( (string) $value );
				break;
			case 'email':
				$sanitized[ $key ] = sanitize_email( (string) $value );
				break;
			case 'textarea':
				$sanitized[ $key ] = sanitize_textarea_field( (string) $value );
				break;
			case 'int':
				$sanitized[ $key ] = absint( $value );
				break;
			case 'checkbox':
				$sanitized[ $key ] = liga_sanitize_checkbox( $value );
				break;
			default:
				$sanitized[ $key ] = sanitize_text_field( (string) $value );
				break;
		}
	}

	return $sanitized;
}

/**
 * Registra setting de opciones de tema.
 *
 * @return void
 */
function liga_register_theme_options() {
	register_setting(
		'liga_theme_options_group',
		'liga_theme_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'liga_sanitize_theme_options',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'liga_register_theme_options' );

/**
 * Agrega submenu en Apariencia.
 *
 * @return void
 */
function liga_register_theme_options_page() {
	// Deprecated: las opciones viven en Liga Basquet > Configuracion.
}

/**
 * Renderiza campo de opcion.
 *
 * @param string $key Clave.
 * @param string $label Etiqueta.
 * @param string $type Tipo.
 * @param mixed  $default Valor por defecto.
 * @return void
 */
function liga_render_option_field( $key, $label, $type = 'text', $default = '' ) {
	$value = liga_get_option( $key, $default );
	$name  = 'liga_theme_options[' . $key . ']';
	echo '<tr>';
	echo '<th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>';
	echo '<td>';
	echo '<input type="hidden" name="liga_theme_options[_active_fields][]" value="' . esc_attr( $key ) . '">';

	if ( 'textarea' === $type ) {
		echo '<textarea class="large-text" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
	} elseif ( 'checkbox' === $type ) {
		echo '<label><input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( (int) $value, 1, false ) . '> ' . esc_html__( 'Activado', 'liga-basket-chile' ) . '</label>';
	} else {
		$input_type = in_array( $type, array( 'url', 'email', 'number' ), true ) ? $type : 'text';
		echo '<input class="regular-text" type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
	}

	echo '</td>';
	echo '</tr>';
}

/**
 * Tabs soportadas por la pantalla unificada.
 *
 * @return array<string, string>
 */
function liga_get_theme_options_tabs() {
	return array(
		'general' => __( 'General', 'liga-basket-chile' ),
		'header'  => __( 'Header / Identidad', 'liga-basket-chile' ),
		'home'    => __( 'Home', 'liga-basket-chile' ),
		'footer'  => __( 'Footer', 'liga-basket-chile' ),
		'social'  => __( 'Redes / Contacto', 'liga-basket-chile' ),
	);
}

/**
 * Resuelve tab actual de opciones.
 *
 * @return string
 */
function liga_get_current_theme_options_tab() {
	$tabs = liga_get_theme_options_tabs();
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

	return array_key_exists( $tab, $tabs ) ? $tab : 'general';
}

/**
 * Renderiza navegacion por tabs.
 *
 * @param string $active_tab Tab activa.
 * @return void
 */
function liga_render_theme_options_tabs( $active_tab ) {
	$tabs = liga_get_theme_options_tabs();

	echo '<nav class="nav-tab-wrapper" aria-label="' . esc_attr__( 'Secciones de configuracion', 'liga-basket-chile' ) . '">';
	foreach ( $tabs as $tab => $label ) {
		$url = add_query_arg(
			array(
				'page' => 'liga-basquet-configuracion',
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);
		echo '<a class="nav-tab ' . ( $active_tab === $tab ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</nav>';
}

/**
 * Renderiza pantalla de opciones.
 *
 * @return void
 */
function liga_render_theme_options_page() {
	$active_tab = liga_get_current_theme_options_tab();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Configuracion Liga Basquet', 'liga-basket-chile' ); ?></h1>
		<?php settings_errors( 'liga_theme_options' ); ?>
		<?php liga_render_theme_options_tabs( $active_tab ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'liga_theme_options_group' ); ?>

			<?php if ( 'general' === $active_tab ) : ?>
			<h2><?php esc_html_e( 'General', 'liga-basket-chile' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php liga_render_option_field( 'branding_nombre', __( 'Branding', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'current_season', __( 'Temporada actual', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'institutional_phrase', __( 'Frase institucional', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'api_key', __( 'API Key', 'liga-basket-chile' ) ); ?>
			</table>

			<h2><?php esc_html_e( 'Reglamento Deportivo', 'liga-basket-chile' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><?php esc_html_e( 'Partido jugado', 'liga-basket-chile' ); ?></th>
					<td><?php esc_html_e( 'Ganador +2 pts. Perdedor +1 pt.', 'liga-basket-chile' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Incomparecencia', 'liga-basket-chile' ); ?></th>
					<td><?php esc_html_e( 'Ganador +2 pts. Equipo ausente +0 pts e INC +1.', 'liga-basket-chile' ); ?></td>
				</tr>
			</table>
			<?php endif; ?>

			<?php if ( 'header' === $active_tab ) : ?>
			<h2><?php esc_html_e( 'Header / Identidad', 'liga-basket-chile' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php liga_render_option_field( 'header_slogan', __( 'Slogan', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'header_cta_label', __( 'CTA texto', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'header_cta_url', __( 'CTA URL', 'liga-basket-chile' ), 'url' ); ?>
				<?php liga_render_option_field( 'header_login_enabled', __( 'Mostrar boton iniciar sesion', 'liga-basket-chile' ), 'checkbox' ); ?>
				<?php liga_render_option_field( 'header_login_label', __( 'Texto boton iniciar sesion', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'header_login_url', __( 'URL boton iniciar sesion', 'liga-basket-chile' ), 'url' ); ?>
				<?php liga_render_option_field( 'top_contact_label', __( 'CTA top bar texto', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'top_contact_url', __( 'CTA top bar URL', 'liga-basket-chile' ), 'url' ); ?>
			</table>
			<?php endif; ?>

			<?php if ( 'home' === $active_tab ) : ?>
			<h2><?php esc_html_e( 'Home', 'liga-basket-chile' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php liga_render_option_field( 'home_modulos_activos', __( 'Activar modulos home', 'liga-basket-chile' ), 'checkbox' ); ?>
				<?php liga_render_option_field( 'featured_division', __( 'Division destacada (ID)', 'liga-basket-chile' ), 'number' ); ?>
				<?php liga_render_option_field( 'hero_title', __( 'Hero titulo', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'hero_subtitle', __( 'Hero subtitulo', 'liga-basket-chile' ), 'textarea' ); ?>
				<?php liga_render_option_field( 'news_count', __( 'Cantidad noticias home', 'liga-basket-chile' ), 'number' ); ?>
				<?php liga_render_option_field( 'sponsors_section_title', __( 'Titulo seccion participantes', 'liga-basket-chile' ), 'text', __( 'Participantes', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'sponsors_cta_label', __( 'Texto enlace participantes', 'liga-basket-chile' ), 'text', __( 'Participar en la competición', 'liga-basket-chile' ) ); ?>
			</table>
			<?php endif; ?>

			<?php if ( 'footer' === $active_tab ) : ?>
			<h2><?php esc_html_e( 'Footer', 'liga-basket-chile' ); ?></h2>
			<p><?php esc_html_e( 'Edita contenido y visibilidad del footer. Los menus se siguen gestionando en Apariencia > Menus.', 'liga-basket-chile' ); ?></p>
			<?php if ( function_exists( 'liga_get_footer_admin_sections' ) ) : ?>
				<?php foreach ( liga_get_footer_admin_sections() as $section ) : ?>
					<h3><?php echo esc_html( (string) $section['title'] ); ?></h3>
					<table class="form-table" role="presentation">
						<?php
						if ( ! empty( $section['fields'] ) && is_array( $section['fields'] ) ) {
							foreach ( $section['fields'] as $field ) {
								liga_render_footer_admin_field( $field );
							}
						}
						?>
					</table>
				<?php endforeach; ?>
			<?php else : ?>
			<table class="form-table" role="presentation">
				<?php liga_render_option_field( 'footer_texto', __( 'Texto footer', 'liga-basket-chile' ), 'textarea' ); ?>
				<?php liga_render_option_field( 'contact_email', __( 'Email contacto', 'liga-basket-chile' ), 'email' ); ?>
				<?php liga_render_option_field( 'contact_phone', __( 'Telefono contacto', 'liga-basket-chile' ) ); ?>
				<?php liga_render_option_field( 'footer_columns', __( 'Columnas footer', 'liga-basket-chile' ), 'number' ); ?>
			</table>
			<?php endif; ?>
			<?php endif; ?>

			<?php if ( 'social' === $active_tab ) : ?>
			<h2><?php esc_html_e( 'Redes / Contacto', 'liga-basket-chile' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php liga_render_option_field( 'social_instagram', __( 'Instagram', 'liga-basket-chile' ), 'url' ); ?>
				<?php liga_render_option_field( 'social_facebook', __( 'Facebook', 'liga-basket-chile' ), 'url' ); ?>
				<?php liga_render_option_field( 'social_youtube', __( 'YouTube', 'liga-basket-chile' ), 'url' ); ?>
				<?php liga_render_option_field( 'contact_email', __( 'Email contacto legacy', 'liga-basket-chile' ), 'email' ); ?>
				<?php liga_render_option_field( 'contact_phone', __( 'Telefono contacto legacy', 'liga-basket-chile' ) ); ?>
			</table>
			<?php endif; ?>

			<?php submit_button( __( 'Guardar cambios', 'liga-basket-chile' ) ); ?>
		</form>

		<?php if ( 'general' === $active_tab ) : ?>
		<?php
		$demo_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'           => 'liga-basquet-configuracion',
					'tab'            => 'general',
					'liga_demo_seed' => 1,
				),
				admin_url( 'admin.php' )
			),
			'liga_demo_seed'
		);
		$topbar_seed_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'             => 'liga-basquet-configuracion',
					'tab'              => 'general',
					'liga_topbar_seed' => 1,
				),
				admin_url( 'admin.php' )
			),
			'liga_topbar_seed'
		);
			$main_menu_seed_url = wp_nonce_url(
				add_query_arg(
				array(
					'page'                => 'liga-basquet-configuracion',
					'tab'                 => 'general',
					'liga_main_menu_seed' => 1,
				),
				admin_url( 'admin.php' )
				),
				'liga_main_menu_seed'
			);
			$sponsor_demo_seed_url = wp_nonce_url(
				add_query_arg(
					array(
						'page'                  => 'liga-basquet-configuracion',
						'tab'                   => 'general',
						'liga_sponsor_demo_seed' => 1,
					),
					admin_url( 'admin.php' )
				),
				'liga_sponsor_demo_seed'
			);
			$sponsor_demo_force_url = wp_nonce_url(
				add_query_arg(
					array(
						'page'                  => 'liga-basquet-configuracion',
						'tab'                   => 'general',
						'liga_sponsor_demo_seed' => 1,
						'force'                 => 1,
					),
					admin_url( 'admin.php' )
				),
				'liga_sponsor_demo_seed'
			);
			?>
		<hr>
		<h2><?php esc_html_e( 'Datos Demo', 'liga-basket-chile' ); ?></h2>
		<p><?php esc_html_e( 'Carga 6 equipos, 2 divisiones, 12 partidos, 6 noticias y sponsors demo.', 'liga-basket-chile' ); ?></p>
		<p><a class="button button-secondary" href="<?php echo esc_url( $demo_url ); ?>"><?php esc_html_e( 'Cargar datos demo', 'liga-basket-chile' ); ?></a></p>
		<h2><?php esc_html_e( 'Menú Principal Demo', 'liga-basket-chile' ); ?></h2>
		<p><?php esc_html_e( 'Crea/asigna el Menú Principal y agrega enlaces referenciales si aún no existen.', 'liga-basket-chile' ); ?></p>
		<p><a class="button button-secondary" href="<?php echo esc_url( $main_menu_seed_url ); ?>"><?php esc_html_e( 'Poblar Menú Principal', 'liga-basket-chile' ); ?></a></p>
			<h2><?php esc_html_e( 'Topbar Demo', 'liga-basket-chile' ); ?></h2>
			<p><?php esc_html_e( 'Crea/asigna el Menú Topbar y agrega contenido referencial e iconos si aun no existen.', 'liga-basket-chile' ); ?></p>
			<p><a class="button button-secondary" href="<?php echo esc_url( $topbar_seed_url ); ?>"><?php esc_html_e( 'Poblar Menú Topbar', 'liga-basket-chile' ); ?></a></p>
			<h2><?php esc_html_e( 'Sponsors NBA Demo', 'liga-basket-chile' ); ?></h2>
			<p><?php esc_html_e( 'Crea sponsors demo con logos de equipos NBA para la sección patrocinadores del home.', 'liga-basket-chile' ); ?></p>
			<p>
				<a class="button button-secondary" href="<?php echo esc_url( $sponsor_demo_seed_url ); ?>"><?php esc_html_e( 'Cargar sponsors NBA demo', 'liga-basket-chile' ); ?></a>
				<a class="button button-link" href="<?php echo esc_url( $sponsor_demo_force_url ); ?>"><?php esc_html_e( 'Recargar logos (force)', 'liga-basket-chile' ); ?></a>
			</p>
			<?php endif; ?>
		</div>
		<?php
}
