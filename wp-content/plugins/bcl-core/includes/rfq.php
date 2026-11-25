<?php
/**
 * Request for Quotation (RFQ) form: render, validate, store, notify.
 *
 * Variants: "full" (RFQ page, shortcode [bcl_rfq_form]) and "quick" (home page).
 * Anti-spam: honeypot + signed time token + per-IP rate limit + optional Cloudflare Turnstile.
 * Files: ≤ 5 × 10 MB, extension AND sniffed MIME must be on the allow-list, stored under
 * uploads/bcl-rfq/ (web access denied) with random names; admins download through rfq-admin.php.
 */

defined( 'ABSPATH' ) || exit;

define( 'BCL_RFQ_MAX_FILES', 5 );
define( 'BCL_RFQ_MAX_BYTES', 10 * MB_IN_BYTES );
define( 'BCL_RFQ_RATE_LIMIT', 5 ); // successful submissions per IP per hour
define( 'BCL_RFQ_MIN_SECONDS', 3 );

/** @return array<string,string> extension pattern => mime, WordPress style. */
function bcl_rfq_mimes(): array {
	return [
		'pdf'      => 'application/pdf',
		'jpg|jpeg' => 'image/jpeg',
		'png'      => 'image/png',
		'webp'     => 'image/webp',
		'doc'      => 'application/msword',
		'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xls'      => 'application/vnd.ms-excel',
		'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	];
}

/** @return array<int,string> service ID => title, current language. */
function bcl_rfq_service_options(): array {
	$out = [];
	foreach ( get_posts( [ 'post_type' => 'bcl_service', 'numberposts' => 50, 'orderby' => 'menu_order', 'order' => 'ASC', 'lang' => bcl_lang() ] ) as $p ) {
		$out[ $p->ID ] = $p->post_title;
	}
	return $out;
}

function bcl_rfq_fields(): array {
	return [
		'company'      => [ 'label' => __( 'Company name', 'bcl-core' ), 'req' => [ 'full' ], 'auto' => 'organization' ],
		'contact_name' => [ 'label' => __( 'Contact person', 'bcl-core' ), 'req' => [ 'full', 'quick' ], 'auto' => 'name' ],
		'email'        => [ 'label' => __( 'Email', 'bcl-core' ), 'type' => 'email', 'req' => [ 'full', 'quick' ], 'auto' => 'email' ],
		'phone'        => [ 'label' => __( 'Phone', 'bcl-core' ), 'type' => 'tel', 'req' => [ 'full', 'quick' ], 'auto' => 'tel' ],
		'messenger'    => [ 'label' => __( 'WhatsApp / WeChat / Zalo', 'bcl-core' ), 'placeholder' => __( 'ID or number (optional)', 'bcl-core' ) ],
		'cargo'        => [ 'label' => __( 'Cargo description', 'bcl-core' ), 'req' => [ 'full' ], 'placeholder' => __( 'e.g. electronic parts, frozen seafood, machinery', 'bcl-core' ) ],
		'hs_code'      => [ 'label' => __( 'HS code (if known)', 'bcl-core' ), 'max' => 40 ],
		'origin'       => [ 'label' => __( 'Pickup location', 'bcl-core' ), 'req' => [ 'full', 'quick' ], 'placeholder' => __( 'City, port or factory', 'bcl-core' ) ],
		'destination'  => [ 'label' => __( 'Delivery location', 'bcl-core' ), 'req' => [ 'full', 'quick' ], 'placeholder' => __( 'City, port or factory', 'bcl-core' ) ],
		'weight'       => [ 'label' => __( 'Gross weight (kg)', 'bcl-core' ), 'inputmode' => 'decimal', 'max' => 40 ],
		'packages'     => [ 'label' => __( 'Number of packages', 'bcl-core' ), 'inputmode' => 'numeric', 'max' => 40 ],
		'containers'   => [ 'label' => __( 'Containers (quantity × type)', 'bcl-core' ), 'placeholder' => '2 × 40HC', 'max' => 60 ],
		'vehicle'      => [ 'label' => __( 'Vehicle / equipment', 'bcl-core' ), 'type' => 'select', 'options' => [ '' => __( '— Select —', 'bcl-core' ) ] + bcl_vehicles() ],
		'ship_date'    => [ 'label' => __( 'Expected shipping date', 'bcl-core' ), 'type' => 'date' ],
		'services'     => [ 'label' => __( 'Services needed', 'bcl-core' ), 'type' => 'checkboxes', 'options' => bcl_rfq_service_options() ],
		'notes'        => [ 'label' => __( 'Notes', 'bcl-core' ), 'type' => 'textarea', 'max' => 3000, 'placeholder' => __( 'Incoterms, special handling, temperature, deadlines…', 'bcl-core' ) ],
	];
}

/** Field groups per variant (order = display order). */
function bcl_rfq_layout( string $variant ): array {
	if ( 'quick' === $variant ) {
		return [ '' => [ 'contact_name', 'phone', 'email', 'company', 'origin', 'destination', 'services', 'notes' ] ];
	}
	return [
		__( 'Contact information', 'bcl-core' ) => [ 'company', 'contact_name', 'email', 'phone', 'messenger' ],
		__( 'Cargo', 'bcl-core' )               => [ 'cargo', 'hs_code', 'weight', 'packages', 'containers' ],
		__( 'Transport', 'bcl-core' )           => [ 'origin', 'destination', 'vehicle', 'ship_date', 'services' ],
		__( 'Notes & documents', 'bcl-core' )   => [ 'notes' ],
	];
}

/* ------------------------------------------------------------------ token & helpers */

function bcl_rfq_token(): string {
	$t = (string) time();
	return $t . '.' . hash_hmac( 'sha256', $t, wp_salt( 'nonce' ) );
}

/** Seconds since the form was rendered, or null if the token is forged/missing. */
function bcl_rfq_token_age( string $token ): ?int {
	[ $t, $sig ] = array_pad( explode( '.', $token, 2 ), 2, '' );
	if ( ! ctype_digit( $t ) || ! hash_equals( hash_hmac( 'sha256', $t, wp_salt( 'nonce' ) ), $sig ) ) {
		return null;
	}
	return time() - (int) $t;
}

function bcl_rfq_ip(): string {
	// ponytail: REMOTE_ADDR only; behind Cloudflare/a proxy, map the trusted client-IP header here.
	return filter_var( $_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP ) ?: '0.0.0.0';
}

function bcl_rfq_rate_key(): string {
	return 'bcl_rfq_rl_' . md5( bcl_rfq_ip() );
}

/* ------------------------------------------------------------------ rendering */

add_shortcode( 'bcl_rfq_form', static fn( $atts ) => bcl_rfq_form( ( $atts['variant'] ?? '' ) === 'quick' ? 'quick' : 'full' ) );

/** Prefill from ?service=ID or ?route=ID links (service and route pages). */
function bcl_rfq_prefill(): array {
	$v = [];
	$service = absint( $_GET['service'] ?? 0 );
	if ( $service && 'bcl_service' === get_post_type( $service ) ) {
		$v['services'] = [ (string) $service ];
	}
	$route = absint( $_GET['route'] ?? 0 );
	if ( $route && 'bcl_route' === get_post_type( $route ) ) {
		$v['origin']      = get_post_meta( $route, '_bcl_origin', true );
		$v['destination'] = get_post_meta( $route, '_bcl_destination', true );
		$v['services']    = array_map( 'strval', bcl_translate_ids( (array) get_post_meta( $route, '_bcl_services', true ) ) );
		$v['notes']       = sprintf( __( 'Route: %s', 'bcl-core' ), get_the_title( $route ) );
	}
	return $v;
}

function bcl_rfq_form( string $variant = 'full' ): string {
	$ref = sanitize_text_field( wp_unslash( $_GET['rfq'] ?? '' ) );
	if ( preg_match( '/^RFQ-\d{6}-\d{4,}$/', $ref ) ) {
		return bcl_rfq_success( $ref );
	}

	$state  = $GLOBALS['bcl_rfq_state'] ?? null;
	$mine   = $state && $state['variant'] === $variant;
	$errors = $mine ? $state['errors'] : [];
	$values = $mine ? $state['values'] : bcl_rfq_prefill();
	$fields = bcl_rfq_fields();
	$uid    = 'rfq-' . $variant;
	$action = add_query_arg( 'bcl_rfq', $variant, remove_query_arg( [ 'rfq', 'bcl_rfq', 'service', 'route' ] ) ) . '#' . $uid;
	$site   = bcl_opt( 'turnstile_site' );

	if ( $site ) {
		wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', [], null, [ 'strategy' => 'defer' ] );
	}

	ob_start();
	?>
	<form id="<?php echo esc_attr( $uid ); ?>" class="rfq rfq--<?php echo esc_attr( $variant ); ?>" method="post" action="<?php echo esc_url( $action ); ?>" enctype="multipart/form-data" data-max-files="<?php echo (int) BCL_RFQ_MAX_FILES; ?>" data-max-bytes="<?php echo (int) BCL_RFQ_MAX_BYTES; ?>" data-msg-files="<?php echo esc_attr( sprintf( __( 'Please attach at most %1$d files, %2$d MB each.', 'bcl-core' ), BCL_RFQ_MAX_FILES, BCL_RFQ_MAX_BYTES / MB_IN_BYTES ) ); ?>">
		<?php if ( $errors ) : ?>
			<div class="rfq__alert" role="alert" tabindex="-1">
				<strong><?php esc_html_e( 'Your request was not sent.', 'bcl-core' ); ?></strong>
				<?php echo esc_html( $errors['form'] ?? __( 'Please check the highlighted fields.', 'bcl-core' ) ); ?>
				<?php if ( 'full' === $variant && empty( $errors['form'] ) ) : ?>
					<?php esc_html_e( 'Attached files must be selected again.', 'bcl-core' ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php foreach ( bcl_rfq_layout( $variant ) as $legend => $names ) : ?>
			<fieldset class="rfq__group">
				<?php if ( $legend ) : ?>
					<legend class="rfq__legend"><?php echo esc_html( $legend ); ?></legend>
				<?php endif; ?>
				<div class="rfq__grid">
					<?php
					foreach ( $names as $name ) {
						echo bcl_rfq_field( $uid, $name, $fields[ $name ], $values[ $name ] ?? '', $errors[ $name ] ?? '', $variant ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
					}
					?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<?php if ( 'full' === $variant ) : ?>
			<div class="field field--file<?php echo isset( $errors['files'] ) ? ' has-error' : ''; ?>">
				<label for="<?php echo esc_attr( $uid ); ?>-files"><?php esc_html_e( 'Attachments: Invoice, Packing List, cargo photos', 'bcl-core' ); ?></label>
				<input id="<?php echo esc_attr( $uid ); ?>-files" type="file" name="rfq_files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx" aria-describedby="<?php echo esc_attr( $uid ); ?>-files-help<?php echo isset( $errors['files'] ) ? ' ' . esc_attr( $uid ) . '-files-err' : ''; ?>">
				<p class="field__help" id="<?php echo esc_attr( $uid ); ?>-files-help"><?php printf( esc_html__( 'Up to %1$d files, %2$d MB each. PDF, JPG, PNG, WEBP, DOC(X), XLS(X).', 'bcl-core' ), (int) BCL_RFQ_MAX_FILES, (int) ( BCL_RFQ_MAX_BYTES / MB_IN_BYTES ) ); ?></p>
				<?php if ( isset( $errors['files'] ) ) : ?>
					<p class="field__error" id="<?php echo esc_attr( $uid ); ?>-files-err"><?php echo esc_html( $errors['files'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="field field--consent<?php echo isset( $errors['consent'] ) ? ' has-error' : ''; ?>">
			<label class="check">
				<input type="checkbox" name="rfq[consent]" value="1" required<?php checked( ! empty( $values['consent'] ) ); ?><?php echo isset( $errors['consent'] ) ? ' aria-invalid="true"' : ''; ?>>
				<span>
				<?php
				printf(
					/* translators: %s: privacy policy link */
					esc_html__( 'I agree that BCL may process my personal data to handle this request, as described in the %s.', 'bcl-core' ),
					'<a href="' . esc_url( bcl_page_url( 'privacy' ) ) . '" target="_blank">' . esc_html__( 'Privacy Policy', 'bcl-core' ) . '</a>'
				);
				?>
				<span class="req" aria-hidden="true">*</span></span>
			</label>
			<?php if ( isset( $errors['consent'] ) ) : ?>
				<p class="field__error"><?php echo esc_html( $errors['consent'] ); ?></p>
			<?php endif; ?>
		</div>

		<div class="rfq__hp" aria-hidden="true">
			<label for="<?php echo esc_attr( $uid ); ?>-hp">Leave this field empty</label>
			<input id="<?php echo esc_attr( $uid ); ?>-hp" type="text" name="rfq[hp_url]" value="" tabindex="-1" autocomplete="off">
		</div>
		<input type="hidden" name="bcl_token" value="<?php echo esc_attr( bcl_rfq_token() ); ?>">
		<input type="hidden" name="bcl_redirect" value="<?php echo esc_url( remove_query_arg( [ 'rfq', 'bcl_rfq', 'service', 'route' ] ) ); ?>">

		<?php if ( $site ) : ?>
			<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site ); ?>" data-language="<?php echo esc_attr( bcl_lang() ); ?>"></div>
		<?php endif; ?>

		<div class="rfq__actions">
			<button type="submit" class="btn btn--accent btn--lg"><?php esc_html_e( 'Send request', 'bcl-core' ); ?></button>
			<p class="rfq__note"><span class="req" aria-hidden="true">*</span> <?php esc_html_e( 'Required fields', 'bcl-core' ); ?></p>
		</div>
	</form>
	<?php
	return (string) ob_get_clean();
}

function bcl_rfq_field( string $uid, string $name, array $f, $value, string $error, string $variant ): string {
	$type     = $f['type'] ?? 'text';
	$id       = "{$uid}-{$name}";
	$required = in_array( $variant, $f['req'] ?? [], true );
	$req_mark = $required ? ' <span class="req" aria-hidden="true">*</span>' : '';
	$aria     = $error ? ' aria-invalid="true" aria-describedby="' . esc_attr( $id ) . '-err"' : '';
	$req_attr = $required ? ' required' : '';
	$err_html = $error ? '<p class="field__error" id="' . esc_attr( $id ) . '-err">' . esc_html( $error ) . '</p>' : '';
	$classes  = 'field field--' . $type . ( $error ? ' has-error' : '' ) . ( in_array( $type, [ 'textarea', 'checkboxes' ], true ) ? ' field--wide' : '' );

	if ( 'checkboxes' === $type ) {
		if ( ! $f['options'] ) {
			return '';
		}
		$vals = array_map( 'strval', (array) $value );
		$html = '<fieldset class="' . esc_attr( $classes ) . '"><legend>' . esc_html( $f['label'] ) . $req_mark . '</legend><div class="chips">';
		foreach ( $f['options'] as $ov => $ol ) {
			$html .= '<label class="chip"><input type="checkbox" name="rfq[' . esc_attr( $name ) . '][]" value="' . esc_attr( $ov ) . '"' . checked( in_array( (string) $ov, $vals, true ), true, false ) . '><span>' . esc_html( $ol ) . '</span></label>';
		}
		return $html . '</div>' . $err_html . '</fieldset>';
	}

	$html = '<div class="' . esc_attr( $classes ) . '"><label for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . $req_mark . '</label>';
	$ph   = isset( $f['placeholder'] ) ? ' placeholder="' . esc_attr( $f['placeholder'] ) . '"' : '';
	if ( 'textarea' === $type ) {
		$html .= '<textarea id="' . esc_attr( $id ) . '" name="rfq[' . esc_attr( $name ) . ']" rows="' . ( 'quick' === $variant ? 3 : 5 ) . '" maxlength="' . (int) ( $f['max'] ?? 3000 ) . '"' . $ph . $req_attr . $aria . '>' . esc_textarea( (string) $value ) . '</textarea>';
	} elseif ( 'select' === $type ) {
		$html .= '<select id="' . esc_attr( $id ) . '" name="rfq[' . esc_attr( $name ) . ']"' . $req_attr . $aria . '>';
		foreach ( $f['options'] as $ov => $ol ) {
			$html .= '<option value="' . esc_attr( $ov ) . '"' . selected( (string) $value, (string) $ov, false ) . '>' . esc_html( $ol ) . '</option>';
		}
		$html .= '</select>';
	} else {
		$extra  = isset( $f['auto'] ) ? ' autocomplete="' . esc_attr( $f['auto'] ) . '"' : '';
		$extra .= isset( $f['inputmode'] ) ? ' inputmode="' . esc_attr( $f['inputmode'] ) . '"' : '';
		$html  .= '<input id="' . esc_attr( $id ) . '" type="' . esc_attr( $type ) . '" name="rfq[' . esc_attr( $name ) . ']" value="' . esc_attr( (string) $value ) . '" maxlength="' . (int) ( $f['max'] ?? 200 ) . '"' . $ph . $extra . $req_attr . $aria . '>';
	}
	return $html . $err_html . '</div>';
}

function bcl_rfq_success( string $ref ): string {
	$again = remove_query_arg( 'rfq' );
	ob_start();
	?>
	<div class="rfq-success" id="rfq-success" role="status" tabindex="-1" data-bcl-event="generate_lead" data-bcl-ref="<?php echo esc_attr( $ref ); ?>">
		<svg class="rfq-success__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<h3><?php esc_html_e( 'Thank you — your request has been sent.', 'bcl-core' ); ?></h3>
		<p>
			<?php
			/* translators: %s: reference code */
			printf( esc_html__( 'Reference: %s. A confirmation has been sent to your email; BCL will review the details and contact you.', 'bcl-core' ), '<strong>' . esc_html( $ref ) . '</strong>' );
			?>
		</p>
		<?php if ( $lines = bcl_hotlines() ) : ?>
			<p><?php esc_html_e( 'Urgent shipment? Call or Zalo:', 'bcl-core' ); ?>
				<?php foreach ( $lines as $i => $line ) : ?>
					<?php echo $i ? ' · ' : ''; ?><a href="<?php echo esc_url( bcl_tel_href( $line ), [ 'tel' ] ); ?>" data-track="click_call"><strong><?php echo esc_html( $line ); ?></strong></a>
				<?php endforeach; ?>
			</p>
		<?php endif; ?>
		<p><a class="btn btn--ghost" href="<?php echo esc_url( $again ); ?>"><?php esc_html_e( 'Send another request', 'bcl-core' ); ?></a></p>
	</div>
	<?php
	return (string) ob_get_clean();
}

/* ------------------------------------------------------------------ handling */

add_action( 'template_redirect', 'bcl_rfq_handle', 1 );

function bcl_rfq_handle(): void {
	$variant = sanitize_key( $_GET['bcl_rfq'] ?? '' );
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! in_array( $variant, [ 'full', 'quick' ], true ) ) {
		return;
	}
	nocache_headers();

	$fail = static function ( array $errors, array $values = [] ) use ( $variant ): void {
		$GLOBALS['bcl_rfq_state'] = compact( 'variant', 'errors', 'values' );
	};

	// PHP drops the whole body when post_max_size is exceeded.
	if ( empty( $_POST ) ) {
		$fail( [ 'form' => __( 'The upload is too large. Please attach smaller files or fewer files.', 'bcl-core' ) ] );
		return;
	}

	$in       = (array) wp_unslash( $_POST['rfq'] ?? [] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized field by field.
	$redirect = wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['bcl_redirect'] ?? '' ) ), home_url( '/' ) );

	// 1. Honeypot: pretend success so bots learn nothing.
	if ( ! empty( $in['hp_url'] ) ) {
		wp_safe_redirect( add_query_arg( 'rfq', 'RFQ-000000-0000', $redirect ) . '#rfq-success' );
		exit;
	}

	[ $values, $errors ] = bcl_rfq_validate( $in, $variant );

	// 2. Signed time token.
	$age = bcl_rfq_token_age( sanitize_text_field( wp_unslash( $_POST['bcl_token'] ?? '' ) ) );
	if ( null === $age || $age > 14 * DAY_IN_SECONDS ) {
		$errors['form'] = __( 'The form has expired. Please review and send it again.', 'bcl-core' );
	} elseif ( $age < BCL_RFQ_MIN_SECONDS ) {
		$errors['form'] = __( 'The form was sent too quickly. Please review and send it again.', 'bcl-core' );
	}

	// 3. Rate limit.
	if ( (int) get_transient( bcl_rfq_rate_key() ) >= BCL_RFQ_RATE_LIMIT ) {
		$errors['form'] = __( 'Too many requests from your network. Please call our hotline or try again later.', 'bcl-core' );
	}

	// 4. Cloudflare Turnstile (only when configured).
	if ( ! $errors && bcl_opt( 'turnstile_secret' ) && ! bcl_rfq_turnstile_ok() ) {
		$errors['form'] = __( 'Anti-spam check failed. Please try again.', 'bcl-core' );
	}

	// 5. Files (full form only).
	$files = [];
	if ( 'full' === $variant ) {
		[ $files, $file_error ] = bcl_rfq_check_files();
		if ( $file_error ) {
			$errors['files'] = $file_error;
		}
	}

	if ( $errors ) {
		$fail( $errors, $values );
		return;
	}

	$id = bcl_rfq_save( $values, $files, $variant, $redirect );
	if ( ! $id ) {
		$fail( [ 'form' => __( 'Something went wrong while saving. Please try again or call our hotline.', 'bcl-core' ) ], $values );
		return;
	}
	set_transient( bcl_rfq_rate_key(), (int) get_transient( bcl_rfq_rate_key() ) + 1, HOUR_IN_SECONDS );

	wp_safe_redirect( add_query_arg( 'rfq', get_post_meta( $id, '_bcl_rfq_ref', true ), $redirect ) . '#rfq-success' );
	exit;
}

/** @return array{0:array,1:array} [clean values, errors] */
function bcl_rfq_validate( array $in, string $variant ): array {
	$values = [];
	$errors = [];
	foreach ( bcl_rfq_fields() as $name => $f ) {
		$type = $f['type'] ?? 'text';
		$raw  = $in[ $name ] ?? '';
		if ( 'checkboxes' === $type ) {
			$v = array_values( array_intersect( array_map( 'strval', (array) $raw ), array_map( 'strval', array_keys( $f['options'] ) ) ) );
		} elseif ( 'textarea' === $type ) {
			$v = mb_substr( sanitize_textarea_field( (string) $raw ), 0, $f['max'] ?? 3000 );
		} else {
			$v = mb_substr( sanitize_text_field( is_string( $raw ) ? $raw : '' ), 0, $f['max'] ?? 200 );
		}
		$values[ $name ] = $v;

		$empty = ( [] === $v || '' === $v );
		if ( $empty ) {
			if ( in_array( $variant, $f['req'] ?? [], true ) ) {
				$errors[ $name ] = __( 'This field is required.', 'bcl-core' );
			}
			continue;
		}
		$bad = match ( $type ) {
			'email'  => ! is_email( $v ),
			'tel'    => ! preg_match( '/^[0-9+()\s.\-]{6,25}$/', $v ),
			'date'   => ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ),
			'select' => ! array_key_exists( $v, $f['options'] ),
			default  => false,
		};
		if ( $bad ) {
			$errors[ $name ] = match ( $type ) {
				'email' => __( 'Please enter a valid email address.', 'bcl-core' ),
				'tel'   => __( 'Please enter a valid phone number.', 'bcl-core' ),
				'date'  => __( 'Please enter a valid date.', 'bcl-core' ),
				default => __( 'Please choose a valid option.', 'bcl-core' ),
			};
		}
	}
	$values['consent'] = ! empty( $in['consent'] );
	if ( ! $values['consent'] ) {
		$errors['consent'] = __( 'Please agree to the processing of your personal data.', 'bcl-core' );
	}
	return [ $values, $errors ];
}

