<?php
/**
 * Hardening that belongs in code. Server-level items (HTTPS, backups, WAF, updates) are in docs/HANDOVER.md.
 */

defined( 'ABSPATH' ) || exit;

remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'login_errors', static fn() => 'Thông tin đăng nhập không đúng.' );

add_action( 'send_headers', static function () {
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
} );

/* No user enumeration: /?author=1 and the public users REST endpoint. */
add_action( 'template_redirect', static function () {
	if ( is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
} );

add_filter( 'rest_endpoints', static function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );
