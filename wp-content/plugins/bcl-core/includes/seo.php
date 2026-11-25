<?php
/**
 * Technical SEO: titles, meta description, Open Graph, JSON-LD, breadcrumbs, GA4, Search Console.
 * hreflang + per-language sitemaps come from Polylang; the XML sitemap is WordPress core (/wp-sitemap.xml).
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'pre_get_document_title', static function ( $title ) {
	if ( is_singular() || is_home() ) {
		$custom = get_post_meta( get_queried_object_id(), '_bcl_seo_title', true );
		if ( $custom ) {
			return $custom;
		}
	}
	return $title;
} );
add_filter( 'document_title_separator', static fn() => '–' );

/** Archive titles/descriptions for CPT listings (used by SEO and the theme). */
function bcl_archive_texts( string $post_type ): array {
	return match ( $post_type ) {
		'bcl_service' => [
			'title' => __( 'Logistics services', 'bcl-core' ),
			'desc'  => __( 'Customs clearance, Vietnam – Cambodia trucking, China – Vietnam – Cambodia transit, North – South transport, barge and integrated logistics by BCL.', 'bcl-core' ),
		],
		'bcl_route'   => [
			'title' => __( 'Transport routes', 'bcl-core' ),
			'desc'  => __( 'Key Vietnam – Cambodia border gates, China – Vietnam – Cambodia transit, North – South and waterway routes, with the real operating status of each route.', 'bcl-core' ),
		],
		default       => [ 'title' => '', 'desc' => '' ],
	};
}

function bcl_meta_description(): string {
	$d = '';
	if ( is_singular() || is_home() ) {
		$id = get_queried_object_id();
		$d  = get_post_meta( $id, '_bcl_seo_desc', true ) ?: ( has_excerpt( $id ) ? get_the_excerpt( $id ) : '' );
		if ( ! $d && is_singular() ) {
			$d = wp_trim_words( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', $id ) ) ), 30, '…' );
		}
	} elseif ( is_post_type_archive( [ 'bcl_service', 'bcl_route' ] ) ) {
		$d = bcl_archive_texts( get_query_var( 'post_type' ) )['desc'];
	} elseif ( is_category() || is_tax() ) {
		$d = term_description() ?: '';
	}
	$d = $d ?: bcl_opt( 'tagline', get_bloginfo( 'description' ) );
	return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $d ) ) );
}

function bcl_og_locale(): string {
	$l = function_exists( 'pll_current_language' ) ? (string) pll_current_language( 'locale' ) : get_locale();
	return str_contains( $l, '_' ) ? $l : ( 'vi' === $l ? 'vi_VN' : $l );
}

function bcl_logo_url(): string {
	$id = (int) get_theme_mod( 'custom_logo' );
	return $id ? (string) wp_get_attachment_image_url( $id, 'full' ) : get_theme_file_uri( 'assets/img/logo.png' );
}

/**
 * Breadcrumb trail for the current request.
 *
 * @return array<int,array{name:string,url:string}> Without the current page's own URL on the last item.
 */
function bcl_breadcrumbs(): array {
	if ( is_front_page() ) {
		return [];
	}
	$trail = [ [ 'name' => __( 'Home', 'bcl-core' ), 'url' => function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ) ] ];
	$news  = static fn() => [ 'name' => get_the_title( bcl_page_id( 'news' ) ), 'url' => bcl_page_url( 'news' ) ];

	if ( is_singular( [ 'bcl_service', 'bcl_route' ] ) ) {
		$pt      = get_post_type();
		$trail[] = [ 'name' => bcl_archive_texts( $pt )['title'], 'url' => (string) get_post_type_archive_link( $pt ) ];
		$trail[] = [ 'name' => get_the_title(), 'url' => '' ];
	} elseif ( is_singular( 'post' ) ) {
		$trail[] = $news();
		$cat     = get_the_category()[0] ?? null;
		if ( $cat ) {
			$trail[] = [ 'name' => $cat->name, 'url' => get_category_link( $cat ) ];
		}
		$trail[] = [ 'name' => get_the_title(), 'url' => '' ];
	} elseif ( is_singular() || is_home() ) {
		$trail[] = [ 'name' => get_the_title( get_queried_object_id() ), 'url' => '' ];
	} elseif ( is_post_type_archive() ) {
		$trail[] = [ 'name' => bcl_archive_texts( get_query_var( 'post_type' ) )['title'], 'url' => '' ];
	} elseif ( is_tax( 'bcl_route_region' ) ) {
		$trail[] = [ 'name' => bcl_archive_texts( 'bcl_route' )['title'], 'url' => (string) get_post_type_archive_link( 'bcl_route' ) ];
		$trail[] = [ 'name' => single_term_title( '', false ), 'url' => '' ];
	} elseif ( is_category() ) {
		$trail[] = $news();
		$trail[] = [ 'name' => single_cat_title( '', false ), 'url' => '' ];
	} elseif ( is_search() ) {
		$trail[] = [ 'name' => __( 'Search', 'bcl-core' ), 'url' => '' ];
	} elseif ( is_404() ) {
		$trail[] = [ 'name' => __( 'Page not found', 'bcl-core' ), 'url' => '' ];
	}
	return $trail;
}