/** @return array{0:array,1:string} [accepted files, error message] */
function bcl_rfq_check_files(): array {
	$f = $_FILES['rfq_files'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated below.
	if ( ! $f || ! is_array( $f['name'] ?? null ) ) {
		return [ [], '' ];
	}
	$files = [];
	foreach ( $f['name'] as $i => $name ) {
		$err = (int) ( $f['error'][ $i ] ?? UPLOAD_ERR_NO_FILE );
		if ( UPLOAD_ERR_NO_FILE === $err ) {
			continue;
		}
		$name = sanitize_file_name( (string) $name );
		if ( in_array( $err, [ UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ], true ) || (int) $f['size'][ $i ] > BCL_RFQ_MAX_BYTES ) {
			/* translators: 1: file name 2: size limit in MB */
			return [ [], sprintf( __( '"%1$s" is larger than %2$d MB.', 'bcl-core' ), $name, BCL_RFQ_MAX_BYTES / MB_IN_BYTES ) ];
		}
		if ( UPLOAD_ERR_OK !== $err || ! is_uploaded_file( $f['tmp_name'][ $i ] ) ) {
			return [ [], sprintf( __( '"%s" could not be uploaded. Please try again.', 'bcl-core' ), $name ) ];
		}
		$check = wp_check_filetype_and_ext( $f['tmp_name'][ $i ], $name, bcl_rfq_mimes() );
		if ( ! $check['ext'] || ! $check['type'] ) {
			return [ [], sprintf( __( '"%s" is not an allowed file type.', 'bcl-core' ), $name ) ];
		}
		$files[] = [ 'name' => $name, 'tmp' => $f['tmp_name'][ $i ], 'size' => (int) $f['size'][ $i ], 'ext' => $check['ext'], 'type' => $check['type'] ];
	}
	if ( count( $files ) > BCL_RFQ_MAX_FILES ) {
		return [ [], sprintf( __( 'Please attach at most %d files.', 'bcl-core' ), BCL_RFQ_MAX_FILES ) ];
	}
	return [ $files, '' ];
}

function bcl_rfq_turnstile_ok(): bool {
	$res = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', [
		'timeout' => 8,
		'body'    => [
			'secret'   => bcl_opt( 'turnstile_secret' ),
			'response' => sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ?? '' ) ),
			'remoteip' => bcl_rfq_ip(),
		],
	] );
	return ! is_wp_error( $res ) && ! empty( json_decode( wp_remote_retrieve_body( $res ), true )['success'] );
}

