<?php
/**
 * Template Name: Giới thiệu
 */
defined( 'ABSPATH' ) || exit;
get_header();
the_post();
get_template_part( 'template-parts/page-hero', null, [
	'lead'   => has_excerpt() ? get_the_excerpt() : '',
	'kicker' => 'Fly High, Delivery Fast',
	'image'  => bcl_post_image( null, 'full', 'about' ),
] );
?>
<div class="section">
	<div class="container with-aside">
		<div class="prose"><?php the_content(); ?></div>
		<aside class="aside">
			<div class="card fact-card">
				<h2 class="fact-card__title"><?php esc_html_e( 'Company information', 'bcl' ); ?></h2>
				<dl>
					<div><dt><?php esc_html_e( 'Vietnamese name', 'bcl' ); ?></dt><dd>CÔNG TY TNHH TIẾP VẬN SẾU XANH</dd></div>
					<div><dt><?php esc_html_e( 'English name', 'bcl' ); ?></dt><dd>BLUE CRANE LOGISTICS CO., LTD.</dd></div>
					<div><dt><?php esc_html_e( 'Short name', 'bcl' ); ?></dt><dd>BCL</dd></div>
					<div><dt>Website</dt><dd>www.bcl.vn</dd></div>
					<div><dt><?php esc_html_e( 'Hotline / Zalo', 'bcl' ); ?></dt><dd><?php echo esc_html( implode( ' · ', bcl_hotlines() ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Address', 'bcl' ); ?></dt><dd><?php echo esc_html( bcl_opt( 'address' ) ?: __( 'Being updated', 'bcl' ) ); ?></dd></div>
				</dl>
				<a class="btn btn--accent btn--block" href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>" data-track="click_rfq"><?php esc_html_e( 'Request a quote', 'bcl' ); ?></a>
			</div>
		</aside>
	</div>
</div>
<section class="section section--tint" aria-labelledby="about-services">
	<div class="container">
		<?php bcl_section_head( __( 'Fields of activity', 'bcl' ), bcl_archive_texts( 'bcl_service' )['title'], '', 'about-services' ); ?>
		<div class="grid grid--3">
			<?php foreach ( bcl_services() as $s ) : ?>
				<?php get_template_part( 'template-parts/card-service', null, [ 'post' => $s, 'scope' => 0 ] ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php
get_footer();
