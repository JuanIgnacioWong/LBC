<?php
/**
 * Pantalla administrativa para opciones del footer.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna metadatos de campos del footer.
 *
 * @return array<string, array<string, mixed>>
 */
function liga_get_footer_admin_sections() {
	return array(
		'brand'      => array(
			'title'  => __( 'Marca', 'liga-basket-chile' ),
			'fields' => array(
				array( 'key' => 'footer_brand_title', 'label' => __( 'Titulo de marca', 'liga-basket-chile' ), 'type' => 'text' ),
				array( 'key' => 'footer_brand_description', 'label' => __( 'Descripcion de marca', 'liga-basket-chile' ), 'type' => 'textarea' ),
				array( 'key' => 'footer_logo_id', 'label' => __( 'Logo del footer', 'liga-basket-chile' ), 'type' => 'image' ),
				array( 'key' => 'footer_show_logo', 'label' => __( 'Mostrar logo', 'liga-basket-chile' ), 'type' => 'checkbox' ),
				array( 'key' => 'footer_show_brand_title', 'label' => __( 'Mostrar titulo de marca', 'liga-basket-chile' ), 'type' => 'checkbox' ),
				array( 'key' => 'footer_show_brand_description', 'label' => __( 'Mostrar descripcion', 'liga-basket-chile' ), 'type' => 'checkbox' ),
			),
		),
		'social'     => array(
			'title'  => __( 'Redes', 'liga-basket-chile' ),
			'fields' => array(
				array( 'key' => 'footer_social_instagram', 'label' => __( 'Instagram', 'liga-basket-chile' ), 'type' => 'url' ),
				array( 'key' => 'footer_social_x', 'label' => __( 'X', 'liga-basket-chile' ), 'type' => 'url' ),
				array( 'key' => 'footer_social_youtube', 'label' => __( 'YouTube', 'liga-basket-chile' ), 'type' => 'url' ),
				array( 'key' => 'footer_social_tiktok', 'label' => __( 'TikTok', 'liga-basket-chile' ), 'type' => 'url' ),
				array( 'key' => 'footer_show_social', 'label' => __( 'Mostrar redes sociales', 'liga-basket-chile' ), 'type' => 'checkbox' ),
			),
		),
		'contact'    => array(
			'title'  => __( 'Contacto', 'liga-basket-chile' ),
			'fields' => array(
				array( 'key' => 'footer_contact_address_1', 'label' => __( 'Direccion linea 1', 'liga-basket-chile' ), 'type' => 'text' ),
				array( 'key' => 'footer_contact_address_2', 'label' => __( 'Direccion linea 2', 'liga-basket-chile' ), 'type' => 'text' ),
				array( 'key' => 'footer_contact_email', 'label' => __( 'Email', 'liga-basket-chile' ), 'type' => 'email' ),
				array( 'key' => 'footer_contact_phone', 'label' => __( 'Telefono', 'liga-basket-chile' ), 'type' => 'text' ),
				array( 'key' => 'footer_show_contact', 'label' => __( 'Mostrar datos de contacto', 'liga-basket-chile' ), 'type' => 'checkbox' ),
			),
		),
		'legal'      => array(
			'title'  => __( 'Legal', 'liga-basket-chile' ),
			'fields' => array(
				array( 'key' => 'footer_copyright_text', 'label' => __( 'Texto copyright', 'liga-basket-chile' ), 'type' => 'text' ),
				array( 'key' => 'footer_legal_extra_text', 'label' => __( 'Texto legal adicional', 'liga-basket-chile' ), 'type' => 'textarea' ),
				array( 'key' => 'footer_show_legal_strip', 'label' => __( 'Mostrar franja legal', 'liga-basket-chile' ), 'type' => 'checkbox' ),
			),
		),
		'visibility' => array(
			'title'  => __( 'Visibilidad general', 'liga-basket-chile' ),
			'fields' => array(
				array( 'key' => 'footer_show_brand_block', 'label' => __( 'Mostrar bloque de marca', 'liga-basket-chile' ), 'type' => 'checkbox' ),
				array( 'key' => 'footer_show_footer_menu', 'label' => __( 'Mostrar menu Navegacion', 'liga-basket-chile' ), 'type' => 'checkbox' ),
				array( 'key' => 'footer_show_secondary_menu', 'label' => __( 'Mostrar menu La Liga', 'liga-basket-chile' ), 'type' => 'checkbox' ),
				array( 'key' => 'footer_show_contact_block', 'label' => __( 'Mostrar bloque de contacto', 'liga-basket-chile' ), 'type' => 'checkbox' ),
			),
		),
	);
}

