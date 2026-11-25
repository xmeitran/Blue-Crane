<?php
/**
 * Idempotent seed: Polylang languages, company settings, 3-language content, menus.
 * Run: docker compose run --rm cli wp eval-file /seed/seed.php
 * Re-running updates seeded items (matched by `_bcl_seed` meta) and never touches other content.
 */

if ( ! defined( 'WP_CLI' ) || ! function_exists( 'PLL' ) || ! defined( 'BCL_CORE_VER' ) ) {
	echo "Needs WP-CLI with Polylang and BCL Core active.\n";
	return;
}

$admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );
wp_set_current_user( (int) ( $admins[0] ?? 1 ) );

$data  = json_decode( file_get_contents( __DIR__ . '/content.json' ), true, 512, JSON_THROW_ON_ERROR );
$LANGS = [
	'vi' => [ 'name' => 'Tiếng Việt', 'slug' => 'vi', 'locale' => 'vi', 'rtl' => false, 'term_group' => 0, 'flag' => 'vn' ],
	'en' => [ 'name' => 'English', 'slug' => 'en', 'locale' => 'en_US', 'rtl' => false, 'term_group' => 1, 'flag' => 'us' ],
	'zh' => [ 'name' => '中文 (简体)', 'slug' => 'zh', 'locale' => 'zh_CN', 'rtl' => false, 'term_group' => 2, 'flag' => 'cn' ],
];
$log = static fn( string $m ) => WP_CLI::log( $m );

/* ------------------------------------------------------------ 1. Polylang */

$model = PLL()->model;
foreach ( $LANGS as $slug => $args ) {
	if ( $model->get_language( $slug ) ) {
		continue;
	}
	$res = isset( $model->languages ) && method_exists( $model->languages, 'add' ) ? $model->languages->add( $args ) : $model->add_language( $args );
	if ( is_wp_error( $res ) ) {
		WP_CLI::error( "Polylang: cannot add $slug: " . $res->get_error_message() );
	}
	$log( "Language added: $slug" );
}
if ( isset( $model->languages ) && method_exists( $model->languages, 'clean_cache' ) ) {
	$model->languages->clean_cache();
} elseif ( method_exists( $model, 'clean_languages_cache' ) ) {
	$model->clean_languages_cache();
}

$pll_set = static function ( string $key, $value ): void {
	$opts = PLL()->options;
	if ( is_object( $opts ) && method_exists( $opts, 'set' ) ) {
		$opts->set( $key, $value );
		if ( method_exists( $opts, 'save' ) ) {
			$opts->save();
		}
		return;
	}
	$raw         = get_option( 'polylang', [] );
	$raw[ $key ] = $value;
	update_option( 'polylang', $raw );
	PLL()->options = $raw;
};
$pll_set( 'default_lang', 'vi' );
$pll_set( 'hide_default', true );   // VI at /, EN at /en/, ZH at /zh/
$pll_set( 'force_lang', 1 );        // language from the directory
$pll_set( 'rewrite', true );        // no /language/ base
$pll_set( 'browser', false );       // never redirect by browser language (SEO)
$pll_set( 'redirect_lang', true );  // front page URL is /en/, not /en/home/
$pll_set( 'media_support', false );
$pll_set( 'sync', [ 'taxonomies', '_thumbnail_id', 'menu_order', '_wp_page_template', 'post_date' ] );

/* ------------------------------------------------------------ 2. Settings */

$defaults = [
	'company_name'     => [ 'vi' => 'Công ty TNHH Tiếp Vận Sếu Xanh', 'en' => 'Blue Crane Logistics Co., Ltd.', 'zh' => 'Blue Crane Logistics Co., Ltd.' ],
	'tagline'          => [ 'vi' => 'Logistics xuyên biên giới Việt Nam – Campuchia', 'en' => 'Vietnam – Cambodia Cross-Border Logistics', 'zh' => '越南—柬埔寨跨境物流' ],
	'address'          => [ 'vi' => '', 'en' => '', 'zh' => '' ],
	'map_embed'        => '',
	'hotline_1'        => '0915 713 979',
	'hotline_2'        => '0981 485 253',
	'zalo'             => '',
	'whatsapp'         => '0915 713 979', // ponytail: assumed = hotline 1 until BCL confirms (SPEC open question 4)
	'wechat'           => '',
	'email'            => '',
	'rfq_emails'       => '',
	'ga4_id'           => '',
	'gsc_verify'       => '',
	'turnstile_site'   => '',
	'turnstile_secret' => '',
];
update_option( 'bcl_settings', array_merge( $defaults, (array) get_option( 'bcl_settings', [] ) ) );
update_option( 'blogname', 'BCL – Blue Crane Logistics' );
update_option( 'blogdescription', $defaults['tagline']['vi'] );

