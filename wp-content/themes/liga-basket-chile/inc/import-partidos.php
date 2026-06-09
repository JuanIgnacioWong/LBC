<?php
/**
 * Importador administrativo de calendario oficial de partidos.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna URL base de la pantalla de importacion de partidos.
 *
 * @param array<string, scalar> $args Query args adicionales.
 * @return string
 */
function liga_import_partidos_get_page_url( $args = array() ) {
	$query_args = array_merge(
		array(
			'page' => 'liga-importar-partidos',
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
function liga_import_partidos_get_preview_transient_key( $token, $user_id ) {
	$token   = sanitize_key( (string) $token );
	$user_id = absint( $user_id );
	return 'liga_import_partidos_preview_' . $user_id . '_' . $token;
}

/**
 * Normaliza texto con sanitizacion y espacios.
 *
 * @param mixed $value Valor.
 * @return string
 */
function liga_import_partidos_normalize_text( $value ) {
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
function liga_import_partidos_normalize_for_compare( $value ) {
	$normalized = liga_import_partidos_normalize_text( $value );
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
function liga_import_partidos_normalize_temporada( $value ) {
	$temporada = liga_import_partidos_normalize_text( $value );
	return liga_is_valid_temporada_label( $temporada ) ? $temporada : '';
}

/**
 * Normaliza estado del partido.
 *
 * @param mixed $value Estado.
 * @return string
 */
function liga_import_partidos_normalize_estado( $value ) {
	$estado = sanitize_key( liga_import_partidos_normalize_text( $value ) );
	if ( '' === $estado ) {
		return 'programado';
	}

	return $estado;
}

/**
 * Obtiene estados permitidos por el sistema manual.
 *
 * @return array<int, string>
 */
function liga_import_partidos_allowed_states() {
	return array( 'programado', 'jugado', 'finalizado', 'suspendido', 'cancelado' );
}

/**
 * Obtiene extensiones soportadas por el importador oficial.
 *
 * @return array<int, string>
 */
function liga_import_partidos_supported_extensions() {
	return array( 'csv', 'xls', 'xlsx' );
}

/**
 * Normaliza encabezados del archivo oficial a una llave comparable.
 *
 * @param mixed $value Encabezado.
 * @return string
 */
function liga_import_partidos_normalize_header_key( $value ) {
	$key = liga_import_partidos_normalize_for_compare( $value );
	$key = preg_replace( '/[^a-z0-9]+/u', '_', $key );
	return trim( (string) $key, '_' );
}

/**
 * Mapa de alias de encabezados reales a campos internos.
 *
 * @return array<string, string>
 */
function liga_import_partidos_header_aliases() {
	return array(
		'jornada'          => 'jornada',
		'fecha'            => 'fecha',
		'dia'              => 'fecha',
		'hora'             => 'hora',
		'horario'          => 'hora',
		'lugar'            => 'recinto',
		'recinto'          => 'recinto',
		'cancha'           => 'recinto',
		'gimnasio'         => 'recinto',
		'sede'             => 'recinto',
		'division'         => 'division',
		'división'         => 'division',
		'categoria'        => 'division',
		'categoría'        => 'division',
		'temporada'        => 'temporada',
		'anio'             => 'temporada',
		'ano'              => 'temporada',
		'equipo_1'         => 'equipo_local',
		'equipo1'          => 'equipo_local',
		'equipo_local'     => 'equipo_local',
		'local'            => 'equipo_local',
		'equipo_2'         => 'equipo_visitante',
		'equipo2'          => 'equipo_visitante',
		'equipo_visitante' => 'equipo_visitante',
		'equipo_visita'    => 'equipo_visitante',
		'visitante'        => 'equipo_visitante',
		'visita'           => 'equipo_visitante',
		'resultado'        => 'resultado',
		'marcador'         => 'resultado',
		'score'            => 'resultado',
		'estado'           => 'estado',
	);
}

/**
 * Convierte encabezados detectados en indices de campos internos.
 *
 * @param array<int, mixed> $header Encabezados.
 * @return array<string, int>
 */
function liga_import_partidos_map_header_indexes( $header ) {
	$aliases = liga_import_partidos_header_aliases();
	$mapped  = array();

	foreach ( $header as $index => $label ) {
		$key = liga_import_partidos_normalize_header_key( $label );
		if ( '' === $key || ! isset( $aliases[ $key ] ) ) {
			continue;
		}

		$field = $aliases[ $key ];
		if ( ! isset( $mapped[ $field ] ) ) {
			$mapped[ $field ] = (int) $index;
		}
	}

	return $mapped;
}

/**
 * Normaliza encabezados de importacion de partidos a campos internos.
 *
 * @param array<int, mixed> $headers Encabezados.
 * @return array<string, int>
 */
function liga_normalize_match_import_headers( array $headers ) {
	return liga_import_partidos_map_header_indexes( $headers );
}

/**
 * Determina si una fila tabular esta vacia.
 *
 * @param array<int, mixed> $row Fila.
 * @return bool
 */
function liga_import_partidos_is_empty_raw_row( $row ) {
	foreach ( $row as $value ) {
		if ( '' !== trim( (string) $value ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Obtiene timezone del sitio con fallback compatible.
 *
 * @return DateTimeZone
 */
function liga_import_partidos_get_timezone() {
	if ( function_exists( 'wp_timezone' ) ) {
		return wp_timezone();
	}

	return new DateTimeZone( 'UTC' );
}

/**
 * Normaliza numero serial de Excel a fecha ISO.
 *
 * @param mixed $value Valor.
 * @return string
 */
function liga_import_partidos_normalize_date_value( $value ) {
	if ( $value instanceof DateTimeInterface ) {
		return $value->format( 'Y-m-d' );
	}

	$raw = liga_import_partidos_normalize_text( $value );
	if ( '' === $raw ) {
		return '';
	}

	if ( is_numeric( $raw ) && (float) $raw > 20000 ) {
		try {
			$date = new DateTimeImmutable( '1899-12-30', liga_import_partidos_get_timezone() );
			return $date->modify( '+' . (int) floor( (float) $raw ) . ' days' )->format( 'Y-m-d' );
		} catch ( Exception $exception ) {
			return $raw;
		}
	}

	$formats = array( 'Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y' );
	foreach ( $formats as $format ) {
		$date = DateTime::createFromFormat( $format, $raw, liga_import_partidos_get_timezone() );
		if ( $date instanceof DateTimeInterface ) {
			return $date->format( 'Y-m-d' );
		}
	}

	return $raw;
}

/**
 * Normaliza hora desde texto o fraccion Excel.
 *
 * @param mixed $value Valor.
 * @return string
 */
function liga_import_partidos_normalize_time_value( $value ) {
	if ( $value instanceof DateTimeInterface ) {
		return $value->format( 'H:i' );
	}

	$raw = liga_import_partidos_normalize_text( $value );
	if ( '' === $raw ) {
		return '';
	}

	if ( is_numeric( $raw ) && (float) $raw > 0 && (float) $raw < 1 ) {
		$total_minutes = (int) round( (float) $raw * 24 * 60 );
		$hours         = (int) floor( $total_minutes / 60 ) % 24;
		$minutes       = $total_minutes % 60;
		return sprintf( '%02d:%02d', $hours, $minutes );
	}

	if ( 1 === preg_match( '/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $raw, $matches ) ) {
		return sprintf( '%02d:%02d', min( 23, (int) $matches[1] ), min( 59, (int) $matches[2] ) );
	}

	return $raw;
}

/**
 * Parsea la celda Resultado del calendario oficial.
 *
 * @param mixed $value Resultado.
 * @return array{has_result:bool,puntos_local:int,puntos_visita:int,incomparecencia:string,error:string}
 */
function liga_import_partidos_parse_result_value( $value ) {
	$raw = liga_import_partidos_normalize_text( $value );
	$out = array(
		'has_result'      => false,
		'puntos_local'    => 0,
		'puntos_visita'   => 0,
		'incomparecencia' => 'ninguna',
		'error'           => '',
	);

	if ( '' === $raw ) {
		return $out;
	}

	if ( 1 === preg_match( '/^(\d{1,3})\s*[-:\/]\s*(\d{1,3})$/', $raw, $matches ) ) {
		$out['has_result']    = true;
		$out['puntos_local']  = absint( $matches[1] );
		$out['puntos_visita'] = absint( $matches[2] );
		return $out;
	}

	$compare = liga_import_partidos_normalize_for_compare( $raw );
	if ( 1 === preg_match( '/(?:^|[^\pL\pN])(?:w\.?o\.?|walkover|incomparecencia|no comparecio)(?:$|[^\pL\pN])/u', $compare ) ) {
		$out['has_result'] = true;
		if ( false !== strpos( $compare, 'local' ) || false !== strpos( $compare, 'equipo 1' ) ) {
			$out['incomparecencia'] = 'local_no_comparecio';
		} elseif ( false !== strpos( $compare, 'visita' ) || false !== strpos( $compare, 'visitante' ) || false !== strpos( $compare, 'equipo 2' ) ) {
			$out['incomparecencia'] = 'visita_no_comparecio';
		} else {
			$out['error'] = __( 'resultado por incomparecencia ambiguo: indica si no comparecio local o visita.', 'liga-basket-chile' );
		}

		if ( '' === $out['error'] ) {
			$walkover_score       = liga_get_walkover_score( $out['incomparecencia'] );
			$out['puntos_local']  = (int) $walkover_score['local'];
			$out['puntos_visita'] = (int) $walkover_score['visita'];
		}

		return $out;
	}

	$out['error'] = __( 'resultado invalido. Usa formato tipo 73-39 o deja la celda vacia.', 'liga-basket-chile' );
	return $out;
}

/**
 * Parsea marcador de importacion de partidos desde una sola columna.
 *
 * @param mixed $raw_score Marcador.
 * @return array<string,mixed>
 */
function liga_parse_match_score( $raw_score ) {
	$parsed = liga_import_partidos_parse_result_value( $raw_score );

	return array(
		'has_score'     => ! empty( $parsed['has_result'] ),
		'puntos_local'  => isset( $parsed['puntos_local'] ) ? (int) $parsed['puntos_local'] : 0,
		'puntos_visita' => isset( $parsed['puntos_visita'] ) ? (int) $parsed['puntos_visita'] : 0,
		'error'         => isset( $parsed['error'] ) ? (string) $parsed['error'] : '',
	);
}

/**
 * Obtiene lookup de divisiones por nombre normalizado.
 *
 * @return array{items:array<int,array{id:int,name:string}>,by_key:array<string,int>}
 */
function liga_import_partidos_get_division_lookup() {
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
		$division_name = liga_import_partidos_normalize_text( get_the_title( $division_id ) );
		$meta_name     = liga_import_partidos_normalize_text( get_post_meta( $division_id, 'liga_nombre_division', true ) );

		$lookup['items'][ $division_id ] = array(
			'id'   => $division_id,
			'name' => '' !== $meta_name ? $meta_name : $division_name,
		);

		$title_key = liga_import_partidos_normalize_for_compare( $division_name );
		if ( '' !== $title_key && ! isset( $lookup['by_key'][ $title_key ] ) ) {
			$lookup['by_key'][ $title_key ] = $division_id;
		}

		$meta_key = liga_import_partidos_normalize_for_compare( $meta_name );
		if ( '' !== $meta_key && ! isset( $lookup['by_key'][ $meta_key ] ) ) {
			$lookup['by_key'][ $meta_key ] = $division_id;
		}
	}

	return $lookup;
}

/**
 * Resuelve division por etiqueta.
 *
 * @param string                                               $division_label Label CSV.
 * @param array{items:array<int,array{id:int,name:string}>,by_key:array<string,int>} $lookup Lookup.
 * @return int
 */
function liga_import_partidos_resolve_division_id( $division_label, $lookup ) {
	if ( function_exists( 'liga_get_division_id_by_name' ) ) {
		$central_division_id = liga_get_division_id_by_name( $division_label );
		if ( $central_division_id > 0 ) {
			return $central_division_id;
		}
	}

	$key = liga_import_partidos_normalize_for_compare( $division_label );
	if ( '' === $key ) {
		return 0;
	}

	return isset( $lookup['by_key'][ $key ] ) ? (int) $lookup['by_key'][ $key ] : 0;
}

/**
 * Obtiene mapa de equipos validos para un contexto division+temporada.
 *
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @return array{by_key:array<string,int[]>,names:array<int,string>}
 */
function liga_import_partidos_get_context_team_map( $division_id, $temporada ) {
	$division_id = absint( $division_id );
	$temporada   = liga_import_partidos_normalize_temporada( $temporada );

	$teams = array();
	if ( function_exists( 'liga_get_available_teams_by_division_and_season' ) ) {
		$teams = liga_get_available_teams_by_division_and_season( $division_id, $temporada );
	} else {
		$teams = get_posts(
			array(
				'post_type'      => 'equipo',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'   => 'liga_division',
						'value' => $division_id,
						'type'  => 'NUMERIC',
					),
					array(
						'key'   => 'liga_temporada',
						'value' => $temporada,
					),
				),
			)
		);
	}

	$map = array(
		'by_key' => array(),
		'names'  => array(),
	);

	foreach ( $teams as $team ) {
		$team_id   = (int) $team->ID;
		$team_name = liga_get_equipo_nombre( $team_id );
		$name_key  = liga_import_partidos_normalize_for_compare( $team_name );
		if ( '' === $name_key ) {
			continue;
		}

		if ( ! isset( $map['by_key'][ $name_key ] ) ) {
			$map['by_key'][ $name_key ] = array();
		}

		$map['by_key'][ $name_key ][] = $team_id;
		$map['names'][ $team_id ]     = liga_import_partidos_normalize_text( $team_name );
	}

	return $map;
}

/**
 * Resuelve ID de equipo por nombre y contexto.
 *
 * @param string                                    $team_label Label.
 * @param int                                       $division_id Division.
 * @param string                                    $temporada Temporada.
 * @param array<string, array{by_key:array<string,int[]>,names:array<int,string>}> $cache Cache por contexto.
 * @param string                                    $error_message Error resuelto.
 * @return int
 */
function liga_import_partidos_resolve_team_id( $team_label, $division_id, $temporada, &$cache, &$error_message ) {
	$team_key = liga_import_partidos_normalize_for_compare( $team_label );
	if ( '' === $team_key ) {
		$error_message = __( 'equipo no informado.', 'liga-basket-chile' );
		return 0;
	}

	if ( function_exists( 'liga_get_team_ids_by_name_division_and_season' ) ) {
		$team_ids = liga_get_team_ids_by_name_division_and_season( $team_label, $division_id, $temporada );
		if ( empty( $team_ids ) ) {
			$error_message = __( 'equipo no existe en la division/temporada indicada.', 'liga-basket-chile' );
			return 0;
		}

		if ( count( $team_ids ) > 1 ) {
			$error_message = __( 'equipo ambiguo: existe mas de un registro con ese nombre en el contexto.', 'liga-basket-chile' );
			return 0;
		}

		$resolved_team_id = (int) $team_ids[0];
		if ( function_exists( 'liga_team_belongs_to_competition_context' ) && ! liga_team_belongs_to_competition_context( $resolved_team_id, $division_id, $temporada ) ) {
			$error_message = __( 'equipo no pertenece al contexto competitivo indicado.', 'liga-basket-chile' );
			return 0;
		}

		$error_message = '';
		return $resolved_team_id;
	}

	$context_key = (string) absint( $division_id ) . '|' . liga_import_partidos_normalize_temporada( $temporada );
	if ( ! isset( $cache[ $context_key ] ) ) {
		$cache[ $context_key ] = liga_import_partidos_get_context_team_map( $division_id, $temporada );
	}

	$team_map = $cache[ $context_key ];
	if ( ! isset( $team_map['by_key'][ $team_key ] ) || empty( $team_map['by_key'][ $team_key ] ) ) {
		$error_message = __( 'equipo no existe en la division/temporada indicada.', 'liga-basket-chile' );
		return 0;
	}

	$ids = $team_map['by_key'][ $team_key ];
	if ( count( $ids ) > 1 ) {
		$error_message = __( 'equipo ambiguo: existe mas de un registro con ese nombre en el contexto.', 'liga-basket-chile' );
		return 0;
	}

	$error_message = '';
	return (int) $ids[0];
}

/**
 * Determina si un partido ya existe en el sistema para la misma llave logica.
 *
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @param int    $local_id Local.
 * @param int    $visita_id Visita.
 * @param string $fecha Fecha.
 * @param string $hora Hora.
 * @return bool
 */
function liga_import_partidos_exists_in_system( $division_id, $temporada, $local_id, $visita_id, $fecha, $hora ) {
	$division_id = absint( $division_id );
	$local_id    = absint( $local_id );
	$visita_id   = absint( $visita_id );
	$temporada   = liga_import_partidos_normalize_temporada( $temporada );
	$fecha       = liga_import_partidos_normalize_text( $fecha );
	$hora        = liga_import_partidos_normalize_text( $hora );

	if ( function_exists( 'liga_match_exists_in_competition_context' ) ) {
		return liga_match_exists_in_competition_context( $division_id, $temporada, $local_id, $visita_id, $fecha, $hora );
	}

	return false;
}

/**
 * Convierte filas tabulares a filas internas del importador.
 *
 * @param array<int, array<int, mixed>> $raw_rows Filas.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function liga_import_partidos_normalize_tabular_rows( $raw_rows ) {
	$header = array();
	$offset = 0;

	foreach ( $raw_rows as $index => $raw_row ) {
		if ( liga_import_partidos_is_empty_raw_row( $raw_row ) ) {
			continue;
		}

		$raw_row[0] = isset( $raw_row[0] ) ? preg_replace( '/^\xEF\xBB\xBF/u', '', (string) $raw_row[0] ) : '';
		$mapped     = liga_import_partidos_map_header_indexes( $raw_row );
		if ( isset( $mapped['equipo_local'], $mapped['equipo_visitante'] ) ) {
			$header = $mapped;
			$offset = (int) $index + 1;
			break;
		}
	}

	if ( empty( $header ) ) {
		return new WP_Error(
			'import_header_invalid',
			__( 'No se detectaron encabezados validos. El calendario debe incluir Equipo 1 y Equipo 2, y puede incluir Resultado, Fecha, Hora, Lugar y Jornada.', 'liga-basket-chile' )
		);
	}

	$rows = array();
	foreach ( $raw_rows as $index => $raw_row ) {
		if ( (int) $index < $offset || liga_import_partidos_is_empty_raw_row( $raw_row ) ) {
			continue;
		}

		$row         = liga_normalize_match_import_row( $raw_row, $header );
		$row['line'] = (int) $index + 1;

		$is_empty = true;
		foreach ( $row as $key => $value ) {
			if ( 'line' === $key ) {
				continue;
			}
			if ( '' !== trim( (string) $value ) ) {
				$is_empty = false;
				break;
			}
		}

		if ( ! $is_empty ) {
			$rows[] = $row;
		}
	}

	return $rows;
}

/**
 * Normaliza una fila tabular de importacion de partidos segun mapa de encabezados.
 *
 * @param array<int, mixed>    $row Fila original.
 * @param array<string, int>   $header_map Mapa campo interno => indice.
 * @return array<string, mixed>
 */
function liga_normalize_match_import_row( array $row, array $header_map ) {
	$get_value = static function ( $field ) use ( $row, $header_map ) {
		if ( ! isset( $header_map[ $field ] ) ) {
			return '';
		}

		$index = (int) $header_map[ $field ];
		return isset( $row[ $index ] ) ? $row[ $index ] : '';
	};

	return array(
		'jornada'          => $get_value( 'jornada' ),
		'division'         => $get_value( 'division' ),
		'temporada'        => $get_value( 'temporada' ),
		'equipo_local'     => $get_value( 'equipo_local' ),
		'equipo_visitante' => $get_value( 'equipo_visitante' ),
		'resultado'        => $get_value( 'resultado' ),
		'fecha'            => $get_value( 'fecha' ),
		'hora'             => $get_value( 'hora' ),
		'recinto'          => $get_value( 'recinto' ),
		'estado'           => $get_value( 'estado' ),
	);
}

/**
 * Parsea CSV con encabezados oficiales o legacy.
 *
 * @param string $file_path Ruta temporal.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function liga_import_partidos_parse_csv( $file_path ) {
	$handle = fopen( $file_path, 'r' );
	if ( ! $handle ) {
		return new WP_Error( 'import_csv_open_failed', __( 'No fue posible leer el archivo CSV.', 'liga-basket-chile' ) );
	}

	$raw_rows = array();
	while ( ( $data = fgetcsv( $handle, 0, ',' ) ) !== false ) {
		$raw_rows[] = is_array( $data ) ? $data : array();
	}

	fclose( $handle );

	if ( empty( $raw_rows ) ) {
		return new WP_Error( 'import_csv_empty', __( 'El archivo CSV esta vacio.', 'liga-basket-chile' ) );
	}

	return liga_import_partidos_normalize_tabular_rows( $raw_rows );
}

/**
 * Parsea Excel si PhpSpreadsheet esta cargado en el proyecto.
 *
 * @param string $file_path Ruta temporal.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function liga_import_partidos_parse_excel( $file_path ) {
	if ( ! class_exists( '\PhpOffice\PhpSpreadsheet\IOFactory' ) ) {
		$autoload_candidates = array(
			get_template_directory() . '/vendor/autoload.php',
			ABSPATH . 'vendor/autoload.php',
		);

		foreach ( $autoload_candidates as $autoload_path ) {
			if ( file_exists( $autoload_path ) ) {
				require_once $autoload_path;
				break;
			}
		}
	}

	if ( ! class_exists( '\PhpOffice\PhpSpreadsheet\IOFactory' ) ) {
		return new WP_Error(
			'import_excel_reader_missing',
			__( 'El servidor no tiene un lector Excel disponible. Exporta el calendario oficial a CSV manteniendo sus encabezados reales, o instala PhpSpreadsheet en el proyecto.', 'liga-basket-chile' )
		);
	}

	try {
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $file_path );
		$sheet       = $spreadsheet->getActiveSheet();
		$raw_rows    = $sheet->toArray( null, true, true, false );
	} catch ( Exception $exception ) {
		return new WP_Error( 'import_excel_read_failed', __( 'No fue posible leer el archivo Excel.', 'liga-basket-chile' ) );
	}

	if ( empty( $raw_rows ) ) {
		return new WP_Error( 'import_excel_empty', __( 'El archivo Excel esta vacio.', 'liga-basket-chile' ) );
	}

	return liga_import_partidos_normalize_tabular_rows( $raw_rows );
}

/**
 * Parsea archivo de partidos segun extension.
 *
 * @param string $file_path Ruta temporal.
 * @param string $extension Extension.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function liga_import_partidos_parse_file( $file_path, $extension ) {
	$extension = strtolower( sanitize_key( (string) $extension ) );

	if ( 'csv' === $extension ) {
		return liga_import_partidos_parse_csv( $file_path );
	}

	if ( in_array( $extension, array( 'xls', 'xlsx' ), true ) ) {
		return liga_import_partidos_parse_excel( $file_path );
	}

	return new WP_Error( 'import_file_type_invalid', __( 'Formato no soportado. Usa .xls, .xlsx o .csv.', 'liga-basket-chile' ) );
}

/**
 * Valida fecha de partido.
 *
 * @param string $fecha Fecha.
 * @return bool
 */
function liga_import_partidos_is_valid_date( $fecha ) {
	if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fecha ) ) {
		return false;
	}

	$dt = DateTime::createFromFormat( 'Y-m-d', $fecha );
	return $dt && $dt->format( 'Y-m-d' ) === $fecha;
}

/**
 * Busca un partido existente para actualizarlo.
 *
 * @param int    $division_id Division.
 * @param string $temporada Temporada.
 * @param int    $local_id Local.
 * @param int    $visita_id Visita.
 * @param string $fecha Fecha.
 * @param string $hora Hora.
 * @param string $jornada Jornada.
 * @return int
 */
function liga_import_partidos_find_existing_match_id( $division_id, $temporada, $local_id, $visita_id, $fecha = '', $hora = '', $jornada = '' ) {
	$division_id = absint( $division_id );
	$temporada   = liga_import_partidos_normalize_temporada( $temporada );
	$local_id    = absint( $local_id );
	$visita_id   = absint( $visita_id );
	$fecha       = liga_import_partidos_normalize_text( $fecha );
	$hora        = liga_import_partidos_normalize_text( $hora );
	$jornada     = liga_import_partidos_normalize_text( $jornada );

	if ( $division_id <= 0 || '' === $temporada || $local_id <= 0 || $visita_id <= 0 ) {
		return 0;
	}

	$meta_query = array(
		array(
			'key'   => 'liga_division',
			'value' => $division_id,
			'type'  => 'NUMERIC',
		),
		array(
			'key'   => 'liga_temporada',
			'value' => $temporada,
		),
		array(
			'key'   => 'liga_equipo_local',
			'value' => $local_id,
			'type'  => 'NUMERIC',
		),
		array(
			'key'   => 'liga_equipo_visita',
			'value' => $visita_id,
			'type'  => 'NUMERIC',
		),
	);

	if ( '' !== $fecha ) {
		$meta_query[] = array(
			'key'   => 'liga_fecha_partido',
			'value' => $fecha,
		);
		$meta_query[] = array(
			'key'   => 'liga_hora_partido',
			'value' => $hora,
		);
	} elseif ( '' !== $jornada ) {
		$meta_query[] = array(
			'key'   => 'liga_jornada_partido',
			'value' => $jornada,
		);
	}

	$matches = get_posts(
		array(
			'post_type'      => 'partido',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => 2,
			'fields'         => 'ids',
			'meta_query'     => $meta_query,
		)
	);

	if ( ! is_array( $matches ) || 1 !== count( $matches ) ) {
		return 0;
	}

	return (int) $matches[0];
}

/**
 * Determina accion prevista y conflictos contra un partido existente.
 *
 * @param int                 $existing_id Partido existente.
 * @param array<string,mixed> $resultado Resultado parseado.
 * @return array{action:string,errors:array<int,string>}
 */
function liga_import_partidos_get_preview_action( $existing_id, $resultado ) {
	$existing_id = absint( $existing_id );
	$has_result  = ! empty( $resultado['has_result'] );

	if ( $existing_id <= 0 ) {
		return array(
			'action' => $has_result ? 'create_played' : 'create_scheduled',
			'errors' => array(),
		);
	}

	$current_estado = sanitize_key( (string) get_post_meta( $existing_id, 'liga_estado_partido', true ) );
	$current_played = in_array( $current_estado, array( 'jugado', 'finalizado' ), true );
	$current_local  = (int) get_post_meta( $existing_id, 'liga_puntos_local', true );
	$current_visita = (int) get_post_meta( $existing_id, 'liga_puntos_visita', true );

	if ( $current_played && ! $has_result ) {
		return array(
			'action' => 'conflict',
			'errors' => array( __( 'partido existente ya tiene resultado; el calendario no trae marcador y no se sobrescribira.', 'liga-basket-chile' ) ),
		);
	}

	if ( $current_played && $has_result ) {
		if ( $current_local !== (int) $resultado['puntos_local'] || $current_visita !== (int) $resultado['puntos_visita'] ) {
			return array(
				'action' => 'conflict',
				'errors' => array( __( 'partido existente ya tiene un resultado distinto; revisa manualmente antes de sobrescribir.', 'liga-basket-chile' ) ),
			);
		}

		return array(
			'action' => 'update_played',
			'errors' => array(),
		);
	}

	return array(
		'action' => $has_result ? 'update_result' : 'update_scheduled',
		'errors' => array(),
	);
}

/**
 * Devuelve etiqueta administrativa para accion prevista.
 *
 * @param string $action Accion.
 * @return string
 */
function liga_import_partidos_get_action_label( $action ) {
	$labels = array(
		'create_scheduled' => __( 'Crear programado', 'liga-basket-chile' ),
		'create_played'    => __( 'Crear jugado', 'liga-basket-chile' ),
		'update_scheduled' => __( 'Actualizar programado', 'liga-basket-chile' ),
		'update_result'    => __( 'Actualizar resultado', 'liga-basket-chile' ),
		'update_played'    => __( 'Actualizar jugado', 'liga-basket-chile' ),
		'conflict'         => __( 'Conflicto', 'liga-basket-chile' ),
		'skip_error'       => __( 'Omitir por error', 'liga-basket-chile' ),
	);

	return isset( $labels[ $action ] ) ? (string) $labels[ $action ] : (string) $action;
}

/**
 * Valida filas del calendario oficial de partidos.
 *
 * @param array<int, array<string, mixed>> $rows Filas parseadas.
 * @param array<string,mixed>              $context Contexto seleccionado.
 * @return array<string, mixed>
 */
function liga_import_partidos_validate_rows( $rows, $context = array() ) {
	$division_lookup = liga_import_partidos_get_division_lookup();
	$team_cache      = array();
	$file_seen       = array();
	$validated_rows  = array();
	$valid_rows      = array();
	$invalid_rows    = array();

	$context_division_id = isset( $context['division_id'] ) ? absint( $context['division_id'] ) : 0;
	$context_temporada   = liga_import_partidos_normalize_temporada( isset( $context['temporada'] ) ? $context['temporada'] : '' );

	foreach ( $rows as $row ) {
		$line = isset( $row['line'] ) ? absint( $row['line'] ) : 0;

		$division_raw = $context_division_id > 0
			? ( isset( $division_lookup['items'][ $context_division_id ]['name'] ) ? (string) $division_lookup['items'][ $context_division_id ]['name'] : '' )
			: liga_import_partidos_normalize_text( isset( $row['division'] ) ? $row['division'] : '' );
		$temporada    = '' !== $context_temporada ? $context_temporada : liga_import_partidos_normalize_temporada( isset( $row['temporada'] ) ? $row['temporada'] : '' );
		$jornada      = liga_import_partidos_normalize_text( isset( $row['jornada'] ) ? $row['jornada'] : '' );
		$local_raw    = liga_import_partidos_normalize_text( isset( $row['equipo_local'] ) ? $row['equipo_local'] : '' );
		$visita_raw   = liga_import_partidos_normalize_text( isset( $row['equipo_visitante'] ) ? $row['equipo_visitante'] : '' );
		$fecha        = liga_import_partidos_normalize_date_value( isset( $row['fecha'] ) ? $row['fecha'] : '' );
		$hora         = liga_import_partidos_normalize_time_value( isset( $row['hora'] ) ? $row['hora'] : '' );
		$recinto      = liga_import_partidos_normalize_text( isset( $row['recinto'] ) ? $row['recinto'] : '' );
		$estado       = liga_import_partidos_normalize_estado( isset( $row['estado'] ) ? $row['estado'] : '' );
		$resultado    = liga_import_partidos_parse_result_value( isset( $row['resultado'] ) ? $row['resultado'] : '' );

		if ( ! empty( $resultado['has_result'] ) ) {
			$estado = 'jugado';
		} elseif ( '' === liga_import_partidos_normalize_text( isset( $row['estado'] ) ? $row['estado'] : '' ) ) {
			$estado = 'programado';
		}

		$errors      = array();
		$division_id = $context_division_id;
		$local_id    = 0;
		$visita_id   = 0;
		$existing_id = 0;

		if ( $division_id <= 0 && '' !== $division_raw ) {
			$division_id = liga_import_partidos_resolve_division_id( $division_raw, $division_lookup );
		}

		if ( $division_id <= 0 ) {
			$errors[] = __( 'selecciona una division valida para este calendario.', 'liga-basket-chile' );
		}

		if ( '' === $temporada ) {
			$errors[] = __( 'selecciona una temporada valida con formato YYYY.', 'liga-basket-chile' );
		}

		if ( $division_id > 0 && '' !== $temporada ) {
			$division_temporada = liga_get_division_temporada_label( $division_id );
			if ( liga_is_valid_temporada_label( $division_temporada ) && $division_temporada !== $temporada ) {
				$errors[] = __( 'la temporada no coincide con la temporada configurada en la division.', 'liga-basket-chile' );
			}
		}

		if ( '' === $local_raw ) {
			$errors[] = __( 'equipo_local es obligatorio.', 'liga-basket-chile' );
		}

		if ( '' === $visita_raw ) {
			$errors[] = __( 'equipo_visitante es obligatorio.', 'liga-basket-chile' );
		}

		if ( '' !== $fecha && ! liga_import_partidos_is_valid_date( $fecha ) ) {
			$errors[] = __( 'fecha invalida. Usa formato YYYY-MM-DD.', 'liga-basket-chile' );
		}

		if ( '' !== $hora && 1 !== preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora ) ) {
			$errors[] = __( 'hora invalida. Usa formato HH:MM.', 'liga-basket-chile' );
		}

		if ( ! in_array( $estado, liga_import_partidos_allowed_states(), true ) ) {
			$errors[] = __( 'estado invalido para el sistema.', 'liga-basket-chile' );
		}

		if ( '' !== $resultado['error'] ) {
			$errors[] = (string) $resultado['error'];
		}

		if ( empty( $resultado['has_result'] ) && in_array( $estado, array( 'jugado', 'finalizado' ), true ) ) {
			$errors[] = __( 'estado jugado/finalizado requiere marcador valido en Resultado.', 'liga-basket-chile' );
		}

		if ( ! empty( $resultado['has_result'] ) && 'ninguna' === $resultado['incomparecencia'] ) {
			if ( (int) $resultado['puntos_local'] === (int) $resultado['puntos_visita'] ) {
				$errors[] = __( 'resultado invalido: no se permiten empates.', 'liga-basket-chile' );
			}

			if ( 0 === (int) $resultado['puntos_local'] && 0 === (int) $resultado['puntos_visita'] ) {
				$errors[] = __( 'resultado invalido: no se permite marcador 0-0.', 'liga-basket-chile' );
			}
		}

		if ( $division_id > 0 && '' !== $temporada && '' !== $local_raw ) {
			$local_error = '';
			$local_id    = liga_import_partidos_resolve_team_id( $local_raw, $division_id, $temporada, $team_cache, $local_error );
			if ( $local_id <= 0 ) {
				$errors[] = sprintf( __( 'equipo_local: %s', 'liga-basket-chile' ), $local_error );
			}
		}

		if ( $division_id > 0 && '' !== $temporada && '' !== $visita_raw ) {
			$visita_error = '';
			$visita_id    = liga_import_partidos_resolve_team_id( $visita_raw, $division_id, $temporada, $team_cache, $visita_error );
			if ( $visita_id <= 0 ) {
				$errors[] = sprintf( __( 'equipo_visitante: %s', 'liga-basket-chile' ), $visita_error );
			}
		}

		if ( $local_id > 0 && $visita_id > 0 && $local_id === $visita_id ) {
			$errors[] = __( 'equipo local y visitante no pueden ser el mismo.', 'liga-basket-chile' );
		}

		if ( $local_id > 0 && $visita_id > 0 && $division_id > 0 && '' !== $temporada ) {
			$matchup_validation = liga_validate_basketball_matchup( $local_id, $visita_id, $division_id, $temporada );
			if ( is_wp_error( $matchup_validation ) ) {
				$errors[] = $matchup_validation->get_error_message();
			}
		}

		$match_key = '';
		if ( empty( $errors ) ) {
			$match_key = implode(
				'|',
				array(
					(string) $division_id,
					$temporada,
					(string) $local_id,
					(string) $visita_id,
					$jornada,
					$fecha,
					$hora,
				)
			);

			if ( isset( $file_seen[ $match_key ] ) ) {
				$errors[] = sprintf(
					/* translators: %d: numero de fila en el mismo archivo */
					__( 'partido duplicado dentro del archivo (primera aparicion en fila %d).', 'liga-basket-chile' ),
					(int) $file_seen[ $match_key ]
				);
			} else {
				$file_seen[ $match_key ] = $line;
			}

			$existing_id = liga_import_partidos_find_existing_match_id( $division_id, $temporada, $local_id, $visita_id, $fecha, $hora, $jornada );
			$preview_action = liga_import_partidos_get_preview_action( $existing_id, $resultado );
			if ( ! empty( $preview_action['errors'] ) ) {
				$errors = array_merge( $errors, $preview_action['errors'] );
			}
		}

		$division_name = $division_id > 0 && isset( $division_lookup['items'][ $division_id ]['name'] )
			? (string) $division_lookup['items'][ $division_id ]['name']
			: $division_raw;
		$import_action = isset( $preview_action['action'] ) ? (string) $preview_action['action'] : ( $existing_id > 0 ? 'update_scheduled' : 'create_scheduled' );
		if ( ! empty( $errors ) && 'conflict' !== $import_action ) {
			$import_action = 'skip_error';
		}

		$validated_row = array(
			'line'             => $line,
			'jornada'          => $jornada,
			'division'         => $division_name,
			'division_id'      => $division_id,
			'temporada'        => $temporada,
			'equipo_local'     => $local_raw,
			'equipo_local_id'  => $local_id,
			'equipo_visitante' => $visita_raw,
			'equipo_visitante_id' => $visita_id,
			'fecha'            => $fecha,
			'hora'             => $hora,
			'recinto'          => $recinto,
			'estado'           => $estado,
			'resultado'        => ! empty( $resultado['has_result'] ) ? (string) $resultado['puntos_local'] . '-' . (string) $resultado['puntos_visita'] : '',
			'puntos_local'     => (int) $resultado['puntos_local'],
			'puntos_visita'    => (int) $resultado['puntos_visita'],
			'incomparecencia'  => (string) $resultado['incomparecencia'],
			'existing_match_id' => $existing_id,
			'import_action'    => $import_action,
			'match_key'        => $match_key,
			'errors'           => $errors,
			'is_valid'         => empty( $errors ),
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
 * @param int                 $user_id Usuario.
 * @param array<string,mixed> $payload Payload.
 * @return string
 */
function liga_import_partidos_store_preview( $user_id, $payload ) {
	$token = wp_generate_password( 20, false, false );
	$key   = liga_import_partidos_get_preview_transient_key( $token, $user_id );

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
function liga_import_partidos_get_preview( $token, $user_id ) {
	$key   = liga_import_partidos_get_preview_transient_key( $token, $user_id );
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
function liga_import_partidos_delete_preview( $token, $user_id ) {
	$key = liga_import_partidos_get_preview_transient_key( $token, $user_id );
	delete_transient( $key );
}

/**
 * Crea o actualiza partido validado en estructura real del sistema.
 *
 * @param array<string,mixed> $row Fila validada.
 * @return array{match_id:int,action:string,recalculated:bool}|WP_Error
 */
function liga_import_partidos_upsert_match( $row ) {
	$division_id = isset( $row['division_id'] ) ? absint( $row['division_id'] ) : 0;
	$temporada   = liga_import_partidos_normalize_temporada( isset( $row['temporada'] ) ? $row['temporada'] : '' );
	$local_id    = isset( $row['equipo_local_id'] ) ? absint( $row['equipo_local_id'] ) : 0;
	$visita_id   = isset( $row['equipo_visitante_id'] ) ? absint( $row['equipo_visitante_id'] ) : 0;
	$fecha       = liga_import_partidos_normalize_text( isset( $row['fecha'] ) ? $row['fecha'] : '' );
	$hora        = liga_import_partidos_normalize_text( isset( $row['hora'] ) ? $row['hora'] : '' );
	$recinto     = liga_import_partidos_normalize_text( isset( $row['recinto'] ) ? $row['recinto'] : '' );
	$jornada     = liga_import_partidos_normalize_text( isset( $row['jornada'] ) ? $row['jornada'] : '' );
	$estado      = liga_import_partidos_normalize_estado( isset( $row['estado'] ) ? $row['estado'] : '' );
	$puntos_local = isset( $row['puntos_local'] ) ? absint( $row['puntos_local'] ) : 0;
	$puntos_visita = isset( $row['puntos_visita'] ) ? absint( $row['puntos_visita'] ) : 0;
	$incomparecencia = isset( $row['incomparecencia'] ) ? sanitize_key( (string) $row['incomparecencia'] ) : 'ninguna';
	if ( ! in_array( $incomparecencia, array( 'ninguna', 'local_no_comparecio', 'visita_no_comparecio' ), true ) ) {
		$incomparecencia = 'ninguna';
	}

	if ( $division_id <= 0 || '' === $temporada || $local_id <= 0 || $visita_id <= 0 ) {
		return new WP_Error( 'import_match_invalid', __( 'Fila invalida para importacion.', 'liga-basket-chile' ) );
	}

	$matchup_validation = liga_validate_basketball_matchup( $local_id, $visita_id, $division_id, $temporada );
	if ( is_wp_error( $matchup_validation ) ) {
		return $matchup_validation;
	}

	$post_title = liga_get_equipo_nombre( $local_id ) . ' vs ' . liga_get_equipo_nombre( $visita_id );
	$post_id    = isset( $row['existing_match_id'] ) ? absint( $row['existing_match_id'] ) : 0;
	if ( $post_id <= 0 ) {
		$post_id = liga_import_partidos_find_existing_match_id( $division_id, $temporada, $local_id, $visita_id, $fecha, $hora, $jornada );
	}

	$action = 'update';
	if ( $post_id <= 0 ) {
		$action  = 'create';
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'partido',
				'post_status' => 'publish',
				'post_title'  => $post_title,
			),
			true
		);
	} else {
		$update_title = wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $post_title,
			),
			true
		);
		if ( is_wp_error( $update_title ) ) {
			return $update_title;
		}
	}

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$meta_fields = array(
		'liga_equipo_local'    => $local_id,
		'liga_equipo_visita'   => $visita_id,
		'liga_division'        => $division_id,
		'liga_temporada'       => $temporada,
		'liga_fecha_partido'   => $fecha,
		'liga_hora_partido'    => $hora,
		'liga_cancha'          => $recinto,
		'liga_jornada_partido' => $jornada,
		'liga_estado_partido'  => $estado,
		'liga_puntos_local'    => $puntos_local,
		'liga_puntos_visita'   => $puntos_visita,
		'liga_incomparecencia' => $incomparecencia,
	);

	foreach ( $meta_fields as $meta_key => $meta_value ) {
		update_post_meta( $post_id, $meta_key, $meta_value );
	}

	$recalculated = false;
	if ( function_exists( 'liga_maybe_recalculate_standings_for_match' ) ) {
		$recalculated = (bool) liga_maybe_recalculate_standings_for_match( (int) $post_id );
	}

	return array(
		'match_id'     => (int) $post_id,
		'action'       => $action,
		'recalculated' => $recalculated,
	);
}

/**
 * Procesa acciones del importador de partidos.
 *
 * @return void
 */
function liga_handle_admin_import_partidos_actions() {
	if ( ! is_admin() || ! isset( $_POST['liga_import_partidos_action'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		liga_add_admin_alert( 'error', __( 'No tienes permisos para importar partidos.', 'liga-basket-chile' ) );
		wp_safe_redirect( liga_import_partidos_get_page_url() );
		exit;
	}

	$action  = sanitize_key( wp_unslash( $_POST['liga_import_partidos_action'] ) );
	$user_id = get_current_user_id();

	if ( 'validate_csv' === $action ) {
		check_admin_referer( 'liga_validate_partidos_csv', 'liga_validate_partidos_csv_nonce' );

		$context_division_id = isset( $_POST['liga_import_division'] ) ? absint( wp_unslash( $_POST['liga_import_division'] ) ) : 0;
		$context_temporada   = liga_import_partidos_normalize_temporada( isset( $_POST['liga_import_temporada'] ) ? wp_unslash( $_POST['liga_import_temporada'] ) : '' );

		if ( $context_division_id <= 0 || ! liga_is_valid_post_type_id( $context_division_id, 'division' ) ) {
			liga_add_admin_alert( 'error', __( 'Debes seleccionar una division valida para el calendario.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		if ( '' === $context_temporada ) {
			liga_add_admin_alert( 'error', __( 'Debes seleccionar una temporada valida para el calendario.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$division_temporada = liga_get_division_temporada_label( $context_division_id );
		if ( liga_is_valid_temporada_label( $division_temporada ) && $division_temporada !== $context_temporada ) {
			liga_add_admin_alert( 'error', __( 'La temporada seleccionada no coincide con la temporada configurada en la division.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		if ( empty( $_FILES['liga_partidos_archivo'] ) || ! is_array( $_FILES['liga_partidos_archivo'] ) ) {
			liga_add_admin_alert( 'error', __( 'Debes seleccionar un archivo de calendario.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$file       = $_FILES['liga_partidos_archivo'];
		$error_code = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			liga_add_admin_alert( 'error', __( 'No fue posible subir el archivo de calendario.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		$name     = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
		$size     = isset( $file['size'] ) ? (int) $file['size'] : 0;

		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			liga_add_admin_alert( 'error', __( 'Archivo temporal invalido para validacion.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		if ( $size <= 0 ) {
			liga_add_admin_alert( 'error', __( 'El archivo de calendario esta vacio.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$extension = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, liga_import_partidos_supported_extensions(), true ) ) {
			liga_add_admin_alert( 'error', __( 'El archivo debe tener extension .xls, .xlsx o .csv.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$rows = liga_import_partidos_parse_file( $tmp_name, $extension );
		if ( is_wp_error( $rows ) ) {
			liga_add_admin_alert( 'error', $rows->get_error_message() );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$validation = liga_import_partidos_validate_rows(
			$rows,
			array(
				'division_id' => $context_division_id,
				'temporada'   => $context_temporada,
			)
		);
		if ( empty( $validation['summary']['total'] ) ) {
			liga_add_admin_alert( 'warning', __( 'No se detectaron filas de datos en el calendario.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$token = liga_import_partidos_store_preview( $user_id, $validation );

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
			liga_import_partidos_get_page_url(
				array(
					'import_token' => $token,
				)
			)
		);
		exit;
	}

	if ( 'confirm_import' === $action ) {
		check_admin_referer( 'liga_confirm_partidos_csv', 'liga_confirm_partidos_csv_nonce' );

		$token = isset( $_POST['import_token'] ) ? sanitize_key( wp_unslash( $_POST['import_token'] ) ) : '';
		if ( '' === $token ) {
			liga_add_admin_alert( 'error', __( 'Token de confirmacion invalido.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$preview = liga_import_partidos_get_preview( $token, $user_id );
		if ( null === $preview ) {
			liga_add_admin_alert( 'error', __( 'La prevalidacion expiro. Vuelve a validar el archivo.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$payload    = $preview['payload'];
		$valid_rows = isset( $payload['valid_rows'] ) && is_array( $payload['valid_rows'] ) ? $payload['valid_rows'] : array();
		$created    = 0;
		$updated    = 0;
		$skipped    = 0;
		$failed     = 0;
		$recalculated = 0;

		foreach ( $valid_rows as $row ) {
			$upsert = liga_import_partidos_upsert_match( $row );
			if ( is_wp_error( $upsert ) ) {
				$failed++;
				continue;
			}

			if ( isset( $upsert['action'] ) && 'update' === $upsert['action'] ) {
				$updated++;
			} else {
				$created++;
			}

			if ( ! empty( $upsert['recalculated'] ) ) {
				$recalculated++;
			}
		}

		liga_import_partidos_delete_preview( $token, $user_id );

		liga_add_admin_alert(
			'success',
			sprintf(
				/* translators: 1: creados, 2: actualizados, 3: omitidos, 4: fallidos, 5: recalculos */
				__( 'Importacion finalizada. Creados: %1$d | Actualizados: %2$d | Omitidos: %3$d | Fallidos: %4$d | Recalculos de tabla: %5$d', 'liga-basket-chile' ),
				$created,
				$updated,
				$skipped,
				$failed,
				$recalculated
			)
		);

		wp_safe_redirect(
			liga_import_partidos_get_page_url(
				array(
					'import_done'    => 1,
					'import_created' => $created,
					'import_updated' => $updated,
					'import_skipped' => $skipped,
					'import_failed'  => $failed,
					'import_recalculated' => $recalculated,
				)
			)
		);
		exit;
	}
}
add_action( 'admin_init', 'liga_handle_admin_import_partidos_actions' );

/**
 * Descarga ejemplo CSV compatible con el calendario oficial.
 *
 * @return void
 */
function liga_download_partidos_csv_template() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'No tienes permisos para descargar esta plantilla.', 'liga-basket-chile' ) );
	}

	check_admin_referer( 'liga_download_partidos_csv_template' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename=ejemplo-calendario-oficial-liga.csv' );

	echo "\xEF\xBB\xBF";

	$output = fopen( 'php://output', 'w' );
	if ( false !== $output ) {
		fputcsv( $output, array( 'Jornada', 'Equipo 1', 'Resultado', 'Equipo 2', 'Fecha', 'Hora', 'Lugar' ) );
		fputcsv( $output, array( 'Jornada 1', 'AV Express', '73-39', 'Vipla', '2026-06-07', '10:00', 'Gimnasio Municipal' ) );
		fputcsv( $output, array( 'Jornada 2', 'Equipo A', '', 'Equipo B', '', '', '' ) );
		fclose( $output );
	}

	exit;
}
add_action( 'admin_post_liga_download_partidos_csv_template', 'liga_download_partidos_csv_template' );

/**
 * Renderiza pantalla admin Importar Partidos.
 *
 * @return void
 */
function liga_render_admin_import_partidos_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( esc_html__( 'No tienes permisos para acceder a esta pagina.', 'liga-basket-chile' ) );
	}

	$user_id = get_current_user_id();
	$token   = isset( $_GET['import_token'] ) ? sanitize_key( wp_unslash( $_GET['import_token'] ) ) : '';
	$preview = '' !== $token ? liga_import_partidos_get_preview( $token, $user_id ) : null;

	$payload      = is_array( $preview ) && isset( $preview['payload'] ) && is_array( $preview['payload'] ) ? $preview['payload'] : array();
	$summary      = isset( $payload['summary'] ) && is_array( $payload['summary'] ) ? $payload['summary'] : array();
	$preview_rows = isset( $payload['rows'] ) && is_array( $payload['rows'] ) ? $payload['rows'] : array();
	$valid_rows   = isset( $payload['valid_rows'] ) && is_array( $payload['valid_rows'] ) ? $payload['valid_rows'] : array();
	$divisions    = liga_get_posts_map( 'division' );
	$temporadas   = liga_get_available_temporadas();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Importar Partidos', 'liga-basket-chile' ); ?></h1>
		<p><?php esc_html_e( 'Importa el calendario oficial del gestor de competencia para crear programacion, actualizar resultados y recalcular tablas desde partidos reales.', 'liga-basket-chile' ); ?></p>

		<p>
			<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=liga_download_partidos_csv_template' ), 'liga_download_partidos_csv_template' ) ); ?>">
				<?php esc_html_e( 'Descargar ejemplo CSV compatible', 'liga-basket-chile' ); ?>
			</a>
		</p>

		<div class="card">
			<h2><?php esc_html_e( 'Paso 1: Validar calendario oficial', 'liga-basket-chile' ); ?></h2>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'liga_validate_partidos_csv', 'liga_validate_partidos_csv_nonce' ); ?>
				<input type="hidden" name="liga_import_partidos_action" value="validate_csv">
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="liga_import_division"><?php esc_html_e( 'Division / categoria', 'liga-basket-chile' ); ?></label></th>
						<td>
							<select id="liga_import_division" name="liga_import_division" required>
								<option value=""><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
								<?php foreach ( $divisions as $division_id => $division_title ) : ?>
									<option value="<?php echo esc_attr( (string) $division_id ); ?>"><?php echo esc_html( (string) $division_title ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'El archivo no se usa para decidir categoria; este contexto manda sobre cualquier columna del Excel.', 'liga-basket-chile' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="liga_import_temporada"><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></label></th>
						<td>
							<select id="liga_import_temporada" name="liga_import_temporada" required>
								<option value=""><?php esc_html_e( 'Seleccionar', 'liga-basket-chile' ); ?></option>
								<?php foreach ( $temporadas as $temporada_key => $temporada_label ) : ?>
									<option value="<?php echo esc_attr( (string) $temporada_key ); ?>"><?php echo esc_html( (string) $temporada_label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Ejemplo: 2026. La tabla se recalcula solo para esta division y temporada.', 'liga-basket-chile' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="liga_partidos_archivo"><?php esc_html_e( 'Archivo de calendario', 'liga-basket-chile' ); ?></label></th>
						<td>
							<input type="file" id="liga_partidos_archivo" name="liga_partidos_archivo" accept=".xls,.xlsx,.csv,text/csv" required>
							<p class="description"><?php esc_html_e( 'Encabezados esperados del gestor: Jornada, Equipo 1, Resultado, Equipo 2, Fecha, Hora, Lugar. CSV mantiene compatibilidad si el servidor no tiene lector Excel.', 'liga-basket-chile' ); ?></p>
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
					<strong><?php esc_html_e( 'Actualizados', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) absint( isset( $_GET['import_updated'] ) ? wp_unslash( $_GET['import_updated'] ) : 0 ) ); ?>
					|
					<strong><?php esc_html_e( 'Omitidos', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) absint( isset( $_GET['import_skipped'] ) ? wp_unslash( $_GET['import_skipped'] ) : 0 ) ); ?>
					|
					<strong><?php esc_html_e( 'Fallidos', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) absint( isset( $_GET['import_failed'] ) ? wp_unslash( $_GET['import_failed'] ) : 0 ) ); ?>
					|
					<strong><?php esc_html_e( 'Recalculos', 'liga-basket-chile' ); ?>:</strong>
					<?php echo esc_html( (string) absint( isset( $_GET['import_recalculated'] ) ? wp_unslash( $_GET['import_recalculated'] ) : 0 ) ); ?>
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
							<th><?php esc_html_e( 'Jornada', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Equipo local', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Resultado', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Equipo visitante', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Fecha', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Hora', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Recinto', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Accion', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Estado resultante', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Validacion', 'liga-basket-chile' ); ?></th>
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
								<td><?php echo esc_html( (string) ( isset( $row['jornada'] ) ? $row['jornada'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['division'] ) ? $row['division'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['temporada'] ) ? $row['temporada'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['equipo_local'] ) ? $row['equipo_local'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['resultado'] ) ? $row['resultado'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['equipo_visitante'] ) ? $row['equipo_visitante'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['fecha'] ) ? $row['fecha'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['hora'] ) ? $row['hora'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['recinto'] ) ? $row['recinto'] : '' ) ); ?></td>
								<td><?php echo esc_html( liga_import_partidos_get_action_label( isset( $row['import_action'] ) ? (string) $row['import_action'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['estado'] ) ? $row['estado'] : '' ) ); ?></td>
								<td>
									<?php if ( $is_valid ) : ?>
										<span style="color:#008a20;"><?php esc_html_e( 'Valida', 'liga-basket-chile' ); ?></span>
									<?php else : ?>
										<span style="color:#b32d2e;"><?php echo esc_html( implode( ' | ', array_map( 'sanitize_text_field', $row_errors ) ) ); ?></span>
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
					<?php wp_nonce_field( 'liga_confirm_partidos_csv', 'liga_confirm_partidos_csv_nonce' ); ?>
					<input type="hidden" name="liga_import_partidos_action" value="confirm_import">
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
