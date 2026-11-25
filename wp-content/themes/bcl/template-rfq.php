<?php
/**
 * Template Name: Yêu cầu báo giá
 */
defined( 'ABSPATH' ) || exit;
$GLOBALS['bcl_no_cta'] = true;
get_header();
the_post();
get_template_part( 'template-parts/page-hero', null, [ 'lead' => has_excerpt() ? get_the_excerpt() : '' ] );
?>
<div class="section">
	<div class="container with-aside with-aside--form">
		<div>
			<div class="prose prose--intro"><?php the_content(); ?></div>
			<div class="card card--form"><?php echo bcl_rfq_form( 'full' ); // phpcs:ignore -- escaped inside. ?></div>
		</div>
		<aside class="aside">
			<div class="card aside-card">
				<h2><?php esc_html_e( 'What happens next?', 'bcl' ); ?></h2>
				<ol class="mini-steps">
					<li><?php esc_html_e( 'You receive a confirmation email with a reference code.', 'bcl' ); ?></li>
					<li><?php esc_html_e( 'BCL reviews cargo, documents and route conditions.', 'bcl' ); ?></li>
					<li><?php esc_html_e( 'We contact you with a quotation or questions.', 'bcl' ); ?></li>
				</ol>
			</div>
			<div class="card aside-card">
				<h2><?php esc_html_e( 'Prefer to talk?', 'bcl' ); ?></h2>
				<?php bcl_contact_list( 'contact-list--compact' ); ?>
			</div>
		</aside>
	</div>
</div>
<?php
get_footer();