/* ------------------------------------------------------------ helpers */

/** Simple HTML (h2,h3,p,ul,ol,table) → Gutenberg block markup, so content is editable block by block. */
$to_blocks = static function ( string $html ): string {
	preg_match_all( '#<(h2|h3|p|ul|ol|table)\b[^>]*>.*?</\1>#s', $html, $m, PREG_SET_ORDER );
	if ( trim( preg_replace( '#<(h2|h3|p|ul|ol|table)\b[^>]*>.*?</\1>#s', '', $html ) ) !== '' ) {
		return $html; // unexpected markup: keep as classic content rather than lose text
	}
	$out = [];
	foreach ( $m as [ $el, $tag ] ) {
		$inner = preg_replace( '#^<' . $tag . '\b[^>]*>|</' . $tag . '>$#', '', $el );
		$out[] = match ( $tag ) {
			'h2'    => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">$inner</h2>\n<!-- /wp:heading -->",
			'h3'    => "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">$inner</h3>\n<!-- /wp:heading -->",
			'p'     => "<!-- wp:paragraph -->\n<p>$inner</p>\n<!-- /wp:paragraph -->",
			'table' => "<!-- wp:table {\"hasFixedLayout\":false} -->\n<figure class=\"wp-block-table\"><table>$inner</table></figure>\n<!-- /wp:table -->",
			default => ( 'ol' === $tag ? "<!-- wp:list {\"ordered\":true} -->\n<ol class=\"wp-block-list\">" : "<!-- wp:list -->\n<ul class=\"wp-block-list\">" )
				. preg_replace( '#<li>(.*?)</li>#s', "<!-- wp:list-item -->\n<li>\$1</li>\n<!-- /wp:list-item -->", $inner )
				. "</$tag>\n<!-- /wp:list -->",
		};
	}
	return implode( "\n\n", $out );
};

$find = static function ( string $seed, string $type ): int {
	$ids = get_posts( [ 'post_type' => $type, 'post_status' => 'any', 'meta_key' => '_bcl_seed', 'meta_value' => $seed, 'fields' => 'ids', 'numberposts' => 1, 'lang' => '' ] );
	return (int) ( $ids[0] ?? 0 );
};

/** Upsert one post per language, link translations. @return array<string,int> lang => ID */
$upsert = static function ( string $type, string $key, array $item, array $extra = [] ) use ( $find, $to_blocks, $LANGS ): array {
	$ids = [];
	foreach ( array_keys( $LANGS ) as $lang ) {
		$t    = $item[ $lang ];
		$seed = "$type:$key:$lang";
		$post = array_merge( [
			'ID'           => $find( $seed, $type ),
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $t['title'],
			'post_name'    => $t['slug'],
			'post_excerpt' => $t['excerpt'] ?? '',
			'post_content' => isset( $t['content'] ) ? $to_blocks( $t['content'] ) : '',
			'menu_order'   => (int) ( $item['order'] ?? 0 ),
		], $extra );
		$id = wp_insert_post( wp_slash( $post ), true );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( "$seed: " . $id->get_error_message() );
		}
		pll_set_post_language( $id, $lang );
		update_post_meta( $id, '_bcl_seed', $seed );
		update_post_meta( $id, '_bcl_seo_title', $t['seo_title'] ?? '' );
		update_post_meta( $id, '_bcl_seo_desc', $t['seo_desc'] ?? '' );
		if ( ! empty( $item['image'] ) ) {
			update_post_meta( $id, '_bcl_image', $item['image'] );
		}
		$ids[ $lang ] = $id;
	}
	pll_save_post_translations( $ids );
	return $ids;
};

