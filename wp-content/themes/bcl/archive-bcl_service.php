<?php
defined( 'ABSPATH' ) || exit;
get_header();
$t = bcl_archive_texts( 'bcl_service' );
get_template_part( 'template-parts/page-hero', null, [ 'title' => $t['title'], 'lead' => $t['desc'] ] );
?>
<div class="section">
	<div class="container grid grid--3">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php get_template_part( 'template-parts/card-service', null, [ 'post' => get_post(), 'scope' => 6 ] ); ?>
		<?php endwhile; ?>
	</div>
</div>
<?php
get_footer();
