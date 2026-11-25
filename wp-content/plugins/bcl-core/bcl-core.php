<?php
/**
 * Plugin Name: BCL Core
 * Description: Dịch vụ, tuyến vận chuyển, form yêu cầu báo giá (RFQ), thông tin công ty, SEO và bảo mật cho bcl.vn.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: BCL
 * Text Domain: bcl-core
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

const BCL_CORE_VER = '1.0.0';
define( 'BCL_CORE_DIR', __DIR__ . '/' );
define( 'BCL_CORE_URL', plugin_dir_url( __FILE__ ) );

foreach ( [ 'helpers', 'post-types', 'settings', 'meta', 'rfq', 'rfq-admin', 'seo', 'security' ] as $bcl_file ) {
	require BCL_CORE_DIR . "includes/{$bcl_file}.php";
}

add_action( 'init', static function () {
	load_plugin_textdomain( 'bcl-core', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}, 0 );

register_activation_hook( __FILE__, static function () {
	bcl_register_content_types();
	bcl_setup_roles();
	flush_rewrite_rules();
} );