function bcl_schema_graph(): array {
	$home = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
	$org  = [
		'@type'         => 'Organization',
		'@id'           => home_url( '/#organization' ),
		'name'          => 'Blue Crane Logistics Co., Ltd.',
		'alternateName' => [ 'Công ty TNHH Tiếp Vận Sếu Xanh', 'BCL' ],
		'url'           => home_url( '/' ),
		'logo'          => bcl_logo_url(),
		'slogan'        => 'Fly High, Delivery Fast',
		'areaServed'    => [ 'VN', 'KH', 'CN' ],
		'contactPoint'  => array_map( static fn( $p ) => [
			'@type'             => 'ContactPoint',
			'telephone'         => '+' . bcl_intl( $p ),
			'contactType'       => 'customer service',
			'availableLanguage' => [ 'vi', 'en', 'zh' ],
		], bcl_hotlines() ),
	];
	if ( bcl_opt( 'email' ) ) {
		$org['email'] = bcl_opt( 'email' );
	}
	if ( bcl_opt( 'address' ) ) {
		$org['address'] = [ '@type' => 'PostalAddress', 'streetAddress' => bcl_opt( 'address' ), 'addressCountry' => 'VN' ];
	}
	$graph = [ $org, [ '@type' => 'WebSite', '@id' => $home . '#website', 'url' => $home, 'name' => 'BCL – Blue Crane Logistics', 'inLanguage' => str_replace( '_', '-', bcl_og_locale() ), 'publisher' => [ '@id' => $org['@id'] ] ] ];

	if ( is_singular( [ 'bcl_service', 'bcl_route' ] ) ) {
		$graph[] = [
			'@type'       => 'Service',
			'name'        => get_the_title(),
			'description' => bcl_meta_description(),
			'url'         => get_permalink(),
			'serviceType' => is_singular( 'bcl_route' ) ? 'Freight transport route' : 'Logistics',
			'provider'    => [ '@id' => $org['@id'] ],
			'areaServed'  => [ 'VN', 'KH', 'CN' ],
			'image'       => bcl_post_image( null, 'large' )['src'] ?? '',
		];
	} elseif ( is_singular( 'post' ) ) {
		$graph[] = [
			'@type'         => 'BlogPosting',
			'headline'      => get_the_title(),
			'description'   => bcl_meta_description(),
			'datePublished' => get_the_date( 'c' ),
			'dateModified'  => get_the_modified_date( 'c' ),
			'mainEntityOfPage' => get_permalink(),
			'image'         => bcl_post_image( null, 'large' )['src'] ?? '',
			'author'        => [ '@id' => $org['@id'] ],
			'publisher'     => [ '@id' => $org['@id'] ],
			'inLanguage'    => str_replace( '_', '-', bcl_og_locale() ),
		];
	}

	$crumbs = bcl_breadcrumbs();
	if ( $crumbs ) {
		$graph[] = [
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array_map( static fn( $c, $i ) => array_filter( [
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $c['name'],
				'item'     => $c['url'] ?: null,
			] ), $crumbs, array_keys( $crumbs ) ),
		];
	}
	return $graph;
}

add_action( 'wp_head', static function () {
	$desc  = bcl_meta_description();
	$title = wp_get_document_title();
	$url   = is_singular() || is_home() ? get_permalink( get_queried_object_id() ) : home_url( add_query_arg( [] ) );
	$img   = ( is_singular() ? bcl_post_image( null, 'large' ) : null ) ?? bcl_post_image( (int) get_option( 'page_on_front' ), 'large', 'hero' );

	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( bcl_opt( 'gsc_verify' ) ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( bcl_opt( 'gsc_verify' ) ) . '">' . "\n";
	}
	$og = [
		'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
		'og:site_name'   => 'BCL – Blue Crane Logistics',
		'og:title'       => $title,
		'og:description' => $desc,
		'og:url'         => $url,
		'og:locale'      => bcl_og_locale(),
		'og:image'       => $img['src'] ?? '',
	];
	foreach ( array_filter( $og ) as $prop => $content ) {
		echo '<meta property="' . esc_attr( $prop ) . '" content="' . esc_attr( $content ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<script type="application/ld+json">' . wp_json_encode( [ '@context' => 'https://schema.org', '@graph' => bcl_schema_graph() ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";

	$ga = bcl_opt( 'ga4_id' );
	if ( $ga ) {
		echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $ga ) . '"></script>' . "\n";
		echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config'," . wp_json_encode( $ga ) . ");</script>\n";
	}
}, 2 );

add_filter( 'wp_robots', static function ( $robots ) {
	if ( is_search() || is_404() ) {
		$robots['noindex'] = true;
	}
	return $robots;
} );

add_filter( 'wp_sitemaps_add_provider', static fn( $provider, $name ) => 'users' === $name ? false : $provider, 10, 2 );
