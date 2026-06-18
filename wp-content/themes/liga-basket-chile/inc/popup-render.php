<?php
/**
 * Render frontend del pop-up Swish.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si la solicitud permite mostrar pop-ups.
 *
 * @return bool
 */
function liga_can_render_popup_request() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() ) {
		return false;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return false;
	}

	if ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] ) {
		return false;
	}

	return true;
}

/**
 * Obtiene el primer pop-up activo. Los pop-ups antiguos sin meta activo se
 * consideran activos para no ocultar el modal tras esta migración.
 *
 * @return WP_Post|null
 */
function liga_get_active_popup() {
	if ( ! post_type_exists( 'liga_popup' ) ) {
		return null;
	}

	$query_args = array(
		'post_type'           => 'liga_popup',
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'orderby'             => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
	);

	$popup_query = new WP_Query(
		array_merge(
			$query_args,
			array(
				'meta_key'   => '_liga_popup_active',
				'meta_value' => '1',
			)
		)
	);

	if ( empty( $popup_query->posts ) ) {
		$popup_query = new WP_Query(
			array_merge(
				$query_args,
				array(
					'meta_query' => array(
						array(
							'key'     => '_liga_popup_active',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			)
		);
	}

	if ( empty( $popup_query->posts ) ) {
		return null;
	}

	return $popup_query->posts[0];
}

/**
 * Determina si el pop-up debe mostrarse en la vista actual.
 *
 * @param int $popup_id ID del pop-up.
 * @return bool
 */
function liga_should_display_popup_on_current_page( $popup_id ) {
	$scope = (string) get_post_meta( $popup_id, '_liga_popup_scope', true );

	if ( '' === $scope ) {
		$scope = (string) liga_get_popup_meta_value( $popup_id, '_liga_popup_display_scope', 'home' );
	}

	if ( ! array_key_exists( $scope, liga_get_popup_scope_options() ) ) {
		$scope = 'home';
	}

	if ( 'all' === $scope ) {
		return true;
	}

	return is_front_page() || is_home();
}

/**
 * Indica si hay un pop-up renderizable para esta vista.
 *
 * @return bool
 */
function liga_has_renderable_popup() {
	if ( ! liga_can_render_popup_request() ) {
		return false;
	}

	$popup = liga_get_active_popup();
	return $popup instanceof WP_Post && liga_should_display_popup_on_current_page( (int) $popup->ID );
}

/**
 * Obtiene código personalizado del pop-up activo.
 *
 * @return array{post:WP_Post|null,html:string,css:string,js:string}
 */
function liga_get_active_popup_code() {
	$popup = liga_get_active_popup();

	if ( ! $popup instanceof WP_Post ) {
		return array(
			'post' => null,
			'html' => '',
			'css'  => '',
			'js'   => '',
		);
	}

	return array(
		'post' => $popup,
		'html' => (string) get_post_meta( $popup->ID, '_liga_popup_custom_html', true ),
		'css'  => (string) get_post_meta( $popup->ID, '_liga_popup_custom_css', true ),
		'js'   => (string) get_post_meta( $popup->ID, '_liga_popup_custom_js', true ),
	);
}

/**
 * Indica si debe cargarse el controlador JS por defecto.
 *
 * @return bool
 */
function liga_should_enqueue_default_popup_script() {
	if ( ! liga_has_renderable_popup() ) {
		return false;
	}

	$code = liga_get_active_popup_code();
	$custom_js  = liga_popup_normalize_code_for_compare( $code['js'] );
	$default_js = liga_popup_normalize_code_for_compare( liga_get_default_swish_popup_js() );

	return '' === $custom_js || $custom_js === $default_js;
}

/**
 * Evita cierres accidentales de style/script dentro de código administrable.
 *
 * @param string $code Código.
 * @param string $tag Tag.
 * @return string
 */
function liga_popup_escape_inline_close_tag( $code, $tag ) {
	return str_ireplace( '</' . $tag, '<\/' . $tag, $code );
}

/**
 * Normaliza código para comparaciones sin afectar el render.
 *
 * @param string $code Código.
 * @return string
 */
function liga_popup_normalize_code_for_compare( $code ) {
	return trim( str_replace( array( "\r\n", "\r" ), "\n", $code ) );
}

/**
 * Lee un asset del tema de forma segura.
 *
 * @param string $relative_path Ruta relativa al tema.
 * @return string
 */
function liga_get_popup_theme_asset_contents( $relative_path ) {
	$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );

	if ( ! is_readable( $path ) ) {
		return '';
	}

	$contents = file_get_contents( $path );
	return is_string( $contents ) ? $contents : '';
}

/**
 * Obtiene el CSS por defecto del pop-up.
 *
 * @return string
 */
function liga_get_default_swish_popup_css() {
	$css    = liga_get_popup_theme_asset_contents( 'assets/css/main.css' );
	$start  = strpos( $css, '/* SWISH POPUP — NO MODIFICAR */' );
	$finish = strpos( $css, '/* /SWISH POPUP — NO MODIFICAR */' );

	if ( false === $start || false === $finish || $finish <= $start ) {
		return '';
	}

	return trim( substr( $css, $start, $finish - $start ) );
}

/**
 * Obtiene el JS por defecto del pop-up.
 *
 * @return string
 */
function liga_get_default_swish_popup_js() {
	return trim( liga_get_popup_theme_asset_contents( 'assets/js/popup.js' ) );
}

/**
 * Renderiza el diseño Swish por defecto.
 *
 * @return void
 */
function liga_render_default_swish_popup_html() {
	?>
<div id="swish-popup-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.65); z-index:9999; align-items:center; justify-content:center; padding:1rem;">
  <div id="swish-popup" style="background:#0a0a0f; border-radius:20px; max-width:480px; width:100%; overflow:hidden; border:1px solid rgba(255,255,255,0.08); position:relative; animation:swishPopupIn 0.35s cubic-bezier(0.34,1.56,0.64,1) forwards;">
    <div style="position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#F97316,#EF4444,#A855F7);"></div>
    <button onclick="document.getElementById('swish-popup-overlay').style.display='none'" aria-label="Cerrar popup" style="position:absolute; top:14px; right:14px; background:rgba(255,255,255,0.07); border:none; border-radius:50%; width:30px; height:30px; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:10; color:#fff; font-size:18px; line-height:1;">×</button>
    <div style="padding:2rem 2rem 0;">
      <div style="display:flex; justify-content:center; margin-bottom:1.5rem;">
        <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:14px 28px; display:inline-flex; align-items:center; justify-content:center;">
          <img
            src="https://swishbynbn23.com/wp-content/uploads/2023/06/Horizontal.png"
            alt="Swish by NBN23"
            onerror="this.parentElement.innerHTML='<div style=\'display:flex;align-items:center;gap:10px;\'><div style=\'width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,#F97316,#EF4444);display:flex;align-items:center;justify-content:center;\'><svg width=20 height=20 viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'#fff\' stroke-width=\'2.2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><circle cx=12 cy=12 r=10/><path d=\'M12 8v4l3 3\'/></svg></div><span style=\'font-size:20px;font-weight:800;color:#fff;\'>Swish</span><span style=\'font-size:11px;color:rgba(255,255,255,0.4);margin-left:4px;align-self:flex-end;margin-bottom:2px;\'>by NBN23</span></div>'"
            style="height:36px; width:auto; object-fit:contain; filter:brightness(0) invert(1);"
          />
        </div>
      </div>
      <div style="display:flex; align-items:center; justify-content:center; gap:6px; margin-bottom:0.75rem;">
        <span id="swish-live-dot" style="width:7px; height:7px; border-radius:50%; background:#EF4444; display:inline-block;"></span>
        <p style="margin:0; font-size:11px; color:#F97316; font-weight:600; letter-spacing:0.12em; text-transform:uppercase;">Liga en vivo</p>
      </div>
      <h2 style="margin:0 0 0.5rem; font-size:22px; font-weight:800; color:#fff; line-height:1.25; text-align:center;">
        Estadísticas y resultados
        <span style="background:linear-gradient(90deg,#F97316,#EF4444); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">en tiempo real</span>
      </h2>
      <p style="margin:0 0 1.4rem; font-size:14px; color:rgba(255,255,255,0.5); line-height:1.6; text-align:center;">
        Tabla de posiciones, goleadores y resultados al instante. Apenas termina el partido, todo está actualizado.
      </p>
      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:1.5rem;">
        <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:12px 10px; text-align:center;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#F97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block; margin:0 auto 6px;"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
          <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.5); line-height:1.4;">Estadísticas en vivo</p>
        </div>
        <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:12px 10px; text-align:center;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#A855F7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block; margin:0 auto 6px;"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
          <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.5); line-height:1.4;">Tabla de posiciones</p>
        </div>
        <div style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.07); border-radius:12px; padding:12px 10px; text-align:center;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:block; margin:0 auto 6px;"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a6 6 0 0 1 12 0v2"/></svg>
          <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.5); line-height:1.4;">Top goleadores</p>
        </div>
      </div>
    </div>
    <div style="padding:0 2rem 1.8rem;">
      <p style="margin:0 0 0.7rem; font-size:12px; color:rgba(255,255,255,0.35); text-transform:uppercase; letter-spacing:0.1em; font-weight:600;">Disponible en</p>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="https://apps.apple.com/es/app/swish-by-nbn23/id1472705925" target="_blank" rel="noopener" style="flex:1; min-width:150px; display:flex; align-items:center; gap:11px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:12px; padding:11px 16px; text-decoration:none;">
          <svg height="22" viewBox="0 0 24 24" fill="rgba(255,255,255,0.9)" xmlns="http://www.w3.org/2000/svg"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
          <div>
            <p style="margin:0; font-size:10px; color:rgba(255,255,255,0.4);">Disponible en</p>
            <p style="margin:0; font-size:14px; font-weight:700; color:#fff;">App Store</p>
          </div>
        </a>
        <a href="https://play.google.com/store/apps/details?id=com.nbn23.fansapp&utm_source=landing&utm_medium=landing&utm_campaign=swish&utm_content=Landing" target="_blank" rel="noopener" style="flex:1; min-width:150px; display:flex; align-items:center; gap:11px; background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:12px; padding:11px 16px; text-decoration:none;">
          <svg height="22" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3.18 23.65c.3.17.65.19.96.06L15 13l-3.27-3.27L3.18 23.65z" fill="#EA4335"/><path d="M20.82 10.67 17.5 8.77l-3.66 3.23L17.5 15.2l3.35-1.92a1.45 1.45 0 0 0 0-2.61z" fill="#FBBC05"/><path d="M3.18.35A1.43 1.43 0 0 0 3 1v22a1.43 1.43 0 0 0 .18.65L14.07 12 3.18.35z" fill="#4285F4"/><path d="m3.96.29 11.6 10.44L12.23 14l-8.27-8.27L3.96.29z" fill="#34A853"/></svg>
          <div>
            <p style="margin:0; font-size:10px; color:rgba(255,255,255,0.4);">Disponible en</p>
            <p style="margin:0; font-size:14px; font-weight:700; color:#fff;">Google Play</p>
          </div>
        </a>
      </div>
      <a href="https://swishbynbn23.com/es" target="_blank" rel="noopener" style="display:block; margin-top:12px; background:linear-gradient(135deg,#F97316,#EF4444); border-radius:12px; padding:14px; text-align:center; text-decoration:none; color:#fff; font-weight:700; font-size:15px; letter-spacing:0.01em;">
        Descargar Swish gratis
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px; margin-left:6px;"><path d="M12 16V8M8 12l4 4 4-4"/><rect x="3" y="3" width="18" height="18" rx="3"/></svg>
      </a>
      <p style="margin:12px 0 0; font-size:11px; color:rgba(255,255,255,0.2); text-align:center;">Gratis · Sin publicidad · Actualización automática</p>
    </div>
  </div>
