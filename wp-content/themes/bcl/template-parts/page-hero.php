<?php
defined( 'ABSPATH' ) || exit;
$a = wp_parse_args( $args ?? [], [ 'title' => get_the_title(), 'lead' => '', 'image' => null, 'kicker' => '', 'extra' => '' ] );
?>
<section class="page-hero<?php echo $a['image'] ? ' page-hero--image' : ''; ?>">
	<?php if ( $a['image'] ) : ?>
		<div class="page-hero__media"><?php echo bcl_img( $a['image'], '100vw', [ 'loading' => 'eager', 'fetchpriority' => 'high' ] ); // phpcs:ignore ?></div>
	<?php endif; ?>
	<div class="container page-hero__inner">
		<?php bcl_breadcrumbs_html(); ?>
		<?php if ( $a['kicker'] ) : ?>
			<p class="eyebrow eyebrow--light"><?php echo esc_html( $a['kicker'] ); ?></p>
		<?php endif; ?>
		<h1><?php echo esc_html( $a['title'] ); ?></h1>
		<?php if ( $a['lead'] ) : ?>
			<p class="page-hero__lead"><?php echo esc_html( $a['lead'] ); ?></p>
		<?php endif; ?>
		<?php echo $a['extra']; // phpcs:ignore -- built from escaped helpers. ?>
	</div>
</section>
