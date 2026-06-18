<?php
/**
 * CPT Pop-up Swish.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra el CPT de pop-ups.
 *
 * @return void
 */
function liga_register_popup_cpt() {
	register_post_type(
		'liga_popup',
		array(
			'labels'              => array(
				'name'               => __( 'Pop-ups', 'liga-basket-chile' ),
				'singular_name'      => __( 'Pop-up', 'liga-basket-chile' ),
				'add_new_item'       => __( 'Agregar pop-up', 'liga-basket-chile' ),
				'edit_item'          => __( 'Editar pop-up', 'liga-basket-chile' ),
				'new_item'           => __( 'Nuevo pop-up', 'liga-basket-chile' ),
				'view_item'          => __( 'Ver pop-up', 'liga-basket-chile' ),
				'search_items'       => __( 'Buscar pop-ups', 'liga-basket-chile' ),
				'not_found'          => __( 'No se encontraron pop-ups', 'liga-basket-chile' ),
				'not_found_in_trash' => __( 'No hay pop-ups en papelera', 'liga-basket-chile' ),
			),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'has_archive'         => false,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-megaphone',
			'supports'            => array( 'title', 'editor', 'thumbnail' ),
		)
	);
}
add_action( 'init', 'liga_register_popup_cpt' );

/**
 * Opciones de visualización del pop-up.
 *
 * @return array<string, string>
 */
function liga_get_popup_scope_options() {
	return array(
		'home' => __( 'Solo home', 'liga-basket-chile' ),
		'all'  => __( 'Todo el sitio', 'liga-basket-chile' ),
	);
}

/**
 * Obtiene meta de pop-up con fallback.
 *
 * @param int    $post_id ID del pop-up.
 * @param string $key Meta key.
 * @param mixed  $default Valor por defecto.
 * @return mixed
 */
function liga_get_popup_meta_value( $post_id, $key, $default = '' ) {
	$value = get_post_meta( $post_id, $key, true );
	return '' === $value ? $default : $value;
}

/**
 * Registra metadatos del pop-up.
 *
 * @return void
 */
