<?php
defined( 'ABSPATH' ) || exit;
$p   = $args['post'];
$cat = get_the_category( $p->ID )[0] ?? null;
?>
<article class="card post-card">
	<div class="post-card__media"><?php echo bcl_img( bcl_post_image( $p, 'bcl-card' ), '(min-width: 1000px) 380px, (min-width: 640px) 50vw, 100vw', [ 'width' => 720, 'height' => 480 ] ); // phpcs:ignore ?></div>
	<div class="post-card__body">
		<p class="post-card__meta">
			<?php if ( $cat ) : ?><span><?php echo esc_html( $cat->name ); ?></span> · <?php endif; ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c', $p ) ); ?>"><?php echo esc_html( get_the_date( '', $p ) ); ?></time>
		</p>
		<h3 class="card__title"><a class="stretched" href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></h3>
		<p class="card__text"><?php echo esc_html( get_the_excerpt( $p ) ); ?></p>
	</div>
</article>
