<?php
/**
 * Importador CSV de equipos.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna URL base de la pantalla de importacion.
 *
 * @param array<string, scalar> $args Query args adicionales.
 * @return string
 */
function liga_import_equipos_get_page_url( $args = array() ) {
	$query_args = array_merge(
		array(
			'page' => 'liga-importar-equipos',
		),
		$args
	);

	return add_query_arg( $query_args, admin_url( 'admin.php' ) );
}

/**
 * Construye key de transient para preview de importacion por usuario.
 *
 * @param string $token Token de preview.
 * @param int    $user_id Usuario.
 * @return string
 */
function liga_import_equipos_get_preview_transient_key( $token, $user_id ) {
	$token   = sanitize_key( (string) $token );
	$user_id = absint( $user_id );
	return 'liga_import_equipos_preview_' . $user_id . '_' . $token;
}

/**
 * Normaliza texto con sanitizacion y espacios.
 *
 * @param mixed $value Valor.
 * @return string
 */
function liga_import_equipos_normalize_text( $value ) {
	$normalized = (string) $value;

	if ( function_exists( 'mb_detect_encoding' ) && function_exists( 'mb_convert_encoding' ) ) {
		$encoding = mb_detect_encoding( $normalized, array( 'UTF-8', 'ISO-8859-1', 'Windows-1252' ), true );
		if ( false !== $encoding && 'UTF-8' !== $encoding ) {
			$normalized = mb_convert_encoding( $normalized, 'UTF-8', $encoding );
		}
	}

	$normalized = sanitize_text_field( $normalized );
	$normalized = preg_replace( '/\s+/u', ' ', $normalized );

	return trim( (string) $normalized );
}

/**
 * Normaliza texto para comparacion logica.
 *
 * @param mixed $value Valor.
 * @return string
 */
function liga_import_equipos_normalize_for_compare( $value ) {
	$normalized = liga_import_equipos_normalize_text( $value );
	if ( '' === $normalized ) {
		return '';
	}

	$normalized = remove_accents( $normalized );
	if ( function_exists( 'mb_strtolower' ) ) {
		$normalized = mb_strtolower( $normalized, 'UTF-8' );
	} else {
		$normalized = strtolower( $normalized );
	}

	$normalized = preg_replace( '/\s+/u', ' ', $normalized );
	return trim( (string) $normalized );
}

/**
 * Normaliza temporada con formato YYYY obligatorio.
 *
 * @param mixed $value Valor temporada.
 * @return string
 */
function liga_import_equipos_normalize_temporada( $value ) {
	$temporada = liga_import_equipos_normalize_text( $value );
	return liga_is_valid_temporada_label( $temporada ) ? $temporada : '';
}

/**
 * Obtiene lookup de divisiones por nombre normalizado.
 *
 * @return array{items:array<int,array{id:int,name:string}>,by_key:array<string,int>}
 */
