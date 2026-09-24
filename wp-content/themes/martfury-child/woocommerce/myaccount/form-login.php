<?php
/**
 * Login / înregistrare – versiune română, intuitivă.
 * Copiat din Martfury și adaptat: texte RO, parolă aleasă de client, taburi funcționale.
 *
 * @package Martfury Child
 * @version 9.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$redirect_to = '';
if ( ! empty( $_GET['redirect_to'] ) ) {
	$redirect_to = esc_url_raw( wp_unslash( $_GET['redirect_to'] ) );
} elseif ( ! empty( $_POST['redirect'] ) ) {
	$redirect_to = esc_url_raw( wp_unslash( $_POST['redirect'] ) );
}
$from_checkout = $redirect_to && ( strpos( $redirect_to, 'checkout' ) !== false || strpos( $redirect_to, 'finalizare' ) !== false );

$login_actived = true;
if ( ! empty( $_POST ) && isset( $_POST['woocommerce-register-nonce'] ) && ! empty( $_POST['woocommerce-register-nonce'] ) ) {
	$login_actived = false;
}
if ( isset( $_GET['tip_client'] ) && sanitize_text_field( wp_unslash( $_GET['tip_client'] ) ) === 'pj' ) {
	$login_actived = false;
}

$login_form_layout = function_exists( 'martfury_get_option' ) ? martfury_get_option( 'login_register_layout' ) : 'tabs';
$login_layout      = 'martfury-login-' . $login_form_layout . ' martfury-login-tabs';

$col_login_class = 'col-md-6 col-sm-6 col-md-offset-3 col-sm-offset-3';
if ( $login_form_layout === 'promotion' ) {
	$col_login_class = 'col-md-5 col-sm-12';
}

$login_class    = $login_actived ? 'active' : '';
$register_class = ! $login_actived ? 'active' : '';
?>

<?php do_action( 'woocommerce_before_customer_login_form' ); ?>

<div class="customer-login">
	<div class="row">
		<div class="<?php echo esc_attr( $col_login_class ); ?> col-login">
			<div class="<?php echo esc_attr( $login_layout ); ?>">
				<ul class="tabs-nav">
					<li class="<?php echo $login_actived ? 'active' : ''; ?>">
						<a href="#autentificare" class="<?php echo esc_attr( $login_class ); ?>">Autentificare</a>
					</li>
					<?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>
						<li class="<?php echo ! $login_actived ? 'active' : ''; ?>">
							<a href="#creare-cont" class="<?php echo esc_attr( $register_class ); ?>">Creează cont</a>
						</li>
					<?php endif; ?>
				</ul>

				<div class="tabs-content">
					<div class="tabs-panel <?php echo esc_attr( $login_class ); ?>" id="autentificare">
						<h2>Intră în cont</h2>
						<p class="webgsm-auth-hint"><?php echo $from_checkout ? 'Ca să plasezi comanda trebuie un cont cu email confirmat. Autentifică-te sau creează un cont.' : 'Folosește emailul și parola pe care le-ai ales la înregistrare.'; ?></p>

						<form class="woocommerce-form woocommerce-form-login login" method="post">
							<?php do_action( 'woocommerce_login_form_start' ); ?>
							<?php if ( $redirect_to ) : ?>
								<input type="hidden" name="redirect" value="<?php echo esc_attr( $redirect_to ); ?>" />
							<?php endif; ?>

							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="username">Email <span class="required">*</span></label>
								<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" required
									placeholder="ex: ioni@firma.ro"
									name="username" id="username" autocomplete="username"
									value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
							</p>

							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide form-row-password">
								<label for="password">Parolă <span class="required">*</span></label>
								<input class="woocommerce-Input woocommerce-Input--text input-text" required
									placeholder="Parola ta"
									type="password"
									autocomplete="current-password"
									name="password" id="password" />
							</p>

							<?php do_action( 'woocommerce_login_form' ); ?>

							<p class="form-row">
								<span class="woocommerce-form-row__remember">
									<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
										<input class="woocommerce-form__input woocommerce-form__input-checkbox"
											name="rememberme" type="checkbox" id="rememberme" value="forever"/>
										<span>Ține-mă minte</span>
									</label>
									<a class="lost-password" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Ai uitat parola?</a>
								</span>

								<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
								<button type="submit" class="woocommerce-Button button" name="login" value="Autentificare">Autentificare</button>
							</p>

							<?php do_action( 'woocommerce_login_form_end' ); ?>
						</form>
					</div>

					<?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>
						<div class="tabs-panel <?php echo esc_attr( $register_class ); ?>" id="creare-cont">
							<h2>Creează-ți contul</h2>
							<p class="webgsm-auth-hint">Îți alegi parola aici. După creare îți trimitem un email — trebuie să confirmi adresa înainte să te poți autentifica și comanda.</p>

							<form method="post" class="register woocommerce-form woocommerce-form-register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
								<?php do_action( 'woocommerce_register_form_start' ); ?>

								<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
									<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
										<label for="reg_username">Utilizator <span class="required">*</span></label>
										<input type="text" required
											class="woocommerce-Input woocommerce-Input--text input-text"
											placeholder="Utilizator"
											name="username" id="reg_username" autocomplete="username"
											value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
									</p>
								<?php endif; ?>

								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="reg_email">Email <span class="required">*</span></label>
									<input type="email" required
										class="woocommerce-Input woocommerce-Input--text input-text"
										placeholder="ex: ioni@firma.ro"
										name="email" id="reg_email" autocomplete="email"
										value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
								</p>

								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="reg_password">Alege o parolă <span class="required">*</span></label>
									<input type="password" required
										placeholder="Minim 8 caractere"
										class="woocommerce-Input woocommerce-Input--text input-text"
										autocomplete="new-password"
										name="password" id="reg_password" />
								</p>

								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="reg_password_confirm">Confirmă parola <span class="required">*</span></label>
									<input type="password" required
										placeholder="Rescrie parola"
										class="woocommerce-Input woocommerce-Input--text input-text"
										autocomplete="new-password"
										name="password_confirm" id="reg_password_confirm" />
								</p>

								<?php do_action( 'woocommerce_register_form' ); ?>

								<?php
								$terms_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'terms' ) : 0;
								$terms_url     = ( $terms_page_id && $terms_page_id > 0 ) ? get_permalink( $terms_page_id ) : home_url( '/termeni-si-conditii/' );
								$terms_checked = ! empty( $_POST['webgsm_accept_terms'] );
								?>
								<p class="form-row form-row-wide webgsm-terms-accept">
									<label for="webgsm_accept_terms">
										<input type="checkbox" name="webgsm_accept_terms" id="webgsm_accept_terms" value="1" <?php checked( $terms_checked, true ); ?> required />
										<span>Am citit și accept <a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer">Termenii și condițiile</a> <span class="required">*</span></span>
									</label>
								</p>

								<p class="woocommerce-form-row form-row">
									<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
									<button type="submit" class="woocommerce-Button button" name="register" value="Creează cont">Creează cont</button>
								</p>

								<?php do_action( 'woocommerce_register_form_end' ); ?>
							</form>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<?php do_action( 'martfury_after_login_form' ); ?>
	</div>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