/** Upsert a term per language. @return array<string,int> */
$upsert_terms = static function ( string $tax, array $item ) use ( $LANGS ): array {
	$ids = [];
	foreach ( array_keys( $LANGS ) as $lang ) {
		$t        = $item[ $lang ];
		$existing = get_term_by( 'slug', $t['slug'], $tax );
		if ( $existing ) {
			wp_update_term( $existing->term_id, $tax, [ 'name' => $t['name'], 'description' => $t['description'] ?? '' ] );
			$id = $existing->term_id;
		} else {
			$res = wp_insert_term( $t['name'], $tax, [ 'slug' => $t['slug'], 'description' => $t['description'] ?? '' ] );
			if ( is_wp_error( $res ) ) {
				WP_CLI::error( "$tax {$t['slug']}: " . $res->get_error_message() );
			}
			$id = $res['term_id'];
		}
		pll_set_term_language( $id, $lang );
		$ids[ $lang ] = (int) $id;
	}
	pll_save_term_translations( $ids );
	return $ids;
};

/* ------------------------------------------------------------ 3. Terms */

$cats = [];
foreach ( $data['categories'] as $key => $item ) {
	$cats[ $key ] = $upsert_terms( 'category', $item );
}
$regions = [];
foreach ( $data['route_regions'] as $key => $item ) {
	$regions[ $key ] = $upsert_terms( 'bcl_route_region', $item );
}
update_option( 'default_category', $cats['company-news']['vi'] );
$log( 'Terms: ' . count( $cats ) . ' categories, ' . count( $regions ) . ' route groups' );

/* ------------------------------------------------------------ 4. Services */

$services = [];
foreach ( $data['services'] as $key => $item ) {
	$services[ $key ] = $upsert( 'bcl_service', $key, $item );
	foreach ( $services[ $key ] as $lang => $id ) {
		update_post_meta( $id, '_bcl_icon', $item['icon'] );
		update_post_meta( $id, '_bcl_scope', implode( "\n", $item[ $lang ]['scope'] ) );
	}
}
$log( 'Services: ' . count( $services ) );

/* ------------------------------------------------------------ 5. Routes */

foreach ( $data['routes'] as $key => $item ) {
	$ids = $upsert( 'bcl_route', $key, $item );
	foreach ( $ids as $lang => $id ) {
		$t = $item[ $lang ];
		update_post_meta( $id, '_bcl_origin', $t['origin'] );
		update_post_meta( $id, '_bcl_destination', $t['destination'] );
		update_post_meta( $id, '_bcl_gate', $t['gate'] );
		update_post_meta( $id, '_bcl_transit', $t['transit_time'] );
		update_post_meta( $id, '_bcl_status', $item['status'] );
		update_post_meta( $id, '_bcl_vehicles', $item['vehicles'] );
		// Related services stored as default-language IDs (strings), translated on display.
		update_post_meta( $id, '_bcl_services', array_map( static fn( $s ) => (string) $services[ $s ]['vi'], $item['services'] ) );
		wp_set_object_terms( $id, [ $regions[ $item['region'] ][ $lang ] ], 'bcl_route_region' );
	}
}
$log( 'Routes: ' . count( $data['routes'] ) );

/* ------------------------------------------------------------ 6. Pages */

$templates = [ 'about' => 'template-about.php', 'rfq' => 'template-rfq.php', 'contact' => 'template-contact.php' ];
$pages     = [];
foreach ( $data['pages'] as $key => $item ) {
	if ( 'about' === $key ) {
		$item['image'] = 'about';
	} elseif ( 'home' === $key ) {
		$item['image'] = 'hero';
	}
	$pages[ $key ] = $upsert( 'page', $key, $item, [ 'page_template' => $templates[ $key ] ?? 'default' ] );
	if ( 'home' === $key ) {
		foreach ( $pages['home'] as $lang => $id ) {
			update_post_meta( $id, '_bcl_hero_kicker', $item[ $lang ]['hero_kicker'] );
			update_post_meta( $id, '_bcl_hero_title', $item[ $lang ]['hero_title'] );
			update_post_meta( $id, '_bcl_hero_text', $item[ $lang ]['hero_text'] );
		}
	}
}
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $pages['home']['vi'] );
update_option( 'page_for_posts', $pages['news']['vi'] );
update_option( 'wp_page_for_privacy_policy', $pages['privacy']['vi'] );
update_option( 'bcl_pages', array_map( static fn( $ids ) => $ids['vi'], $pages ) );
$log( 'Pages: ' . count( $pages ) );

