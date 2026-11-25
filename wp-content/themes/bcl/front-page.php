<?php
/**
 * Home: banner, intro, services, routes, process, gallery, news, partners, RFQ + contact.
 */
defined( 'ABSPATH' ) || exit;

$GLOBALS['bcl_no_cta'] = true;
get_header();

$home     = get_queried_object_id() ?: bcl_page_id( 'home' );
$meta     = static fn( $k, $d = '' ) => get_post_meta( $home, $k, true ) ?: $d;
$hero_img = bcl_post_image( $home, 'full', 'hero' );
$routes   = get_posts( [ 'post_type' => 'bcl_route', 'numberposts' => 6, 'orderby' => 'menu_order', 'order' => 'ASC', 'lang' => bcl_lang() ] );
$news     = get_posts( [ 'post_type' => 'post', 'numberposts' => 3, 'lang' => bcl_lang() ] );
$gallery  = array_filter( array_map( 'intval', explode( ',', (string) $meta( '_bcl_gallery' ) ) ) );
$partners = array_filter( array_map( 'intval', explode( ',', (string) $meta( '_bcl_partners' ) ) ) );
?>

<section class="hero">
	<div class="hero__media"><?php echo bcl_img( $hero_img, '100vw', [ 'loading' => 'eager', 'fetchpriority' => 'high' ] ); // phpcs:ignore ?></div>
	<div class="container hero__inner">
		<p class="hero__kicker"><?php echo esc_html( $meta( '_bcl_hero_kicker', 'Vietnam – Cambodia – China' ) ); ?></p>
		<h1 class="hero__title"><?php echo esc_html( $meta( '_bcl_hero_title', 'International Logistics & Customs Clearance Solutions' ) ); ?></h1>
		<p class="hero__text"><?php echo esc_html( $meta( '_bcl_hero_text' ) ); ?></p>
		<div class="hero__actions">
			<a class="btn btn--accent btn--lg" href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>" data-track="click_rfq"><?php esc_html_e( 'Request a quote', 'bcl' ); ?></a>
			<?php if ( $line = bcl_hotlines()[0] ?? '' ) : ?>
				<a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( bcl_tel_href( $line ), [ 'tel' ] ); ?>" data-track="click_call"><?php echo bcl_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( $line ); ?></a>
			<?php endif; ?>
		</div>
		<p class="hero__slogan">Fly High, Delivery Fast</p>
	</div>
</section>

<section class="facts" aria-label="<?php esc_attr_e( 'BCL at a glance', 'bcl' ); ?>">
	<div class="container">
		<ul class="facts__list">
			<li><strong><?php echo esc_html( number_format_i18n( count( bcl_services() ) ) ); ?></strong><span><?php esc_html_e( 'core logistics services', 'bcl' ); ?></span></li>
			<li><strong>5</strong><span><?php esc_html_e( 'Vietnam – Cambodia border-gate pairs', 'bcl' ); ?></span></li>
			<li><strong>VN · KH · CN</strong><span><?php esc_html_e( 'cross-border corridor', 'bcl' ); ?></span></li>
			<li><strong>VI · EN · 中文</strong><span><?php esc_html_e( 'support languages', 'bcl' ); ?></span></li>
		</ul>
	</div>
</section>

<section class="section intro">
	<div class="container intro__grid">
		<div class="intro__text">
			<p class="eyebrow"><?php esc_html_e( 'About BCL', 'bcl' ); ?></p>
			<h2><?php echo esc_html( bcl_opt( 'company_name' ) ); ?></h2>
			<div class="prose"><?php echo apply_filters( 'the_content', get_post_field( 'post_content', $home ) ); // phpcs:ignore ?></div>
			<ul class="checklist">
				<li><?php echo bcl_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'One contact for customs clearance and transport', 'bcl' ); ?></li>
				<li><?php echo bcl_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Hands-on experience at Vietnam – Cambodia border gates', 'bcl' ); ?></li>
				<li><?php echo bcl_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Transparent quotations, confirmed per shipment', 'bcl' ); ?></li>
			</ul>
			<a class="btn btn--ghost" href="<?php echo esc_url( bcl_page_url( 'about' ) ); ?>"><?php esc_html_e( 'More about us', 'bcl' ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="intro__media">
			<?php echo bcl_img( bcl_post_image( bcl_page_id( 'about' ), 'large', 'about' ), '(min-width: 960px) 520px, 100vw' ); // phpcs:ignore ?>
			<p class="intro__badge"><?php esc_html_e( 'Your Reliable Cross-Border Logistics Partner', 'bcl' ); ?></p>
		</div>
	</div>
</section>

<section class="section section--tint" aria-labelledby="home-services">
	<div class="container">
		<?php bcl_section_head( __( 'Services', 'bcl' ), __( 'Cross-border logistics, end to end', 'bcl' ), __( 'From customs declarations to trucks, containers and barges — one team coordinates every step between Vietnam, Cambodia and China.', 'bcl' ), 'home-services' ); ?>
		<div class="grid grid--3">
			<?php foreach ( bcl_services() as $s ) : ?>
				<?php get_template_part( 'template-parts/card-service', null, [ 'post' => $s ] ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section--dark" aria-labelledby="home-routes">
	<div class="container routes-home">
		<div class="routes-home__map"><?php get_template_part( 'template-parts/routes-map' ); ?></div>
		<div class="routes-home__list">
			<?php bcl_section_head( __( 'Routes', 'bcl' ), __( 'Key transport routes', 'bcl' ), __( 'Each route shows its real operating status. Conditions are confirmed per shipment.', 'bcl' ), 'home-routes' ); ?>
			<ul class="route-rows">
				<?php foreach ( $routes as $r ) : ?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $r ) ); ?>">
							<span class="route-rows__name"><?php echo esc_html( $r->post_title ); ?></span>
							<span class="route-rows__gate"><?php echo esc_html( get_post_meta( $r->ID, '_bcl_gate', true ) ); ?></span>
							<?php echo bcl_status_badge( $r->ID ); // phpcs:ignore ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<a class="btn btn--outline-light" href="<?php echo esc_url( get_post_type_archive_link( 'bcl_route' ) ); ?>"><?php esc_html_e( 'All routes', 'bcl' ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
	</div>
