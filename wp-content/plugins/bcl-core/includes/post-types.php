<?php
/**
 * Content types: Dịch vụ (bcl_service), Tuyến (bcl_route + bcl_route_region), Yêu cầu báo giá (bcl_rfq).
 * Admin labels are Vietnamese literals: the BCL team administers the site in Vietnamese.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'bcl_register_content_types' );

function bcl_register_content_types(): void {
	add_post_type_support( 'page', 'excerpt' ); // page hero lead + SEO description fallback

	register_post_type( 'bcl_service', [
		'labels'        => [
			'name'          => 'Dịch vụ',
			'singular_name' => 'Dịch vụ',
			'add_new_item'  => 'Thêm dịch vụ',
			'edit_item'     => 'Sửa dịch vụ',
			'all_items'     => 'Tất cả dịch vụ',
			'search_items'  => 'Tìm dịch vụ',
		],
		'public'        => true,
		'has_archive'   => 'services',
		'rewrite'       => [ 'slug' => 'services', 'with_front' => false ],
		'menu_icon'     => 'dashicons-portfolio',
		'menu_position' => 20,
		'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ],
		'show_in_rest'  => true,
	] );

	register_post_type( 'bcl_route', [
		'labels'        => [
			'name'          => 'Tuyến vận chuyển',
			'singular_name' => 'Tuyến',
			'add_new_item'  => 'Thêm tuyến',
			'edit_item'     => 'Sửa tuyến',
			'all_items'     => 'Tất cả tuyến',
			'search_items'  => 'Tìm tuyến',
		],
		'public'        => true,
		'has_archive'   => 'routes',
		'rewrite'       => [ 'slug' => 'routes', 'with_front' => false ],
		'menu_icon'     => 'dashicons-location-alt',
		'menu_position' => 21,
		'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ],
		'show_in_rest'  => true,
	] );

	register_taxonomy( 'bcl_route_region', 'bcl_route', [
		'labels'            => [ 'name' => 'Nhóm tuyến', 'singular_name' => 'Nhóm tuyến', 'add_new_item' => 'Thêm nhóm tuyến' ],
		'hierarchical'      => true,
		'public'            => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => [ 'slug' => 'route-region', 'with_front' => false ],
	] );

	register_post_type( 'bcl_rfq', [
		'labels'          => [
			'name'               => 'Yêu cầu báo giá',
			'singular_name'      => 'Yêu cầu báo giá',
			'edit_item'          => 'Chi tiết yêu cầu báo giá',
			'all_items'          => 'Tất cả yêu cầu',
			'search_items'       => 'Tìm yêu cầu',
			'not_found'          => 'Chưa có yêu cầu báo giá nào.',
			'not_found_in_trash' => 'Thùng rác trống.',
		],
		'public'          => false,
		'show_ui'         => true,
		'menu_icon'       => 'dashicons-email-alt',
		'menu_position'   => 22,
		'supports'        => [ 'title' ],
		'capability_type' => [ 'bcl_rfq', 'bcl_rfqs' ],
		'map_meta_cap'    => true,
		'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
	] );
}

/** Services, routes and regions are translatable in Polylang (not optional in its settings). */
add_filter( 'pll_get_post_types', static function ( $types, $is_settings ) {
	if ( ! $is_settings ) {
		$types['bcl_service'] = 'bcl_service';
		$types['bcl_route']   = 'bcl_route';
	}
	unset( $types['bcl_rfq'] );
	return $types;
}, 10, 2 );

add_filter( 'pll_get_taxonomies', static function ( $taxes, $is_settings ) {
	if ( ! $is_settings ) {
		$taxes['bcl_route_region'] = 'bcl_route_region';
	}
	return $taxes;
}, 10, 2 );

/** Language-neutral fields are kept in sync between translations. */
add_filter( 'pll_copy_post_metas', static function ( $keys ) {
	return array_merge( $keys, [ '_bcl_icon', '_bcl_status', '_bcl_vehicles', '_bcl_services', '_bcl_gallery', '_bcl_partners', '_bcl_image' ] );
} );

/** Roles: admins/editors manage RFQs; "Nhân viên kinh doanh" sees only RFQs. */
function bcl_setup_roles(): void {
	$all = [
		'edit_bcl_rfqs', 'edit_others_bcl_rfqs', 'edit_published_bcl_rfqs', 'edit_private_bcl_rfqs',
		'publish_bcl_rfqs', 'read_private_bcl_rfqs',
		'delete_bcl_rfqs', 'delete_others_bcl_rfqs', 'delete_published_bcl_rfqs', 'delete_private_bcl_rfqs',
	];
	foreach ( [ 'administrator', 'editor' ] as $role_name ) {
		$role = get_role( $role_name );
		foreach ( $all as $cap ) {
			$role?->add_cap( $cap );
		}
	}
	$sales = array_fill_keys( [ 'read', 'edit_bcl_rfqs', 'edit_others_bcl_rfqs', 'edit_published_bcl_rfqs', 'read_private_bcl_rfqs' ], true );
	remove_role( 'bcl_sales' );
	add_role( 'bcl_sales', 'Nhân viên kinh doanh (RFQ)', $sales );
	update_option( 'bcl_core_roles', BCL_CORE_VER );
}

add_action( 'init', static function () {
	if ( get_option( 'bcl_core_roles' ) !== BCL_CORE_VER ) {
		bcl_setup_roles();
	}
} );
