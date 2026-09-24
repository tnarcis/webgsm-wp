<?php
/**
 * Login / înregistrare: română, parolă aleasă pe formular,
 * termeni obligatorii + confirmare email înainte de autentificare/comandă.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parola o alege clientul pe formular. Username = email.
 */
add_filter( 'pre_option_woocommerce_registration_generate_password', function () {
	return 'no';
} );

add_filter( 'pre_option_woocommerce_registration_generate_username', function () {
	return 'yes';
} );

/**
 * Texte Martfury/WooCommerce rămase în engleză (header + formular).
 */
add_filter( 'gettext', 'webgsm_login_register_gettext', 9999, 3 );
add_filter( 'gettext_with_context', function ( $translated, $text, $context, $domain ) {
	return webgsm_login_register_gettext( $translated, $text, $domain );
}, 9999, 4 );

function webgsm_login_register_gettext( $translated, $text, $domain ) {
	$allowed = array( 'martfury', 'woocommerce', 'ajax-search-for-woocommerce' );
	if ( ! in_array( $domain, $allowed, true ) ) {
		return $translated;
	}

	static $map = array(
		'Log in'                  => 'Autentificare',
		'Login'                   => 'Autentificare',
		'Register'                => 'Creează cont',
		'Register An Account'     => 'Creează-ți contul',
		'Log In Your Account'     => 'Intră în cont',
		'Username or email address' => 'Email',
		'Username or email'       => 'Email',
		'Email address'           => 'Email',
		'Password'                => 'Parolă',
		'Remember me'             => 'Ține-mă minte',
		'Forgot your password?'   => 'Ai uitat parola?',
		'Lost your password?'     => 'Ai uitat parola?',
		'A password will be sent to your email address.' => 'Îți alegi parola chiar aici, pe formular.',
		'A link to set a new password will be sent to your email address.' => 'Îți alegi parola chiar aici, pe formular.',
		'Your account was created successfully and a password has been sent to your email address.' => 'Contul a fost creat. Confirmă adresa din emailul pe care ți l-am trimis, apoi te poți autentifica.',
		'Your account was created successfully. Your login details have been sent to your email address.' => 'Contul a fost creat. Confirmă adresa din emailul pe care ți l-am trimis, apoi te poți autentifica.',
		'Loading'                 => 'Se încarcă',
		'Loading...'              => 'Se încarcă...',
		'See all products...'     => 'Vezi toate produsele',
		'See all products'        => 'Vezi toate produsele',
		'See all results...'      => 'Vezi toate rezultatele',
		'See all results'         => 'Vezi toate rezultatele',
		'Nothing found'           => 'Nu s-a găsit nimic',
		'Search Results for'      => 'Rezultate căutare pentru',
		'Search results for &ldquo;%s&rdquo;' => 'Rezultate căutare pentru „%s”',
		'Search results for "%s"' => 'Rezultate căutare pentru „%s”',
		'Search Results for: %s'  => 'Rezultate căutare pentru „%s”',
	);

	return isset( $map[ $text ] ) ? $map[ $text ] : $translated;
}

/**
 * FiboSearch ține textul „See all products” în setări, nu doar în gettext.
 */
add_filter( 'dgwt/wcas/settings/load_value/key=search_see_all_results_text', function ( $value ) {
	if ( $value === '' || stripos( (string) $value, 'see all' ) !== false ) {
		return 'Vezi toate produsele';
	}
	return $value;
} );

add_filter( 'dgwt/wcas/labels', function ( $labels ) {
	if ( ! is_array( $labels ) ) {
		return $labels;
	}
	foreach ( array( 'show_more', 'show_more_details' ) as $key ) {
		if ( empty( $labels[ $key ] ) || stripos( (string) $labels[ $key ], 'see all' ) !== false ) {
			$labels[ $key ] = 'Vezi toate produsele';
		}
	}
	return $labels;
} );

add_filter( 'woocommerce_pagination_args', function ( $args ) {
	if ( ! is_array( $args ) ) {
		return $args;
	}
	if ( ! empty( $args['next_text'] ) && is_string( $args['next_text'] ) ) {
		$args['next_text'] = str_replace( array( 'Loading', 'Next Page' ), array( 'Se încarcă', 'Pagina următoare' ), $args['next_text'] );
	}
	if ( ! empty( $args['prev_text'] ) && is_string( $args['prev_text'] ) ) {
		$args['prev_text'] = str_replace( 'Previous Page', 'Pagina anterioară', $args['prev_text'] );
	}
	return $args;
} );

