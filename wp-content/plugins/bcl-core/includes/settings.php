<?php
/**
 * "Cài đặt BCL": company info, contact channels, RFQ recipients, analytics & anti-spam keys.
 * Stored in one option `bcl_settings`. Fields marked l10n hold one value per language.
 */

defined( 'ABSPATH' ) || exit;

/** @return array<string,array{label:string,type?:string,l10n?:bool,help?:string,section:string}> */
function bcl_settings_fields(): array {
	return [
		'company_name'     => [ 'section' => 'company', 'label' => 'Tên công ty', 'l10n' => true ],
		'tagline'          => [ 'section' => 'company', 'label' => 'Câu khẩu hiệu', 'l10n' => true ],
		'address'          => [ 'section' => 'company', 'label' => 'Địa chỉ', 'l10n' => true, 'type' => 'textarea', 'help' => 'Để trống → website hiển thị “Đang cập nhật”.' ],
		'map_embed'        => [ 'section' => 'company', 'label' => 'Link nhúng Google Maps', 'type' => 'url', 'help' => 'Google Maps → Chia sẻ → Nhúng bản đồ → copy phần src="…". Để trống → hiển thị khung chờ.' ],
		'hotline_1'        => [ 'section' => 'contact', 'label' => 'Hotline 1', 'type' => 'tel' ],
		'hotline_2'        => [ 'section' => 'contact', 'label' => 'Hotline 2', 'type' => 'tel' ],
		'zalo'             => [ 'section' => 'contact', 'label' => 'Số Zalo', 'type' => 'tel', 'help' => 'Để trống → dùng Hotline 1.' ],
		'whatsapp'         => [ 'section' => 'contact', 'label' => 'Số WhatsApp', 'type' => 'tel', 'help' => 'Để trống → ẩn nút WhatsApp.' ],
		'wechat'           => [ 'section' => 'contact', 'label' => 'WeChat ID', 'help' => 'Để trống → ẩn.' ],
		'email'            => [ 'section' => 'contact', 'label' => 'Email công khai', 'type' => 'email', 'help' => 'Hiển thị trên website.' ],
		'rfq_emails'       => [ 'section' => 'rfq', 'label' => 'Email nhận yêu cầu báo giá', 'help' => 'Nhiều email cách nhau bằng dấu phẩy. Để trống → email quản trị.' ],
		'ga4_id'           => [ 'section' => 'tracking', 'label' => 'GA4 Measurement ID', 'help' => 'Dạng G-XXXXXXX. Sự kiện: generate_lead, click_call, click_zalo, click_whatsapp, click_email.' ],
		'gsc_verify'       => [ 'section' => 'tracking', 'label' => 'Mã xác minh Google Search Console', 'help' => 'Chỉ phần content của thẻ meta google-site-verification.' ],
		'turnstile_site'   => [ 'section' => 'tracking', 'label' => 'Cloudflare Turnstile – Site key', 'help' => 'Tùy chọn: bật lớp chống spam thứ 4 cho form RFQ.' ],
		'turnstile_secret' => [ 'section' => 'tracking', 'label' => 'Cloudflare Turnstile – Secret key', 'type' => 'password' ],
	];
}

add_action( 'admin_menu', static function () {
	add_menu_page( 'Cài đặt BCL', 'Cài đặt BCL', 'manage_options', 'bcl-settings', 'bcl_render_settings', 'dashicons-building', 3 );
} );

add_action( 'admin_init', static function () {
	register_setting( 'bcl_settings', 'bcl_settings', [ 'type' => 'array', 'sanitize_callback' => 'bcl_sanitize_settings' ] );
} );

function bcl_sanitize_settings( $input ): array {
	$out = [];
	foreach ( bcl_settings_fields() as $key => $f ) {
		$raw   = $input[ $key ] ?? '';
		$clean = static function ( $v ) use ( $f ) {
			$v = is_string( $v ) ? wp_unslash( $v ) : '';
			return match ( $f['type'] ?? 'text' ) {
				'textarea' => sanitize_textarea_field( $v ),
				'url'      => esc_url_raw( $v, [ 'https' ] ),
				'email'    => sanitize_email( $v ),
				default    => sanitize_text_field( $v ),
			};
		};
		$out[ $key ] = ! empty( $f['l10n'] ) ? array_map( $clean, (array) $raw ) : $clean( $raw );
	}
	if ( $out['map_embed'] && ! str_starts_with( $out['map_embed'], 'https://www.google.com/maps/embed' ) ) {
		add_settings_error( 'bcl_settings', 'map', 'Link bản đồ phải bắt đầu bằng https://www.google.com/maps/embed — đã bỏ qua.' );
		$out['map_embed'] = '';
	}
	if ( $out['ga4_id'] && ! preg_match( '/^G-[A-Z0-9]{4,}$/', $out['ga4_id'] ) ) {
		add_settings_error( 'bcl_settings', 'ga4', 'GA4 Measurement ID không hợp lệ (dạng G-XXXXXXX) — đã bỏ qua.' );
		$out['ga4_id'] = '';
	}
	return $out;
}

function bcl_render_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$opts     = get_option( 'bcl_settings', [] );
	$sections = [
		'company'  => 'Thông tin công ty',
		'contact'  => 'Kênh liên hệ',
		'rfq'      => 'Yêu cầu báo giá',
		'tracking' => 'Đo lường & chống spam',
	];
	$langs = bcl_langs();
	?>
	<div class="wrap">
		<h1>Cài đặt BCL</h1>
		<?php settings_errors( 'bcl_settings' ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'bcl_settings' ); ?>
			<?php foreach ( $sections as $sid => $stitle ) : ?>
				<h2><?php echo esc_html( $stitle ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( bcl_settings_fields() as $key => $f ) : ?>
						<?php if ( $f['section'] !== $sid ) { continue; } ?>
						<tr>
							<th scope="row"><?php echo esc_html( $f['label'] ); ?></th>
							<td>
								<?php foreach ( ! empty( $f['l10n'] ) ? $langs : [ null ] as $lang ) : ?>
									<?php
									$name  = 'bcl_settings[' . $key . ']' . ( $lang ? '[' . $lang . ']' : '' );
									$value = $lang ? ( $opts[ $key ][ $lang ] ?? '' ) : ( $opts[ $key ] ?? '' );
									$id    = 'bcl-' . $key . ( $lang ? '-' . $lang : '' );
									?>
									<p>
										<?php if ( $lang ) : ?>
											<label for="<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( strtoupper( $lang ) ); ?></strong></label><br>
										<?php endif; ?>
										<?php if ( 'textarea' === ( $f['type'] ?? '' ) ) : ?>
											<textarea class="large-text" rows="2" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
										<?php else : ?>
											<input class="regular-text" type="<?php echo esc_attr( $f['type'] ?? 'text' ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" autocomplete="off">
										<?php endif; ?>
									</p>
								<?php endforeach; ?>
								<?php if ( ! empty( $f['help'] ) ) : ?>
									<p class="description"><?php echo esc_html( $f['help'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
