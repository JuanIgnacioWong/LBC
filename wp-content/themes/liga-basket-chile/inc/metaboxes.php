<?php
/**
 * Metaboxes deportivas (fase 2).
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra metaboxes de equipos, partidos, divisiones y banner principal.
 *
 * @return void
 */
function liga_register_metaboxes() {
	add_meta_box(
		'liga_equipo_detalles',
		__( 'Datos del Equipo', 'liga-basket-chile' ),
		'liga_render_equipo_metabox',
		'equipo',
		'normal',
		'high'
	);

	add_meta_box(
		'liga_partido_detalles',
		__( 'Datos del Partido', 'liga-basket-chile' ),
		'liga_render_partido_metabox',
		'partido',
		'normal',
		'high'
	);

	add_meta_box(
		'liga_division_detalles',
		__( 'Datos de la Division', 'liga-basket-chile' ),
		'liga_render_division_metabox',
		'division',
		'normal',
		'high'
	);

	add_meta_box(
		'liga_banner_principal_detalles',
		__( 'Datos del Banner Principal', 'liga-basket-chile' ),
		'liga_render_banner_principal_metabox',
		'banner-principal',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'liga_register_metaboxes' );

/**
 * Registra metadatos para exponerlos en REST y validar tipos.
 *
 * @return void
 */
function liga_register_post_meta_fields() {
	$equipo_meta = array(
		'liga_nombre_equipo'    => 'string',
		'liga_logo_equipo'      => 'integer',
		'liga_ciudad'           => 'string',
		'liga_anio_fundacion'   => 'integer',
		'liga_division'         => 'integer',
		'liga_temporada'        => 'string',
		'liga_color_principal'  => 'string',
		'liga_entrenador'       => 'string',
		'liga_posicion_manual'  => 'integer',
		'liga_activar_override' => 'integer',
		'liga_equipo_competicion_key' => 'string',
	);

	$partido_meta = array(
		'liga_equipo_local'    => 'integer',
		'liga_equipo_visita'   => 'integer',
		'liga_division'        => 'integer',
		'liga_temporada'       => 'string',
		'liga_fecha_partido'   => 'string',
		'liga_hora_partido'    => 'string',
		'liga_cancha'          => 'string',
		'liga_estado_partido'  => 'string',
		'liga_puntos_local'    => 'integer',
		'liga_puntos_visita'   => 'integer',
		'liga_incomparecencia' => 'string',
		'liga_observaciones'   => 'string',
	);

	$division_meta = array(
		'liga_nombre_division' => 'string',
		'liga_temporada'       => 'string',
		'liga_orden_visual'    => 'integer',
		'liga_activa'          => 'integer',
	);

	$banner_meta = array(
		'_liga_banner_image_id'           => 'integer',
		'liga_banner_eyebrow'             => 'string',
		'liga_banner_titulo'              => 'string',
		'liga_banner_bajada'              => 'string',
		'liga_banner_cta_principal_texto' => 'string',
		'liga_banner_cta_principal_url'   => 'string',
		'liga_banner_cta_secundario_texto'=> 'string',
		'liga_banner_cta_secundario_url'  => 'string',
		'liga_banner_imagen_id'           => 'integer',
		'liga_banner_activo'              => 'integer',
		'liga_banner_orden_visual'        => 'integer',
		'liga_banner_alineacion_texto'    => 'string',
		'liga_banner_altura'              => 'string',
		'liga_banner_overlay'             => 'integer',
		'liga_banner_fondo_degradado'     => 'integer',
		'liga_banner_autoplay'            => 'integer',
	);

	foreach ( $equipo_meta as $key => $type ) {
		register_post_meta(
			'equipo',
			$key,
			array(
				'type'         => $type,
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback'=> static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	foreach ( $partido_meta as $key => $type ) {
		register_post_meta(
			'partido',
			$key,
			array(
				'type'         => $type,
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback'=> static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	foreach ( $division_meta as $key => $type ) {
		register_post_meta(
			'division',
			$key,
			array(
				'type'         => $type,
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback'=> static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	foreach ( $banner_meta as $key => $type ) {
		register_post_meta(
			'banner-principal',
			$key,
			array(
				'type'          => $type,
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'liga_register_post_meta_fields' );

/**
 * Obtiene listado id => titulo para un CPT.
 *
 * @param string $post_type Tipo de post.
 * @return array<int, string>
 */
function liga_get_posts_map( $post_type ) {
	$posts   = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$result  = array();
	foreach ( $posts as $post_item ) {
		$result[ (int) $post_item->ID ] = $post_item->post_title;
	}
	return $result;
}

/**
 * Render metabox equipo.
 *
 * @param WP_Post $post Post actual.
 * @return void
 */
function liga_render_equipo_metabox( $post ) {
	wp_nonce_field( 'liga_save_equipo_meta', 'liga_equipo_nonce' );

	$fields = array(
		'liga_nombre_equipo'   => get_post_meta( $post->ID, 'liga_nombre_equipo', true ),
		'liga_logo_equipo'     => get_post_meta( $post->ID, 'liga_logo_equipo', true ),
		'liga_ciudad'          => get_post_meta( $post->ID, 'liga_ciudad', true ),
		'liga_anio_fundacion'  => get_post_meta( $post->ID, 'liga_anio_fundacion', true ),
		'liga_division'        => get_post_meta( $post->ID, 'liga_division', true ),
		'liga_temporada'       => get_post_meta( $post->ID, 'liga_temporada', true ),
		'liga_color_principal' => get_post_meta( $post->ID, 'liga_color_principal', true ),
		'liga_entrenador'      => get_post_meta( $post->ID, 'liga_entrenador', true ),
		'liga_posicion_manual' => get_post_meta( $post->ID, 'liga_posicion_manual', true ),
		'liga_activar_override'=> get_post_meta( $post->ID, 'liga_activar_override', true ),
	);

	$divisions  = liga_get_posts_map( 'division' );
	$temporadas = liga_get_available_temporadas();
	$current_temporada = trim( sanitize_text_field( (string) $fields['liga_temporada'] ) );
	if ( '' !== $current_temporada && ! isset( $temporadas[ $current_temporada ] ) ) {
		$temporadas[ $current_temporada ] = $current_temporada;
	}
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="liga_nombre_equipo"><?php esc_html_e( 'Nombre equipo', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_nombre_equipo" name="liga_nombre_equipo" value="<?php echo esc_attr( (string) $fields['liga_nombre_equipo'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_logo_equipo"><?php esc_html_e( 'Logo (ID adjunto)', 'liga-basket-chile' ); ?></label></th>
			<td><input type="number" class="small-text" id="liga_logo_equipo" name="liga_logo_equipo" min="0" value="<?php echo esc_attr( (string) $fields['liga_logo_equipo'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_ciudad"><?php esc_html_e( 'Ciudad', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_ciudad" name="liga_ciudad" value="<?php echo esc_attr( (string) $fields['liga_ciudad'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_anio_fundacion"><?php esc_html_e( 'Anio fundacion', 'liga-basket-chile' ); ?></label></th>
			<td><input type="number" class="small-text" id="liga_anio_fundacion" name="liga_anio_fundacion" min="1900" max="2100" value="<?php echo esc_attr( (string) $fields['liga_anio_fundacion'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_division"><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_division" name="liga_division">
					<option value="0"><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $divisions as $division_id => $division_title ) : ?>
						<option value="<?php echo esc_attr( (string) $division_id ); ?>" <?php selected( (int) $fields['liga_division'], $division_id ); ?>>
							<?php echo esc_html( $division_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="liga_temporada"><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_temporada" name="liga_temporada">
					<option value=""><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $temporadas as $temporada_key => $temporada_label ) : ?>
						<option value="<?php echo esc_attr( (string) $temporada_key ); ?>" <?php selected( $current_temporada, (string) $temporada_key ); ?>>
							<?php echo esc_html( (string) $temporada_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'La temporada debe coincidir con la temporada deportiva de la division elegida.', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="liga_color_principal"><?php esc_html_e( 'Color principal', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_color_principal" name="liga_color_principal" placeholder="#2F57D7" value="<?php echo esc_attr( (string) $fields['liga_color_principal'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_entrenador"><?php esc_html_e( 'Entrenador', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_entrenador" name="liga_entrenador" value="<?php echo esc_attr( (string) $fields['liga_entrenador'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_posicion_manual"><?php esc_html_e( 'Posicion manual', 'liga-basket-chile' ); ?></label></th>
			<td><input type="number" class="small-text" id="liga_posicion_manual" name="liga_posicion_manual" min="1" value="<?php echo esc_attr( (string) $fields['liga_posicion_manual'] ); ?>"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Activar override', 'liga-basket-chile' ); ?></th>
			<td><label><input type="checkbox" name="liga_activar_override" value="1" <?php checked( (int) $fields['liga_activar_override'], 1 ); ?>> <?php esc_html_e( 'Usar posicion manual', 'liga-basket-chile' ); ?></label></td>
		</tr>
	</table>
	<?php
}

/**
 * Render metabox partido.
 *
 * @param WP_Post $post Post actual.
 * @return void
 */
function liga_render_partido_metabox( $post ) {
	wp_nonce_field( 'liga_save_partido_meta', 'liga_partido_nonce' );

	$fields = array(
		'liga_equipo_local'    => get_post_meta( $post->ID, 'liga_equipo_local', true ),
		'liga_equipo_visita'   => get_post_meta( $post->ID, 'liga_equipo_visita', true ),
		'liga_division'        => get_post_meta( $post->ID, 'liga_division', true ),
		'liga_temporada'       => get_post_meta( $post->ID, 'liga_temporada', true ),
		'liga_fecha_partido'   => get_post_meta( $post->ID, 'liga_fecha_partido', true ),
		'liga_hora_partido'    => get_post_meta( $post->ID, 'liga_hora_partido', true ),
		'liga_cancha'          => get_post_meta( $post->ID, 'liga_cancha', true ),
		'liga_estado_partido'  => get_post_meta( $post->ID, 'liga_estado_partido', true ),
		'liga_puntos_local'    => get_post_meta( $post->ID, 'liga_puntos_local', true ),
		'liga_puntos_visita'   => get_post_meta( $post->ID, 'liga_puntos_visita', true ),
		'liga_incomparecencia' => get_post_meta( $post->ID, 'liga_incomparecencia', true ),
		'liga_observaciones'   => get_post_meta( $post->ID, 'liga_observaciones', true ),
	);

	$divisions  = liga_get_posts_map( 'division' );
	$temporadas = liga_get_available_temporadas();
	$current_temporada = trim( sanitize_text_field( (string) $fields['liga_temporada'] ) );
	if ( '' !== $current_temporada && ! isset( $temporadas[ $current_temporada ] ) ) {
		$temporadas[ $current_temporada ] = $current_temporada;
	}

	$team_posts = get_posts(
		array(
			'post_type'      => 'equipo',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$team_contexts = array();
	foreach ( $team_posts as $team_post ) {
		$team_id        = (int) $team_post->ID;
		$team_division  = liga_get_equipo_division_id( $team_id );
		$team_temporada = liga_get_equipo_temporada_label( $team_id );

		if ( $team_division <= 0 || ! liga_is_valid_temporada_label( $team_temporada ) ) {
			continue;
		}

		$team_contexts[] = array(
			'id'        => $team_id,
			'name'      => liga_get_equipo_nombre( $team_id ),
			'division'  => $team_division,
			'temporada' => $team_temporada,
		);
	}

	$current_division = absint( $fields['liga_division'] );
	$context_ready    = $current_division > 0 && liga_is_valid_temporada_label( $current_temporada );
	$selected_local   = absint( $fields['liga_equipo_local'] );
	$selected_visita  = absint( $fields['liga_equipo_visita'] );
	$filtered_teams   = array();

	if ( $context_ready ) {
		foreach ( $team_contexts as $team_context ) {
			if ( (int) $team_context['division'] !== $current_division || (string) $team_context['temporada'] !== $current_temporada ) {
				continue;
			}

			$filtered_teams[] = $team_context;
		}
	}

	$valid_team_ids = array_map(
		static function ( $team_context ) {
			return (int) $team_context['id'];
		},
		$filtered_teams
	);

	if ( ! in_array( $selected_local, $valid_team_ids, true ) ) {
		$selected_local = 0;
	}

	if ( ! in_array( $selected_visita, $valid_team_ids, true ) ) {
		$selected_visita = 0;
	}

	if ( $selected_local > 0 && $selected_local === $selected_visita ) {
		$selected_visita = 0;
	}

	$statuses  = array(
		'programado' => __( 'Programado', 'liga-basket-chile' ),
		'jugado'     => __( 'Jugado', 'liga-basket-chile' ),
		'finalizado' => __( 'Finalizado', 'liga-basket-chile' ),
		'suspendido' => __( 'Suspendido', 'liga-basket-chile' ),
		'cancelado'  => __( 'Cancelado', 'liga-basket-chile' ),
	);
	$walkovers = array(
		'ninguna'              => __( 'Ninguna', 'liga-basket-chile' ),
		'local_no_comparecio'  => __( 'Local no comparecio', 'liga-basket-chile' ),
		'visita_no_comparecio' => __( 'Visita no comparecio', 'liga-basket-chile' ),
	);
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="liga_division"><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_division" name="liga_division">
					<option value="0"><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $divisions as $division_id => $division_title ) : ?>
						<option value="<?php echo esc_attr( (string) $division_id ); ?>" <?php selected( $current_division, $division_id ); ?>><?php echo esc_html( $division_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="liga_temporada"><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_temporada" name="liga_temporada">
					<option value=""><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $temporadas as $temporada_key => $temporada_label ) : ?>
						<option value="<?php echo esc_attr( (string) $temporada_key ); ?>" <?php selected( $current_temporada, (string) $temporada_key ); ?>>
							<?php echo esc_html( (string) $temporada_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Selecciona primero division y temporada para habilitar cruces validos.', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="liga_equipo_local"><?php esc_html_e( 'Equipo local', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_equipo_local" name="liga_equipo_local" <?php disabled( ! $context_ready ); ?>>
					<option value="0"><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $filtered_teams as $team_context ) : ?>
						<?php if ( (int) $team_context['id'] === $selected_visita ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<option value="<?php echo esc_attr( (string) $team_context['id'] ); ?>" <?php selected( $selected_local, (int) $team_context['id'] ); ?>><?php echo esc_html( (string) $team_context['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="liga_equipo_visita"><?php esc_html_e( 'Equipo visita', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_equipo_visita" name="liga_equipo_visita" <?php disabled( ! $context_ready ); ?>>
					<option value="0"><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
					<?php foreach ( $filtered_teams as $team_context ) : ?>
						<?php if ( (int) $team_context['id'] === $selected_local ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<option value="<?php echo esc_attr( (string) $team_context['id'] ); ?>" <?php selected( $selected_visita, (int) $team_context['id'] ); ?>><?php echo esc_html( (string) $team_context['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="liga_fecha_partido"><?php esc_html_e( 'Fecha', 'liga-basket-chile' ); ?></label></th>
			<td><input type="date" id="liga_fecha_partido" name="liga_fecha_partido" value="<?php echo esc_attr( (string) $fields['liga_fecha_partido'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_hora_partido"><?php esc_html_e( 'Hora', 'liga-basket-chile' ); ?></label></th>
			<td><input type="time" id="liga_hora_partido" name="liga_hora_partido" value="<?php echo esc_attr( (string) $fields['liga_hora_partido'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_cancha"><?php esc_html_e( 'Cancha', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_cancha" name="liga_cancha" value="<?php echo esc_attr( (string) $fields['liga_cancha'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_estado_partido"><?php esc_html_e( 'Estado', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_estado_partido" name="liga_estado_partido">
					<?php foreach ( $statuses as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $fields['liga_estado_partido'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="liga_puntos_local"><?php esc_html_e( 'Puntos local', 'liga-basket-chile' ); ?></label></th>
			<td><input type="number" class="small-text" min="0" id="liga_puntos_local" name="liga_puntos_local" value="<?php echo esc_attr( (string) $fields['liga_puntos_local'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_puntos_visita"><?php esc_html_e( 'Puntos visita', 'liga-basket-chile' ); ?></label></th>
			<td><input type="number" class="small-text" min="0" id="liga_puntos_visita" name="liga_puntos_visita" value="<?php echo esc_attr( (string) $fields['liga_puntos_visita'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_incomparecencia"><?php esc_html_e( 'Incomparecencia', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_incomparecencia" name="liga_incomparecencia">
					<?php foreach ( $walkovers as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( (string) $fields['liga_incomparecencia'], $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="liga_observaciones"><?php esc_html_e( 'Observaciones', 'liga-basket-chile' ); ?></label></th>
			<td><textarea id="liga_observaciones" name="liga_observaciones" class="large-text" rows="3"><?php echo esc_textarea( (string) $fields['liga_observaciones'] ); ?></textarea></td>
		</tr>
	</table>
	<script>
		(function () {
			const divisionSelect = document.getElementById('liga_division');
			const temporadaSelect = document.getElementById('liga_temporada');
			const localSelect = document.getElementById('liga_equipo_local');
			const visitaSelect = document.getElementById('liga_equipo_visita');
			const teamPool = <?php echo wp_json_encode( $team_contexts ); ?>;

			if (!divisionSelect || !temporadaSelect || !localSelect || !visitaSelect || !Array.isArray(teamPool)) {
				return;
			}

			const placeholderLabel = '<?php echo esc_js( __( 'Seleccionar', 'liga-basket-chile' ) ); ?>';
			const isValidSeason = function (value) {
				return /^\d{4}$/.test(String(value || '').trim());
			};

			const getContext = function () {
				return {
					division: parseInt(divisionSelect.value, 10) || 0,
					temporada: String(temporadaSelect.value || '').trim()
				};
			};

			const getEligibleTeams = function (context) {
				return teamPool.filter(function (team) {
					return Number(team.division) === context.division && String(team.temporada) === context.temporada;
				});
			};

			const fillSelect = function (selectElement, teams, selectedId, excludedId, disableSelect) {
				const baseOption = document.createElement('option');
				baseOption.value = '0';
				baseOption.textContent = placeholderLabel;

				selectElement.innerHTML = '';
				selectElement.appendChild(baseOption);

				let hasSelected = false;

				teams.forEach(function (team) {
					const teamId = Number(team.id) || 0;
					if (teamId <= 0 || teamId === excludedId) {
						return;
					}

					const option = document.createElement('option');
					option.value = String(teamId);
					option.textContent = String(team.name || '');
					if (teamId === selectedId) {
						option.selected = true;
						hasSelected = true;
					}

					selectElement.appendChild(option);
				});

				selectElement.disabled = !!disableSelect;
				if (!hasSelected) {
					selectElement.value = '0';
				}
			};

			const syncTeamSelectors = function (resetSelections) {
				const context = getContext();
				const contextReady = context.division > 0 && isValidSeason(context.temporada);
				const eligibleTeams = contextReady ? getEligibleTeams(context) : [];
				const eligibleTeamIds = eligibleTeams.map(function (team) {
					return Number(team.id) || 0;
				});

				let localSelected = parseInt(localSelect.value, 10) || 0;
				let visitaSelected = parseInt(visitaSelect.value, 10) || 0;

				if (!contextReady || resetSelections) {
					localSelected = 0;
					visitaSelected = 0;
				}

				if (localSelected > 0 && eligibleTeamIds.indexOf(localSelected) === -1) {
					localSelected = 0;
				}

				if (visitaSelected > 0 && eligibleTeamIds.indexOf(visitaSelected) === -1) {
					visitaSelected = 0;
				}

				if (localSelected > 0 && localSelected === visitaSelected) {
					visitaSelected = 0;
				}

				fillSelect(localSelect, eligibleTeams, localSelected, visitaSelected, !contextReady);
				localSelected = parseInt(localSelect.value, 10) || 0;
				fillSelect(visitaSelect, eligibleTeams, visitaSelected, localSelected, !contextReady);
			};

			divisionSelect.addEventListener('change', function () {
				syncTeamSelectors(true);
			});

			temporadaSelect.addEventListener('change', function () {
				syncTeamSelectors(true);
			});

			localSelect.addEventListener('change', function () {
				syncTeamSelectors(false);
			});

			visitaSelect.addEventListener('change', function () {
				syncTeamSelectors(false);
			});

			syncTeamSelectors(false);
		})();
	</script>
	<?php
}

/**
 * Render metabox division.
 *
 * @param WP_Post $post Post actual.
 * @return void
 */
function liga_render_division_metabox( $post ) {
	wp_nonce_field( 'liga_save_division_meta', 'liga_division_nonce' );

	$fields = array(
		'liga_nombre_division' => get_post_meta( $post->ID, 'liga_nombre_division', true ),
		'liga_temporada'       => get_post_meta( $post->ID, 'liga_temporada', true ),
		'liga_orden_visual'    => get_post_meta( $post->ID, 'liga_orden_visual', true ),
		'liga_activa'          => get_post_meta( $post->ID, 'liga_activa', true ),
	);
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="liga_nombre_division"><?php esc_html_e( 'Nombre division', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_nombre_division" name="liga_nombre_division" value="<?php echo esc_attr( (string) $fields['liga_nombre_division'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_temporada"><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></label></th>
			<td><input type="number" class="small-text" min="2000" max="2100" id="liga_temporada" name="liga_temporada" value="<?php echo esc_attr( (string) $fields['liga_temporada'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_orden_visual"><?php esc_html_e( 'Orden visual', 'liga-basket-chile' ); ?></label></th>
			<td><input type="number" class="small-text" min="1" id="liga_orden_visual" name="liga_orden_visual" value="<?php echo esc_attr( (string) $fields['liga_orden_visual'] ); ?>"></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Activa', 'liga-basket-chile' ); ?></th>
			<td><label><input type="checkbox" name="liga_activa" value="1" <?php checked( (int) $fields['liga_activa'], 1 ); ?>> <?php esc_html_e( 'Division activa', 'liga-basket-chile' ); ?></label></td>
		</tr>
	</table>
	<?php
}

/**
 * Render metabox de banner principal.
 *
 * @param WP_Post $post Post actual.
 * @return void
 */
function liga_render_banner_principal_metabox( $post ) {
	wp_nonce_field( 'liga_save_banner_principal_meta', 'liga_banner_principal_nonce' );

	$primary_image_id = (int) get_post_meta( $post->ID, '_liga_banner_image_id', true );
	$legacy_image_id  = (int) get_post_meta( $post->ID, 'liga_banner_imagen_id', true );
	$image_id         = $primary_image_id > 0 ? $primary_image_id : $legacy_image_id;

	$fields = array(
		'liga_banner_eyebrow'              => get_post_meta( $post->ID, 'liga_banner_eyebrow', true ),
		'liga_banner_titulo'               => get_post_meta( $post->ID, 'liga_banner_titulo', true ),
		'liga_banner_bajada'               => get_post_meta( $post->ID, 'liga_banner_bajada', true ),
		'liga_banner_cta_principal_texto'  => get_post_meta( $post->ID, 'liga_banner_cta_principal_texto', true ),
		'liga_banner_cta_principal_url'    => get_post_meta( $post->ID, 'liga_banner_cta_principal_url', true ),
		'liga_banner_cta_secundario_texto' => get_post_meta( $post->ID, 'liga_banner_cta_secundario_texto', true ),
		'liga_banner_cta_secundario_url'   => get_post_meta( $post->ID, 'liga_banner_cta_secundario_url', true ),
		'liga_banner_imagen_id'            => $image_id,
		'liga_banner_activo'               => get_post_meta( $post->ID, 'liga_banner_activo', true ),
		'liga_banner_orden_visual'         => get_post_meta( $post->ID, 'liga_banner_orden_visual', true ),
		'liga_banner_alineacion_texto'     => get_post_meta( $post->ID, 'liga_banner_alineacion_texto', true ),
		'liga_banner_altura'               => get_post_meta( $post->ID, 'liga_banner_altura', true ),
		'liga_banner_overlay'              => get_post_meta( $post->ID, 'liga_banner_overlay', true ),
		'liga_banner_fondo_degradado'      => get_post_meta( $post->ID, 'liga_banner_fondo_degradado', true ),
		'liga_banner_autoplay'             => get_post_meta( $post->ID, 'liga_banner_autoplay', true ),
	);

	$text_align = in_array( (string) $fields['liga_banner_alineacion_texto'], array( 'izquierda', 'centro', 'derecha' ), true ) ? (string) $fields['liga_banner_alineacion_texto'] : 'izquierda';
	$height     = in_array( (string) $fields['liga_banner_altura'], array( 'compacta', 'normal', 'amplia' ), true ) ? (string) $fields['liga_banner_altura'] : 'normal';
	if ( '' === (string) $fields['liga_banner_overlay'] ) {
		$fields['liga_banner_overlay'] = 1;
	}
	if ( '' === (string) $fields['liga_banner_fondo_degradado'] ) {
		$fields['liga_banner_fondo_degradado'] = 1;
	}
	if ( '' === (string) $fields['liga_banner_autoplay'] ) {
		$fields['liga_banner_autoplay'] = 1;
	}

	if ( '' === trim( (string) $fields['liga_banner_titulo'] ) ) {
		$fields['liga_banner_titulo'] = get_the_title( $post->ID );
	}
	$has_image = $image_id > 0 && wp_attachment_is_image( $image_id );
	if ( ! $has_image ) {
		$image_id = 0;
	}
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th><label for="liga_banner_eyebrow"><?php esc_html_e( 'Eyebrow / etiqueta superior', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_banner_eyebrow" name="liga_banner_eyebrow" value="<?php echo esc_attr( (string) $fields['liga_banner_eyebrow'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_banner_titulo"><?php esc_html_e( 'Titulo principal', 'liga-basket-chile' ); ?></label></th>
			<td>
				<input type="text" class="large-text" id="liga_banner_titulo" name="liga_banner_titulo" value="<?php echo esc_attr( (string) $fields['liga_banner_titulo'] ); ?>">
				<p class="description"><?php esc_html_e( 'Este titulo se usa en el hero y se sincroniza con el titulo del post para facilitar la administracion.', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="liga_banner_bajada"><?php esc_html_e( 'Bajada', 'liga-basket-chile' ); ?></label></th>
			<td>
				<textarea id="liga_banner_bajada" name="liga_banner_bajada" class="large-text" rows="3"><?php echo esc_textarea( (string) $fields['liga_banner_bajada'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Texto corto que aparece debajo del titulo principal.', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="liga_banner_cta_principal_texto"><?php esc_html_e( 'Texto CTA principal', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_banner_cta_principal_texto" name="liga_banner_cta_principal_texto" value="<?php echo esc_attr( (string) $fields['liga_banner_cta_principal_texto'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_banner_cta_principal_url"><?php esc_html_e( 'URL CTA principal', 'liga-basket-chile' ); ?></label></th>
			<td><input type="url" class="regular-text" id="liga_banner_cta_principal_url" name="liga_banner_cta_principal_url" value="<?php echo esc_attr( (string) $fields['liga_banner_cta_principal_url'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_banner_cta_secundario_texto"><?php esc_html_e( 'Texto CTA secundario', 'liga-basket-chile' ); ?></label></th>
			<td><input type="text" class="regular-text" id="liga_banner_cta_secundario_texto" name="liga_banner_cta_secundario_texto" value="<?php echo esc_attr( (string) $fields['liga_banner_cta_secundario_texto'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_banner_cta_secundario_url"><?php esc_html_e( 'URL CTA secundario', 'liga-basket-chile' ); ?></label></th>
			<td><input type="url" class="regular-text" id="liga_banner_cta_secundario_url" name="liga_banner_cta_secundario_url" value="<?php echo esc_attr( (string) $fields['liga_banner_cta_secundario_url'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_banner_image_id"><?php esc_html_e( 'Imagen del banner', 'liga-basket-chile' ); ?></label></th>
			<td>
				<div
					class="liga-banner-media-field"
					data-liga-banner-media
					data-select-label="<?php echo esc_attr__( 'Seleccionar imagen', 'liga-basket-chile' ); ?>"
					data-change-label="<?php echo esc_attr__( 'Cambiar imagen', 'liga-basket-chile' ); ?>"
					data-remove-label="<?php echo esc_attr__( 'Eliminar imagen', 'liga-basket-chile' ); ?>"
					data-modal-title="<?php echo esc_attr__( 'Seleccionar imagen del banner', 'liga-basket-chile' ); ?>"
					data-modal-button="<?php echo esc_attr__( 'Usar esta imagen', 'liga-basket-chile' ); ?>"
					data-empty-text="<?php echo esc_attr__( 'No hay imagen seleccionada.', 'liga-basket-chile' ); ?>"
				>
					<div class="liga-banner-media-field__preview" data-liga-banner-preview>
						<?php if ( $has_image ) : ?>
							<?php
							echo wp_kses_post(
								wp_get_attachment_image(
									$image_id,
									'medium',
									false,
									array(
										'class'   => 'liga-banner-media-field__preview-image',
										'loading' => 'lazy',
									)
								)
							);
							?>
						<?php else : ?>
							<p class="liga-banner-media-field__empty"><?php esc_html_e( 'No hay imagen seleccionada.', 'liga-basket-chile' ); ?></p>
						<?php endif; ?>
					</div>

					<input type="hidden" id="liga_banner_image_id" name="_liga_banner_image_id" value="<?php echo esc_attr( (string) $image_id ); ?>">

					<div class="liga-banner-media-field__actions">
						<button type="button" class="button liga-banner-media-field__select" data-liga-banner-select>
							<?php echo $has_image ? esc_html__( 'Cambiar imagen', 'liga-basket-chile' ) : esc_html__( 'Seleccionar imagen', 'liga-basket-chile' ); ?>
						</button>
						<button type="button" class="button button-link-delete liga-banner-media-field__remove<?php echo $has_image ? '' : ' is-hidden'; ?>" data-liga-banner-remove>
							<?php esc_html_e( 'Eliminar imagen', 'liga-basket-chile' ); ?>
						</button>
					</div>
				</div>
				<p class="description"><?php esc_html_e( 'Selecciona una imagen rectangular recomendada en formato 16:9.', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="liga_banner_orden_visual"><?php esc_html_e( 'Orden visual', 'liga-basket-chile' ); ?></label></th>
			<td><input type="number" class="small-text" id="liga_banner_orden_visual" name="liga_banner_orden_visual" min="0" value="<?php echo esc_attr( (string) $fields['liga_banner_orden_visual'] ); ?>"></td>
		</tr>
		<tr>
			<th><label for="liga_banner_alineacion_texto"><?php esc_html_e( 'Alineacion de texto', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_banner_alineacion_texto" name="liga_banner_alineacion_texto">
					<option value="izquierda" <?php selected( $text_align, 'izquierda' ); ?>><?php esc_html_e( 'Izquierda', 'liga-basket-chile' ); ?></option>
					<option value="centro" <?php selected( $text_align, 'centro' ); ?>><?php esc_html_e( 'Centro', 'liga-basket-chile' ); ?></option>
					<option value="derecha" <?php selected( $text_align, 'derecha' ); ?>><?php esc_html_e( 'Derecha', 'liga-basket-chile' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><label for="liga_banner_altura"><?php esc_html_e( 'Altura del banner', 'liga-basket-chile' ); ?></label></th>
			<td>
				<select id="liga_banner_altura" name="liga_banner_altura">
					<option value="compacta" <?php selected( $height, 'compacta' ); ?>><?php esc_html_e( 'Compacta', 'liga-basket-chile' ); ?></option>
					<option value="normal" <?php selected( $height, 'normal' ); ?>><?php esc_html_e( 'Normal', 'liga-basket-chile' ); ?></option>
					<option value="amplia" <?php selected( $height, 'amplia' ); ?>><?php esc_html_e( 'Amplia', 'liga-basket-chile' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Estado', 'liga-basket-chile' ); ?></th>
			<td><label><input type="checkbox" name="liga_banner_activo" value="1" <?php checked( (int) $fields['liga_banner_activo'], 1 ); ?>> <?php esc_html_e( 'Banner activo', 'liga-basket-chile' ); ?></label></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Autoplay slider', 'liga-basket-chile' ); ?></th>
			<td>
				<label><input type="checkbox" name="liga_banner_autoplay" value="1" <?php checked( (int) $fields['liga_banner_autoplay'], 1 ); ?>> <?php esc_html_e( 'Activar cambio automatico cada 5 segundos', 'liga-basket-chile' ); ?></label>
				<p class="description"><?php esc_html_e( 'Si hay multiples banners activos, se usa el valor del banner con mayor prioridad (orden visual y fecha).', 'liga-basket-chile' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Overlay imagen', 'liga-basket-chile' ); ?></th>
			<td><label><input type="checkbox" name="liga_banner_overlay" value="1" <?php checked( (int) $fields['liga_banner_overlay'], 1 ); ?>> <?php esc_html_e( 'Activar overlay sobre la imagen', 'liga-basket-chile' ); ?></label></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Fondo degradado', 'liga-basket-chile' ); ?></th>
			<td><label><input type="checkbox" name="liga_banner_fondo_degradado" value="1" <?php checked( (int) $fields['liga_banner_fondo_degradado'], 1 ); ?>> <?php esc_html_e( 'Activar fondo degradado del slide', 'liga-basket-chile' ); ?></label></td>
		</tr>
	</table>
	<?php
}

/**
 * Guarda metadatos del equipo.
 *
 * @param int $post_id ID del post.
 * @return void
 */
function liga_save_equipo_meta( $post_id ) {
	if ( ! isset( $_POST['liga_equipo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['liga_equipo_nonce'] ) ), 'liga_save_equipo_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$data = array(
		'liga_nombre_equipo'    => isset( $_POST['liga_nombre_equipo'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_nombre_equipo'] ) ) : '',
		'liga_logo_equipo'      => isset( $_POST['liga_logo_equipo'] ) ? absint( wp_unslash( $_POST['liga_logo_equipo'] ) ) : 0,
		'liga_ciudad'           => isset( $_POST['liga_ciudad'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_ciudad'] ) ) : '',
		'liga_anio_fundacion'   => isset( $_POST['liga_anio_fundacion'] ) ? absint( wp_unslash( $_POST['liga_anio_fundacion'] ) ) : 0,
		'liga_division'         => isset( $_POST['liga_division'] ) ? absint( wp_unslash( $_POST['liga_division'] ) ) : 0,
		'liga_temporada'        => isset( $_POST['liga_temporada'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_temporada'] ) ) : '',
		'liga_color_principal'  => isset( $_POST['liga_color_principal'] ) ? sanitize_hex_color( wp_unslash( $_POST['liga_color_principal'] ) ) : '',
		'liga_entrenador'       => isset( $_POST['liga_entrenador'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_entrenador'] ) ) : '',
		'liga_posicion_manual'  => isset( $_POST['liga_posicion_manual'] ) ? absint( wp_unslash( $_POST['liga_posicion_manual'] ) ) : 0,
		'liga_activar_override' => liga_sanitize_checkbox( isset( $_POST['liga_activar_override'] ) ? wp_unslash( $_POST['liga_activar_override'] ) : 0 ),
	);

	$nombre_equipo = trim( sanitize_text_field( (string) $data['liga_nombre_equipo'] ) );
	if ( '' === $nombre_equipo ) {
		$nombre_equipo = trim( sanitize_text_field( (string) get_the_title( $post_id ) ) );
	}

	if ( '' === $nombre_equipo ) {
		liga_add_admin_alert( 'error', __( 'Validacion: el nombre del equipo es obligatorio.', 'liga-basket-chile' ) );
		return;
	}

	$division_id = (int) $data['liga_division'];
	if ( $division_id <= 0 || ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: la division del equipo es obligatoria y debe ser valida.', 'liga-basket-chile' ) );
		return;
	}

	$division_temporada = liga_get_division_temporada_label( $division_id );
	$temporada_input    = trim( sanitize_text_field( (string) $data['liga_temporada'] ) );
	$temporada          = $temporada_input;

	if ( '' === $temporada && liga_is_valid_temporada_label( $division_temporada ) ) {
		$temporada = $division_temporada;
	}

	if ( ! liga_is_valid_temporada_label( $temporada ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: la temporada/anio del equipo es obligatoria y debe tener formato YYYY.', 'liga-basket-chile' ) );
		return;
	}

	if ( liga_is_valid_temporada_label( $division_temporada ) && $temporada !== $division_temporada ) {
		liga_add_admin_alert( 'error', __( 'Validacion: la temporada del equipo debe coincidir con la temporada de la division seleccionada.', 'liga-basket-chile' ) );
		return;
	}

	$context_key = liga_build_equipo_competicion_key( $nombre_equipo, $division_id, $temporada );
	if ( liga_team_exists_by_name_division_and_season( $nombre_equipo, $division_id, $temporada, $post_id ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: ya existe un equipo con el mismo nombre, division y temporada.', 'liga-basket-chile' ) );
		return;
	}

	$data['liga_nombre_equipo']          = $nombre_equipo;
	$data['liga_division']               = $division_id;
	$data['liga_temporada']              = $temporada;
	$data['liga_equipo_competicion_key'] = $context_key;

	if ( ! liga_is_valid_temporada_label( $data['liga_temporada'] ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: temporada invalida en equipo.', 'liga-basket-chile' ) );
		return;
	}

	foreach ( $data as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	// Limpia cache de tabla por potencial cambio de override.
	if ( function_exists( 'liga_flush_table_cache' ) ) {
		liga_flush_table_cache();
	}
}
add_action( 'save_post_equipo', 'liga_save_equipo_meta' );

/**
 * Guarda metadatos del partido con validaciones deportivas.
 *
 * @param int $post_id ID del post.
 * @return void
 */
function liga_save_partido_meta( $post_id ) {
	if ( ! isset( $_POST['liga_partido_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['liga_partido_nonce'] ) ), 'liga_save_partido_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$division_id = isset( $_POST['liga_division'] ) ? absint( wp_unslash( $_POST['liga_division'] ) ) : 0;
	if ( ! liga_is_valid_post_type_id( $division_id, 'division' ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: debes seleccionar una division valida para el partido.', 'liga-basket-chile' ) );
		return;
	}

	$estado = isset( $_POST['liga_estado_partido'] ) ? sanitize_key( wp_unslash( $_POST['liga_estado_partido'] ) ) : 'programado';
	if ( ! in_array( $estado, array( 'programado', 'jugado', 'finalizado', 'suspendido', 'cancelado' ), true ) ) {
		$estado = 'programado';
	}

	$incomparecencia = isset( $_POST['liga_incomparecencia'] ) ? sanitize_key( wp_unslash( $_POST['liga_incomparecencia'] ) ) : 'ninguna';
	if ( ! in_array( $incomparecencia, array( 'ninguna', 'local_no_comparecio', 'visita_no_comparecio' ), true ) ) {
		$incomparecencia = 'ninguna';
	}

	$local_id  = isset( $_POST['liga_equipo_local'] ) ? absint( wp_unslash( $_POST['liga_equipo_local'] ) ) : 0;
	$visita_id = isset( $_POST['liga_equipo_visita'] ) ? absint( wp_unslash( $_POST['liga_equipo_visita'] ) ) : 0;

	if ( ! liga_is_valid_post_type_id( $local_id, 'equipo' ) || ! liga_is_valid_post_type_id( $visita_id, 'equipo' ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: local y visita deben ser equipos validos.', 'liga-basket-chile' ) );
		return;
	}

	if ( $local_id === $visita_id ) {
		liga_add_admin_alert( 'error', __( 'Validacion: el equipo local no puede ser igual al visita.', 'liga-basket-chile' ) );
		return;
	}

	$division_temporada = liga_get_division_temporada_label( $division_id );
	$temporada_input    = isset( $_POST['liga_temporada'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['liga_temporada'] ) ) ) : '';
	$temporada          = $temporada_input;

	if ( ! liga_is_valid_temporada_label( $temporada ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: la temporada del partido es obligatoria y debe tener formato YYYY.', 'liga-basket-chile' ) );
		return;
	}

	if ( liga_is_valid_temporada_label( $division_temporada ) && $temporada !== $division_temporada ) {
		liga_add_admin_alert( 'error', __( 'Validacion: la temporada del partido debe coincidir con la temporada de la division seleccionada.', 'liga-basket-chile' ) );
		return;
	}

	if ( function_exists( 'liga_validate_basketball_matchup' ) ) {
		$matchup_validation = liga_validate_basketball_matchup( $local_id, $visita_id, $division_id, $temporada );
		if ( is_wp_error( $matchup_validation ) ) {
			liga_add_admin_alert( 'error', $matchup_validation->get_error_message() );
			return;
		}
	}

	$puntos_local  = isset( $_POST['liga_puntos_local'] ) ? absint( wp_unslash( $_POST['liga_puntos_local'] ) ) : 0;
	$puntos_visita = isset( $_POST['liga_puntos_visita'] ) ? absint( wp_unslash( $_POST['liga_puntos_visita'] ) ) : 0;
	$estado_computable = in_array( $estado, array( 'jugado', 'finalizado' ), true );

	if ( ! $estado_computable ) {
		$incomparecencia = 'ninguna';
		$puntos_local    = 0;
		$puntos_visita   = 0;
	} elseif ( 'ninguna' !== $incomparecencia ) {
		$walkover_score = liga_get_walkover_score( $incomparecencia );
		$puntos_local   = (int) $walkover_score['local'];
		$puntos_visita  = (int) $walkover_score['visita'];
	} else {
		if ( $puntos_local === $puntos_visita ) {
			liga_add_admin_alert( 'error', __( 'Validacion: no se permiten empates en partidos jugados/finalizados.', 'liga-basket-chile' ) );
			return;
		}

		if ( 0 === $puntos_local && 0 === $puntos_visita ) {
			liga_add_admin_alert( 'error', __( 'Validacion: para estado jugado/finalizado debes cargar puntaje deportivo valido.', 'liga-basket-chile' ) );
			return;
		}
	}

	$fecha_partido = isset( $_POST['liga_fecha_partido'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_fecha_partido'] ) ) : '';
	$hora_partido  = isset( $_POST['liga_hora_partido'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_hora_partido'] ) ) : '';

	if ( '' !== $fecha_partido && 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha_partido ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: la fecha del partido no tiene formato valido (YYYY-MM-DD).', 'liga-basket-chile' ) );
		return;
	}

	if ( '' !== $hora_partido && 1 !== preg_match( '/^\d{2}:\d{2}$/', $hora_partido ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: la hora del partido no tiene formato valido (HH:MM).', 'liga-basket-chile' ) );
		return;
	}

	$data = array(
		'liga_equipo_local'    => $local_id,
		'liga_equipo_visita'   => $visita_id,
		'liga_division'        => $division_id,
		'liga_temporada'       => $temporada,
		'liga_fecha_partido'   => $fecha_partido,
		'liga_hora_partido'    => $hora_partido,
		'liga_cancha'          => isset( $_POST['liga_cancha'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_cancha'] ) ) : '',
		'liga_estado_partido'  => $estado,
		'liga_puntos_local'    => $puntos_local,
		'liga_puntos_visita'   => $puntos_visita,
		'liga_incomparecencia' => $incomparecencia,
		'liga_observaciones'   => isset( $_POST['liga_observaciones'] ) ? sanitize_textarea_field( wp_unslash( $_POST['liga_observaciones'] ) ) : '',
	);

	foreach ( $data as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post_partido', 'liga_save_partido_meta' );

/**
 * Guarda metadatos de division.
 *
 * @param int $post_id ID del post.
 * @return void
 */
function liga_save_division_meta( $post_id ) {
	if ( ! isset( $_POST['liga_division_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['liga_division_nonce'] ) ), 'liga_save_division_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$data = array(
		'liga_nombre_division' => isset( $_POST['liga_nombre_division'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_nombre_division'] ) ) : '',
		'liga_temporada'       => liga_normalize_temporada_label(
			isset( $_POST['liga_temporada'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_temporada'] ) ) : '',
			liga_get_current_season_label()
		),
		'liga_orden_visual'    => isset( $_POST['liga_orden_visual'] ) ? absint( wp_unslash( $_POST['liga_orden_visual'] ) ) : 0,
		'liga_activa'          => liga_sanitize_checkbox( isset( $_POST['liga_activa'] ) ? wp_unslash( $_POST['liga_activa'] ) : 0 ),
	);

	if ( ! liga_is_valid_temporada_label( $data['liga_temporada'] ) ) {
		liga_add_admin_alert( 'error', __( 'Validacion: la temporada de la division debe tener formato YYYY.', 'liga-basket-chile' ) );
		return;
	}

	foreach ( $data as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}
}
add_action( 'save_post_division', 'liga_save_division_meta' );

/**
 * Guarda metadatos de banner principal.
 *
 * @param int $post_id ID del post.
 * @return void
 */
function liga_save_banner_principal_meta( $post_id ) {
	if ( ! isset( $_POST['liga_banner_principal_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['liga_banner_principal_nonce'] ) ), 'liga_save_banner_principal_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$allowed_align = array( 'izquierda', 'centro', 'derecha' );
	$allowed_height = array( 'compacta', 'normal', 'amplia' );
	$image_id = 0;

	if ( isset( $_POST['_liga_banner_image_id'] ) ) {
		$image_id = absint( wp_unslash( $_POST['_liga_banner_image_id'] ) );
	} elseif ( isset( $_POST['liga_banner_imagen_id'] ) ) {
		// Compatibilidad con versiones anteriores.
		$image_id = absint( wp_unslash( $_POST['liga_banner_imagen_id'] ) );
	}

	$title = isset( $_POST['liga_banner_titulo'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_banner_titulo'] ) ) : '';
	if ( '' === trim( $title ) ) {
		$title = sanitize_text_field( get_the_title( $post_id ) );
	}

	$data = array(
		'liga_banner_eyebrow'              => isset( $_POST['liga_banner_eyebrow'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_banner_eyebrow'] ) ) : '',
		'liga_banner_titulo'               => $title,
		'liga_banner_bajada'               => isset( $_POST['liga_banner_bajada'] ) ? sanitize_textarea_field( wp_unslash( $_POST['liga_banner_bajada'] ) ) : '',
		'liga_banner_cta_principal_texto'  => isset( $_POST['liga_banner_cta_principal_texto'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_banner_cta_principal_texto'] ) ) : '',
		'liga_banner_cta_principal_url'    => isset( $_POST['liga_banner_cta_principal_url'] ) ? esc_url_raw( wp_unslash( $_POST['liga_banner_cta_principal_url'] ) ) : '',
		'liga_banner_cta_secundario_texto' => isset( $_POST['liga_banner_cta_secundario_texto'] ) ? sanitize_text_field( wp_unslash( $_POST['liga_banner_cta_secundario_texto'] ) ) : '',
		'liga_banner_cta_secundario_url'   => isset( $_POST['liga_banner_cta_secundario_url'] ) ? esc_url_raw( wp_unslash( $_POST['liga_banner_cta_secundario_url'] ) ) : '',
		'_liga_banner_image_id'            => $image_id,
		'liga_banner_imagen_id'            => $image_id,
		'liga_banner_activo'               => liga_sanitize_checkbox( isset( $_POST['liga_banner_activo'] ) ? wp_unslash( $_POST['liga_banner_activo'] ) : 0 ),
		'liga_banner_orden_visual'         => isset( $_POST['liga_banner_orden_visual'] ) ? absint( wp_unslash( $_POST['liga_banner_orden_visual'] ) ) : 0,
		'liga_banner_alineacion_texto'     => isset( $_POST['liga_banner_alineacion_texto'] ) ? sanitize_key( wp_unslash( $_POST['liga_banner_alineacion_texto'] ) ) : 'izquierda',
		'liga_banner_altura'               => isset( $_POST['liga_banner_altura'] ) ? sanitize_key( wp_unslash( $_POST['liga_banner_altura'] ) ) : 'normal',
		'liga_banner_overlay'              => liga_sanitize_checkbox( isset( $_POST['liga_banner_overlay'] ) ? wp_unslash( $_POST['liga_banner_overlay'] ) : 0 ),
		'liga_banner_fondo_degradado'      => liga_sanitize_checkbox( isset( $_POST['liga_banner_fondo_degradado'] ) ? wp_unslash( $_POST['liga_banner_fondo_degradado'] ) : 0 ),
		'liga_banner_autoplay'             => liga_sanitize_checkbox( isset( $_POST['liga_banner_autoplay'] ) ? wp_unslash( $_POST['liga_banner_autoplay'] ) : 0 ),
	);

	if ( ! in_array( $data['liga_banner_alineacion_texto'], $allowed_align, true ) ) {
		$data['liga_banner_alineacion_texto'] = 'izquierda';
	}

	if ( ! in_array( $data['liga_banner_altura'], $allowed_height, true ) ) {
		$data['liga_banner_altura'] = 'normal';
	}

	foreach ( $data as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}

	if ( '' !== $title ) {
		remove_action( 'save_post_banner-principal', 'liga_save_banner_principal_meta' );
		wp_update_post(
			array(
				'ID'         => (int) $post_id,
				'post_title' => $title,
			)
		);
		add_action( 'save_post_banner-principal', 'liga_save_banner_principal_meta' );
	}

	if ( function_exists( 'liga_flush_home_banner_cache' ) ) {
		liga_flush_home_banner_cache();
	}
}
add_action( 'save_post_banner-principal', 'liga_save_banner_principal_meta' );

/**
 * Encola assets admin del selector de imagen en Banner Principal.
 *
 * @param string $hook_suffix Pantalla admin actual.
 * @return void
 */
function liga_enqueue_banner_principal_admin_media_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'banner-principal' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style(
		'liga-admin-banner-media',
		get_template_directory_uri() . '/assets/css/admin.css',
		array(),
		liga_asset_version( 'assets/css/admin.css' )
	);

	wp_enqueue_script(
		'liga-admin-banner-media',
		get_template_directory_uri() . '/assets/js/admin-banner-media.js',
		array( 'media-editor' ),
		liga_asset_version( 'assets/js/admin-banner-media.js' ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'liga_enqueue_banner_principal_admin_media_assets' );

/**
 * Limpia cache del hero al mover/eliminar banners.
 *
 * @param int $post_id ID del post afectado.
 * @return void
 */
function liga_flush_home_banner_cache_on_banner_post_change( $post_id ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 || 'banner-principal' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( function_exists( 'liga_flush_home_banner_cache' ) ) {
		liga_flush_home_banner_cache();
	}
}
add_action( 'trashed_post', 'liga_flush_home_banner_cache_on_banner_post_change' );
add_action( 'untrashed_post', 'liga_flush_home_banner_cache_on_banner_post_change' );
add_action( 'before_delete_post', 'liga_flush_home_banner_cache_on_banner_post_change' );
