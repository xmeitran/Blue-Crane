<?php
defined( 'ABSPATH' ) || exit;
get_header();
the_post();
$cat     = get_the_category()[0] ?? null;
$related = $cat ? get_posts( [ 'numberposts' => 3, 'category' => $cat->term_id, 'exclude' => [ get_the_ID() ], 'lang' => bcl_lang() ] ) : [];
get_template_part( 'template-parts/page-hero', null, [
	'kicker' => $cat ? $cat->name : '',
	'extra'  => '<p class="page-hero__meta"><time datetime="' . esc_attr( get_the_date( 'c' ) ) . '">' . esc_html( get_the_date() ) . '</time></p>',
] );
?>
<div class="section">
	<article class="container container--narrow">
		<figure class="article-media"><?php echo bcl_img( bcl_post_image( null, 'large' ), '(min-width: 800px) 760px, 100vw', [ 'loading' => 'eager' ] ); // phpcs:ignore ?></figure>
		<?php if ( has_excerpt() ) : ?>
			<p class="article-lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
		<?php endif; ?>
		<div class="prose"><?php the_content(); ?></div>
		<aside class="article-cta card">
			<div>
				<h2 class="h4"><?php esc_html_e( 'Have a shipment like this?', 'bcl' ); ?></h2>
				<p><?php esc_html_e( 'BCL can review your documents and route and send you a quotation.', 'bcl' ); ?></p>
			</div>
			<a class="btn btn--accent" href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>" data-track="click_rfq"><?php esc_html_e( 'Request a quote', 'bcl' ); ?></a>
		</aside>
	</article>
</div>
<?php if ( $related ) : ?>
	<section class="section section--tint" aria-labelledby="related-posts">
		<div class="container">
			<?php bcl_section_head( '', __( 'Related articles', 'bcl' ), '', 'related-posts' ); ?>
			<div class="grid grid--3">
				<?php foreach ( $related as $r ) : ?>
					<?php get_template_part( 'template-parts/card-post', null, [ 'post' => $r ] ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
<?php
get_footer();