</section>

<section class="section" aria-labelledby="home-process">
	<div class="container">
		<?php bcl_section_head( __( 'How we work', 'bcl' ), __( 'From request to delivery in four steps', 'bcl' ), '', 'home-process' ); ?>
		<ol class="steps">
			<li><h3><?php esc_html_e( 'Send your request', 'bcl' ); ?></h3><p><?php esc_html_e( 'Cargo, route, quantities and documents via the online form, hotline or Zalo.', 'bcl' ); ?></p></li>
			<li><h3><?php esc_html_e( 'Review & quotation', 'bcl' ); ?></h3><p><?php esc_html_e( 'We check HS codes, documents and route conditions, then send a clear quotation.', 'bcl' ); ?></p></li>
			<li><h3><?php esc_html_e( 'Clearance & transport', 'bcl' ); ?></h3><p><?php esc_html_e( 'Customs declaration, border procedures and transport are coordinated by one team.', 'bcl' ); ?></p></li>
			<li><h3><?php esc_html_e( 'Delivery & documents', 'bcl' ); ?></h3><p><?php esc_html_e( 'Cargo is delivered and the full document set is handed over to you.', 'bcl' ); ?></p></li>
		</ol>
	</div>
</section>

<section class="section section--tint" aria-labelledby="home-gallery">
	<div class="container">
		<?php bcl_section_head( __( 'On the ground', 'bcl' ), __( 'Operations gallery', 'bcl' ), '', 'home-gallery' ); ?>
		<ul class="gallery">
			<?php if ( $gallery ) : ?>
				<?php foreach ( array_slice( $gallery, 0, 6 ) as $aid ) : ?>
					<li><?php echo wp_get_attachment_image( $aid, 'large', false, [ 'sizes' => '(min-width: 960px) 400px, 50vw' ] ); ?></li>
				<?php endforeach; ?>
			<?php else : ?>
				<?php for ( $i = 1; $i <= 6; $i++ ) : ?>
					<li><?php echo bcl_img( bcl_placeholder_image( "gallery-$i" ), '(min-width: 960px) 400px, 50vw' ); // phpcs:ignore ?></li>
				<?php endfor; ?>
			<?php endif; ?>
		</ul>
	</div>
</section>

<?php if ( $news ) : ?>
<section class="section" aria-labelledby="home-news">
	<div class="container">
		<div class="section-head section-head--row">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'News & Insights', 'bcl' ); ?></p>
				<h2 id="home-news"><?php esc_html_e( 'Customs, border and trade updates', 'bcl' ); ?></h2>
			</div>
			<a class="btn btn--ghost" href="<?php echo esc_url( bcl_page_url( 'news' ) ); ?>"><?php esc_html_e( 'All articles', 'bcl' ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="grid grid--3">
			<?php foreach ( $news as $n ) : ?>
				<?php get_template_part( 'template-parts/card-post', null, [ 'post' => $n ] ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $partners ) : ?>
<section class="section section--tint partners" aria-labelledby="home-partners">
	<div class="container">
		<?php bcl_section_head( '', __( 'Partners', 'bcl' ), '', 'home-partners' ); ?>
		<ul class="partners__list">
			<?php foreach ( $partners as $aid ) : ?>
				<li><?php echo wp_get_attachment_image( $aid, 'medium' ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<section class="section quote" id="quote" aria-labelledby="home-quote">
	<div class="container quote__grid">
		<div class="quote__info">
			<p class="eyebrow eyebrow--light"><?php esc_html_e( 'Get a quotation', 'bcl' ); ?></p>
			<h2 id="home-quote"><?php esc_html_e( 'Tell us about your shipment', 'bcl' ); ?></h2>
			<p><?php esc_html_e( 'Quick request below, or use the detailed form to attach Invoice and Packing List.', 'bcl' ); ?></p>
			<?php bcl_contact_list( 'contact-list--light' ); ?>
			<a class="link-light" href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>"><?php esc_html_e( 'Detailed form with attachments', 'bcl' ); ?> <?php echo bcl_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="quote__form card">
			<?php echo bcl_rfq_form( 'quick' ); // phpcs:ignore -- escaped inside. ?>
		</div>
	</div>
</section>

<?php
get_footer();