function liga_register_popup_meta_fields() {
	$meta_fields = array(
		'_liga_popup_active'      => 'integer',
		'_liga_popup_scope'       => 'string',
		'_liga_popup_custom_html' => 'string',
		'_liga_popup_custom_css'  => 'string',
		'_liga_popup_custom_js'   => 'string',
	);

	foreach ( $meta_fields as $key => $type ) {
		register_post_meta(
			'liga_popup',
			$key,
			array(
				'type'          => $type,
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'liga_register_popup_meta_fields' );

/**
 * Registra metaboxes del pop-up.
 *
 * @return void
 */
function liga_register_popup_metaboxes() {
	add_meta_box(
		'liga_popup_code',
		__( 'HTML, CSS y JS del Pop-up', 'liga-basket-chile' ),
		'liga_render_popup_code_metabox',
		'liga_popup',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_liga_popup', 'liga_register_popup_metaboxes' );

/**
 * Renderiza el metabox de código del pop-up.
 *
 * @param WP_Post $post Post actual.
 * @return void
 */
function liga_render_popup_code_metabox( $post ) {
	wp_nonce_field( 'liga_save_popup_code', 'liga_popup_code_nonce' );

	$active      = (int) liga_get_popup_meta_value( $post->ID, '_liga_popup_active', 1 );
	$scope       = (string) liga_get_popup_meta_value( $post->ID, '_liga_popup_scope', 'home' );
	$custom_html = (string) get_post_meta( $post->ID, '_liga_popup_custom_html', true );
	$custom_css  = (string) get_post_meta( $post->ID, '_liga_popup_custom_css', true );
	$custom_js   = (string) get_post_meta( $post->ID, '_liga_popup_custom_js', true );
	$can_code    = current_user_can( 'unfiltered_html' );

	if ( '' === trim( $custom_html ) && function_exists( 'liga_get_default_swish_popup_html' ) ) {
		$custom_html = liga_get_default_swish_popup_html();
	}

	if ( '' === trim( $custom_css ) && function_exists( 'liga_get_default_swish_popup_css' ) ) {
		$custom_css = liga_get_default_swish_popup_css();
	}

	if ( '' === trim( $custom_js ) && function_exists( 'liga_get_default_swish_popup_js' ) ) {
		$custom_js = liga_get_default_swish_popup_js();
	}
	?>
	<p class="description">
		<?php esc_html_e( 'Estos campos reemplazan el HTML/CSS/JS renderizado del pop-up activo. Si el HTML queda vacío, el tema usa el diseño Swish por defecto.', 'liga-basket-chile' ); ?>
	</p>

	<?php if ( ! $can_code ) : ?>
		<div class="notice notice-warning inline">
			<p><?php esc_html_e( 'Tu usuario no tiene permiso para guardar CSS o JS crudo. Pide acceso de administrador con unfiltered_html.', 'liga-basket-chile' ); ?></p>
		</div>
	<?php endif; ?>

	<table class="form-table" role="presentation">
		<tr>
			<th><?php esc_html_e( 'Estado', 'liga-basket-chile' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="_liga_popup_active" value="1" <?php checked( $active, 1 ); ?>>
					<?php esc_html_e( 'Activar este pop-up', 'liga-basket-chile' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th><label for="liga_popup_scope"><?php esc_html_e( 'Dónde mostrar', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_popup_scope" name="_liga_popup_scope">
					<?php foreach ( liga_get_popup_scope_options() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $scope, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="liga_popup_custom_html"><?php esc_html_e( 'HTML', 'liga-basket-chile' ); ?></label></th>
			<td>
				<textarea id="liga_popup_custom_html" name="_liga_popup_custom_html" class="large-text code" rows="16" spellcheck="false"><?php echo esc_textarea( $custom_html ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Pega aquí el HTML completo del pop-up. Puede incluir IDs, clases y atributos necesarios para tu JS.', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="liga_popup_custom_css"><?php esc_html_e( 'CSS', 'liga-basket-chile' ); ?></label></th>
			<td>
				<textarea id="liga_popup_custom_css" name="_liga_popup_custom_css" class="large-text code" rows="12" spellcheck="false" <?php disabled( ! $can_code ); ?>><?php echo esc_textarea( $custom_css ); ?></textarea>
				<p class="description"><?php esc_html_e( 'No incluyas etiquetas <style>; el tema las agrega al renderizar.', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="liga_popup_custom_js"><?php esc_html_e( 'JS', 'liga-basket-chile' ); ?></label></th>
			<td>
				<textarea id="liga_popup_custom_js" name="_liga_popup_custom_js" class="large-text code" rows="12" spellcheck="false" <?php disabled( ! $can_code ); ?>><?php echo esc_textarea( $custom_js ); ?></textarea>
				<p class="description"><?php esc_html_e( 'No incluyas etiquetas <script>; el tema las agrega al renderizar.', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Guarda el código personalizado del pop-up.
 *
 * @param int $post_id ID del post.
 * @return void
 */
function liga_save_popup_code_metabox( $post_id ) {
	if ( ! isset( $_POST['liga_popup_code_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['liga_popup_code_nonce'] ) ), 'liga_save_popup_code' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( 'liga_popup' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$scope_options = array_keys( liga_get_popup_scope_options() );
	$scope         = isset( $_POST['_liga_popup_scope'] ) ? sanitize_key( wp_unslash( $_POST['_liga_popup_scope'] ) ) : 'home';

	if ( ! in_array( $scope, $scope_options, true ) ) {
		$scope = 'home';
	}

	update_post_meta( $post_id, '_liga_popup_active', isset( $_POST['_liga_popup_active'] ) ? 1 : 0 );
	update_post_meta( $post_id, '_liga_popup_scope', $scope );

	$can_code = current_user_can( 'unfiltered_html' );
	$html     = isset( $_POST['_liga_popup_custom_html'] ) ? wp_unslash( $_POST['_liga_popup_custom_html'] ) : '';

	update_post_meta( $post_id, '_liga_popup_custom_html', $can_code ? $html : wp_kses_post( $html ) );

	if ( $can_code ) {
		update_post_meta( $post_id, '_liga_popup_custom_css', isset( $_POST['_liga_popup_custom_css'] ) ? wp_unslash( $_POST['_liga_popup_custom_css'] ) : '' );
		update_post_meta( $post_id, '_liga_popup_custom_js', isset( $_POST['_liga_popup_custom_js'] ) ? wp_unslash( $_POST['_liga_popup_custom_js'] ) : '' );
	}
}
add_action( 'save_post_liga_popup', 'liga_save_popup_code_metabox' );
