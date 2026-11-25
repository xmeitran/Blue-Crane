<?php
/**
 * RFQ admin: list columns, status filter, detail view, status/notes, secure downloads, CSV export.
 */

defined( 'ABSPATH' ) || exit;

function bcl_rfq_statuses(): array {
	return [ 'new' => 'Mới', 'processing' => 'Đang xử lý', 'quoted' => 'Đã báo giá', 'won' => 'Thành công', 'closed' => 'Đóng' ];
}

/* ---------------- list screen */

add_filter( 'manage_bcl_rfq_posts_columns', static fn() => [
	'cb'          => '<input type="checkbox">',
	'title'       => 'Mã / Khách hàng',
	'bcl_contact' => 'Liên hệ',
	'bcl_route'   => 'Tuyến',
	'bcl_status'  => 'Trạng thái',
	'bcl_lang'    => 'NN',
	'date'        => 'Ngày gửi',
] );

add_action( 'manage_bcl_rfq_posts_custom_column', static function ( $col, $id ) {
	$v = (array) get_post_meta( $id, '_bcl_rfq', true );
	switch ( $col ) {
		case 'bcl_contact':
			echo esc_html( $v['contact_name'] ?? '' ) . '<br><a href="mailto:' . esc_attr( $v['email'] ?? '' ) . '">' . esc_html( $v['email'] ?? '' ) . '</a><br>' . esc_html( $v['phone'] ?? '' );
			break;
		case 'bcl_route':
			echo esc_html( ( $v['origin'] ?? '' ) . ' → ' . ( $v['destination'] ?? '' ) );
			break;
		case 'bcl_status':
			$s = get_post_meta( $id, '_bcl_rfq_status', true ) ?: 'new';
			echo '<span class="bcl-st bcl-st--' . esc_attr( $s ) . '">' . esc_html( bcl_rfq_statuses()[ $s ] ?? $s ) . '</span>';
			break;
		case 'bcl_lang':
			echo esc_html( strtoupper( (string) get_post_meta( $id, '_bcl_rfq_lang', true ) ) );
			break;
	}
}, 10, 2 );

add_filter( 'post_row_actions', static function ( $actions, $post ) {
	if ( 'bcl_rfq' === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
	}
	return $actions;
}, 10, 2 );
add_filter( 'bulk_actions-edit-bcl_rfq', static function ( $actions ) {
	unset( $actions['edit'] );
	return $actions;
} );