</div>
	<?php
}

/**
 * Obtiene el HTML por defecto del pop-up.
 *
 * @return string
 */
function liga_get_default_swish_popup_html() {
	ob_start();
	liga_render_default_swish_popup_html();
	return (string) ob_get_clean();
}

/**
 * Renderiza el pop-up activo.
 *
 * @return void
 */
function liga_render_active_popup() {
	static $rendered = false;

	if ( $rendered || ! liga_has_renderable_popup() ) {
		return;
	}

	$code = liga_get_active_popup_code();
	if ( ! $code['post'] instanceof WP_Post ) {
		return;
	}

	$rendered = true;

	$custom_css  = liga_popup_normalize_code_for_compare( $code['css'] );
	$default_css = liga_popup_normalize_code_for_compare( liga_get_default_swish_popup_css() );
	$custom_js   = liga_popup_normalize_code_for_compare( $code['js'] );
	$default_js  = liga_popup_normalize_code_for_compare( liga_get_default_swish_popup_js() );

	if ( '' !== $custom_css && $custom_css !== $default_css ) {
		?>
<style id="liga-popup-custom-css">
<?php echo liga_popup_escape_inline_close_tag( $code['css'], 'style' ); ?>
</style>
		<?php
	}

	if ( '' !== trim( $code['html'] ) ) {
		echo $code['html'];
	} else {
		liga_render_default_swish_popup_html();
	}

	if ( '' !== $custom_js && $custom_js !== $default_js ) {
		?>
<script id="liga-popup-custom-js">
<?php echo liga_popup_escape_inline_close_tag( $code['js'], 'script' ); ?>
</script>
		<?php
	}
}
add_action( 'wp_footer', 'liga_render_active_popup', 99 );