add_action( 'wp_footer', function () {
	?>
	<script>
	(function() {
		var map = [
			['See all products...', 'Vezi toate produsele'],
			['See all products', 'Vezi toate produsele'],
			['See all results...', 'Vezi toate rezultatele'],
			['See all results', 'Vezi toate rezultatele']
		];
		function replaceIn(el) {
			if (!el || el.nodeType !== 1) return;
			var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
			var node;
			while ((node = walker.nextNode())) {
				var t = node.nodeValue;
				if (!t) continue;
				for (var i = 0; i < map.length; i++) {
					if (t.indexOf(map[i][0]) !== -1) {
						t = t.split(map[i][0]).join(map[i][1]);
					}
				}
				if (t.indexOf('Loading') !== -1 && (el.id === 'martfury-products-loading' || el.classList.contains('dots-loading') || (el.closest && el.closest('#martfury-products-loading, .dots-loading, .woocommerce-pagination')))) {
					t = t.replace(/\bLoading\b/g, 'Se încarcă');
				}
				if (t !== node.nodeValue) node.nodeValue = t;
			}
		}
		function run() {
			if (window.dgwt_wcas && window.dgwt_wcas.labels) {
				['show_more', 'show_more_details'].forEach(function(k) {
					if (!dgwt_wcas.labels[k] || /see all/i.test(dgwt_wcas.labels[k])) {
						dgwt_wcas.labels[k] = 'Vezi toate produsele';
					}
				});
			}
			document.querySelectorAll('.dgwt-wcas-suggestions-wrapp, .dgwt-wcas-suggestion, .dgwt-wcas-st-more, .dgwt-wcas-suggestion-more, .search-results, #martfury-products-loading, .dots-loading, .woocommerce-pagination').forEach(replaceIn);
		}
		var tmr;
		function schedule() {
			clearTimeout(tmr);
			tmr = setTimeout(run, 80);
		}
		if (document.readyState !== 'loading') schedule();
		else document.addEventListener('DOMContentLoaded', schedule);
		// Observă doar dropdown-ul de search, nu tot document.body
		document.addEventListener('DOMContentLoaded', function() {
			var target = document.querySelector('.dgwt-wcas-search-wrapp, .header-search, .products-search') || null;
			if (!target || typeof MutationObserver === 'undefined') return;
			new MutationObserver(schedule).observe(target, { childList: true, subtree: true });
		});
	})();
	</script>
	<?php
}, 99 );

/**
 * Nu autentifica automat: fără confirmare email, botii ar putea comanda imediat.
 */
add_filter( 'woocommerce_registration_auth_new_customer', '__return_false' );

/**
 * Comenzi doar din conturi cu email confirmat — fără guest checkout și fără creare cont pe checkout.
 */
add_filter( 'pre_option_woocommerce_enable_guest_checkout', function () {
	return 'no';
} );
add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', function () {
	return 'no';
} );

/**
 * Cont vechi (fără meta) = considerat confirmat. Adminii trec mereu.
 */
function webgsm_customer_can_checkout( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}
	if ( user_can( $user_id, 'manage_options' ) ) {
		return true;
	}
	$confirmed = get_user_meta( $user_id, '_email_confirmed', true );
	return $confirmed === '' || (int) $confirmed === 1;
}

function webgsm_checkout_login_url() {
	$checkout = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
	return add_query_arg(
		'redirect_to',
		rawurlencode( $checkout ),
		wc_get_page_permalink( 'myaccount' )
	);
}

/**
 * Oaspeții și conturile neconfirmate nu ajung pe checkout.
 */
add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		wc_add_notice( 'Ca să plasezi o comandă trebuie un cont cu email confirmat. Autentifică-te sau creează un cont.', 'notice' );
		wp_safe_redirect( webgsm_checkout_login_url() );
		exit;
	}

	if ( ! webgsm_customer_can_checkout() ) {
		wc_add_notice( 'Confirmă adresa de email înainte de a comanda. Verifică inbox-ul și apasă linkul primit.', 'error' );
		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
		exit;
	}
}, 20 );

