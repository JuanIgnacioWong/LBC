<?php
/**
 * Pantalla admin para tabla (fase 2).
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra menu administrativo de Liga Basquet.
 *
 * @return void
 */
function liga_register_admin_menu() {
	add_menu_page(
		__( 'Liga Basquet', 'liga-basket-chile' ),
		__( 'Liga Basquet', 'liga-basket-chile' ),
		'edit_posts',
		'liga-basquet-dashboard',
		'liga_render_admin_dashboard_page',
		'dashicons-trophy',
		30
	);

	add_submenu_page(
		'liga-basquet-dashboard',
		__( 'Dashboard', 'liga-basket-chile' ),
		__( 'Dashboard', 'liga-basket-chile' ),
		'edit_posts',
		'liga-basquet-dashboard',
		'liga_render_admin_dashboard_page'
	);

	add_submenu_page(
		'liga-basquet-dashboard',
		__( 'Tabla Posiciones', 'liga-basket-chile' ),
		__( 'Tabla Posiciones', 'liga-basket-chile' ),
		'edit_posts',
		'liga-basquet-tabla',
		'liga_render_admin_tabla_page'
	);

	add_submenu_page(
		'liga-basquet-dashboard',
		__( 'Equipos', 'liga-basket-chile' ),
		__( 'Equipos', 'liga-basket-chile' ),
		'edit_posts',
		'edit.php?post_type=equipo'
	);

	add_submenu_page(
		'liga-basquet-dashboard',
		__( 'Importar Equipos', 'liga-basket-chile' ),
		__( 'Importar Equipos', 'liga-basket-chile' ),
		'edit_posts',
		'liga-importar-equipos',
		'liga_render_admin_import_equipos_page'
	);

	add_submenu_page(
		'liga-basquet-dashboard',
		__( 'Partidos', 'liga-basket-chile' ),
		__( 'Partidos', 'liga-basket-chile' ),
		'edit_posts',
		'edit.php?post_type=partido'
	);

	add_submenu_page(
		'liga-basquet-dashboard',
		__( 'Importar Partidos', 'liga-basket-chile' ),
		__( 'Importar Partidos', 'liga-basket-chile' ),
		'edit_posts',
		'liga-importar-partidos',
		'liga_render_admin_import_partidos_page'
	);

	add_submenu_page(
		'liga-basquet-dashboard',
		__( 'Noticias', 'liga-basket-chile' ),
		__( 'Noticias', 'liga-basket-chile' ),
		'edit_posts',
		'edit.php'
	);

	add_submenu_page(
		'liga-basquet-dashboard',
		__( 'Configuracion', 'liga-basket-chile' ),
		__( 'Configuracion', 'liga-basket-chile' ),
		'manage_options',
		'liga-basquet-configuracion',
		'liga_render_admin_config_page'
	);
}
add_action( 'admin_menu', 'liga_register_admin_menu' );

/**
 * Muestra alertas administrativas.
 *
 * @return void
 */
