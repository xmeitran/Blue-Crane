<?php
defined( 'ABSPATH' ) || exit;
get_header();
get_template_part( 'template-parts/page-hero', null, [
	'title' => __( 'Page not found', 'bcl' ),
	'lead'  => __( 'The page you are looking for may have moved. Try one of these instead:', 'bcl' ),
] );
?>
<div class="section">
	<div class="container container--narrow">
		<ul class="link-list link-list--big">
			<li><a href="<?php echo esc_url( bcl_page_url( 'home' ) ); ?>"><?php echo esc_html( get_the_title( bcl_page_id( 'home' ) ) ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a></li>
			<li><a href="<?php echo esc_url( get_post_type_archive_link( 'bcl_service' ) ); ?>"><?php echo esc_html( bcl_archive_texts( 'bcl_service' )['title'] ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a></li>
			<li><a href="<?php echo esc_url( get_post_type_archive_link( 'bcl_route' ) ); ?>"><?php echo esc_html( bcl_archive_texts( 'bcl_route' )['title'] ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a></li>
			<li><a href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>"><?php echo esc_html( get_the_title( bcl_page_id( 'rfq' ) ) ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a></li>
		</ul>
	</div>
</div>
<?php
get_footer();
