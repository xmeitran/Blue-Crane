<?php
/**
 * Fallback (search results and anything without a dedicated template).
 */
defined( 'ABSPATH' ) || exit;
get_header();
get_template_part( 'template-parts/page-hero', null, [
	'title' => is_search() ? sprintf( __( 'Search: %s', 'bcl' ), get_search_query() ) : wp_strip_all_tags( get_the_archive_title() ),
] );
?>
<div class="section">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="grid grid--3">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/card-post', null, [ 'post' => get_post() ] ); ?>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( [ 'mid_size' => 1, 'prev_text' => '‹', 'next_text' => '›' ] ); ?>
		<?php else : ?>
			<p class="empty"><?php esc_html_e( 'Nothing found.', 'bcl' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
