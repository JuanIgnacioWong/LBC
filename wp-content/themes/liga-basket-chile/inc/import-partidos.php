<?php
/**
 * Importador CSV de partidos.
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
 * Parsea CSV de partidos validando encabezado obligatorio.
 *
 * @param string $file_path Ruta temporal.
 * @return array<int, array<string, mixed>>|WP_Error
 */
function liga_import_partidos_parse_csv( $file_path ) {
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
			return sanitize_key( liga_import_partidos_normalize_text( $field ) );
		},
		$header
	);

	$expected_header = array( 'division', 'temporada', 'equipo_local', 'equipo_visitante', 'fecha', 'hora', 'recinto', 'estado' );
	if ( $header !== $expected_header ) {
		fclose( $handle );
		return new WP_Error(
			'import_csv_header_invalid',
			__( 'Encabezado invalido. Debe ser exactamente: division,temporada,equipo_local,equipo_visitante,fecha,hora,recinto,estado', 'liga-basket-chile' )
		);
	}

	$rows = array();
	$line = 1;
	while ( ( $data = fgetcsv( $handle, 0, ',' ) ) !== false ) {
		$line++;
		$data = array_pad( $data, 8, '' );

		$row = array(
			'line'             => $line,
			'division'         => isset( $data[0] ) ? (string) $data[0] : '',
			'temporada'        => isset( $data[1] ) ? (string) $data[1] : '',
			'equipo_local'     => isset( $data[2] ) ? (string) $data[2] : '',
			'equipo_visitante' => isset( $data[3] ) ? (string) $data[3] : '',
			'fecha'            => isset( $data[4] ) ? (string) $data[4] : '',
			'hora'             => isset( $data[5] ) ? (string) $data[5] : '',
			'recinto'          => isset( $data[6] ) ? (string) $data[6] : '',
			'estado'           => isset( $data[7] ) ? (string) $data[7] : '',
		);

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

		if ( $is_empty ) {
			continue;
		}

		$rows[] = $row;
	}

	fclose( $handle );

	return $rows;
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
 * Valida filas del CSV de partidos.
 *
 * @param array<int, array<string, mixed>> $rows Filas parseadas.
 * @return array<string, mixed>
 */
