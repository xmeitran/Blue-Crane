<?php
/**
 * News & Insights (posts page) and category archives.
 */
defined( 'ABSPATH' ) || exit;
get_header();
$news_id = bcl_page_id( 'news' );
$current = is_category() ? get_queried_object_id() : 0;
get_template_part( 'template-parts/page-hero', null, [
	'title' => $current ? single_cat_title( '', false ) : get_the_title( $news_id ),
	'lead'  => $current ? wp_strip_all_tags( category_description() ) : get_the_excerpt( $news_id ),
] );
$cats = get_categories( [ 'hide_empty' => true, 'lang' => bcl_lang() ] );
?>
<div class="section">
	<div class="container">
		<nav class="filter" aria-label="<?php esc_attr_e( 'Topics', 'bcl' ); ?>">
			<a href="<?php echo esc_url( bcl_page_url( 'news' ) ); ?>"<?php echo $current ? '' : ' aria-current="page"'; ?>><?php esc_html_e( 'All', 'bcl' ); ?></a>
			<?php foreach ( $cats as $c ) : ?>
				<a href="<?php echo esc_url( get_category_link( $c ) ); ?>"<?php echo $current === $c->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
			<?php endforeach; ?>
		</nav>
		<?php if ( have_posts() ) : ?>
			<div class="grid grid--3">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php get_template_part( 'template-parts/card-post', null, [ 'post' => get_post() ] ); ?>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( [ 'mid_size' => 1, 'prev_text' => '‹', 'next_text' => '›' ] ); ?>
		<?php else : ?>
			<p class="empty"><?php esc_html_e( 'No articles yet in this topic.', 'bcl' ); ?></p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
