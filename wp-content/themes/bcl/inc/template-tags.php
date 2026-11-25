<?php
/**
 * Small render helpers shared by templates.
 */

defined( 'ABSPATH' ) || exit;

/** Inline SVG icon (stroke, currentColor). */
function bcl_icon( string $name, string $class = '' ): string {
	static $paths = [
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'chat'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z"/><path d="M8.5 10.5h7M8.5 13.5h4.5"/>',
		'whatsapp' => '<path d="M21 12a9 9 0 0 1-13.2 7.9L3 21l1.2-4.6A9 9 0 1 1 21 12Z"/><path d="M9 8.5c0 3.6 2.9 6.5 6.5 6.5l1-1.6-2.1-1-1 .9a4.5 4.5 0 0 1-2.7-2.7l.9-1-1-2.1Z"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'      => '<path d="M12 21s-7-6.1-7-11a7 7 0 1 1 14 0c0 4.9-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'check'    => '<path d="M20 6 9 17l-5-5"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'chevron'  => '<path d="m6 9 6 6 6-6"/>',
		'doc'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
		'customs'  => '<path d="M12 3 4 6v5c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6Z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
		'truck'    => '<path d="M2 6h11v10H2zM13 9h4l4 4v3h-8"/><circle cx="6" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
		'transit'  => '<path d="M12 2v4M9 6h6"/><rect x="3" y="10" width="18" height="10" rx="1"/><path d="M7 10v10M11 10v10M15 10v10M19 10v10M12 6v4"/>',
		'highway'  => '<path d="M8 3 4 21M16 3l4 18M12 4v2M12 10v3M12 17v3"/>',
		'barge'    => '<path d="M3 14h18l-2.5 4h-13Z"/><path d="M6 14V9h4v5M10 14V7h4v7M14 14v-4h4v4"/><path d="M2 21c1.5 0 1.5-1 3-1s1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1"/>',
		'package'  => '<path d="M3 8 12 3l9 5v8l-9 5-9-5Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
		'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
	];
	return '<svg class="icon ' . esc_attr( $class ) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ( $paths[ $name ] ?? '' ) . '</svg>';
}

