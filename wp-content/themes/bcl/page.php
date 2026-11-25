<?php
defined( 'ABSPATH' ) || exit;
get_header();
the_post();
get_template_part( 'template-parts/page-hero', null, [ 'lead' => has_excerpt() ? get_the_excerpt() : '' ] );
?>
<div class="section">
	<div class="container container--narrow prose"><?php the_content(); ?></div>
</div>
<?php
get_footer();
