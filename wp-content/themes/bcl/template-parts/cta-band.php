<?php defined( 'ABSPATH' ) || exit; ?>
<section class="cta-band" aria-labelledby="cta-band-title">
	<div class="container cta-band__inner">
		<div>
			<h2 id="cta-band-title"><?php esc_html_e( 'Planning your next shipment?', 'bcl' ); ?></h2>
			<p><?php esc_html_e( 'Send us the cargo details and documents — BCL will check the route, customs requirements and propose a quotation.', 'bcl' ); ?></p>
		</div>
		<div class="cta-band__actions">
			<a class="btn btn--accent btn--lg" href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>" data-track="click_rfq"><?php esc_html_e( 'Request a quote', 'bcl' ); ?></a>
			<?php if ( $line = bcl_hotlines()[0] ?? '' ) : ?>
				<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( bcl_tel_href( $line ), [ 'tel' ] ); ?>" data-track="click_call"><?php echo bcl_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( $line ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