function liga_import_partidos_validate_rows( $rows ) {
	$division_lookup = liga_import_partidos_get_division_lookup();
	$team_cache      = array();
	$file_seen       = array();
	$validated_rows  = array();
	$valid_rows      = array();
	$invalid_rows    = array();

	foreach ( $rows as $row ) {
		$line = isset( $row['line'] ) ? absint( $row['line'] ) : 0;

		$division_raw = liga_import_partidos_normalize_text( isset( $row['division'] ) ? $row['division'] : '' );
		$temporada    = liga_import_partidos_normalize_temporada( isset( $row['temporada'] ) ? $row['temporada'] : '' );
		$local_raw    = liga_import_partidos_normalize_text( isset( $row['equipo_local'] ) ? $row['equipo_local'] : '' );
		$visita_raw   = liga_import_partidos_normalize_text( isset( $row['equipo_visitante'] ) ? $row['equipo_visitante'] : '' );
		$fecha        = liga_import_partidos_normalize_text( isset( $row['fecha'] ) ? $row['fecha'] : '' );
		$hora         = liga_import_partidos_normalize_text( isset( $row['hora'] ) ? $row['hora'] : '' );
		$recinto      = liga_import_partidos_normalize_text( isset( $row['recinto'] ) ? $row['recinto'] : '' );
		$estado       = liga_import_partidos_normalize_estado( isset( $row['estado'] ) ? $row['estado'] : '' );

		$errors      = array();
		$division_id = 0;
		$local_id    = 0;
		$visita_id   = 0;

		if ( '' === $division_raw ) {
			$errors[] = __( 'division es obligatoria.', 'liga-basket-chile' );
		} else {
			$division_id = liga_import_partidos_resolve_division_id( $division_raw, $division_lookup );
			if ( $division_id <= 0 ) {
				$errors[] = __( 'division no existe en el sistema.', 'liga-basket-chile' );
			}
		}

		if ( '' === liga_import_partidos_normalize_text( isset( $row['temporada'] ) ? $row['temporada'] : '' ) ) {
			$errors[] = __( 'temporada es obligatoria.', 'liga-basket-chile' );
		} elseif ( '' === $temporada ) {
			$errors[] = __( 'temporada debe tener formato YYYY.', 'liga-basket-chile' );
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

		if ( '' === $fecha ) {
			$errors[] = __( 'fecha es obligatoria.', 'liga-basket-chile' );
		} elseif ( ! liga_import_partidos_is_valid_date( $fecha ) ) {
			$errors[] = __( 'fecha invalida. Usa formato YYYY-MM-DD.', 'liga-basket-chile' );
		}

		if ( '' !== $hora && 1 !== preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora ) ) {
			$errors[] = __( 'hora invalida. Usa formato HH:MM.', 'liga-basket-chile' );
		}

		if ( ! in_array( $estado, liga_import_partidos_allowed_states(), true ) ) {
			$errors[] = __( 'estado invalido para el sistema.', 'liga-basket-chile' );
		}

		if ( in_array( $estado, array( 'jugado', 'finalizado' ), true ) ) {
			$errors[] = __( 'este importador es para programacion. Usa estado programado/suspendido/cancelado.', 'liga-basket-chile' );
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

			if ( liga_import_partidos_exists_in_system( $division_id, $temporada, $local_id, $visita_id, $fecha, $hora ) ) {
				$errors[] = __( 'partido duplicado en el sistema para el mismo contexto/fecha/hora.', 'liga-basket-chile' );
			}
		}

		$division_name = $division_id > 0 && isset( $division_lookup['items'][ $division_id ]['name'] )
			? (string) $division_lookup['items'][ $division_id ]['name']
			: $division_raw;

		$validated_row = array(
			'line'             => $line,
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
 * Inserta partido validado en estructura real del sistema.
 *
 * @param array<string,mixed> $row Fila validada.
 * @return int|WP_Error
 */
function liga_import_partidos_insert_match( $row ) {
	$division_id = isset( $row['division_id'] ) ? absint( $row['division_id'] ) : 0;
	$temporada   = liga_import_partidos_normalize_temporada( isset( $row['temporada'] ) ? $row['temporada'] : '' );
	$local_id    = isset( $row['equipo_local_id'] ) ? absint( $row['equipo_local_id'] ) : 0;
	$visita_id   = isset( $row['equipo_visitante_id'] ) ? absint( $row['equipo_visitante_id'] ) : 0;
	$fecha       = liga_import_partidos_normalize_text( isset( $row['fecha'] ) ? $row['fecha'] : '' );
	$hora        = liga_import_partidos_normalize_text( isset( $row['hora'] ) ? $row['hora'] : '' );
	$recinto     = liga_import_partidos_normalize_text( isset( $row['recinto'] ) ? $row['recinto'] : '' );
	$estado      = liga_import_partidos_normalize_estado( isset( $row['estado'] ) ? $row['estado'] : '' );

	if ( $division_id <= 0 || '' === $temporada || $local_id <= 0 || $visita_id <= 0 || '' === $fecha ) {
		return new WP_Error( 'import_match_invalid', __( 'Fila invalida para insercion.', 'liga-basket-chile' ) );
	}

	$matchup_validation = liga_validate_basketball_matchup( $local_id, $visita_id, $division_id, $temporada );
	if ( is_wp_error( $matchup_validation ) ) {
		return $matchup_validation;
	}

	if ( liga_import_partidos_exists_in_system( $division_id, $temporada, $local_id, $visita_id, $fecha, $hora ) ) {
		return new WP_Error( 'import_match_duplicate', __( 'Partido duplicado detectado antes de guardar.', 'liga-basket-chile' ) );
	}

	$post_title = liga_get_equipo_nombre( $local_id ) . ' vs ' . liga_get_equipo_nombre( $visita_id );
	$post_id    = wp_insert_post(
		array(
			'post_type'   => 'partido',
			'post_status' => 'publish',
			'post_title'  => $post_title,
		),
		true
	);

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
		'liga_estado_partido'  => $estado,
		'liga_puntos_local'    => 0,
		'liga_puntos_visita'   => 0,
		'liga_incomparecencia' => 'ninguna',
		'liga_observaciones'   => '',
	);

	foreach ( $meta_fields as $meta_key => $meta_value ) {
		update_post_meta( $post_id, $meta_key, $meta_value );
	}

	return (int) $post_id;
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

		if ( empty( $_FILES['liga_partidos_csv'] ) || ! is_array( $_FILES['liga_partidos_csv'] ) ) {
			liga_add_admin_alert( 'error', __( 'Debes seleccionar un archivo CSV.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$file       = $_FILES['liga_partidos_csv'];
		$error_code = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error_code ) {
			liga_add_admin_alert( 'error', __( 'No fue posible subir el archivo CSV.', 'liga-basket-chile' ) );
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
			liga_add_admin_alert( 'error', __( 'El archivo CSV esta vacio.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$extension = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( 'csv' !== $extension ) {
			liga_add_admin_alert( 'error', __( 'El archivo debe tener extension .csv.', 'liga-basket-chile' ) );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$rows = liga_import_partidos_parse_csv( $tmp_name );
		if ( is_wp_error( $rows ) ) {
			liga_add_admin_alert( 'error', $rows->get_error_message() );
			wp_safe_redirect( liga_import_partidos_get_page_url() );
			exit;
		}

		$validation = liga_import_partidos_validate_rows( $rows );
		if ( empty( $validation['summary']['total'] ) ) {
			liga_add_admin_alert( 'warning', __( 'No se detectaron filas de datos en el CSV.', 'liga-basket-chile' ) );
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
		$skipped    = 0;
		$failed     = 0;

		foreach ( $valid_rows as $row ) {
			$insert = liga_import_partidos_insert_match( $row );
			if ( is_wp_error( $insert ) ) {
				$error_code = (string) $insert->get_error_code();
				if ( 'import_match_duplicate' === $error_code ) {
					$skipped++;
				} else {
					$failed++;
				}
				continue;
			}

			$created++;
		}

		liga_import_partidos_delete_preview( $token, $user_id );

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
			liga_import_partidos_get_page_url(
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
add_action( 'admin_init', 'liga_handle_admin_import_partidos_actions' );

/**
 * Descarga plantilla oficial de partidos CSV.
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
	header( 'Content-Disposition: attachment; filename=plantilla-partidos-liga.csv' );

	echo "\xEF\xBB\xBF";

	$output = fopen( 'php://output', 'w' );
	if ( false !== $output ) {
		fputcsv( $output, array( 'division', 'temporada', 'equipo_local', 'equipo_visitante', 'fecha', 'hora', 'recinto', 'estado' ) );
		fputcsv( $output, array( 'Primera', '2026', 'Club Deportivo Oriente', 'Leones de Concepcion', '2026-05-10', '20:00', 'Gimnasio Municipal', 'programado' ) );
		fputcsv( $output, array( 'U17', '2026', 'Halcones del Sur', 'Academia Bio Bio', '2026-05-11', '18:00', 'Cancha 2', 'programado' ) );
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
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Importar Partidos', 'liga-basket-chile' ); ?></h1>
		<p><?php esc_html_e( 'Carga masiva de partidos programados integrada al mismo sistema real de fixture, administracion y validacion deportiva.', 'liga-basket-chile' ); ?></p>

		<p>
			<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=liga_download_partidos_csv_template' ), 'liga_download_partidos_csv_template' ) ); ?>">
				<?php esc_html_e( 'Descargar plantilla CSV', 'liga-basket-chile' ); ?>
			</a>
		</p>

		<div class="card">
			<h2><?php esc_html_e( 'Paso 1: Validar archivo', 'liga-basket-chile' ); ?></h2>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'liga_validate_partidos_csv', 'liga_validate_partidos_csv_nonce' ); ?>
				<input type="hidden" name="liga_import_partidos_action" value="validate_csv">
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="liga_partidos_csv"><?php esc_html_e( 'Archivo CSV', 'liga-basket-chile' ); ?></label></th>
						<td>
							<input type="file" id="liga_partidos_csv" name="liga_partidos_csv" accept=".csv,text/csv" required>
							<p class="description"><?php esc_html_e( 'Encabezado exacto requerido: division,temporada,equipo_local,equipo_visitante,fecha,hora,recinto,estado', 'liga-basket-chile' ); ?></p>
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
							<th><?php esc_html_e( 'Division', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Temporada', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Local', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Visitante', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Fecha', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Hora', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Recinto', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Estado', 'liga-basket-chile' ); ?></th>
							<th><?php esc_html_e( 'Resultado', 'liga-basket-chile' ); ?></th>
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
								<td><?php echo esc_html( (string) ( isset( $row['division'] ) ? $row['division'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['temporada'] ) ? $row['temporada'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['equipo_local'] ) ? $row['equipo_local'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['equipo_visitante'] ) ? $row['equipo_visitante'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['fecha'] ) ? $row['fecha'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['hora'] ) ? $row['hora'] : '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( isset( $row['recinto'] ) ? $row['recinto'] : '' ) ); ?></td>
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
