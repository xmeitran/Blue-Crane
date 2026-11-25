<?php
/**
 * Placeholder photos — Unsplash License (free for commercial use, no attribution required).
 * Shown only while a post has no featured image. Replace with BCL's real photos
 * (trucks, border gates, barges…) by setting the featured image in the admin.
 */

defined( 'ABSPATH' ) || exit;

$u = static fn( string $id, string $alt, string $author, string $page ): array => [
	'src'    => 'https://images.unsplash.com/' . $id,
	'alt'    => $alt,
	'author' => $author,
	'page'   => 'https://unsplash.com/photos/' . $page,
];

return [
	'hero'            => $u( 'photo-1769837496184-32831581c55a', 'Container ship and cranes at a busy port', 'Albert Stoynov', 'oL3wEM0JEwQ' ),
	'svc-customs'     => $u( 'photo-1631557777127-6495c07ba6b9', 'Stack of shipping documents on a desk', '2H Media', 'GJNT3tqQW8I' ),
	'svc-trucking'    => $u( 'photo-1700716465891-9e5e9f501d7d', 'Truck parked in front of shipping containers', 'Bernd Dittrich', 'Xk1IfNnEhRA' ),
	'svc-transit'     => $u( 'photo-1706499856012-14f062c72b49', 'Gantry crane above stacked containers', 'taro ohtani', '5T5zmIqs0AM' ),
	'svc-northsouth'  => $u( 'photo-1766785368863-f2188a8c8b32', 'Two trucks driving on a highway', 'Bhargav Panchal', 'P0bVatS8Jdw' ),
	'svc-barge'       => $u( 'photo-1777295630323-410a79e88e21', 'Barge on a river with cranes in the background', 'Low Angle', 'EFxBNMBAg-4' ),
	'svc-integrated'  => $u( 'photo-1740914994657-f1cdffdc418e', 'Forklift in a large warehouse', 'Kseniia Ilinykh', '82ZiY5pzl1c' ),
	'route-border'    => $u( 'photo-1591419478162-a4dd21b7ec0a', 'Line of trucks waiting on a road', 'Wolfgang Hasselmann', 'nbRgZltoOck' ),
	'route-port'      => $u( 'photo-1773126378915-793b5c48fb38', 'Containers stacked at a seaport', '雙 film', '1gveplodu04' ),
	'route-highway'   => $u( 'photo-1766608422198-5be9ac0aac9e', 'Truck with trailer on the road', 'Preston A Larimer', 'S4FXynMI_bU' ),
	'route-river'     => $u( 'photo-1782454641755-79534ad09aa8', 'Barge on a green river landscape', 'Alexey Elfimov', 'WtamE44lucU' ),
	'about'           => $u( 'photo-1494412685616-a5d310fbb07d', 'Aerial view of containers in an industrial port', 'CHUTTERSNAP', 'kyCNGGKCvyw' ),
	'post-documents'  => $u( 'photo-1468779036391-52341f60b55d', 'Stack of document folders', 'Beatriz Pérez Moya', 'XN4T2PVUUgk' ),
	'post-transit'    => $u( 'photo-1765206257996-9b4a5d886a2c', 'Container ships and cranes at a port', 'Wolfgang Weiser', 'q5vLlL0a6Kg' ),
	'post-containers' => $u( 'photo-1678182451047-196f22a4143e', 'Containers stacked on top of each other', 'Ali Mkumbwa', 'Annl9CjEaEs' ),
	'post-border'     => $u( 'photo-1741495515999-0567609a236e', 'Trucks parked in a row', 'Sijmen van Hooff', 'kuDtwayYiSA' ),
	'post-company'    => $u( 'photo-1521791136064-7986c2920216', 'Business handshake', 'Cytonn Photography', 'n95VMLxqM2I' ),
	'gallery-1'       => $u( 'photo-1616432043562-3671ea2e5242', 'Freight truck on the road', 'Caleb Ruiter', 'EmEQ6kK_5P0' ),
	'gallery-2'       => $u( 'photo-1645736315000-6f788915923b', 'Forklift moving pallets in a warehouse', 'Bernd Dittrich', 'F2C_mSrb6iM' ),
	'gallery-3'       => $u( 'photo-1606185540834-d6e7483ee1a4', 'Cargo ship on the water', 'william william', 'NndKt2kF1L4' ),
	'gallery-4'       => $u( 'photo-1629881635342-c1272d45d0fa', 'Truck on the road at night', 'jay huang', 'aeouwx28n6M' ),
	'gallery-5'       => $u( 'photo-1601897690942-bcacbad33e55', 'Colourful intermodal containers', 'Paul .T', 'bukjsECgmeU' ),
	'gallery-6'       => $u( 'photo-1494412519320-aa613dfb7738', 'Aerial view of a container yard', 'CHUTTERSNAP', '9cCeS9Sg6nU' ),
];