function liga_admin_notices() {
	$alerts = liga_pull_admin_alerts();
	if ( empty( $alerts ) ) {
		return;
	}

	foreach ( $alerts as $alert ) {
		$type = isset( $alert['type'] ) ? sanitize_html_class( $alert['type'] ) : 'info';
		$message = isset( $alert['message'] ) ? $alert['message'] : '';
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'liga_admin_notices' );

/**
 * Render dashboard principal de la liga.
 *
 * @return void
 */
function liga_render_admin_dashboard_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pagina.', 'liga-basket-chile' ) );
	}

	$today = gmdate( 'Y-m-d' );

	$upcoming = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'meta_query'     => array(
				array(
					'key'   => 'liga_estado_partido',
					'value' => 'programado',
				),
				array(
					'key'     => 'liga_fecha_partido',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
			'meta_key'       => 'liga_fecha_partido',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		)
	);

	$results = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'meta_query'     => array(
				array(
					'key'     => 'liga_estado_partido',
					'value'   => array( 'jugado', 'finalizado' ),
					'compare' => 'IN',
				),
			),
			'meta_key'       => 'liga_fecha_partido',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
		)
	);

	$teams_total = wp_count_posts( 'equipo' );
	$alerts      = array();
	$season      = liga_get_current_season_label();
	$divisions   = get_posts(
		array(
			'post_type'      => 'division',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	foreach ( $divisions as $division ) {
		$table = liga_calcular_tabla_posiciones( $division->ID, $season );
		if ( ! empty( $table['alerts'] ) ) {
			$alerts = array_merge( $alerts, $table['alerts'] );
		}
	}

	$recent_news = wp_get_recent_posts(
		array(
			'post_type'   => 'post',
			'numberposts' => 5,
			'post_status' => 'publish',
		),
		OBJECT
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Dashboard Liga Basquet', 'liga-basket-chile' ); ?></h1>
		<div class="card">
			<p><strong><?php esc_html_e( 'Equipos inscritos', 'liga-basket-chile' ); ?>:</strong> <?php echo esc_html( (string) (int) $teams_total->publish ); ?></p>
			<p><strong><?php esc_html_e( 'Temporada activa', 'liga-basket-chile' ); ?>:</strong> <?php echo esc_html( $season ); ?></p>
		</div>

		<h2><?php esc_html_e( 'Proximos partidos', 'liga-basket-chile' ); ?></h2>
		<ul>
			<?php if ( empty( $upcoming ) ) : ?>
				<li><?php esc_html_e( 'No hay partidos programados.', 'liga-basket-chile' ); ?></li>
			<?php endif; ?>
			<?php foreach ( $upcoming as $match ) : ?>
				<?php
				$fecha = get_post_meta( $match->ID, 'liga_fecha_partido', true );
				$hora  = get_post_meta( $match->ID, 'liga_hora_partido', true );
				?>
				<li><?php echo esc_html( $match->post_title . ' - ' . $fecha . ' ' . $hora ); ?></li>
			<?php endforeach; ?>
		</ul>

		<h2><?php esc_html_e( 'Ultimos resultados', 'liga-basket-chile' ); ?></h2>
		<ul>
			<?php if ( empty( $results ) ) : ?>
				<li><?php esc_html_e( 'No hay resultados disponibles.', 'liga-basket-chile' ); ?></li>
			<?php endif; ?>
			<?php foreach ( $results as $match ) : ?>
				<?php
				$local_points  = (int) get_post_meta( $match->ID, 'liga_puntos_local', true );
				$visita_points = (int) get_post_meta( $match->ID, 'liga_puntos_visita', true );
				?>
				<li><?php echo esc_html( $match->post_title . ' (' . $local_points . ' - ' . $visita_points . ')' ); ?></li>
			<?php endforeach; ?>
		</ul>

		<h2><?php esc_html_e( 'Alertas pendientes', 'liga-basket-chile' ); ?></h2>
		<ul>
			<?php if ( empty( $alerts ) ) : ?>
				<li><?php esc_html_e( 'Sin alertas pendientes.', 'liga-basket-chile' ); ?></li>
			<?php else : ?>
				<?php foreach ( $alerts as $alert ) : ?>
					<li><?php echo esc_html( $alert ); ?></li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>

		<h2><?php esc_html_e( 'Noticias recientes', 'liga-basket-chile' ); ?></h2>
		<ul>
			<?php if ( empty( $recent_news ) ) : ?>
				<li><?php esc_html_e( 'No hay noticias publicadas.', 'liga-basket-chile' ); ?></li>
			<?php endif; ?>
			<?php foreach ( $recent_news as $news ) : ?>
				<li><a href="<?php echo esc_url( get_edit_post_link( $news->ID ) ); ?>"><?php echo esc_html( $news->post_title ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * Maneja guardado de override manual de posiciones.
 *
 * @return void
 */
function liga_handle_override_submission() {
	if ( ! isset( $_POST['liga_override_nonce'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['liga_override_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'liga_save_override' ) ) {
		return;
	}

	$equipo_id = isset( $_POST['equipo_id'] ) ? absint( wp_unslash( $_POST['equipo_id'] ) ) : 0;
	if ( $equipo_id <= 0 || 'equipo' !== get_post_type( $equipo_id ) ) {
		liga_add_admin_alert( 'error', __( 'No se pudo actualizar el override del equipo.', 'liga-basket-chile' ) );
		return;
	}

	$posicion_manual = isset( $_POST['posicion_manual'] ) ? absint( wp_unslash( $_POST['posicion_manual'] ) ) : 0;
	$activar         = liga_sanitize_checkbox( isset( $_POST['activar_override'] ) ? wp_unslash( $_POST['activar_override'] ) : 0 );

	update_post_meta( $equipo_id, 'liga_posicion_manual', $posicion_manual );
	update_post_meta( $equipo_id, 'liga_activar_override', $activar );
	liga_flush_table_cache();

	liga_add_admin_alert( 'success', __( 'Override manual actualizado.', 'liga-basket-chile' ) );
}
add_action( 'admin_init', 'liga_handle_override_submission' );

/**
 * Renderiza pagina tabla de posiciones admin.
 *
 * @return void
 */
function liga_render_admin_tabla_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pagina.', 'liga-basket-chile' ) );
	}

	$season_default = liga_get_current_season_label();
	$division_id    = isset( $_GET['division'] ) ? absint( wp_unslash( $_GET['division'] ) ) : 0;
	$temporada      = liga_normalize_temporada_label(
		isset( $_GET['temporada'] ) ? sanitize_text_field( wp_unslash( $_GET['temporada'] ) ) : '',
		$season_default
	);
	$force_recalc   = false;
	if ( isset( $_GET['recalcular'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['recalcular'] ) ) ) {
		$nonce        = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		$force_recalc = wp_verify_nonce( $nonce, 'liga_recalcular_tabla_admin' );
		if ( ! $force_recalc ) {
			liga_add_admin_alert( 'error', __( 'Nonce invalido al recalcular la tabla.', 'liga-basket-chile' ) );
		}
	}

	$divisions = get_posts(
		array(
			'post_type'      => 'division',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'liga_orden_visual',
			'order'          => 'ASC',
		)
	);

	$temporadas = liga_get_available_temporadas();
	if ( '' !== $temporada && ! isset( $temporadas[ $temporada ] ) ) {
		$temporadas[ $temporada ] = $temporada;
	}

	$table        = array(
		'tabla'                => array(),
		'alerts'               => array(),
		'partidos_computados'  => 0,
		'partidos_descartados' => 0,
		'total_equipos'        => 0,
	);
	$can_preview  = $division_id > 0 && liga_is_valid_temporada_label( $temporada );
	if ( $can_preview ) {
		if ( $force_recalc && function_exists( 'liga_clear_standings_cache' ) ) {
			liga_clear_standings_cache( $division_id, $temporada );
		}
		$table = liga_calcular_tabla_posiciones( $division_id, $temporada, $force_recalc );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Tabla de Posiciones', 'liga-basket-chile' ); ?></h1>
		<form method="get">
			<input type="hidden" name="page" value="liga-basquet-tabla">
			<label for="division"><strong><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></strong></label>
			<select id="division" name="division">
				<option value="0"><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
				<?php foreach ( $divisions as $division ) : ?>
					<option value="<?php echo esc_attr( (string) $division->ID ); ?>" <?php selected( $division_id, (int) $division->ID ); ?>>
						<?php echo esc_html( $division->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="temporada"><strong><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></strong></label>
			<select id="temporada" name="temporada">
				<?php foreach ( $temporadas as $temporada_key => $temporada_label ) : ?>
					<option value="<?php echo esc_attr( (string) $temporada_key ); ?>" <?php selected( $temporada, (string) $temporada_key ); ?>>
						<?php echo esc_html( (string) $temporada_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php wp_nonce_field( 'liga_recalcular_tabla_admin' ); ?>
			<button class="button button-primary" type="submit"><?php esc_html_e( 'Previsualizar tabla', 'liga-basket-chile' ); ?></button>
			<button class="button" type="submit" name="recalcular" value="1"><?php esc_html_e( 'Recalcular', 'liga-basket-chile' ); ?></button>
		</form>

		<?php if ( ! $can_preview ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Selecciona una division/categoria y temporada para previsualizar la tabla.', 'liga-basket-chile' ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! empty( $table['alerts'] ) ) : ?>
			<div class="notice notice-warning"><p><?php echo esc_html( implode( ' | ', $table['alerts'] ) ); ?></p></div>
		<?php endif; ?>
		<?php if ( $can_preview && empty( $table['total_equipos'] ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'No hay equipos inscritos para esta division y temporada.', 'liga-basket-chile' ); ?></p></div>
		<?php elseif ( $can_preview && empty( $table['partidos_computados'] ) ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'No se encontraron partidos jugados computables. Los equipos se muestran con estadisticas en cero.', 'liga-basket-chile' ); ?></p></div>
		<?php endif; ?>
		<p>
			<strong><?php esc_html_e( 'Partidos computados', 'liga-basket-chile' ); ?>:</strong>
			<?php echo esc_html( (string) (int) ( isset( $table['partidos_computados'] ) ? $table['partidos_computados'] : 0 ) ); ?>
			|
			<strong><?php esc_html_e( 'Partidos descartados', 'liga-basket-chile' ); ?>:</strong>
			<?php echo esc_html( (string) (int) ( isset( $table['partidos_descartados'] ) ? $table['partidos_descartados'] : 0 ) ); ?>
		</p>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Pos', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'Equipo', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'PJ', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'PG', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'PP', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'INC', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'PTS', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'PF', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'PC', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'DIF', 'liga-basket-chile' ); ?></th>
					<th><?php esc_html_e( 'Override', 'liga-basket-chile' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $table['tabla'] ) ) : ?>
					<tr><td colspan="11"><?php echo esc_html( $can_preview ? __( 'No hay datos para los filtros seleccionados.', 'liga-basket-chile' ) : __( 'Selecciona filtros para ver la previsualizacion.', 'liga-basket-chile' ) ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $table['tabla'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( (string) (int) $row['pos'] ); ?></td>
						<td><?php echo esc_html( (string) $row['equipo'] ); ?></td>
						<td><?php echo esc_html( (string) (int) $row['pj'] ); ?></td>
						<td><?php echo esc_html( (string) (int) $row['pg'] ); ?></td>
						<td><?php echo esc_html( (string) (int) $row['pp'] ); ?></td>
						<td><?php echo esc_html( (string) (int) $row['inc'] ); ?></td>
						<td><strong><?php echo esc_html( (string) (int) $row['pts'] ); ?></strong></td>
						<td><?php echo esc_html( (string) (int) ( isset( $row['pf'] ) ? $row['pf'] : 0 ) ); ?></td>
						<td><?php echo esc_html( (string) (int) ( isset( $row['pc'] ) ? $row['pc'] : 0 ) ); ?></td>
						<td><?php echo esc_html( (string) (int) ( isset( $row['dif'] ) ? $row['dif'] : 0 ) ); ?></td>
						<td>
							<form method="post">
								<?php wp_nonce_field( 'liga_save_override', 'liga_override_nonce' ); ?>
								<input type="hidden" name="equipo_id" value="<?php echo esc_attr( (string) (int) $row['equipo_id'] ); ?>">
								<input type="number" name="posicion_manual" class="small-text" min="1" value="<?php echo esc_attr( (string) (int) $row['override_posicion'] ); ?>">
								<label>
									<input type="checkbox" name="activar_override" value="1" <?php checked( (int) $row['override_activo'], 1 ); ?>>
									<?php esc_html_e( 'Activo', 'liga-basket-chile' ); ?>
								</label>
								<button type="submit" class="button button-secondary"><?php esc_html_e( 'Guardar', 'liga-basket-chile' ); ?></button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Render submenu de configuracion reutilizando opciones del tema.
 *
 * @return void
 */
function liga_render_admin_config_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pagina.', 'liga-basket-chile' ) );
	}

	if ( function_exists( 'liga_render_theme_options_page' ) ) {
		liga_render_theme_options_page();
		return;
	}

	echo '<div class="wrap"><h1>' . esc_html__( 'Configuracion', 'liga-basket-chile' ) . '</h1><p>' . esc_html__( 'Modulo de opciones no disponible.', 'liga-basket-chile' ) . '</p></div>';
}

/**
 * Registra widgets en dashboard nativo de WordPress.
 *
 * @return void
 */
function liga_register_wp_dashboard_widgets() {
	wp_add_dashboard_widget( 'liga_widget_upcoming', __( 'Liga: Proximos partidos', 'liga-basket-chile' ), 'liga_widget_upcoming_matches' );
	wp_add_dashboard_widget( 'liga_widget_results', __( 'Liga: Ultimos resultados', 'liga-basket-chile' ), 'liga_widget_latest_results' );
	wp_add_dashboard_widget( 'liga_widget_teams', __( 'Liga: Equipos inscritos', 'liga-basket-chile' ), 'liga_widget_teams_total' );
	wp_add_dashboard_widget( 'liga_widget_alerts', __( 'Liga: Alertas', 'liga-basket-chile' ), 'liga_widget_alerts' );
	wp_add_dashboard_widget( 'liga_widget_news', __( 'Liga: Noticias recientes', 'liga-basket-chile' ), 'liga_widget_recent_news' );
}
add_action( 'wp_dashboard_setup', 'liga_register_wp_dashboard_widgets' );

/**
 * Widget proximos partidos.
 *
 * @return void
 */
function liga_widget_upcoming_matches() {
	$matches = get_posts(
		array(
			'post_type'      => 'partido',
			'posts_per_page' => 5,
			'post_status'    => 'publish',
			'meta_key'       => 'liga_fecha_partido',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'   => 'liga_estado_partido',
					'value' => 'programado',
				),
			),
		)
	);

	if ( empty( $matches ) ) {
		echo '<p>' . esc_html__( 'Sin partidos programados.', 'liga-basket-chile' ) . '</p>';
		return;
	}

	echo '<ul>';
	foreach ( $matches as $match ) {
		echo '<li>' . esc_html( $match->post_title ) . '</li>';
	}
	echo '</ul>';
}

/**
 * Widget ultimos resultados.
 *
 * @return void
 */
function liga_widget_latest_results() {
	$matches = get_posts(
		array(
			'post_type'      => 'partido',
			'posts_per_page' => 5,
			'post_status'    => 'publish',
			'meta_key'       => 'liga_fecha_partido',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => 'liga_estado_partido',
					'value'   => array( 'jugado', 'finalizado' ),
					'compare' => 'IN',
				),
			),
		)
	);

	if ( empty( $matches ) ) {
		echo '<p>' . esc_html__( 'Sin resultados recientes.', 'liga-basket-chile' ) . '</p>';
		return;
	}

	echo '<ul>';
	foreach ( $matches as $match ) {
		$local  = (int) get_post_meta( $match->ID, 'liga_puntos_local', true );
		$visita = (int) get_post_meta( $match->ID, 'liga_puntos_visita', true );
		echo '<li>' . esc_html( $match->post_title . ' (' . $local . ' - ' . $visita . ')' ) . '</li>';
	}
	echo '</ul>';
}

/**
 * Widget total de equipos.
 *
 * @return void
 */
function liga_widget_teams_total() {
	$count = wp_count_posts( 'equipo' );
	echo '<p><strong>' . esc_html__( 'Equipos publicados:', 'liga-basket-chile' ) . '</strong> ' . esc_html( (string) (int) $count->publish ) . '</p>';
}

/**
 * Widget de alertas.
 *
 * @return void
 */
function liga_widget_alerts() {
	$season    = liga_get_current_season_label();
	$divisions = get_posts(
		array(
			'post_type'      => 'division',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
		)
	);

	$alerts = array();
	foreach ( $divisions as $division ) {
		$data = liga_calcular_tabla_posiciones( $division->ID, $season );
		if ( ! empty( $data['alerts'] ) ) {
			$alerts = array_merge( $alerts, $data['alerts'] );
		}
	}

	if ( empty( $alerts ) ) {
		echo '<p>' . esc_html__( 'Sin alertas pendientes.', 'liga-basket-chile' ) . '</p>';
		return;
	}

	echo '<ul>';
	foreach ( $alerts as $alert ) {
		echo '<li>' . esc_html( $alert ) . '</li>';
	}
	echo '</ul>';
}

/**
 * Widget noticias recientes.
 *
 * @return void
 */
function liga_widget_recent_news() {
	$news = wp_get_recent_posts(
		array(
			'post_type'   => 'post',
			'numberposts' => 5,
			'post_status' => 'publish',
		),
		OBJECT
	);

	if ( empty( $news ) ) {
		echo '<p>' . esc_html__( 'Sin noticias recientes.', 'liga-basket-chile' ) . '</p>';
		return;
	}

	echo '<ul>';
	foreach ( $news as $item ) {
		echo '<li><a href="' . esc_url( get_edit_post_link( $item->ID ) ) . '">' . esc_html( $item->post_title ) . '</a></li>';
	}
	echo '</ul>';
}
