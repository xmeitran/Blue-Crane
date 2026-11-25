<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'bcl' ); ?></a>

<div class="topbar">
	<div class="container topbar__inner">
		<p class="topbar__tag"><?php echo esc_html( bcl_opt( 'tagline' ) ); ?></p>
		<ul class="topbar__contacts">
			<?php foreach ( bcl_hotlines() as $line ) : ?>
				<li><a href="<?php echo esc_url( bcl_tel_href( $line ), [ 'tel' ] ); ?>" data-track="click_call"><?php echo bcl_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( $line ); ?></a></li>
			<?php endforeach; ?>
			<?php if ( bcl_opt( 'email' ) ) : ?>
				<li><a href="mailto:<?php echo esc_attr( bcl_opt( 'email' ) ); ?>" data-track="click_email"><?php echo bcl_icon( 'mail' ); // phpcs:ignore ?><?php echo esc_html( bcl_opt( 'email' ) ); ?></a></li>
			<?php endif; ?>
		</ul>
		<?php bcl_language_switcher( 'langs--top' ); ?>
	</div>
</div>

<header class="site-header">
	<div class="container site-header__inner">
		<a class="brand" href="<?php echo esc_url( function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' ) ); ?>" rel="home">
			<?php
			$logo_id = (int) get_theme_mod( 'custom_logo' );
			echo $logo_id
				? wp_get_attachment_image( $logo_id, 'full', false, [ 'class' => 'brand__logo', 'alt' => 'BCL – Blue Crane Logistics', 'loading' => 'eager' ] )
				: '<img class="brand__logo" src="' . esc_url( get_theme_file_uri( 'assets/img/logo.png' ) ) . '" width="510" height="241" alt="BCL – Blue Crane Logistics">';
			?>
			<span class="brand__name" aria-hidden="true">Blue Crane<br>Logistics</span>
		</a>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
			<?php echo bcl_icon( 'menu', 'nav-toggle__open' ) . bcl_icon( 'close', 'nav-toggle__close' ); // phpcs:ignore ?>
			<span class="sr-only"><?php esc_html_e( 'Menu', 'bcl' ); ?></span>
		</button>

		<nav id="site-nav" class="site-nav" aria-label="<?php esc_attr_e( 'Main menu', 'bcl' ); ?>">
			<?php
			wp_nav_menu( [
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'menu',
				'depth'          => 2,
				'fallback_cb'    => 'bcl_fallback_menu',
			] );
			?>
			<div class="site-nav__extra">
				<a class="btn btn--accent btn--block" href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>" data-track="click_rfq"><?php esc_html_e( 'Request a quote', 'bcl' ); ?></a>
				<?php bcl_language_switcher( 'langs--nav' ); ?>
			</div>
		</nav>

		<a class="btn btn--accent header-cta" href="<?php echo esc_url( bcl_page_url( 'rfq' ) ); ?>" data-track="click_rfq"><?php esc_html_e( 'Request a quote', 'bcl' ); ?></a>
	</div>
</header>

<main id="main" tabindex="-1">