/** Private storage root for attachments; web access denied by .htaccess (Apache). */
function bcl_rfq_dir(): string {
	$root = trailingslashit( wp_upload_dir( null, false )['basedir'] ) . 'bcl-rfq';
	if ( ! file_exists( "$root/.htaccess" ) ) {
		wp_mkdir_p( $root );
		file_put_contents( "$root/.htaccess", "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		file_put_contents( "$root/index.php", "<?php // Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $root;
}

function bcl_rfq_save( array $values, array $files, string $variant, string $source ): int {
	$who = $values['company'] ?: $values['contact_name'];
	$id  = wp_insert_post( [ 'post_type' => 'bcl_rfq', 'post_status' => 'publish', 'post_title' => $who ], true );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	$ref = sprintf( 'RFQ-%s-%04d', wp_date( 'ymd' ), $id );
	wp_update_post( [ 'ID' => $id, 'post_title' => "$ref · $who" ] );

	$saved = [];
	if ( $files ) {
		$sub = gmdate( 'Y/m' );
		$dir = bcl_rfq_dir() . '/' . $sub;
		wp_mkdir_p( $dir );
		foreach ( $files as $file ) {
			$stored = wp_generate_password( 24, false ) . '.' . $file['ext'];
			if ( move_uploaded_file( $file['tmp'], "$dir/$stored" ) ) {
				$saved[] = [ 'name' => $file['name'], 'path' => "$sub/$stored", 'size' => $file['size'], 'type' => $file['type'] ];
			}
		}
	}

	$meta = [
		'_bcl_rfq'         => $values,
		'_bcl_rfq_ref'     => $ref,
		'_bcl_rfq_status'  => 'new',
		'_bcl_rfq_lang'    => bcl_lang(),
		'_bcl_rfq_variant' => $variant,
		'_bcl_rfq_files'   => $saved,
		'_bcl_rfq_source'  => $source,
		// Proof of consent: when, and which policy version (page URL).
		'_bcl_rfq_consent' => [ 'at' => current_time( 'mysql' ), 'policy' => bcl_page_url( 'privacy' ) ],
	];
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}

	bcl_rfq_send_mails( $id, $ref, $values, $saved );

	/** CRM hook: do_action( 'bcl_rfq_submitted', $rfq_id, $values, $files ). */
	do_action( 'bcl_rfq_submitted', $id, $values, $saved );
	return $id;
}

/* ------------------------------------------------------------------ email */

/** Plain-text summary in the current locale. */
function bcl_rfq_summary( array $values, array $files ): string {
	$lines = [];
	foreach ( bcl_rfq_fields() as $name => $f ) {
		$v = $values[ $name ] ?? '';
		if ( [] === $v || '' === $v ) {
			continue;
		}
		if ( 'checkboxes' === ( $f['type'] ?? '' ) ) {
			$v = implode( ', ', array_map( static fn( $id ) => get_the_title( (int) $id ), (array) $v ) );
		} elseif ( 'select' === ( $f['type'] ?? '' ) ) {
			$v = $f['options'][ $v ] ?? $v;
		}
		$lines[] = $f['label'] . ': ' . $v;
	}
	foreach ( $files as $file ) {
		$lines[] = __( 'Attachment', 'bcl-core' ) . ': ' . $file['name'] . ' (' . size_format( $file['size'] ) . ')';
	}
	return implode( "\n", $lines );
}

function bcl_rfq_send_mails( int $id, string $ref, array $values, array $files ): void {
	$to = array_filter( array_map( 'trim', explode( ',', bcl_opt( 'rfq_emails' ) ?: get_option( 'admin_email' ) ) ), 'is_email' );
	$hotlines = implode( ' – ', bcl_hotlines() );

	// Internal notification: always Vietnamese.
	$switched = switch_to_locale( 'vi' );
	$subject  = sprintf( __( '[BCL] New quotation request %1$s – %2$s', 'bcl-core' ), $ref, $values['company'] ?: $values['contact_name'] );
	$body     = bcl_rfq_summary( $values, $files )
		. "\n\n" . __( 'Language', 'bcl-core' ) . ': ' . strtoupper( bcl_lang() )
		. "\n" . __( 'Open in admin', 'bcl-core' ) . ': ' . admin_url( 'post.php?post=' . $id . '&action=edit' );
	if ( $switched ) {
		restore_previous_locale();
	}
	$reply_name = trim( str_replace( [ '"', '<', '>', ',', ';' ], '', $values['contact_name'] ) );
	if ( $to ) {
		wp_mail( $to, $subject, $body, [ 'Reply-To: ' . $reply_name . ' <' . $values['email'] . '>' ] );
	}

	// Confirmation to the customer, in the language they used.
	$subject = sprintf( __( 'BCL has received your quotation request %s', 'bcl-core' ), $ref );
	$body    = sprintf( __( 'Dear %s,', 'bcl-core' ), $values['contact_name'] ) . "\n\n"
		. sprintf( __( 'Thank you for contacting Blue Crane Logistics. We have received your request %s and will contact you after reviewing the details.', 'bcl-core' ), $ref ) . "\n\n"
		. __( 'Summary of your request', 'bcl-core' ) . ":\n" . bcl_rfq_summary( $values, $files ) . "\n\n"
		. ( $hotlines ? __( 'Hotline / Zalo', 'bcl-core' ) . ': ' . $hotlines . "\n" : '' )
		. bcl_opt( 'company_name', 'Blue Crane Logistics' ) . "\n" . home_url( '/' );
	wp_mail( $values['email'], $subject, $body );
}

add_filter( 'wp_mail_from', static function ( $from ) {
	$email = bcl_opt( 'email' );
	if ( $email ) {
		return $email;
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return str_contains( (string) $host, '.' ) ? 'no-reply@' . $host : 'no-reply@bcl.vn';
} );
add_filter( 'wp_mail_from_name', static fn() => 'BCL – Blue Crane Logistics' );
