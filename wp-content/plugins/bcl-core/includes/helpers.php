<?php
/**
 * Shared helpers used by the plugin and the bcl theme.
 */

defined( 'ABSPATH' ) || exit;

/** Current language slug (vi|en|zh…). Falls back to 'vi' when Polylang is off. */
function bcl_lang(): string {
	if ( function_exists( 'pll_current_language' ) ) {
		return pll_current_language() ?: ( pll_default_language() ?: 'vi' );
	}
	return 'vi';
}

/** @return string[] Language slugs in admin order. */
function bcl_langs(): array {
	return function_exists( 'pll_languages_list' ) ? ( pll_languages_list() ?: [ 'vi' ] ) : [ 'vi' ];
}

/** Company settings (Cài đặt BCL). Per-language values are arrays keyed by language. */
function bcl_opt( string $key, string $default = '' ): string {
	$opts = get_option( 'bcl_settings', [] );
	$val  = $opts[ $key ] ?? '';
	if ( is_array( $val ) ) {
		$val = $val[ bcl_lang() ] ?? ( $val['vi'] ?? '' );
	}
	return '' === $val ? $default : (string) $val;
}

function bcl_digits( string $phone ): string {
	return preg_replace( '/\D+/', '', $phone );
}

/** Vietnamese local number → E.164 digits without '+', e.g. 0915713979 → 84915713979. */
function bcl_intl( string $phone ): string {
	$d = bcl_digits( $phone );
	return str_starts_with( $d, '0' ) ? '84' . substr( $d, 1 ) : $d;
}

function bcl_tel_href( string $phone ): string {
	return 'tel:+' . bcl_intl( $phone );
}

/** @return array<int,string> Non-empty hotlines. */
function bcl_hotlines(): array {
	return array_values( array_filter( [ bcl_opt( 'hotline_1' ), bcl_opt( 'hotline_2' ) ] ) );
}

function bcl_zalo_url(): string {
	$n = bcl_opt( 'zalo', bcl_opt( 'hotline_1' ) );
	return $n ? 'https://zalo.me/' . bcl_digits( $n ) : '';
}

function bcl_whatsapp_url(): string {
	$n = bcl_opt( 'whatsapp' );
	return $n ? 'https://wa.me/' . bcl_intl( $n ) : '';
}

/** ID of a key page (home, about, news, rfq, contact, privacy) in the current language. */
function bcl_page_id( string $key ): int {
	$id = (int) ( get_option( 'bcl_pages', [] )[ $key ] ?? 0 );
	if ( $id && function_exists( 'pll_get_post' ) ) {
		$id = (int) ( pll_get_post( $id, bcl_lang() ) ?: $id );
	}
	return $id;
}