/**
 * Defaults de opciones para el footer.
 *
 * @return array<string, mixed>
 */
function liga_get_footer_admin_defaults() {
	$site_name = get_bloginfo( 'name' );
	$site_name = $site_name ? $site_name : 'Liga de Basquetbol Concepcion';

	return array(
		'footer_brand_title'           => $site_name,
		'footer_brand_description'     => liga_get_option( 'footer_texto', '' ),
		'footer_logo_id'               => 0,
		'footer_show_logo'             => 1,
		'footer_show_brand_title'      => 1,
		'footer_show_brand_description' => 0,
		'footer_social_instagram'      => '',
		'footer_social_x'              => '',
		'footer_social_youtube'        => '',
		'footer_social_tiktok'         => '',
		'footer_show_social'           => 1,
		'footer_contact_address_1'     => 'Gimnasio Municipal de Concepcion',
		'footer_contact_address_2'     => 'J. Campos 775, Concepcion',
		'footer_contact_email'         => 'info@ligaconcep.cl',
		'footer_contact_phone'         => '+56 9 1234 5678',
		'footer_show_contact'          => 1,
		'footer_copyright_text'        => '',
		'footer_legal_extra_text'      => '',
		'footer_show_legal_strip'      => 1,
		'footer_show_brand_block'      => 1,
		'footer_show_footer_menu'      => 1,
		'footer_show_secondary_menu'   => 1,
		'footer_show_contact_block'    => 1,
	);
}

/**
 * Obtiene un valor para admin de footer.
 *
 * @param string $key Clave.
 * @return mixed
 */
function liga_get_footer_admin_value( $key ) {
	$defaults = liga_get_footer_admin_defaults();
	$default  = array_key_exists( $key, $defaults ) ? $defaults[ $key ] : '';
	return liga_get_theme_option( $key, $default );
}

/**
 * Registra submenu de Footer bajo Liga Basquet.
 *
 * @return void
 */
function liga_register_footer_admin_submenu() {
	// Deprecated: Footer vive en Liga Basquet > Configuracion > Footer.
}

/**
 * Redirige el slug legacy de Footer hacia la pestaña unificada.
 *
 * @return void
 */
function liga_redirect_legacy_footer_options_page() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'liga-footer-options' !== $page ) {
		return;
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'liga-basquet-configuracion',
				'tab'  => 'footer',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'liga_redirect_legacy_footer_options_page' );

/**
 * Carga assets de media uploader solo en la pantalla Footer.
 *
 * @param string $hook Hook suffix de pantalla.
 * @return void
 */
function liga_footer_admin_enqueue_assets( $hook ) {
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';

	if ( 'liga-footer-options' !== $page && ( 'liga-basquet-configuracion' !== $page || 'footer' !== $tab ) ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );

	$media_strings = array(
		'title'  => __( 'Seleccionar logo footer', 'liga-basket-chile' ),
		'button' => __( 'Usar logo', 'liga-basket-chile' ),
		'select' => __( 'Seleccionar logo', 'liga-basket-chile' ),
		'remove' => __( 'Quitar logo', 'liga-basket-chile' ),
		'empty'  => __( 'Sin logo seleccionado', 'liga-basket-chile' ),
	);

	wp_add_inline_script(
		'jquery',
		'jQuery(function($){' .
			'const strings=' . wp_json_encode( $media_strings ) . ';' .
			'const wrap=$(".liga-footer-logo-control");' .
			'if(!wrap.length){return;}' .
			'const input=wrap.find("input[type=hidden]");' .
			'const preview=wrap.find(".liga-footer-logo-preview");' .
			'const previewImage=preview.find("img");' .
			'const previewEmpty=preview.find(".liga-footer-logo-empty");' .
			'let frame=null;' .
			'wrap.on("click",".liga-footer-media-select",function(e){' .
				'e.preventDefault();' .
				'if(frame){frame.open();return;}' .
				'frame=wp.media({title:strings.title,button:{text:strings.button},multiple:false,library:{type:"image"}});' .
				'frame.on("select",function(){' .
					'const attachment=frame.state().get("selection").first().toJSON();' .
					'const src=(attachment.sizes&&attachment.sizes.medium)?attachment.sizes.medium.url:attachment.url;' .
					'input.val(attachment.id);' .
					'previewImage.attr("src",src).show();' .
					'previewEmpty.hide();' .
				'});' .
				'frame.open();' .
			'});' .
			'wrap.on("click",".liga-footer-media-remove",function(e){' .
				'e.preventDefault();' .
				'input.val("0");' .
				'previewImage.attr("src","").hide();' .
				'previewEmpty.text(strings.empty).show();' .
			'});' .
		'});'
	);

	wp_add_inline_style(
		'wp-admin',
		'.liga-footer-logo-control{display:grid;gap:.6rem;max-width:420px}' .
		'.liga-footer-logo-preview{width:120px;height:120px;border:1px solid #c3c4c7;background:#fff;display:grid;place-items:center;border-radius:4px;overflow:hidden}' .
		'.liga-footer-logo-preview img{max-width:100%;max-height:100%;display:block}' .
		'.liga-footer-logo-empty{font-size:12px;color:#646970;padding:.5rem;text-align:center}' .
		'.liga-footer-logo-actions{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}'
	);
}
add_action( 'admin_enqueue_scripts', 'liga_footer_admin_enqueue_assets' );

