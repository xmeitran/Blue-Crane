<?php
defined( 'ABSPATH' ) || exit;
get_header();
the_post();
$id       = get_the_ID();
$status   = get_post_meta( $id, '_bcl_status', true ) ?: 'research';
$services = get_posts( [ 'post_type' => 'bcl_service', 'post__in' => bcl_translate_ids( (array) get_post_meta( $id, '_bcl_services', true ) ) ?: [ 0 ], 'orderby' => 'menu_order', 'order' => 'ASC', 'numberposts' => 6, 'lang' => '' ] );
$quote    = bcl_page_url( 'rfq', [ 'route' => $id ] );
get_template_part( 'template-parts/page-hero', null, [
	'lead'  => get_the_excerpt(),
	'image' => bcl_post_image( null, 'full' ),
	'extra' => '<p class="page-hero__actions">' . bcl_status_badge( $id ) . '</p>',
] );
?>
<div class="section">
	<div class="container with-aside">
		<article>
			<?php if ( 'active' !== $status ) : ?>
				<div class="notice notice--<?php echo esc_attr( bcl_route_statuses()[ $status ]['tone'] ?? 'muted' ); ?>" role="note">
					<?php echo bcl_icon( 'info' ); // phpcs:ignore ?>
					<p><strong><?php echo esc_html( bcl_route_statuses()[ $status ]['label'] ?? '' ); ?>.</strong>
					<?php esc_html_e( 'This route is not offered as a regular scheduled service. Availability, vehicles and timing must be confirmed with BCL for each shipment.', 'bcl' ); ?></p>
				</div>
			<?php endif; ?>

			<dl class="spec">
				<div><dt><?php esc_html_e( 'Origin', 'bcl' ); ?></dt><dd><?php echo esc_html( get_post_meta( $id, '_bcl_origin', true ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Destination', 'bcl' ); ?></dt><dd><?php echo esc_html( get_post_meta( $id, '_bcl_destination', true ) ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Gate / port', 'bcl' ); ?></dt><dd><?php echo esc_html( get_post_meta( $id, '_bcl_gate', true ) ?: '—' ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Equipment', 'bcl' ); ?></dt><dd><?php echo esc_html( bcl_route_vehicles( $id ) ?: '—' ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Transit time', 'bcl' ); ?></dt><dd><?php echo esc_html( get_post_meta( $id, '_bcl_transit', true ) ?: '—' ); ?></dd></div>
				<div><dt><?php esc_html_e( 'Status', 'bcl' ); ?></dt><dd><?php echo bcl_status_badge( $id ); // phpcs:ignore ?></dd></div>
			</dl>

			<div class="prose"><?php the_content(); ?></div>
		</article>
		<aside class="aside aside--sticky">
			<div class="card aside-card aside-card--accent">
				<h2><?php esc_html_e( 'Quote for this route', 'bcl' ); ?></h2>
				<p><?php esc_html_e( 'The form will be pre-filled with this route.', 'bcl' ); ?></p>
				<a class="btn btn--accent btn--block" href="<?php echo esc_url( $quote ); ?>" data-track="click_rfq"><?php esc_html_e( 'Request a quote', 'bcl' ); ?></a>
				<?php bcl_contact_list( 'contact-list--compact' ); ?>
			</div>
			<?php if ( $services ) : ?>
				<nav class="card aside-card" aria-labelledby="route-services">
					<h2 id="route-services"><?php esc_html_e( 'Related services', 'bcl' ); ?></h2>
					<ul class="link-list">
						<?php foreach ( $services as $s ) : ?>
							<li><a href="<?php echo esc_url( get_permalink( $s ) ); ?>"><?php echo esc_html( $s->post_title ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>
			<p><a class="btn btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'bcl_route' ) ); ?>"><?php esc_html_e( 'All routes', 'bcl' ); ?></a></p>
		</aside>
	</div>
</div>
<?php
get_footer();
