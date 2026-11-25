<?php
defined( 'ABSPATH' ) || exit;
get_header();
the_post();
$id     = get_the_ID();
$icon   = get_post_meta( $id, '_bcl_icon', true ) ?: 'package';
$scope  = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $id, '_bcl_scope', true ) ) ) );
$quote  = bcl_page_url( 'rfq', [ 'service' => $id ] );
$routes = get_posts( [
	'post_type'   => 'bcl_route',
	'numberposts' => 4,
	'orderby'     => 'menu_order',
	'order'       => 'ASC',
	'lang'        => bcl_lang(),
	'meta_query'  => [ [ 'key' => '_bcl_services', 'value' => '"' . ( function_exists( 'pll_get_post' ) ? (int) pll_get_post( $id, pll_default_language() ) : $id ) . '"', 'compare' => 'LIKE' ] ],
] );
get_template_part( 'template-parts/page-hero', null, [
	'lead'  => get_the_excerpt(),
	'image' => bcl_post_image( null, 'full' ),
	'extra' => '<p class="page-hero__actions"><a class="btn btn--accent btn--lg" href="' . esc_url( $quote ) . '" data-track="click_rfq">' . esc_html__( 'Request a quote for this service', 'bcl' ) . '</a></p>',
] );
?>
<div class="section">
	<div class="container with-aside">
		<article>
			<?php if ( $scope ) : ?>
				<ul class="scope-grid">
					<?php foreach ( $scope as $item ) : ?>
						<li><?php echo bcl_icon( 'check' ); // phpcs:ignore ?><?php echo esc_html( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="prose"><?php the_content(); ?></div>

			<?php if ( $routes ) : ?>
				<h2 class="h3 related-title"><?php esc_html_e( 'Related routes', 'bcl' ); ?></h2>
				<div class="grid grid--2">
					<?php foreach ( $routes as $r ) : ?>
						<?php get_template_part( 'template-parts/card-route', null, [ 'post' => $r ] ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</article>
		<aside class="aside aside--sticky">
			<div class="card aside-card aside-card--accent">
				<span class="svc-icon svc-icon--<?php echo esc_attr( $icon ); ?>"><?php echo bcl_icon( $icon ); // phpcs:ignore ?></span>
				<h2><?php esc_html_e( 'Need a quotation?', 'bcl' ); ?></h2>
				<p><?php esc_html_e( 'Send cargo details, route and documents. We reply after checking customs and route conditions.', 'bcl' ); ?></p>
				<a class="btn btn--accent btn--block" href="<?php echo esc_url( $quote ); ?>" data-track="click_rfq"><?php esc_html_e( 'Request a quote', 'bcl' ); ?></a>
				<?php bcl_contact_list( 'contact-list--compact' ); ?>
			</div>
			<nav class="card aside-card" aria-labelledby="other-services">
				<h2 id="other-services"><?php esc_html_e( 'Other services', 'bcl' ); ?></h2>
				<ul class="link-list">
					<?php foreach ( bcl_services( -1, [ $id ] ) as $s ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $s ) ); ?>"><?php echo esc_html( $s->post_title ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>
		</aside>
	</div>
</div>
<?php
get_footer();