function bcl_page_url( string $key, array $args = [] ): string {
	$id  = bcl_page_id( $key );
	$url = $id ? get_permalink( $id ) : home_url( '/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

/** @return array<string,array{label:string,tone:string}> */
function bcl_route_statuses(): array {
	return [
		'active'     => [ 'label' => __( 'In operation', 'bcl-core' ), 'tone' => 'ok' ],
		'on_request' => [ 'label' => __( 'On request', 'bcl-core' ), 'tone' => 'info' ],
		'trial'      => [ 'label' => __( 'Pilot', 'bcl-core' ), 'tone' => 'warn' ],
		'research'   => [ 'label' => __( 'Under study', 'bcl-core' ), 'tone' => 'muted' ],
	];
}

/** @return array<string,string> */
function bcl_vehicles(): array {
	return [
		'20dc'    => __( '20DC container', 'bcl-core' ),
		'40hc'    => __( '40HC container', 'bcl-core' ),
		'truck'   => __( 'Truck', 'bcl-core' ),
		'flatbed' => __( 'Flatbed trailer', 'bcl-core' ),
		'fence'   => __( 'Side-wall trailer', 'bcl-core' ),
		'reefer'  => __( 'Reefer (on request)', 'bcl-core' ),
		'barge'   => __( 'Barge', 'bcl-core' ),
		'other'   => __( 'Other / not sure', 'bcl-core' ),
	];
}

/**
 * Placeholder photos (Unsplash License) keyed by the `_bcl_image` post meta.
 * A featured image set in the admin always wins — replace these with BCL's own photos.
 *
 * @return array<string,array{src:string,alt:string,author:string,page:string}>
 */
function bcl_image_registry(): array {
	static $map;
	return $map ??= require BCL_CORE_DIR . 'includes/images.php';
}

function bcl_unsplash( string $key, int $w = 1200 ): string {
	$img = bcl_image_registry()[ $key ] ?? null;
	return $img ? $img['src'] . '?auto=format&fit=crop&q=70&w=' . $w : '';
}

/**
 * Image for a post: featured image if set, else its placeholder photo.
 *
 * @return array{src:string,srcset:string,alt:string,id:int}|null
 */
function bcl_post_image( $post = null, string $size = 'large', string $fallback_key = '' ): ?array {
	$post = get_post( $post );
	if ( $post && has_post_thumbnail( $post ) ) {
		$id = (int) get_post_thumbnail_id( $post );
		return [
			'id'     => $id,
			'src'    => (string) wp_get_attachment_image_url( $id, $size ),
			'srcset' => (string) wp_get_attachment_image_srcset( $id, $size ),
			'alt'    => (string) ( get_post_meta( $id, '_wp_attachment_image_alt', true ) ?: get_the_title( $post ) ),
		];
	}
	$key = $post ? ( get_post_meta( $post->ID, '_bcl_image', true ) ?: $fallback_key ) : $fallback_key;
	return bcl_placeholder_image( $key, $post ? get_the_title( $post ) : '' );
}

/** @return array{src:string,srcset:string,alt:string,id:int}|null */
function bcl_placeholder_image( string $key, string $alt = '' ): ?array {
	if ( ! isset( bcl_image_registry()[ $key ] ) ) {
		return null;
	}
	$srcset = implode( ', ', array_map( static fn( $w ) => bcl_unsplash( $key, $w ) . " {$w}w", [ 480, 800, 1200, 1800 ] ) );
	return [
		'id'     => 0,
		'src'    => bcl_unsplash( $key, 1200 ),
		'srcset' => $srcset,
		'alt'    => $alt ?: bcl_image_registry()[ $key ]['alt'],
	];
}

/** Translate a list of post IDs (stored in the default language) to the current language. */
function bcl_translate_ids( array $ids ): array {
	if ( ! function_exists( 'pll_get_post' ) ) {
		return array_map( 'intval', $ids );
	}
	return array_values( array_filter( array_map( static fn( $id ) => (int) pll_get_post( (int) $id, bcl_lang() ), $ids ) ) );
}

/** SMTP from wp-config constants (BCL_SMTP_HOST…). Without them WordPress uses PHP mail(). */
add_action( 'phpmailer_init', static function ( $mailer ) {
	if ( ! defined( 'BCL_SMTP_HOST' ) || ! BCL_SMTP_HOST ) {
		return;
	}
	$mailer->isSMTP();
	$mailer->Host       = BCL_SMTP_HOST;
	$mailer->Port       = defined( 'BCL_SMTP_PORT' ) ? (int) BCL_SMTP_PORT : 25;
	$mailer->SMTPAuth   = defined( 'BCL_SMTP_USER' ) && BCL_SMTP_USER;
	$mailer->Username   = defined( 'BCL_SMTP_USER' ) ? BCL_SMTP_USER : '';
	$mailer->Password   = defined( 'BCL_SMTP_PASS' ) ? BCL_SMTP_PASS : '';
	$mailer->SMTPSecure = defined( 'BCL_SMTP_SECURE' ) ? BCL_SMTP_SECURE : '';
	$mailer->SMTPAutoTLS = (bool) $mailer->SMTPSecure;
} );
