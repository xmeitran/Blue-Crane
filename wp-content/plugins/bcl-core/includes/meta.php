<?php
/**
 * Meta boxes: service details, route details, home banner/gallery/partners, SEO.
 * One schema, one renderer, one saver.
 */

defined( 'ABSPATH' ) || exit;

function bcl_meta_schema(): array {
	$services = [];
	foreach ( get_posts( [ 'post_type' => 'bcl_service', 'numberposts' => 50, 'orderby' => 'menu_order', 'order' => 'ASC', 'lang' => function_exists( 'pll_default_language' ) ? pll_default_language() : '' ] ) as $s ) {
		$services[ $s->ID ] = $s->post_title;
	}
	return [
		'bcl_service_box' => [
			'title'   => 'Thông tin dịch vụ',
			'screens' => [ 'bcl_service' ],
			'fields'  => [
				'_bcl_icon'  => [ 'label' => 'Biểu tượng', 'type' => 'select', 'options' => [ 'customs' => 'Hải quan', 'truck' => 'Xe tải', 'transit' => 'Container / quá cảnh', 'highway' => 'Đường bộ Bắc – Nam', 'barge' => 'Sà lan / đường thủy', 'package' => 'Trọn gói / XNK' ] ],
				'_bcl_scope' => [ 'label' => 'Phạm vi nổi bật (mỗi dòng một ý, hiển thị trên thẻ dịch vụ)', 'type' => 'textarea' ],
			],
		],
		'bcl_route_box'   => [
			'title'   => 'Thông tin tuyến',
			'screens' => [ 'bcl_route' ],
			'fields'  => [
				'_bcl_status'      => [ 'label' => 'Trạng thái khai thác (bắt buộc ghi đúng)', 'type' => 'select', 'options' => wp_list_pluck( bcl_route_statuses(), 'label' ) ],
				'_bcl_origin'      => [ 'label' => 'Điểm đi', 'type' => 'text' ],
				'_bcl_destination' => [ 'label' => 'Điểm đến', 'type' => 'text' ],
				'_bcl_gate'        => [ 'label' => 'Cửa khẩu / cảng liên quan', 'type' => 'text' ],
				'_bcl_transit'     => [ 'label' => 'Thời gian vận chuyển (ghi chú)', 'type' => 'text' ],
				'_bcl_vehicles'    => [ 'label' => 'Phương tiện', 'type' => 'checkboxes', 'options' => array_diff_key( bcl_vehicles(), [ 'other' => 1 ] ) ],
				'_bcl_services'    => [ 'label' => 'Dịch vụ liên quan', 'type' => 'checkboxes', 'options' => $services ],
			],
		],
		'bcl_home_box'    => [
			'title'   => 'Trang chủ: banner, ảnh hoạt động, đối tác',
			'screens' => [ 'page' ],
			'when'    => 'bcl_is_front_page',
			'fields'  => [
				'_bcl_hero_kicker' => [ 'label' => 'Dòng nhỏ trên tiêu đề banner', 'type' => 'text' ],
				'_bcl_hero_title'  => [ 'label' => 'Tiêu đề banner', 'type' => 'text' ],
				'_bcl_hero_text'   => [ 'label' => 'Mô tả banner', 'type' => 'textarea' ],
				'_bcl_gallery'     => [ 'label' => 'Ảnh hoạt động (để trống → ảnh minh họa)', 'type' => 'media' ],
				'_bcl_partners'    => [ 'label' => 'Logo đối tác (chỉ đăng khi đối tác cho phép; để trống → ẩn mục)', 'type' => 'media' ],
			],
		],
		'bcl_seo_box'     => [
			'title'   => 'SEO',
			'screens' => [ 'page', 'post', 'bcl_service', 'bcl_route' ],
			'fields'  => [
				'_bcl_seo_title' => [ 'label' => 'Tiêu đề SEO (≤ 60 ký tự, để trống → tiêu đề bài)', 'type' => 'text' ],
				'_bcl_seo_desc'  => [ 'label' => 'Mô tả SEO (120–155 ký tự, để trống → tóm tắt)', 'type' => 'textarea' ],
			],
		],
	];
}

function bcl_is_front_page( WP_Post $post ): bool {
	$front = (int) get_option( 'page_on_front' );
	$ids   = function_exists( 'pll_get_post_translations' ) && $front ? pll_get_post_translations( $front ) : [ $front ];
	return in_array( $post->ID, array_map( 'intval', $ids ), true );
}

