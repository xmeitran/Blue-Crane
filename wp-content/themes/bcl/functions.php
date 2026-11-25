<?php
/**
 * BCL theme — presentation only. Content types, RFQ, settings and SEO live in the bcl-core plugin.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BCL_CORE_VER' ) ) {
	add_action( 'admin_notices', static fn() => print '<div class="notice notice-error"><p>Theme BCL cần plugin <strong>BCL Core</strong> — vui lòng kích hoạt.</p></div>' );
	if ( ! is_admin() && ! wp_doing_cron() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		add_action( 'template_redirect', static fn() => wp_die( 'Website đang bảo trì (thiếu plugin BCL Core).', 'BCL', 503 ) );
	}
	return;
}

require __DIR__ . '/inc/template-tags.php';

add_action( 'after_setup_theme', static function () {
	load_theme_textdomain( 'bcl', __DIR__ . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
	add_theme_support( 'custom-logo', [ 'height' => 120, 'width' => 260, 'flex-width' => true, 'flex-height' => true ] );
	add_image_size( 'bcl-card', 720, 480, true );
	register_nav_menus( [
		'primary' => 'Menu chính',
		'footer'  => 'Menu chân trang',
	] );
} );

add_action( 'wp_enqueue_scripts', static function () {
	$ver = static fn( $rel ) => (string) filemtime( __DIR__ . '/' . $rel );
	wp_enqueue_style( 'bcl-fonts', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap', [], null );
	wp_enqueue_style( 'bcl', get_stylesheet_uri(), [], $ver( 'style.css' ) );
	wp_enqueue_script( 'bcl', get_theme_file_uri( 'assets/main.js' ), [], $ver( 'assets/main.js' ), [ 'strategy' => 'defer', 'in_footer' => true ] );
	wp_dequeue_style( 'classic-theme-styles' );
} );

add_filter( 'wp_resource_hints', static function ( $urls, $type ) {
	if ( 'preconnect' === $type ) {
		$urls[] = [ 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' ];
		$urls[] = [ 'href' => 'https://images.unsplash.com' ];
	}
	return $urls;
}, 10, 2 );

/* Performance: no emoji script/styles. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

/* Favicon from the logo until a Site Icon is set in Appearance → Customize. */
add_action( 'wp_head', static function () {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( get_theme_file_uri( 'assets/img/icon-512.png' ) ) . '" sizes="512x512">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( get_theme_file_uri( 'assets/img/apple-touch-icon.png' ) ) . '">' . "\n";
	}
	echo '<meta name="theme-color" content="#03256D">' . "\n";
}, 3 );

add_filter( 'body_class', static fn( $c ) => array_merge( $c, [ 'lang-' . bcl_lang() ] ) );
add_filter( 'excerpt_length', static fn() => 26 );
add_filter( 'excerpt_more', static fn() => '…' );

/* Routes listing: show all routes, ordered as in the admin. */
add_action( 'pre_get_posts', static function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( [ 'bcl_route', 'bcl_service' ] ) || $q->is_tax( 'bcl_route_region' ) ) {
		$q->set( 'posts_per_page', 50 );
		$q->set( 'orderby', [ 'menu_order' => 'ASC', 'title' => 'ASC' ] );
	}
	if ( $q->is_home() || $q->is_category() ) {
		$q->set( 'posts_per_page', 9 );
	}
} );
