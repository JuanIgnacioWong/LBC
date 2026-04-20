<?php
/**
 * Endurecimiento base de seguridad.
 *
 * @package LigaBasketChile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Oculta version de WordPress en frontend.
 *
 * @return string
 */
function liga_hide_wp_version() {
	return '';
}
add_filter( 'the_generator', 'liga_hide_wp_version' );

/**
 * Desactiva XML-RPC.
 *
 * @return bool
 */
function liga_disable_xmlrpc() {
	return false;
}
add_filter( 'xmlrpc_enabled', 'liga_disable_xmlrpc' );

/**
 * Elimina query arg de version en scripts y estilos.
 *
 * @param string $src URL del asset.
 * @return string
 */
function liga_remove_asset_version( $src ) {
	$parts = wp_parse_url( $src );
	if ( ! isset( $parts['query'] ) ) {
		return $src;
	}

	parse_str( $parts['query'], $query_args );
	if ( isset( $query_args['ver'] ) ) {
		unset( $query_args['ver'] );
		$src = remove_query_arg( 'ver', $src );
		if ( ! empty( $query_args ) ) {
			$src = add_query_arg( $query_args, $src );
		}
	}

	return $src;
}
add_filter( 'script_loader_src', 'liga_remove_asset_version', 20 );
add_filter( 'style_loader_src', 'liga_remove_asset_version', 20 );

/**
 * Agrega cabeceras de seguridad en frontend y admin.
 *
 * @return void
 */
function liga_send_security_headers() {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
}
add_action( 'send_headers', 'liga_send_security_headers' );

/**
 * Verifica nonce con respuesta estandarizada para acciones protegidas.
 *
 * @param string $nonce_name Nombre del campo nonce.
 * @param string $action Accion nonce.
 * @return bool
 */
function liga_verify_nonce_or_reject( $nonce_name, $action ) {
	if ( ! isset( $_POST[ $nonce_name ] ) ) {
		return false;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_name ] ) );
	return wp_verify_nonce( $nonce, $action );
}

/**
 * Sanitiza recursivamente estructuras de input.
 *
 * @param mixed $value Valor a sanitizar.
 * @return mixed
 */
function liga_sanitize_recursive( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'liga_sanitize_recursive', $value );
	}

	return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $value;
}
