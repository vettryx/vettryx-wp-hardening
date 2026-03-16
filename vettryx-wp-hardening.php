<?php
/**
 * Plugin Name: VETTRYX WP Hardening
 * Plugin URI:  https://github.com/vettryx/vettryx-wp-core
 * Description: Submódulo do VETTRYX WP Core para Defesa em Profundidade. Fecha vetores de ataque (REST API, XML-RPC), aplica anti-fingerprinting e bloqueia enumeração de usuários. Zero UI / By Default.
 * Version:     1.0.0
 * Author:      VETTRYX Tech
 * Author URI:  https://vettryx.com.br
 * License:     Proprietária (Uso Comercial Exclusivo)
 * Vettryx Icon: dashicons-lock
 */

// Segurança: Impede o acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ==============================================================================
 * 1. OCULTAÇÃO DE IDENTIDADE (ANTI-FINGERPRINTING)
 * ==============================================================================
 */

// Remove tags de cabeçalho que identificam o WP (Generator, RSD, WLW)
add_action( 'init', 'vettryx_hardening_remove_fingerprinting' );
function vettryx_hardening_remove_fingerprinting() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
}

// Remove a string de versão (?ver=X.X.X) dos arquivos CSS e JS
add_filter( 'style_loader_src', 'vettryx_hardening_remove_asset_versions', 9999 );
add_filter( 'script_loader_src', 'vettryx_hardening_remove_asset_versions', 9999 );
function vettryx_hardening_remove_asset_versions( $src ) {
	if ( strpos( $src, 'ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}


/**
 * ==============================================================================
 * 2. FECHAMENTO DE VETORES DE ATAQUE (API E RPC)
 * ==============================================================================
 */

// Desativa completamente o XML-RPC
add_filter( 'xmlrpc_enabled', '__return_false' );

// Bloqueia acesso não autenticado aos endpoints de usuários na REST API (/wp/v2/users)
add_filter( 'rest_authentication_errors', 'vettryx_hardening_restrict_rest_users' );
function vettryx_hardening_restrict_rest_users( $result ) {
	if ( ! empty( $result ) ) {
		return $result;
	}

	if ( ! is_user_logged_in() ) {
		$rest_route  = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? $GLOBALS['wp']->query_vars['rest_route'] : '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

		if ( strpos( $rest_route, '/wp/v2/users' ) !== false || strpos( $request_uri, '/wp-json/wp/v2/users' ) !== false ) {
			return new WP_Error( 
				'rest_unauthorized', 
				'Acesso negado. Endpoint protegido pelo VETTRYX WP Core.', 
				[ 'status' => 401 ] 
			);
		}
	}

	return $result;
}


/**
 * ==============================================================================
 * 3. PREVENÇÃO CONTRA ENUMERAÇÃO E INVASÃO
 * ==============================================================================
 */

// Bloqueia a enumeração de autores via redirecionamento (/?author=N)
add_action( 'template_redirect', 'vettryx_hardening_block_author_enumeration' );
function vettryx_hardening_block_author_enumeration() {
	if ( is_author() && isset( $_GET['author'] ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		
		if ( $template = get_query_template( '404' ) ) {
			include( $template );
		}
		exit;
	}
}


/**
 * ==============================================================================
 * 4. HARDENING DO PAINEL ADMINISTRATIVO
 * ==============================================================================
 */

// Desativa o Editor de Temas e Plugins nativo do WordPress
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}