/**
 * Blochează și trimiterea formularului (dacă cineva ocolește redirectul).
 */
add_action( 'woocommerce_checkout_process', function () {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		wc_add_notice( 'Ca să plasezi o comandă trebuie un cont cu email confirmat.', 'error' );
		return;
	}
	if ( ! webgsm_customer_can_checkout() ) {
		wc_add_notice( 'Confirmă adresa de email înainte de a plasa o comandă. Verifică inbox-ul și apasă linkul primit.', 'error' );
	}
}, 1 );

/**
 * Checkout Block / Store API — aceeași regulă.
 */
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	$route = $request->get_route();
	if ( strpos( $route, '/wc/store' ) === false || strpos( $route, 'checkout' ) === false ) {
		return $result;
	}
	$method = $request->get_method();
	if ( ! in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
		return $result;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return $result;
	}
	if ( ! is_user_logged_in() || ! webgsm_customer_can_checkout() ) {
		return new WP_Error(
			'webgsm_checkout_requires_confirmed_email',
			'Ca să plasezi o comandă trebuie un cont cu email confirmat.',
			array( 'status' => 401 )
		);
	}
	return $result;
}, 10, 3 );

/**
 * Un singur email (confirmare), nu și mailul WooCommerce cu parolă.
 */
add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );

/**
 * Stiluri scurte pe pagina de cont (hint + formular login).
 */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
		return;
	}
	?>
	<style>
	.webgsm-auth-hint {
		margin: 0 0 22px !important;
		padding: 12px 14px;
		background: #f0f7ff;
		border: 1px solid #d6e8fb;
		border-radius: 10px;
		color: #1565c0;
		font-size: 13px;
		line-height: 1.5;
	}
	.woocommerce .customer-login .tabs-panel h2 {
		font-size: 20px !important;
		font-weight: 600 !important;
		margin-bottom: 12px !important;
	}
	.woocommerce-form-login label,
	.woocommerce-form-register label {
		display: block;
		margin-bottom: 6px;
		font-weight: 500;
		color: #444;
		font-size: 14px;
	}
	.woocommerce-form-login .form-row-wide .input-text {
		width: 100%;
		padding: 14px 16px;
		border: 2px solid #e0e0e0;
		border-radius: 10px;
		font-size: 15px;
	}
	.woocommerce-form-login .form-row-wide .input-text:focus {
		border-color: #2196F3;
		box-shadow: 0 0 0 3px rgba(33,150,243,0.1);
		outline: none;
	}
	.woocommerce .customer-login .tabs-nav a {
		font-size: 22px !important;
	}
	.webgsm-terms-accept {
		margin: 8px 0 18px !important;
	}
	.webgsm-terms-accept label {
		display: flex !important;
		align-items: flex-start;
		gap: 10px;
		font-weight: 500 !important;
		line-height: 1.45;
		cursor: pointer;
		color: #333 !important;
	}
	.webgsm-terms-accept input[type="checkbox"] {
		width: 18px !important;
		height: 18px !important;
		min-width: 18px;
		margin: 2px 0 0 !important;
		padding: 0 !important;
		flex-shrink: 0;
		accent-color: #1976D2;
		border: 2px solid #1976D2 !important;
		border-radius: 4px !important;
		box-shadow: none !important;
	}
	.webgsm-terms-accept a {
		color: #1565C0;
		text-decoration: underline;
	}
	@media (max-width: 600px) {
		.woocommerce .customer-login .tabs-nav a {
			font-size: 18px !important;
			padding: 0 10px !important;
		}
	}
	</style>
	<?php
} );

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
		return;
	}
	?>
	<script>
	jQuery(function($) {
		$('#menu-extra-register').attr('href', function(_, href) {
			if (!href) return href;
			return href.split('#')[0] + '#creare-cont';
		});

		var openRegister = window.location.hash === '#creare-cont' || /[?&]tip_client=pj/.test(window.location.search);
		if (openRegister) {
			$('.martfury-login-tabs .tabs-nav a').eq(1).trigger('click');
		}
		if (/[?&]tip_client=pj/.test(window.location.search)) {
			$('#toggle-pj input').prop('checked', true).trigger('change');
		}
	});
	</script>
	<?php
}, 30 );
