<?php
defined( 'ABSPATH' ) || exit;
$p     = $args['post'];
$icon  = get_post_meta( $p->ID, '_bcl_icon', true ) ?: 'package';
$scope = array_slice( array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $p->ID, '_bcl_scope', true ) ) ) ), 0, $args['scope'] ?? 3 );
?>
<article class="card svc-card">
	<span class="svc-icon svc-icon--<?php echo esc_attr( $icon ); ?>"><?php echo bcl_icon( $icon ); // phpcs:ignore ?></span>
	<h3 class="card__title"><a class="stretched" href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( $p->post_title ); ?></a></h3>
	<p class="card__text"><?php echo esc_html( get_the_excerpt( $p ) ); ?></p>
	<?php if ( $scope ) : ?>
		<ul class="tags">
			<?php foreach ( $scope as $item ) : ?>
				<li><?php echo esc_html( $item ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<span class="card__more" aria-hidden="true"><?php esc_html_e( 'View details', 'bcl' ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></span>
</article>