/**
 * Renderiza un campo de footer.
 *
 * @param array<string, mixed> $field Definicion.
 * @return void
 */
function liga_render_footer_admin_field( $field ) {
	$key   = isset( $field['key'] ) ? sanitize_key( (string) $field['key'] ) : '';
	$label = isset( $field['label'] ) ? (string) $field['label'] : '';
	$type  = isset( $field['type'] ) ? sanitize_key( (string) $field['type'] ) : 'text';
	$value = liga_get_footer_admin_value( $key );
	$name  = 'liga_theme_options[' . $key . ']';

	echo '<tr>';
	echo '<th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>';
	echo '<td>';
	echo '<input type="hidden" name="liga_theme_options[_active_fields][]" value="' . esc_attr( $key ) . '">';

	if ( 'textarea' === $type ) {
		echo '<textarea class="large-text" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( (string) $value ) . '</textarea>';
	} elseif ( 'checkbox' === $type ) {
		echo '<label><input type="checkbox" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( (int) $value, 1, false ) . '> ' . esc_html__( 'Activado', 'liga-basket-chile' ) . '</label>';
	} elseif ( 'image' === $type ) {
		$image_id = absint( $value );
		$image    = $image_id > 0 ? wp_get_attachment_image_src( $image_id, 'thumbnail' ) : false;
		$image_src = ( $image && ! empty( $image[0] ) ) ? $image[0] : '';

		echo '<div class="liga-footer-logo-control">';
		echo '<input type="hidden" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $image_id ) . '">';
		echo '<div class="liga-footer-logo-preview">';
		if ( '' !== $image_src ) {
			echo '<img src="' . esc_url( $image_src ) . '" alt="" />';
			echo '<span class="liga-footer-logo-empty" style="display:none">' . esc_html__( 'Sin logo seleccionado', 'liga-basket-chile' ) . '</span>';
		} else {
			echo '<img src="" alt="" style="display:none" />';
			echo '<span class="liga-footer-logo-empty">' . esc_html__( 'Sin logo seleccionado', 'liga-basket-chile' ) . '</span>';
		}
		echo '</div>';
		echo '<div class="liga-footer-logo-actions">';
		echo '<button type="button" class="button liga-footer-media-select">' . esc_html__( 'Seleccionar logo', 'liga-basket-chile' ) . '</button>';
		echo '<button type="button" class="button button-link-delete liga-footer-media-remove">' . esc_html__( 'Quitar logo', 'liga-basket-chile' ) . '</button>';
		echo '</div>';
		echo '</div>';
	} else {
		$input_type = in_array( $type, array( 'url', 'email', 'number' ), true ) ? $type : 'text';
		echo '<input class="regular-text" type="' . esc_attr( $input_type ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '">';
	}

	echo '</td>';
	echo '</tr>';
}

/**
 * Renderiza pagina de opciones Footer.
 *
 * @return void
 */
function liga_render_footer_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pagina.', 'liga-basket-chile' ) );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'liga-basquet-configuracion',
				'tab'  => 'footer',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