add_action( 'restrict_manage_posts', static function ( $post_type ) {
	if ( 'bcl_rfq' !== $post_type ) {
		return;
	}
	$cur = sanitize_key( $_GET['bcl_status'] ?? '' );
	echo '<select name="bcl_status"><option value="">Mọi trạng thái</option>';
	foreach ( bcl_rfq_statuses() as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select>';
} );

add_action( 'pre_get_posts', static function ( WP_Query $q ) {
	if ( ! is_admin() || ! $q->is_main_query() || 'bcl_rfq' !== $q->get( 'post_type' ) ) {
		return;
	}
	$s = sanitize_key( $_GET['bcl_status'] ?? '' );
	if ( $s && isset( bcl_rfq_statuses()[ $s ] ) ) {
		$q->set( 'meta_key', '_bcl_rfq_status' );
		$q->set( 'meta_value', $s );
	}
} );

add_action( 'manage_posts_extra_tablenav', static function ( $which ) {
	if ( 'top' !== $which || 'bcl_rfq' !== get_current_screen()?->post_type ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=bcl_rfq_export' ), 'bcl_rfq_export' );
	echo '<div class="alignleft actions"><a class="button" href="' . esc_url( $url ) . '">Xuất CSV</a></div>';
} );

/* Bubble with the number of new RFQs. */
add_action( 'admin_menu', static function () {
	global $menu;
	$new = (int) ( new WP_Query( [ 'post_type' => 'bcl_rfq', 'meta_key' => '_bcl_rfq_status', 'meta_value' => 'new', 'fields' => 'ids', 'posts_per_page' => 1, 'no_found_rows' => false ] ) )->found_posts;
	if ( ! $new ) {
		return;
	}
	foreach ( (array) $menu as $i => $item ) {
		if ( 'edit.php?post_type=bcl_rfq' === ( $item[2] ?? '' ) ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $new . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
}, 99 );

add_action( 'admin_head', static function () {
	if ( 'bcl_rfq' !== get_current_screen()?->post_type ) {
		return;
	}
	echo '<style>.bcl-st{display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;background:#eef}.bcl-st--new{background:#fde7c8;color:#7a3e00;font-weight:600}.bcl-st--quoted{background:#dcecff}.bcl-st--won{background:#d6f5dd}.bcl-st--closed{background:#eee;color:#666}.bcl-rfq td,.bcl-rfq th{padding:6px 10px;vertical-align:top;text-align:left}.bcl-rfq th{width:220px;color:#50575e}.page-title-action{display:none}</style>';
} );

/* ---------------- detail screen */

add_action( 'add_meta_boxes_bcl_rfq', static function () {
	add_meta_box( 'bcl_rfq_detail', 'Nội dung yêu cầu', 'bcl_rfq_detail_box', 'bcl_rfq', 'normal', 'high' );
	add_meta_box( 'bcl_rfq_status', 'Xử lý', 'bcl_rfq_status_box', 'bcl_rfq', 'side', 'high' );
} );

function bcl_rfq_detail_box( WP_Post $post ): void {
	$v      = (array) get_post_meta( $post->ID, '_bcl_rfq', true );
	$files  = (array) get_post_meta( $post->ID, '_bcl_rfq_files', true );
	$fields = bcl_rfq_fields();
	echo '<table class="bcl-rfq widefat striped"><tbody>';
	echo '<tr><th>Mã tham chiếu</th><td><strong>' . esc_html( get_post_meta( $post->ID, '_bcl_rfq_ref', true ) ) . '</strong></td></tr>';
	foreach ( $fields as $name => $f ) {
		$val = $v[ $name ] ?? '';
		if ( 'checkboxes' === ( $f['type'] ?? '' ) ) {
			$val = implode( ', ', array_map( static fn( $id ) => get_the_title( (int) $id ), (array) $val ) );
		} elseif ( 'select' === ( $f['type'] ?? '' ) ) {
			$val = bcl_vehicles()[ $val ] ?? $val;
		}
		echo '<tr><th>' . esc_html( $f['label'] ) . '</th><td>' . nl2br( esc_html( (string) $val ) ) . '</td></tr>';
	}
	echo '<tr><th>Tệp đính kèm</th><td>';
	foreach ( $files as $i => $file ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=bcl_rfq_file&id=' . $post->ID . '&f=' . $i ), 'bcl_rfq_file_' . $post->ID );
		echo '<a class="button button-small" href="' . esc_url( $url ) . '">⬇ ' . esc_html( $file['name'] ) . '</a> <span class="description">' . esc_html( size_format( $file['size'] ) ) . '</span><br>';
	}
	echo $files ? '' : '—';
	echo '</td></tr>';
	$consent = (array) get_post_meta( $post->ID, '_bcl_rfq_consent', true );
	echo '<tr><th>Đồng ý xử lý dữ liệu</th><td>' . esc_html( $consent['at'] ?? '—' ) . ' · <a href="' . esc_url( $consent['policy'] ?? '' ) . '" target="_blank">chính sách</a></td></tr>';
	echo '<tr><th>Ngôn ngữ / Trang gửi</th><td>' . esc_html( strtoupper( (string) get_post_meta( $post->ID, '_bcl_rfq_lang', true ) ) ) . ' · ' . esc_html( (string) get_post_meta( $post->ID, '_bcl_rfq_source', true ) ) . '</td></tr>';
	echo '</tbody></table>';
	$email = $v['email'] ?? '';
	if ( $email ) {
		echo '<p><a class="button button-primary" href="mailto:' . esc_attr( $email ) . '?subject=' . rawurlencode( 'BCL – ' . get_post_meta( $post->ID, '_bcl_rfq_ref', true ) ) . '">Trả lời qua email</a></p>';
	}
}

function bcl_rfq_status_box( WP_Post $post ): void {
	wp_nonce_field( 'bcl_rfq_status', '_bcl_rfq_nonce' );
	$cur = get_post_meta( $post->ID, '_bcl_rfq_status', true ) ?: 'new';
	echo '<p><label for="bcl-rfq-status"><strong>Trạng thái</strong></label><br><select id="bcl-rfq-status" name="bcl_rfq_status" class="widefat">';
	foreach ( bcl_rfq_statuses() as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></p><p><label for="bcl-rfq-notes"><strong>Ghi chú nội bộ</strong></label><textarea id="bcl-rfq-notes" name="bcl_rfq_notes" rows="6" class="widefat">' . esc_textarea( (string) get_post_meta( $post->ID, '_bcl_rfq_notes', true ) ) . '</textarea></p>';
}

add_action( 'save_post_bcl_rfq', static function ( $post_id ) {
	if ( ! isset( $_POST['_bcl_rfq_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_bcl_rfq_nonce'] ), 'bcl_rfq_status' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$s = sanitize_key( $_POST['bcl_rfq_status'] ?? '' );
	if ( isset( bcl_rfq_statuses()[ $s ] ) ) {
		update_post_meta( $post_id, '_bcl_rfq_status', $s );
	}
	update_post_meta( $post_id, '_bcl_rfq_notes', sanitize_textarea_field( wp_unslash( $_POST['bcl_rfq_notes'] ?? '' ) ) );
} );

/* ---------------- files */

function bcl_rfq_file_path( int $id, int $index ): string {
	$files = (array) get_post_meta( $id, '_bcl_rfq_files', true );
	if ( empty( $files[ $index ]['path'] ) ) {
		return '';
	}
	$root = realpath( bcl_rfq_dir() );
	$path = realpath( $root . '/' . $files[ $index ]['path'] );
	return ( $root && $path && str_starts_with( $path, $root . DIRECTORY_SEPARATOR ) ) ? $path : '';
}

add_action( 'admin_post_bcl_rfq_file', static function () {
	$id = absint( $_GET['id'] ?? 0 );
	$i  = absint( $_GET['f'] ?? 0 );
	check_admin_referer( 'bcl_rfq_file_' . $id );
	if ( 'bcl_rfq' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Không có quyền tải tệp này.', 403 );
	}
	$path = bcl_rfq_file_path( $id, $i );
	if ( ! $path ) {
		wp_die( 'Không tìm thấy tệp.', 404 );
	}
	$file = ( (array) get_post_meta( $id, '_bcl_rfq_files', true ) )[ $i ];
	nocache_headers();
	header( 'Content-Type: ' . ( $file['type'] ?: 'application/octet-stream' ) );
	header( 'Content-Disposition: attachment; filename="' . rawurlencode( $file['name'] ) . '"; filename*=UTF-8\'\'' . rawurlencode( $file['name'] ) );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
} );

/* Deleting an RFQ permanently also deletes its files (personal data). */
add_action( 'before_delete_post', static function ( $id ) {
	if ( 'bcl_rfq' !== get_post_type( $id ) ) {
		return;
	}
	foreach ( array_keys( (array) get_post_meta( $id, '_bcl_rfq_files', true ) ) as $i ) {
		$path = bcl_rfq_file_path( (int) $id, (int) $i );
		if ( $path ) {
			wp_delete_file( $path );
		}
	}
} );

/* ---------------- CSV export */

add_action( 'admin_post_bcl_rfq_export', static function () {
	check_admin_referer( 'bcl_rfq_export' );
	if ( ! current_user_can( 'edit_bcl_rfqs' ) ) {
		wp_die( 'Không có quyền.', 403 );
	}
	$fields = bcl_rfq_fields();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="bcl-rfq-' . wp_date( 'Ymd-His' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // Excel-friendly UTF-8 BOM.
	fputcsv( $out, array_merge( [ 'Mã', 'Ngày gửi', 'Trạng thái', 'Ngôn ngữ' ], wp_list_pluck( $fields, 'label' ), [ 'Số tệp', 'Ghi chú nội bộ' ] ) );
	$q = new WP_Query( [ 'post_type' => 'bcl_rfq', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true ] );
	foreach ( $q->posts as $p ) {
		$v   = (array) get_post_meta( $p->ID, '_bcl_rfq', true );
		$row = [ get_post_meta( $p->ID, '_bcl_rfq_ref', true ), $p->post_date, bcl_rfq_statuses()[ get_post_meta( $p->ID, '_bcl_rfq_status', true ) ] ?? '', get_post_meta( $p->ID, '_bcl_rfq_lang', true ) ];
		foreach ( $fields as $name => $f ) {
			$val   = $v[ $name ] ?? '';
			$row[] = is_array( $val ) ? implode( ', ', array_map( static fn( $id ) => get_the_title( (int) $id ), $val ) ) : $val;
		}
		$row[] = count( (array) get_post_meta( $p->ID, '_bcl_rfq_files', true ) );
		$row[] = get_post_meta( $p->ID, '_bcl_rfq_notes', true );
		// Neutralise spreadsheet formulas (CSV injection).
		fputcsv( $out, array_map( static fn( $c ) => preg_match( '/^[=+\-@\t\r]/', (string) $c ) ? "'" . $c : $c, $row ) );
	}
	fclose( $out );
	exit;
} );
