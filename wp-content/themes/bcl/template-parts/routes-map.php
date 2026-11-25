<?php
/**
 * Schematic network map (not to scale). Colours come from CSS so it works on light and dark sections.
 */
defined( 'ABSPATH' ) || exit;

$gates = [ // [VN side, KH side, y]
	[ 'Xa Mát', 'Trapeang Phlong', 330 ],
	[ 'Tân Nam', 'Meun Chey', 372 ],
	[ 'Mộc Bài', 'Bavet', 414 ],
	[ 'Bình Hiệp', 'Prey Vor', 456 ],
	[ 'Khánh Bình', 'Chrey Thom', 498 ],
];
$spine = 'M330 64 C 300 150, 392 214, 350 300 S 300 404, 326 450';
?>
<figure class="route-map">
	<svg viewBox="0 0 440 600" role="img" aria-labelledby="route-map-title route-map-desc">
		<title id="route-map-title"><?php esc_html_e( 'BCL route network: China – Vietnam – Cambodia', 'bcl' ); ?></title>
		<desc id="route-map-desc"><?php esc_html_e( 'Schematic: North–South Vietnam corridor, transit from China, five Vietnam–Cambodia border-gate pairs and the Ho Chi Minh City – Phnom Penh waterway.', 'bcl' ); ?></desc>

		<text class="map-country" x="300" y="30" text-anchor="middle"><?php esc_html_e( 'CHINA', 'bcl' ); ?></text>
		<path class="map-border" d="M190 48 H440"/>
		<text class="map-country" x="420" y="250" text-anchor="end"><?php esc_html_e( 'VIETNAM', 'bcl' ); ?></text>
		<text class="map-country" x="20" y="290"><?php esc_html_e( 'CAMBODIA', 'bcl' ); ?></text>
		<path class="map-border" d="M190 300 V560"/>

		<?php foreach ( $gates as [ $vn, $kh, $y ] ) : ?>
			<path class="map-road" d="M326 450 L190 <?php echo (int) $y; ?>"/>
		<?php endforeach; ?>
		<path class="map-road map-road--main" d="M190 414 C 150 450, 110 500, 74 540"/>
		<path class="map-water" d="M326 450 C 330 540, 250 580, 160 572 S 90 556, 74 540"/>
		<path class="map-spine" d="<?php echo esc_attr( $spine ); ?>"/>
		<path class="map-transit" d="<?php echo esc_attr( $spine ); ?>" transform="translate(-9 0)"/>

		<?php foreach ( $gates as [ $vn, $kh, $y ] ) : ?>
			<circle class="map-gate" cx="190" cy="<?php echo (int) $y; ?>" r="6"/>
			<text class="map-label" x="202" y="<?php echo (int) $y - 6; ?>"><?php echo esc_html( $vn ); ?></text>
			<text class="map-label map-label--kh" x="178" y="<?php echo (int) $y - 6; ?>" text-anchor="end"><?php echo esc_html( $kh ); ?></text>
		<?php endforeach; ?>

		<circle class="map-city" cx="330" cy="64" r="8"/>
		<circle class="map-city" cx="337" cy="128" r="8"/>
		<text class="map-label map-label--city" x="322" y="132" text-anchor="end"><?php esc_html_e( 'Hanoi · Hai Phong', 'bcl' ); ?></text>
		<circle class="map-city" cx="326" cy="450" r="9"/>
		<text class="map-label map-label--city" x="342" y="455"><?php esc_html_e( 'HCMC', 'bcl' ); ?></text>
		<text class="map-label" x="342" y="473"><?php esc_html_e( 'seaports', 'bcl' ); ?></text>
		<circle class="map-city" cx="74" cy="540" r="9"/>
		<text class="map-label map-label--city" x="74" y="520" text-anchor="middle">Phnom Penh</text>
	</svg>
	<figcaption>
		<ul class="map-legend">
			<li><span class="map-key map-key--spine"></span><?php esc_html_e( 'North – South road', 'bcl' ); ?></li>
			<li><span class="map-key map-key--transit"></span><?php esc_html_e( 'China – Vietnam – Cambodia transit', 'bcl' ); ?></li>
			<li><span class="map-key map-key--road"></span><?php esc_html_e( 'Border-gate road routes', 'bcl' ); ?></li>
			<li><span class="map-key map-key--water"></span><?php esc_html_e( 'Waterway', 'bcl' ); ?></li>
		</ul>
		<small><?php esc_html_e( 'Schematic, not to scale. Operating status of each route is shown in the route list.', 'bcl' ); ?></small>
	</figcaption>
</figure>
