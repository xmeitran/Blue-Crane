<?php
defined( 'ABSPATH' ) || exit;
get_header();
$t       = bcl_archive_texts( 'bcl_route' );
$current = is_tax( 'bcl_route_region' ) ? get_queried_object_id() : 0;
$regions = get_terms( [ 'taxonomy' => 'bcl_route_region', 'hide_empty' => true, 'orderby' => 'term_id', 'lang' => bcl_lang() ] );
get_template_part( 'template-parts/page-hero', null, [
	'title' => $current ? single_term_title( '', false ) : $t['title'],
	'lead'  => $current ? wp_strip_all_tags( term_description() ) : $t['desc'],
] );
?>
<div class="section">
	<div class="container">
		<?php if ( ! is_wp_error( $regions ) && $regions ) : ?>
			<nav class="filter" aria-label="<?php esc_attr_e( 'Route groups', 'bcl' ); ?>">
				<a href="<?php echo esc_url( get_post_type_archive_link( 'bcl_route' ) ); ?>"<?php echo $current ? '' : ' aria-current="page"'; ?>><?php esc_html_e( 'All routes', 'bcl' ); ?></a>
				<?php foreach ( $regions as $term ) : ?>
					<a href="<?php echo esc_url( get_term_link( $term ) ); ?>"<?php echo $current === $term->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $term->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<div class="status-legend">
			<?php echo bcl_icon( 'info' ); // phpcs:ignore ?>
			<p><?php esc_html_e( 'Status legend:', 'bcl' ); ?>
				<?php foreach ( bcl_route_statuses() as $s ) : ?>
					<span class="badge badge--<?php echo esc_attr( $s['tone'] ); ?>"><span class="badge__dot" aria-hidden="true"></span><?php echo esc_html( $s['label'] ); ?></span>
				<?php endforeach; ?>
				<br><?php esc_html_e( 'Routes in pilot or under study are not regular services yet — please confirm with BCL before planning.', 'bcl' ); ?>
			</p>
		</div>

		<div class="routes-layout">
			<div class="grid grid--2">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/card-route', null, [ 'post' => get_post() ] ); ?>
				<?php endwhile; ?>
			</div>
			<aside class="routes-layout__map card"><?php get_template_part( 'template-parts/routes-map' ); ?></aside>
		</div>
	</div>
</div>
<?php
get_footer();