function liga_import_equipos_get_division_lookup() {
	$divisions = get_posts(
		array(
			'post_type'      => 'division',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$lookup = array(
		'items'  => array(),
		'by_key' => array(),
	);

	foreach ( $divisions as $division ) {
		$division_id   = (int) $division->ID;
		$division_name = liga_import_equipos_normalize_text( get_the_title( $division_id ) );
		$meta_name     = liga_import_equipos_normalize_text( get_post_meta( $division_id, 'liga_nombre_division', true ) );

		$lookup['items'][ $division_id ] = array(
			'id'   => $division_id,
			'name' => '' !== $meta_name ? $meta_name : $division_name,
		);

		$title_key = liga_import_equipos_normalize_for_compare( $division_name );
		if ( '' !== $title_key && ! isset( $lookup['by_key'][ $title_key ] ) ) {
			$lookup['by_key'][ $title_key ] = $division_id;
		}

		$meta_key = liga_import_equipos_normalize_for_compare( $meta_name );
		if ( '' !== $meta_key && ! isset( $lookup['by_key'][ $meta_key ] ) ) {
			$lookup['by_key'][ $meta_key ] = $division_id;
		}
	}

	return $lookup;
}

/**
 * Resuelve division por etiqueta.
 *
 * @param string                                               $division_label Label de archivo.
 * @param array{items:array<int,array{id:int,name:string}>,by_key:array<string,int>} $lookup Lookup.
 * @return int
 */
function liga_import_equipos_resolve_division_id( $division_label, $lookup ) {
	if ( function_exists( 'liga_get_division_id_by_name' ) ) {
		$central_division_id = liga_get_division_id_by_name( $division_label );
		if ( $central_division_id > 0 ) {
			return $central_division_id;
		}
	}

	$key = liga_import_equipos_normalize_for_compare( $division_label );
	if ( '' === $key ) {
		return 0;
	}

	return isset( $lookup['by_key'][ $key ] ) ? (int) $lookup['by_key'][ $key ] : 0;
}

/**
 * Determina si ya existe un equipo en el sistema por contexto competitivo.
 *
 * @param string $nombre Nombre equipo.
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return bool
 */
function liga_import_equipos_exists_in_system( $nombre, $division_id, $temporada ) {
	$nombre      = liga_import_equipos_normalize_text( $nombre );
	$division_id = absint( $division_id );
	$temporada   = liga_import_equipos_normalize_temporada( $temporada );

	if ( function_exists( 'liga_team_exists_by_name_division_and_season' ) ) {
		return liga_team_exists_by_name_division_and_season( $nombre, $division_id, $temporada );
	}

	return false;
}

/**
 * Parsea CSV de equipos validando encabezado obligatorio.
 *
 * @param string $file_path Ruta temporal.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function liga_import_equipos_parse_csv( $file_path ) {
	$handle = fopen( $file_path, 'r' );
	if ( ! $handle ) {
		return new WP_Error( 'import_csv_open_failed', __( 'No fue posible leer el archivo CSV.', 'liga-basket-chile' ) );
	}

	$header = fgetcsv( $handle, 0, ',' );
	if ( false === $header || ! is_array( $header ) ) {
		fclose( $handle );
		return new WP_Error( 'import_csv_empty', __( 'El archivo CSV esta vacio.', 'liga-basket-chile' ) );
	}

	if ( isset( $header[0] ) ) {
		$header[0] = preg_replace( '/^\xEF\xBB\xBF/u', '', (string) $header[0] );
	}

	$header = array_map(
		static function ( $field ) {
			return sanitize_key( liga_import_equipos_normalize_text( $field ) );
		},
		$header
	);

	$expected_header = array( 'nombre_equipo', 'division', 'temporada' );
	if ( $header !== $expected_header ) {
		fclose( $handle );
		return new WP_Error(
			'import_csv_header_invalid',
			__( 'Encabezado invalido. Debe ser exactamente: nombre_equipo,division,temporada', 'liga-basket-chile' )
		);
	}

	$rows      = array();
	$line      = 1;
	$row_index = 0;
	while ( ( $data = fgetcsv( $handle, 0, ',' ) ) !== false ) {
		$line++;
		$row_index++;

		$data = array_pad( $data, 3, '' );
		$row  = array(
			'line'          => $line,
			'row_index'     => $row_index,
			'nombre_equipo' => isset( $data[0] ) ? (string) $data[0] : '',
			'division'      => isset( $data[1] ) ? (string) $data[1] : '',
			'temporada'     => isset( $data[2] ) ? (string) $data[2] : '',
		);

		$is_empty = '' === trim( (string) $row['nombre_equipo'] )
			&& '' === trim( (string) $row['division'] )
			&& '' === trim( (string) $row['temporada'] );

		if ( $is_empty ) {
			continue;
		}

		$rows[] = $row;
	}

	fclose( $handle );

	return $rows;
}

/**
 * Valida filas del CSV contra reglas deportivas y estructura real.
 *
 * @param array<int, array<string, mixed>> $rows Filas parseadas.
 * @return array<string, mixed>
 */
function liga_import_equipos_validate_rows( $rows ) {
	$division_lookup = liga_import_equipos_get_division_lookup();
	$file_seen       = array();
	$validated_rows  = array();
	$valid_rows      = array();
	$invalid_rows    = array();

	foreach ( $rows as $row ) {
		$line = isset( $row['line'] ) ? absint( $row['line'] ) : 0;

		$nombre_equipo = liga_import_equipos_normalize_text( isset( $row['nombre_equipo'] ) ? $row['nombre_equipo'] : '' );
		$division_raw  = liga_import_equipos_normalize_text( isset( $row['division'] ) ? $row['division'] : '' );
		$temporada     = liga_import_equipos_normalize_temporada( isset( $row['temporada'] ) ? $row['temporada'] : '' );

		$errors      = array();
		$division_id = 0;

		if ( '' === $nombre_equipo ) {
			$errors[] = __( 'nombre_equipo es obligatorio.', 'liga-basket-chile' );
		}

		if ( '' === $division_raw ) {
			$errors[] = __( 'division es obligatoria.', 'liga-basket-chile' );
		} else {
			$division_id = liga_import_equipos_resolve_division_id( $division_raw, $division_lookup );
			if ( $division_id <= 0 ) {
				$errors[] = __( 'division no existe en el sistema.', 'liga-basket-chile' );
			}
		}

		if ( '' === liga_import_equipos_normalize_text( isset( $row['temporada'] ) ? $row['temporada'] : '' ) ) {
			$errors[] = __( 'temporada es obligatoria.', 'liga-basket-chile' );
		} elseif ( '' === $temporada ) {
			$errors[] = __( 'temporada debe tener formato YYYY.', 'liga-basket-chile' );
		}

		$context_key = '';
		if ( empty( $errors ) ) {
			$context_key = liga_build_equipo_competicion_key( $nombre_equipo, $division_id, $temporada );

			if ( isset( $file_seen[ $context_key ] ) ) {
				$errors[] = sprintf(
					/* translators: %d: numero de fila en el mismo archivo */
					__( 'duplicado dentro del archivo (primera aparicion en fila %d).', 'liga-basket-chile' ),
					(int) $file_seen[ $context_key ]
				);
			} else {
				$file_seen[ $context_key ] = $line;
			}

			if ( liga_import_equipos_exists_in_system( $nombre_equipo, $division_id, $temporada ) ) {
				$errors[] = __( 'duplicado en el sistema para nombre + division + temporada.', 'liga-basket-chile' );
			}
		}

		$division_name = $division_id > 0 && isset( $division_lookup['items'][ $division_id ]['name'] )
			? (string) $division_lookup['items'][ $division_id ]['name']
			: $division_raw;

		$validated_row = array(
			'line'          => $line,
			'nombre_equipo' => $nombre_equipo,
			'division'      => $division_name,
			'division_id'   => $division_id,
			'temporada'     => $temporada,
			'context_key'   => $context_key,
			'errors'        => $errors,
			'is_valid'      => empty( $errors ),
		);

		$validated_rows[] = $validated_row;

		if ( empty( $errors ) ) {
			$valid_rows[] = $validated_row;
		} else {
			$invalid_rows[] = $validated_row;
		}
	}

	return array(
		'rows'         => $validated_rows,
		'valid_rows'   => $valid_rows,
		'invalid_rows' => $invalid_rows,
		'summary'      => array(
			'total'   => count( $validated_rows ),
			'valid'   => count( $valid_rows ),
			'invalid' => count( $invalid_rows ),
		),
	);
}

/**
 * Guarda preview temporal de validacion.
 *
 * @param int                $user_id Usuario.
 * @param array<string,mixed> $payload Preview.
 * @return string
 */
function liga_import_equipos_store_preview( $user_id, $payload ) {
	$token = wp_generate_password( 20, false, false );
	$key   = liga_import_equipos_get_preview_transient_key( $token, $user_id );

	set_transient(
		$key,
		array(
			'created_at' => time(),
			'payload'    => $payload,
		),
		30 * MINUTE_IN_SECONDS
	);

	return $token;
}

/**
 * Obtiene preview temporal.
 *
 * @param string $token Token.
 * @param int    $user_id Usuario.
 * @return array<string,mixed>|null
 */
function liga_import_equipos_get_preview( $token, $user_id ) {
	$key   = liga_import_equipos_get_preview_transient_key( $token, $user_id );
	$value = get_transient( $key );
	if ( ! is_array( $value ) || ! isset( $value['payload'] ) || ! is_array( $value['payload'] ) ) {
		return null;
	}

	return $value;
}

/**
 * Elimina preview temporal.
 *
 * @param string $token Token.
 * @param int    $user_id Usuario.
 * @return void
 */
function liga_import_equipos_delete_preview( $token, $user_id ) {
	$key = liga_import_equipos_get_preview_transient_key( $token, $user_id );
	delete_transient( $key );
}

/**
 * Inserta equipo validado en la estructura real del sistema.
 *
 * @param array<string,mixed> $row Fila validada.
 * @return int|WP_Error
 */
function liga_import_equipos_insert_team( $row ) {
	$nombre_equipo = liga_import_equipos_normalize_text( isset( $row['nombre_equipo'] ) ? $row['nombre_equipo'] : '' );
	$division_id   = isset( $row['division_id'] ) ? absint( $row['division_id'] ) : 0;
	$temporada     = liga_import_equipos_normalize_temporada( isset( $row['temporada'] ) ? $row['temporada'] : '' );

	if ( '' === $nombre_equipo || $division_id <= 0 || '' === $temporada ) {
		return new WP_Error( 'import_row_invalid', __( 'Fila invalida para insercion.', 'liga-basket-chile' ) );
	}

	if ( liga_import_equipos_exists_in_system( $nombre_equipo, $division_id, $temporada ) ) {
		return new WP_Error( 'import_duplicate', __( 'Registro duplicado detectado antes de guardar.', 'liga-basket-chile' ) );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'equipo',
			'post_status' => 'publish',
			'post_title'  => $nombre_equipo,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$context_key = liga_build_equipo_competicion_key( $nombre_equipo, $division_id, $temporada );
	$meta_fields = array(
		'liga_nombre_equipo'          => $nombre_equipo,
		'liga_logo_equipo'            => 0,
		'liga_ciudad'                 => '',
		'liga_anio_fundacion'         => 0,
		'liga_division'               => $division_id,
		'liga_temporada'              => $temporada,
		'liga_color_principal'        => '',
		'liga_entrenador'             => '',
		'liga_posicion_manual'        => 0,
		'liga_activar_override'       => 0,
		'liga_equipo_competicion_key' => $context_key,
	);

	foreach ( $meta_fields as $meta_key => $meta_value ) {
		update_post_meta( $post_id, $meta_key, $meta_value );
	}

	return (int) $post_id;
}

/**
 * Procesa acciones del importador (validar y confirmar).
 *
 * @return void
 */
function liga_handle_admin_import_equipos_actions() {
	if ( ! is_admin() || ! isset( $_POST['liga_import_equipos_action'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		liga_add_admin_alert( 'error', __( 'No tienes permisos para importar equipos.', 'liga-basket-chile' ) );
		wp_safe_redirect( liga_import_equipos_get_page_url() );
		exit;
	}

	$action  = sanitize_key( wp_unslash( $_POST['liga_import_equipos_action'] ) );
	$user_id = get_current_user_id();

	if ( 'validate_csv' === $action ) {
		check_admin_referer( 'liga_validate_equipos_csv', 'liga_validate_equipos_csv_nonce' );

		if ( empty( $_FILES['liga_equipos_csv'] ) || ! is_array( $_FILES['liga_equipos_csv'] ) ) {
			liga_add_admin_alert( 'error', __( 'Debes seleccionar un archivo CSV.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		$file = $_FILES['liga_equipos_csv'];
		$error_code = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			liga_add_admin_alert( 'error', __( 'No fue posible subir el archivo CSV.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		$tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		$name     = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
		$size     = isset( $file['size'] ) ? (int) $file['size'] : 0;

		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			liga_add_admin_alert( 'error', __( 'Archivo temporal invalido para validacion.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		if ( $size <= 0 ) {
			liga_add_admin_alert( 'error', __( 'El archivo CSV esta vacio.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		$extension = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( 'csv' !== $extension ) {
			liga_add_admin_alert( 'error', __( 'El archivo debe tener extension .csv.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		$rows = liga_import_equipos_parse_csv( $tmp_name );
		if ( is_wp_error( $rows ) ) {
			liga_add_admin_alert( 'error', $rows->get_error_message() );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		$validation = liga_import_equipos_validate_rows( $rows );
		if ( empty( $validation['summary']['total'] ) ) {
			liga_add_admin_alert( 'warning', __( 'No se detectaron filas de datos en el CSV.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		$token = liga_import_equipos_store_preview( $user_id, $validation );

		liga_add_admin_alert(
			'info',
			sprintf(
				/* translators: 1: filas validas, 2: filas invalidas */
				__( 'Archivo validado. Filas validas: %1$d. Filas con error: %2$d.', 'liga-basket-chile' ),
				(int) $validation['summary']['valid'],
				(int) $validation['summary']['invalid']
			)
		);

		wp_safe_redirect(
			liga_import_equipos_get_page_url(
				array(
					'import_token' => $token,
				)
			)
		);
		exit;
	}

	if ( 'confirm_import' === $action ) {
		check_admin_referer( 'liga_confirm_equipos_csv', 'liga_confirm_equipos_csv_nonce' );

		$token = isset( $_POST['import_token'] ) ? sanitize_key( wp_unslash( $_POST['import_token'] ) ) : '';
		if ( '' === $token ) {
			liga_add_admin_alert( 'error', __( 'Token de confirmacion invalido.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		$preview = liga_import_equipos_get_preview( $token, $user_id );
		if ( null === $preview ) {
			liga_add_admin_alert( 'error', __( 'La prevalidacion expiro. Vuelve a validar el archivo.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_equipos_get_page_url() );
			exit;
		}

		$payload    = $preview['payload'];
		$valid_rows = isset( $payload['valid_rows'] ) && is_array( $payload['valid_rows'] ) ? $payload['valid_rows'] : array();
		$created    = 0;
		$skipped    = 0;
		$failed     = 0;

		foreach ( $valid_rows as $row ) {
			$insert = liga_import_equipos_insert_team( $row );
			if ( is_wp_error( $insert ) ) {
				$error_code = (string) $insert->get_error_code();
				if ( 'import_duplicate' === $error_code ) {
					$skipped++;
				} else {
					$failed++;
				}
				continue;
			}

			$created++;
		}

		liga_import_equipos_delete_preview( $token, $user_id );

		if ( $created > 0 && function_exists( 'liga_flush_table_cache' ) ) {
			liga_flush_table_cache();
		}

		liga_add_admin_alert(
			'success',
			sprintf(
				/* translators: 1: creados, 2: omitidos, 3: fallidos */
				__( 'Importacion finalizada. Creados: %1$d | Omitidos: %2$d | Fallidos: %3$d', 'liga-basket-chile' ),
				$created,
				$skipped,
				$failed
			)
		);

		wp_safe_redirect(
			liga_import_equipos_get_page_url(
				array(
					'import_done'    => 1,
					'import_created' => $created,
					'import_skipped' => $skipped,
					'import_failed'  => $failed,
				)
			)
		);
		exit;
	}
}
add_action( 'admin_init', 'liga_handle_admin_import_equipos_actions' );

/**
 * Descarga plantilla oficial de equipos CSV.
 *
 * @return void
 */
function liga_download_equipos_csv_template() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'No tienes permisos para descargar esta plantilla.', 'liga-basket-chile' ) );
	}

	check_admin_referer( 'liga_download_equipos_csv_template' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename=plantilla-equipos-liga.csv' );

	echo "\xEF\xBB\xBF";

	$output = fopen( 'php://output', 'w' );
	if ( false !== $output ) {
		fputcsv( $output, array( 'nombre_equipo', 'division', 'temporada' ) );
		fputcsv( $output, array( 'Club Deportivo Oriente', 'Primera', '2026' ) );
		fputcsv( $output, array( 'Leones de Concepcion', 'Primera', '2026' ) );
		fputcsv( $output, array( 'Halcones del Sur', 'U17', '2026' ) );
		fclose( $output );
	}

	exit;
}
add_action( 'admin_post_liga_download_equipos_csv_template', 'liga_download_equipos_csv_template' );

/**
 * Renderiza pantalla admin Importar Equipos.
 *
 * @return void
 */
function liga_render_admin_import_equipos_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pagina.', 'liga-basket-chile' ) );
	}

	$user_id = get_current_user_id();
	$token   = isset( $_GET['import_token'] ) ? sanitize_key( wp_unslash( $_GET['import_token'] ) ) : '';
	$preview = '' !== $token ? liga_import_equipos_get_preview( $token, $user_id ) : null;

	$payload      = is_array( $preview ) && isset( $preview['payload'] ) && is_array( $preview['payload'] ) ? $preview['payload'] : array();
	$summary      = isset( $payload['summary'] ) && is_array( $payload['summary'] ) ? $payload['summary'] : array();
	$preview_rows = isset( $payload['rows'] ) && is_array( $payload['rows'] ) ? $payload['rows'] : array();
	$valid_rows   = isset( $payload['valid_rows'] ) && is_array( $payload['valid_rows'] ) ? $payload['valid_rows'] : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Importar Equipos', 'liga-basket-chile' ); ?></h1>
		<p><?php esc_html_e( 'Carga masiva de equipos por CSV integrada al mismo modulo deportivo de equipos, partidos y tabla de posiciones.', 'liga-basket-chile' ); ?></p>

		<p>
			<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=liga_download_equipos_csv_template' ), 'liga_download_equipos_csv_template' ) ); ?>">
				<?php esc_html_e( 'Descargar plantilla CSV', 'liga-basket-chile' ); ?>
			</a>
		</p>

		<div class="card">
			<h2><?php esc_html_e( 'Paso 1: Validar archivo', 'liga-basket-chile' ); ?></h2>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'liga_validate_equipos_csv', 'liga_validate_equipos_csv_nonce' ); ?>
				<input type="hidden" name="liga_import_equipos_action" value="validate_csv">
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="liga_equipos_csv"><?php esc_html_e( 'Archivo CSV', 'liga-basket-chile' ); ?></label></th>
						<td>
							<input type="file" id="liga_equipos_csv" name="liga_equipos_csv" accept=".csv,text/csv" required>
							<p class="description"><?php esc_html_e( 'Encabezado exacto requerido: nombre_equipo,division,temporada', 'liga-basket-chile' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Validar archivo', 'liga-basket-chile' ), 'primary', 'submit', false ); ?>
			</form>
		</div>

		<?php if ( isset( $_GET['import_done'] ) ) : ?>
			<div class="card">
				<h2><?php esc_html_e( 'Resumen final de importacion', 'liga-basket-chile' ); ?></h2>
				<p>
					<strong><?php esc_html_e( 'Creados', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) absint( isset( $_GET['import_created'] ) ? wp_unslash( $_GET['import_created'] ) : 0 ) ); ?>
					|
					<strong><?php esc_html_e( 'Omitidos', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) absint( isset( $_GET['import_skipped'] ) ? wp_unslash( $_GET['import_skipped'] ) : 0 ) ); ?>
					|
					<strong><?php esc_html_e( 'Fallidos', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) absint( isset( $_GET['import_failed'] ) ? wp_unslash( $_GET['import_failed'] ) : 0 ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $preview_rows ) ) : ?>
			<div class="card">
				<h2><?php esc_html_e( 'Vista previa de validacion', 'liga-basket-chile' ); ?></h2>
				<p>
					<strong><?php esc_html_e( 'Total filas', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) ( isset( $summary['total'] ) ? (int) $summary['total'] : 0 ) ); ?>
					|
					<strong><?php esc_html_e( 'Validas', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) ( isset( $summary['valid'] ) ? (int) $summary['valid'] : 0 ) ); ?>
					|
					<strong><?php esc_html_e( 'Con error', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) ( isset( $summary['invalid'] ) ? (int) $summary['invalid'] : 0 ) ); ?>
				</p>

				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Fila', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Nombre equipo', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Estado', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Detalle', 'liga-basket-chile' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $preview_rows as $row ) : ?>
							<?php
							$row_errors = isset( $row['errors'] ) && is_array( $row['errors'] ) ? $row['errors'] : array();
							$is_valid   = ! empty( $row['is_valid'] );
							?>
							<tr>
								<td><?php echo esc_html( (string) ( isset( $row['line'] ) ? (int) $row['line'] : 0 ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['nombre_equipo'] ) ? $row['nombre_equipo'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['division'] ) ? $row['division'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['temporada'] ) ? $row['temporada'] : '' ) ); ?></td>
								<td>
									<?php if ( $is_valid ) : ?>
										<span style="color:#008a20;"><?php esc_html_e( 'Valida', 'liga-basket-chile' ); ?></span>
									<?php else : ?>
										<span style="color:#b32d2e;"><?php esc_html_e( 'Con error', 'liga-basket-chile' ); ?></span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $is_valid ) : ?>
										<?php esc_html_e( 'Lista para importar', 'liga-basket-chile' ); ?>
									<?php else : ?>
										<?php echo esc_html( implode( ' | ', array_map( 'sanitize_text_field', $row_errors ) ) ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<h3><?php esc_html_e( 'Paso 2: Confirmar importacion', 'liga-basket-chile' ); ?></h3>
				<p><?php esc_html_e( 'Se importaran solo filas validas. Las filas con error se omiten automaticamente.', 'liga-basket-chile' ); ?></p>
				<?php
				$confirm_button_attributes = empty( $valid_rows )
					? array( 'disabled' => 'disabled' )
					: array();
				?>
				<form method="post">
					<?php wp_nonce_field( 'liga_confirm_equipos_csv', 'liga_confirm_equipos_csv_nonce' ); ?>
					<input type="hidden" name="liga_import_equipos_action" value="confirm_import">
					<input type="hidden" name="import_token" value="<?php echo esc_attr( $token ); ?>">
					<?php submit_button( __( 'Confirmar importacion', 'liga-basket-chile' ), 'primary', 'submit', false, $confirm_button_attributes ); ?>
					<?php if ( empty( $valid_rows ) ) : ?>
						<p class="description"><?php esc_html_e( 'No hay filas validas para importar.', 'liga-basket-chile' ); ?></p>
					<?php endif; ?>
				</form>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
