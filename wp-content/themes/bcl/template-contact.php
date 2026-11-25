<?php
/**
 * Template Name: Liên hệ
 */
defined( 'ABSPATH' ) || exit;
get_header();
the_post();
get_template_part( 'template-parts/page-hero', null, [ 'lead' => has_excerpt() ? get_the_excerpt() : '' ] );
$map = bcl_opt( 'map_embed' );
?>
<div class="section">
	<div class="container contact-grid">
		<div>
			<h2 class="h3"><?php echo esc_html( bcl_opt( 'company_name' ) ); ?></h2>
			<div class="prose"><?php the_content(); ?></div>
			<?php bcl_contact_list( 'contact-list--cards' ); ?>
		</div>
		<div class="map">
			<?php if ( $map ) : ?>
				<iframe src="<?php echo esc_url( $map ); ?>" title="<?php esc_attr_e( 'BCL office map', 'bcl' ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
			<?php else : ?>
				<div class="map__placeholder">
					<?php echo bcl_icon( 'pin' ); // phpcs:ignore ?>
					<p><?php esc_html_e( 'The office map will appear here once BCL confirms the official address.', 'bcl' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php
get_footer();