add_action( 'add_meta_boxes', static function ( $post_type, $post ) {
	foreach ( bcl_meta_schema() as $id => $box ) {
		if ( ! in_array( $post_type, $box['screens'], true ) || ( isset( $box['when'] ) && ! call_user_func( $box['when'], $post ) ) ) {
			continue;
		}
		add_meta_box( $id, $box['title'], 'bcl_render_meta_box', $post_type, 'normal', 'high', $box );
	}
}, 10, 2 );

function bcl_render_meta_box( WP_Post $post, array $mb ): void {
	$box = $mb['args'];
	wp_nonce_field( 'bcl_meta_' . $mb['id'], '_bcl_nonce_' . $mb['id'] );
	echo '<div class="bcl-meta">';
	foreach ( $box['fields'] as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		$id  = 'f' . $key;
		echo '<p class="bcl-meta__row"><label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $f['label'] ) . '</strong></label><br>';
		switch ( $f['type'] ) {
			case 'textarea':
				echo '<textarea class="widefat" rows="3" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( (string) $val ) . '</textarea>';
				break;
			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">';
				foreach ( $f['options'] as $ov => $ol ) {
					echo '<option value="' . esc_attr( $ov ) . '"' . selected( (string) $val, (string) $ov, false ) . '>' . esc_html( $ol ) . '</option>';
				}
				echo '</select>';
				break;
			case 'checkboxes':
				$vals = array_map( 'strval', (array) $val );
				foreach ( $f['options'] as $ov => $ol ) {
					echo '<label style="display:inline-block;margin:0 16px 6px 0"><input type="checkbox" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( $ov ) . '"' . checked( in_array( (string) $ov, $vals, true ), true, false ) . '> ' . esc_html( $ol ) . '</label>';
				}
				break;
			case 'media':
				$ids = array_filter( array_map( 'intval', explode( ',', (string) $val ) ) );
				echo '<span class="bcl-media" data-input="' . esc_attr( $id ) . '">';
				echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( implode( ',', $ids ) ) . '">';
				echo '<span class="bcl-media__list" style="display:flex;flex-wrap:wrap;gap:6px;margin:6px 0">';
				foreach ( $ids as $aid ) {
					echo wp_get_attachment_image( $aid, [ 72, 72 ], false, [ 'style' => 'width:72px;height:72px;object-fit:cover;border-radius:4px' ] );
				}
				echo '</span><button type="button" class="button bcl-media__pick">Chọn ảnh</button> <button type="button" class="button-link bcl-media__clear">Xóa hết</button></span>';
				break;
			default:
				echo '<input class="widefat" type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $val ) . '">';
		}
		echo '</p>';
	}
	echo '</div>';
}

add_action( 'save_post', static function ( $post_id, $post ) {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( bcl_meta_schema() as $id => $box ) {
		$nonce = $_POST[ '_bcl_nonce_' . $id ] ?? '';
		if ( ! in_array( $post->post_type, $box['screens'], true ) || ! wp_verify_nonce( sanitize_key( $nonce ), 'bcl_meta_' . $id ) ) {
			continue;
		}
		foreach ( $box['fields'] as $key => $f ) {
			$raw = wp_unslash( $_POST[ $key ] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per type below.
			$val = match ( $f['type'] ) {
				'textarea'   => sanitize_textarea_field( (string) $raw ),
				'select'     => array_key_exists( (string) $raw, $f['options'] ) ? (string) $raw : (string) array_key_first( $f['options'] ),
				'checkboxes' => array_values( array_intersect( array_map( 'strval', (array) $raw ), array_map( 'strval', array_keys( $f['options'] ) ) ) ),
				'media'      => implode( ',', array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) ) ),
				default      => sanitize_text_field( (string) $raw ),
			};
			update_post_meta( $post_id, $key, $val );
		}
	}
}, 10, 2 );

add_action( 'admin_enqueue_scripts', static function ( $hook ) {
	if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) && 'page' === get_post_type() ) {
		wp_enqueue_media();
		wp_enqueue_script( 'bcl-admin', BCL_CORE_URL . 'assets/admin.js', [], BCL_CORE_VER, true );
	}
} );