/** <img> from bcl_post_image() / bcl_placeholder_image() data. */
function bcl_img( ?array $img, string $sizes = '100vw', array $attr = [] ): string {
	if ( ! $img || ! $img['src'] ) {
		return '';
	}
	$attr = array_merge( [ 'loading' => 'lazy', 'decoding' => 'async' ], $attr );
	$html = '<img src="' . esc_url( $img['src'] ) . '" alt="' . esc_attr( $img['alt'] ) . '"';
	if ( $img['srcset'] ) {
		$html .= ' srcset="' . esc_attr( $img['srcset'] ) . '" sizes="' . esc_attr( $sizes ) . '"';
	}
	foreach ( $attr as $k => $v ) {
		$html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	return $html . '>';
}

function bcl_breadcrumbs_html(): void {
	$trail = bcl_breadcrumbs();
	if ( ! $trail ) {
		return;
	}
	echo '<nav class="crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'bcl' ) . '"><ol>';
	foreach ( $trail as $i => $c ) {
		$last = $i === array_key_last( $trail );
		echo '<li>' . ( $c['url'] && ! $last ? '<a href="' . esc_url( $c['url'] ) . '">' . esc_html( $c['name'] ) . '</a>' : '<span aria-current="page">' . esc_html( $c['name'] ) . '</span>' ) . '</li>';
	}
	echo '</ol></nav>';
}

function bcl_status_badge( int $route_id ): string {
	$key = get_post_meta( $route_id, '_bcl_status', true ) ?: 'research';
	$s   = bcl_route_statuses()[ $key ] ?? bcl_route_statuses()['research'];
	return '<span class="badge badge--' . esc_attr( $s['tone'] ) . '"><span class="badge__dot" aria-hidden="true"></span>' . esc_html( $s['label'] ) . '</span>';
}

function bcl_route_vehicles( int $route_id ): string {
	$labels = bcl_vehicles();
	$keys   = (array) get_post_meta( $route_id, '_bcl_vehicles', true );
	return implode( ' · ', array_filter( array_map( static fn( $k ) => $labels[ $k ] ?? '', $keys ) ) );
}

function bcl_language_switcher( string $class = '' ): void {
	if ( ! function_exists( 'pll_the_languages' ) ) {
		return;
	}
	$names = [ 'vi' => 'VI', 'en' => 'EN', 'zh' => '中文', 'km' => 'ខ្មែរ' ];
	$langs = pll_the_languages( [ 'raw' => 1, 'hide_if_no_translation' => 0, 'hide_if_empty' => 0 ] );
	echo '<nav class="langs ' . esc_attr( $class ) . '" aria-label="' . esc_attr__( 'Language', 'bcl' ) . '"><ul>';
	foreach ( (array) $langs as $l ) {
		$label = $names[ $l['slug'] ] ?? strtoupper( $l['slug'] );
		printf(
			'<li><a href="%1$s" hreflang="%2$s" lang="%2$s" title="%3$s"%4$s>%5$s</a></li>',
			esc_url( $l['url'] ),
			esc_attr( $l['locale'] ? str_replace( '_', '-', $l['locale'] ) : $l['slug'] ),
			esc_attr( $l['name'] ),
			$l['current_lang'] ? ' aria-current="true"' : '',
			esc_html( $label )
		);
	}
	echo '</ul></nav>';
}

/** Section heading with eyebrow. */
function bcl_section_head( string $eyebrow, string $title, string $lead = '', string $id = '' ): void {
	echo '<header class="section-head">';
	if ( $eyebrow ) {
		echo '<p class="eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	echo '<h2' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . '>' . esc_html( $title ) . '</h2>';
	if ( $lead ) {
		echo '<p class="lead">' . esc_html( $lead ) . '</p>';
	}
	echo '</header>';
}

/** Contact channels as a list of buttons (hotlines, Zalo, WhatsApp, email). */
function bcl_contact_list( string $class = '' ): void {
	echo '<ul class="contact-list ' . esc_attr( $class ) . '">';
	foreach ( bcl_hotlines() as $i => $line ) {
		echo '<li><a href="' . esc_url( bcl_tel_href( $line ), [ 'tel' ] ) . '" data-track="click_call">' . bcl_icon( 'phone' ) . '<span><small>' . esc_html( sprintf( __( 'Hotline %d', 'bcl' ), $i + 1 ) ) . '</small>' . esc_html( $line ) . '</span></a></li>';
	}
	if ( bcl_zalo_url() ) {
		echo '<li><a href="' . esc_url( bcl_zalo_url() ) . '" target="_blank" rel="noopener" data-track="click_zalo">' . bcl_icon( 'chat' ) . '<span><small>Zalo</small>' . esc_html( bcl_opt( 'zalo', bcl_opt( 'hotline_1' ) ) ) . '</span></a></li>';
	}
	if ( bcl_whatsapp_url() ) {
		echo '<li><a href="' . esc_url( bcl_whatsapp_url() ) . '" target="_blank" rel="noopener" data-track="click_whatsapp">' . bcl_icon( 'whatsapp' ) . '<span><small>WhatsApp</small>' . esc_html( bcl_opt( 'whatsapp' ) ) . '</span></a></li>';
	}
	if ( bcl_opt( 'wechat' ) ) {
		echo '<li><span class="contact-list__static">' . bcl_icon( 'chat' ) . '<span><small>WeChat</small>' . esc_html( bcl_opt( 'wechat' ) ) . '</span></span></li>';
	}
	$email = bcl_opt( 'email' );
	echo '<li>' . ( $email ? '<a href="mailto:' . esc_attr( $email ) . '" data-track="click_email">' : '<span class="contact-list__static">' ) . bcl_icon( 'mail' ) . '<span><small>Email</small>' . esc_html( $email ?: __( 'Being updated', 'bcl' ) ) . '</span>' . ( $email ? '</a>' : '</span>' ) . '</li>';
	echo '<li><span class="contact-list__static">' . bcl_icon( 'pin' ) . '<span><small>' . esc_html__( 'Address', 'bcl' ) . '</small>' . esc_html( bcl_opt( 'address' ) ?: __( 'Being updated', 'bcl' ) ) . '</span></span></li>';
	echo '</ul>';
}

/** Services in the current language, admin order. */
function bcl_services( int $limit = -1, array $exclude = [] ): array {
	return get_posts( [ 'post_type' => 'bcl_service', 'numberposts' => $limit, 'orderby' => 'menu_order', 'order' => 'ASC', 'exclude' => $exclude, 'lang' => bcl_lang() ] );
}

/** Menu shown until "Menu chính" is assigned in Appearance → Menus. */
function bcl_fallback_menu(): void {
	$items = [
		[ bcl_page_url( 'home' ), get_the_title( bcl_page_id( 'home' ) ) ],
		[ bcl_page_url( 'about' ), get_the_title( bcl_page_id( 'about' ) ) ],
		[ get_post_type_archive_link( 'bcl_service' ), bcl_archive_texts( 'bcl_service' )['title'] ],
		[ get_post_type_archive_link( 'bcl_route' ), bcl_archive_texts( 'bcl_route' )['title'] ],
		[ bcl_page_url( 'news' ), get_the_title( bcl_page_id( 'news' ) ) ],
		[ bcl_page_url( 'contact' ), get_the_title( bcl_page_id( 'contact' ) ) ],
	];
	echo '<ul class="menu">';
	foreach ( $items as [ $url, $label ] ) {
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}
