<?php defined( 'ABSPATH' ) || exit; ?>
</main>

<?php if ( empty( $GLOBALS['bcl_no_cta'] ) ) : ?>
	<?php get_template_part( 'template-parts/cta-band' ); ?>
<?php endif; ?>

<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-brand">
			<span class="footer-brand__plate">
				<img src="<?php echo esc_url( bcl_logo_url() ); ?>" width="510" height="241" alt="BCL – Blue Crane Logistics" loading="lazy">
			</span>
			<p class="footer-brand__name"><?php echo esc_html( bcl_opt( 'company_name' ) ); ?></p>
			<p><?php echo esc_html( bcl_opt( 'tagline' ) ); ?></p>
			<p class="footer-brand__slogan">Fly High, Delivery Fast</p>
		</div>

		<nav class="footer-col" aria-labelledby="f-services">
			<h2 id="f-services"><?php echo esc_html( bcl_archive_texts( 'bcl_service' )['title'] ); ?></h2>
			<ul>
				<?php foreach ( bcl_services() as $s ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $s ) ); ?>"><?php echo esc_html( $s->post_title ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<nav class="footer-col" aria-labelledby="f-company">
			<h2 id="f-company"><?php esc_html_e( 'Company', 'bcl' ); ?></h2>
			<?php
			wp_nav_menu( [
				'theme_location' => 'footer',
				'container'      => false,
				'depth'          => 1,
				'fallback_cb'    => static function () {
					echo '<ul>';
					foreach ( [ 'about', 'news', 'rfq', 'contact', 'privacy' ] as $key ) {
						echo '<li><a href="' . esc_url( bcl_page_url( $key ) ) . '">' . esc_html( get_the_title( bcl_page_id( $key ) ) ) . '</a></li>';
					}
					echo '<li><a href="' . esc_url( get_post_type_archive_link( 'bcl_route' ) ) . '">' . esc_html( bcl_archive_texts( 'bcl_route' )['title'] ) . '</a></li>';
					echo '</ul>';
				},
			] );
			?>
		</nav>

		<div class="footer-col">
			<h2><?php esc_html_e( 'Contact', 'bcl' ); ?></h2>
			<?php bcl_contact_list( 'contact-list--footer' ); ?>
		</div>
	</div>

	<div class="container footer-bottom">
		<p>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( bcl_opt( 'company_name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'bcl' ); ?></p>
		<?php bcl_language_switcher( 'langs--footer' ); ?>
	</div>
</footer>

<nav class="quick-contact" aria-label="<?php esc_attr_e( 'Quick contact', 'bcl' ); ?>">
	<?php if ( $bcl_hotline = bcl_hotlines()[0] ?? '' ) : ?>
		<a class="quick-contact__call" href="<?php echo esc_url( bcl_tel_href( $bcl_hotline ), [ 'tel' ] ); ?>" data-track="click_call"><?php echo bcl_icon( 'phone' ); // phpcs:ignore ?><span><?php esc_html_e( 'Call', 'bcl' ); ?></span></a>
	<?php endif; ?>
	<?php if ( bcl_zalo_url() ) : ?>
		<a class="quick-contact__zalo" href="<?php echo esc_url( bcl_zalo_url() ); ?>" target="_blank" rel="noopener" data-track="click_zalo"><?php echo bcl_icon( 'chat' ); // phpcs:ignore ?><span>Zalo</span></a>
	<?php endif; ?>
	<?php if ( bcl_whatsapp_url() ) : ?>
		<a class="quick-contact__wa" href="<?php echo esc_url( bcl_whatsapp_url() ); ?>" target="_blank" rel="noopener" data-track="click_whatsapp"><?php echo bcl_icon( 'whatsapp' ); // phpcs:ignore ?><span>WhatsApp</span></a>
	<?php endif; ?>
	<a class="quick-contact__rfq" href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>" data-track="click_rfq"><?php echo bcl_icon( 'doc' ); // phpcs:ignore ?><span><?php esc_html_e( 'Quote', 'bcl' ); ?></span></a>
</nav>

<?php wp_footer(); ?>
</body>
</html>