/* ------------------------------------------------------------ 7. Posts */

foreach ( $data['posts'] as $key => $item ) {
	$ids = $upsert( 'post', $key, $item, [ 'post_date' => $item['date'] ] );
	foreach ( $ids as $lang => $id ) {
		wp_set_post_categories( $id, [ $cats[ $item['category'] ][ $lang ] ] );
	}
}
$log( 'Posts: ' . count( $data['posts'] ) );

/* WordPress demo content and empty unseeded categories. */
foreach ( [ 'hello-world' => 'post', 'sample-page' => 'page', 'privacy-policy' => 'page' ] as $slug => $type ) {
	$p = get_page_by_path( $slug, OBJECT, $type );
	if ( $p && ! get_post_meta( $p->ID, '_bcl_seed', true ) ) {
		wp_delete_post( $p->ID, true );
	}
}
$keep = array_merge( ...array_values( array_map( 'array_values', $cats ) ) );
foreach ( get_terms( [ 'taxonomy' => 'category', 'hide_empty' => false, 'lang' => '' ] ) as $term ) {
	if ( ! in_array( $term->term_id, $keep, true ) && 0 === $term->count ) {
		wp_delete_term( $term->term_id, 'category' );
	}
}

/* ------------------------------------------------------------ 8. Menus */

$archive_label = [
	'bcl_service' => [ 'vi' => 'Dịch vụ', 'en' => 'Services', 'zh' => '服务' ],
	'bcl_route'   => [ 'vi' => 'Tuyến vận chuyển', 'en' => 'Routes', 'zh' => '运输线路' ],
];
$nav = [];
foreach ( array_keys( $LANGS ) as $lang ) {
	foreach ( [ 'primary' => 'Menu chính', 'footer' => 'Menu chân trang' ] as $loc => $label ) {
		$name = "$label (" . strtoupper( $lang ) . ')';
		$old  = wp_get_nav_menu_object( $name );
		if ( $old ) {
			wp_delete_nav_menu( $old->term_id );
		}
		$menu = wp_create_nav_menu( $name );
		$add  = static function ( array $args, int $parent = 0 ) use ( $menu ): int {
			return (int) wp_update_nav_menu_item( $menu, 0, array_merge( [ 'menu-item-status' => 'publish', 'menu-item-parent-id' => $parent ], $args ) );
		};
		$page    = static fn( string $k ) => [ 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $pages[ $k ][ $lang ] ];
		$archive = static fn( string $pt ) => [ 'menu-item-type' => 'post_type_archive', 'menu-item-object' => $pt, 'menu-item-title' => $archive_label[ $pt ][ $lang ] ];

		if ( 'primary' === $loc ) {
			$add( $page( 'home' ) );
			$add( $page( 'about' ) );
			$parent = $add( $archive( 'bcl_service' ) );
			foreach ( $services as $ids ) {
				$add( [ 'menu-item-type' => 'post_type', 'menu-item-object' => 'bcl_service', 'menu-item-object-id' => $ids[ $lang ] ], $parent );
			}
			$add( $archive( 'bcl_route' ) );
			$add( $page( 'news' ) );
			$add( $page( 'contact' ) );
		} else {
			$add( $page( 'about' ) );
			$add( $archive( 'bcl_route' ) );
			$add( $page( 'news' ) );
			$add( $page( 'rfq' ) );
			$add( $page( 'contact' ) );
			$add( $page( 'privacy' ) );
		}
		$nav[ $loc ][ $lang ] = $menu;
	}
}
$theme      = get_option( 'stylesheet' );
$nav_option = is_object( PLL()->options ) ? (array) ( PLL()->options->get( 'nav_menus' ) ?: [] ) : (array) ( get_option( 'polylang' )['nav_menus'] ?? [] );
$nav_option[ $theme ] = $nav;
$pll_set( 'nav_menus', $nav_option );
set_theme_mod( 'nav_menu_locations', [ 'primary' => $nav['primary']['vi'], 'footer' => $nav['footer']['vi'] ] );
$log( 'Menus: primary + footer × 3 languages' );

flush_rewrite_rules();
WP_CLI::success( 'Seed complete.' );
