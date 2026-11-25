<?php
defined( 'ABSPATH' ) || exit;
$p        = $args['post'];
$region   = get_the_terms( $p, 'bcl_route_region' )[0] ?? null;
$gate     = get_post_meta( $p->ID, '_bcl_gate', true );
$vehicles = bcl_route_vehicles( $p->ID );
?>
<article class="card route-card">
	<div class="route-card__top">
		<?php echo bcl_status_badge( $p->ID ); // phpcs:ignore ?>
		<?php if ( $region ) : ?>
			<span class="route-card__region"><?php echo esc_html( $region->name ); ?></span>
		<?php endif; ?>
	</div>
	<h3 class="card__title"><a class="stretched" href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( $p->post_title ); ?></a></h3>
	<p class="route-card__path">
		<span><?php echo esc_html( get_post_meta( $p->ID, '_bcl_origin', true ) ); ?></span>
		<?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?>
		<span><?php echo esc_html( get_post_meta( $p->ID, '_bcl_destination', true ) ); ?></span>
	</p>
	<dl class="route-card__meta">
		<?php if ( $gate ) : ?>
			<div><dt><?php esc_html_e( 'Gate / port', 'bcl' ); ?></dt><dd><?php echo esc_html( $gate ); ?></dd></div>
		<?php endif; ?>
		<?php if ( $vehicles ) : ?>
			<div><dt><?php esc_html_e( 'Equipment', 'bcl' ); ?></dt><dd><?php echo esc_html( $vehicles ); ?></dd></div>
		<?php endif; ?>
	</dl>
</article>
