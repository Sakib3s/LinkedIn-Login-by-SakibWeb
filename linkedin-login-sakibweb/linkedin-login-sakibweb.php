<?php
/**
 * Plugin Name:       LinkedIn Login by SakibWeb
 * Description:       Simple LinkedIn OpenID Connect social login for WordPress, WooCommerce and Tutor LMS.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Sakib Hasan
 * Author URI:        https://sakibweb.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       linkedin-login-by-sakibweb
 *
 * @package LinkedInLoginSakibWeb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sakibll_LinkedIn_Login {

	const OPTION_KEY = 'sakibll_settings';
	const META_ID    = '_sakibll_linkedin_id';
	const META_PIC   = '_sakibll_linkedin_picture';

	const AUTH_URL  = 'https://www.linkedin.com/oauth/v2/authorization';
	const TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';
	const USERINFO  = 'https://api.linkedin.com/v2/userinfo';

	public function __construct() {

		// Admin.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			array( $this, 'action_links' )
		);

		// OAuth start + callback.
		add_action( 'init', array( $this, 'handle_request' ) );

		// Shortcode.
		add_shortcode( 'linkedin_login', array( $this, 'shortcode' ) );

		// Default WordPress login/registration pages.
		add_action( 'login_form', array( $this, 'login_form_button' ) );
		add_action( 'register_form', array( $this, 'login_form_button' ) );

		// WooCommerce forms (only fire if WooCommerce is active).
		add_action( 'woocommerce_login_form_end', array( $this, 'login_form_button' ) );
		add_action( 'woocommerce_register_form_end', array( $this, 'login_form_button' ) );

		// Tutor LMS forms (only fire if Tutor LMS is active).
		add_action( 'tutor_student_reg_form_end', array( $this, 'login_form_button' ) );
		add_action( 'tutor_login_form_end', array( $this, 'login_form_button' ) );

		// Styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_styles' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'frontend_styles' ) );
	}

	/**
	 * Get plugin settings.
	 */
	private function settings() {

		$defaults = array(
			'client_id'     => '',
			'client_secret' => '',
			'button_text'   => __( 'Continue with LinkedIn', 'linkedin-login-by-sakibweb' ),
		);

		$settings = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Callback URL.
	 */
	public function callback_url() {

		return add_query_arg( 'linkedin_callback', '1', home_url( '/' ) );
	}

	/**
	 * URL that starts the login flow.
	 */
	private function start_url() {

		return add_query_arg( 'sakibll_start', '1', home_url( '/' ) );
	}

	/**
	 * Settings link on the Plugins screen.
	 *
	 * @param array $links Existing links.
	 */
	public function action_links( $links ) {

		$url = admin_url( 'options-general.php?page=sakib-linkedin-login' );

		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'linkedin-login-by-sakibweb' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Admin menu.
	 */
	public function admin_menu() {

		add_options_page(
			__( 'LinkedIn Login', 'linkedin-login-by-sakibweb' ),
			__( 'LinkedIn Login', 'linkedin-login-by-sakibweb' ),
			'manage_options',
			'sakib-linkedin-login',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {

		register_setting(
			'sakibll_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => array(),
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Raw input.
	 */
	public function sanitize_settings( $input ) {

		if ( ! is_array( $input ) ) {
			$input = array();
		}

		return array(
			'client_id'     => isset( $input['client_id'] )
				? sanitize_text_field( $input['client_id'] )
				: '',

			'client_secret' => isset( $input['client_secret'] )
				? trim( sanitize_text_field( $input['client_secret'] ) )
				: '',

			'button_text'   => ! empty( $input['button_text'] )
				? sanitize_text_field( $input['button_text'] )
				: __( 'Continue with LinkedIn', 'linkedin-login-by-sakibweb' ),
		);
	}

	/**
	 * Admin styles (settings page only).
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function admin_styles( $hook ) {

		if ( 'settings_page_sakib-linkedin-login' !== $hook ) {
			return;
		}

		wp_register_style( 'sakibll-admin', false, array(), '1.0.0' );
		wp_enqueue_style( 'sakibll-admin' );
		wp_add_inline_style(
			'sakibll-admin',
			'.sakibll-box{background:#fff;border:1px solid #ccd0d4;padding:20px;max-width:850px;margin-top:20px}
			.sakibll-note{background:#f0f6fc;border-left:4px solid #0a66c2;padding:15px 20px;max-width:850px;margin-top:20px}'
		);
	}

	/**
	 * Settings page.
	 */
	public function settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->settings();
		?>

		<div class="wrap">

			<h1><?php esc_html_e( 'LinkedIn Login', 'linkedin-login-by-sakibweb' ); ?></h1>

			<p>
				<?php esc_html_e( 'Connect your WordPress website with LinkedIn OpenID Connect.', 'linkedin-login-by-sakibweb' ); ?>
			</p>

			<div class="sakibll-box">

				<form method="post" action="options.php">

				<?php settings_fields( 'sakibll_settings_group' ); ?>

				<h2><?php esc_html_e( 'LinkedIn App Configuration', 'linkedin-login-by-sakibweb' ); ?></h2>

				<p>
					<?php
					echo wp_kses(
						__( 'Create/configure your LinkedIn application and enable <strong>Sign In with LinkedIn using OpenID Connect</strong>.', 'linkedin-login-by-sakibweb' ),
						array( 'strong' => array() )
					);
					?>
				</p>

				<table class="form-table" role="presentation">

					<tr>
						<th scope="row">
							<label for="sakibll_client_id"><?php esc_html_e( 'Client ID', 'linkedin-login-by-sakibweb' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="sakibll_client_id"
								name="<?php echo esc_attr( self::OPTION_KEY ); ?>[client_id]"
								value="<?php echo esc_attr( $settings['client_id'] ); ?>"
								class="regular-text"
								autocomplete="off"
							/>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="sakibll_client_secret"><?php esc_html_e( 'Client Secret', 'linkedin-login-by-sakibweb' ); ?></label>
						</th>
						<td>
							<input
								type="password"
								id="sakibll_client_secret"
								name="<?php echo esc_attr( self::OPTION_KEY ); ?>[client_secret]"
								value="<?php echo esc_attr( $settings['client_secret'] ); ?>"
								class="regular-text"
								autocomplete="new-password"
							/>
							<p class="description">
								<?php esc_html_e( 'Keep this secret. It is stored in your site database and never printed on the front end.', 'linkedin-login-by-sakibweb' ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="sakibll_button_text"><?php esc_html_e( 'Button Text', 'linkedin-login-by-sakibweb' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="sakibll_button_text"
								name="<?php echo esc_attr( self::OPTION_KEY ); ?>[button_text]"
								value="<?php echo esc_attr( $settings['button_text'] ); ?>"
								class="regular-text"
							/>
						</td>
					</tr>

				</table>

				<h2><?php esc_html_e( 'Redirect URL', 'linkedin-login-by-sakibweb' ); ?></h2>

				<p><?php esc_html_e( 'Add this exact URL to your LinkedIn App:', 'linkedin-login-by-sakibweb' ); ?></p>

				<label class="screen-reader-text" for="sakibll_redirect_url">
					<?php esc_html_e( 'Redirect URL', 'linkedin-login-by-sakibweb' ); ?>
				</label>
				<input
					type="text"
					id="sakibll_redirect_url"
					readonly
					value="<?php echo esc_attr( $this->callback_url() ); ?>"
					class="large-text code"
				/>

				<h2><?php esc_html_e( 'Scopes', 'linkedin-login-by-sakibweb' ); ?></h2>

				<code>openid profile email</code>

				<?php submit_button( __( 'Save LinkedIn Settings', 'linkedin-login-by-sakibweb' ) ); ?>

				</form>

			</div>

			<div class="sakibll-note">

				<h3><?php esc_html_e( 'Shortcode', 'linkedin-login-by-sakibweb' ); ?></h3>

				<p><?php esc_html_e( 'Use this anywhere on your website:', 'linkedin-login-by-sakibweb' ); ?></p>

				<code>[linkedin_login]</code>

			</div>

		</div>

		<?php
	}

	/**
	 * Whether the plugin has credentials configured.
	 */
	private function is_configured() {

		$settings = $this->settings();

		return ! empty( $settings['client_id'] ) && ! empty( $settings['client_secret'] );
	}

	/**
	 * Build the LinkedIn authorization URL (creates a one-time state).
	 */
	private function authorization_url() {

		$settings = $this->settings();

		$state = wp_generate_password( 32, false, false );

		set_transient(
			'sakibll_state_' . $state,
			array( 'created' => time() ),
			10 * MINUTE_IN_SECONDS
		);

		return add_query_arg(
			array(
				'response_type' => 'code',
				'client_id'     => $settings['client_id'],
				'redirect_uri'  => $this->callback_url(),
				'state'         => $state,
				'scope'         => 'openid profile email',
			),
			self::AUTH_URL
		);
	}

	/**
	 * Shortcode.
	 */
	public function shortcode() {

		if ( is_user_logged_in() ) {
			return '<div class="sakibll-logged-in">' . esc_html__( 'You are already logged in.', 'linkedin-login-by-sakibweb' ) . '</div>';
		}

		if ( ! $this->is_configured() ) {

			if ( current_user_can( 'manage_options' ) ) {
				return '<div class="sakibll-error">' . esc_html__( 'LinkedIn login is not configured yet.', 'linkedin-login-by-sakibweb' ) . '</div>';
			}

			return '';
		}

		$settings = $this->settings();

		ob_start();
		?>

		<div class="sakibll-wrapper">
			<a class="sakibll-button" href="<?php echo esc_url( $this->start_url() ); ?>" rel="nofollow">
				<span class="sakibll-linkedin-icon" aria-hidden="true">in</span>
				<span><?php echo esc_html( $settings['button_text'] ); ?></span>
			</a>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Button on login/registration forms.
	 */
	public function login_form_button() {

		static $printed = array();

		if ( is_user_logged_in() ) {
			return;
		}

		/*
		 * Some plugins (Tutor LMS, WooCommerce) fire both the core hook and
		 * their own hook inside one form. Print once per form type.
		 */
		$register_hooks = array(
			'register_form',
			'woocommerce_register_form_end',
			'tutor_student_reg_form_end',
		);

		$type = in_array( current_action(), $register_hooks, true )
			? 'register'
			: 'login';

		if ( isset( $printed[ $type ] ) ) {
			return;
		}

		$printed[ $type ] = true;

		echo '<div class="sakibll-login-page">';
		echo wp_kses_post( $this->shortcode() );
		echo '</div>';
	}

	/**
	 * Route plugin requests (start + OAuth callback).
	 */
	public function handle_request() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- OAuth flow, protected by the one-time "state" parameter.
		if ( isset( $_GET['sakibll_start'] ) ) {
			$this->handle_start();
		}

		if ( isset( $_GET['linkedin_callback'] ) ) {
			$this->handle_callback();
		}
		// phpcs:enable
	}

	/**
	 * Redirect the visitor to LinkedIn.
	 */
	private function handle_start() {

		if ( is_user_logged_in() || ! $this->is_configured() ) {
			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		// External LinkedIn URL, built by this plugin only.
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		wp_redirect( $this->authorization_url() );
		exit;
	}

	/**
	 * OAuth callback.
	 */
	private function handle_callback() {

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- OAuth callback, verified by the "state" transient.
		if ( isset( $_GET['error'] ) ) {

			$error = isset( $_GET['error_description'] )
				? sanitize_text_field( wp_unslash( $_GET['error_description'] ) )
				: sanitize_text_field( wp_unslash( $_GET['error'] ) );

			$this->error_page( __( 'LinkedIn Login Failed', 'linkedin-login-by-sakibweb' ), $error );
		}

		if ( empty( $_GET['code'] ) || empty( $_GET['state'] ) ) {

			$this->error_page(
				__( 'LinkedIn Login Failed', 'linkedin-login-by-sakibweb' ),
				__( 'Authorization code or state is missing.', 'linkedin-login-by-sakibweb' )
			);
		}

		$code  = sanitize_text_field( wp_unslash( $_GET['code'] ) );
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ) );
		// phpcs:enable

		$state_data = get_transient( 'sakibll_state_' . $state );

		delete_transient( 'sakibll_state_' . $state );

		if ( ! $state_data ) {

			$this->error_page(
				__( 'Security Check Failed', 'linkedin-login-by-sakibweb' ),
				__( 'The OAuth state is invalid or expired. Please try again.', 'linkedin-login-by-sakibweb' )
			);
		}

		$token = $this->exchange_code_for_token( $code );

		if ( is_wp_error( $token ) ) {
			$this->error_page( __( 'LinkedIn Token Error', 'linkedin-login-by-sakibweb' ), $token->get_error_message() );
		}

		if ( empty( $token['access_token'] ) ) {

			$this->error_page(
				__( 'LinkedIn Token Error', 'linkedin-login-by-sakibweb' ),
				__( 'LinkedIn did not return an access token.', 'linkedin-login-by-sakibweb' )
			);
		}

		$profile = $this->get_linkedin_profile( $token['access_token'] );

		if ( is_wp_error( $profile ) ) {
			$this->error_page( __( 'LinkedIn Profile Error', 'linkedin-login-by-sakibweb' ), $profile->get_error_message() );
		}

		$this->login_or_create_user( $profile );
	}

	/**
	 * Exchange authorization code for access token.
	 *
	 * @param string $code Authorization code.
	 */
	private function exchange_code_for_token( $code ) {

		$settings = $this->settings();

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
				'body'    => array(
					'grant_type'    => 'authorization_code',
					'code'          => $code,
					'redirect_uri'  => $this->callback_url(),
					'client_id'     => $settings['client_id'],
					'client_secret' => $settings['client_secret'],
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {

			$message = ! empty( $data['error_description'] )
				? $data['error_description']
				: __( 'LinkedIn rejected the token request.', 'linkedin-login-by-sakibweb' );

			return new WP_Error( 'linkedin_token_error', $message );
		}

		if ( ! is_array( $data ) ) {

			return new WP_Error(
				'linkedin_invalid_response',
				__( 'Invalid response received from LinkedIn.', 'linkedin-login-by-sakibweb' )
			);
		}

		return $data;
	}

	/**
	 * Get profile using LinkedIn OIDC UserInfo endpoint.
	 *
	 * @param string $access_token Access token.
	 */
	private function get_linkedin_profile( $access_token ) {

		$response = wp_remote_get(
			self::USERINFO,
			array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {

			$message = ! empty( $data['message'] )
				? $data['message']
				: __( 'Unable to retrieve your LinkedIn profile.', 'linkedin-login-by-sakibweb' );

			return new WP_Error( 'linkedin_profile_error', $message );
		}

		if ( ! is_array( $data ) || empty( $data['sub'] ) ) {

			return new WP_Error(
				'linkedin_profile_missing',
				__( 'LinkedIn did not return a valid user profile.', 'linkedin-login-by-sakibweb' )
			);
		}

		return $data;
	}

	/**
	 * Login or create WordPress user.
	 *
	 * @param array $profile LinkedIn userinfo.
	 */
	private function login_or_create_user( $profile ) {

		$linkedin_id = sanitize_text_field( $profile['sub'] );

		$email = ! empty( $profile['email'] ) ? sanitize_email( $profile['email'] ) : '';

		// LinkedIn returns email_verified as a boolean (or the string "true").
		$email_verified = isset( $profile['email_verified'] )
			&& filter_var( $profile['email_verified'], FILTER_VALIDATE_BOOLEAN );

		$name        = ! empty( $profile['name'] ) ? sanitize_text_field( $profile['name'] ) : '';
		$given_name  = ! empty( $profile['given_name'] ) ? sanitize_text_field( $profile['given_name'] ) : '';
		$family_name = ! empty( $profile['family_name'] ) ? sanitize_text_field( $profile['family_name'] ) : '';
		$picture     = ! empty( $profile['picture'] ) ? esc_url_raw( $profile['picture'] ) : '';

		// First find user by LinkedIn ID.
		$user_query = new WP_User_Query(
			array(
				'meta_key'   => self::META_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $linkedin_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'number'     => 1,
			)
		);

		$users = $user_query->get_results();

		if ( ! empty( $users ) ) {

			$user = $users[0];

		} elseif ( $email && $email_verified ) {

			// Only trust the email for linking when LinkedIn has verified it.
			$user = get_user_by( 'email', $email );

			if ( ! $user ) {

				/**
				 * Filters whether LinkedIn login may create new accounts.
				 * Defaults to the "Anyone can register" setting.
				 *
				 * @param bool $allowed Whether registration is allowed.
				 */
				$allowed = apply_filters( 'sakibll_allow_registration', (bool) get_option( 'users_can_register' ) );

				if ( ! $allowed ) {

					$this->error_page(
						__( 'Registration Disabled', 'linkedin-login-by-sakibweb' ),
						__( 'No account exists for this LinkedIn email and new registrations are disabled.', 'linkedin-login-by-sakibweb' )
					);
				}

				$user = $this->create_user( $linkedin_id, $email, $name, $given_name, $family_name );
			}

		} else {

			$this->error_page(
				__( 'Verified Email Required', 'linkedin-login-by-sakibweb' ),
				__( 'LinkedIn did not provide a verified email address. Please verify your email on LinkedIn and try again.', 'linkedin-login-by-sakibweb' )
			);
		}

		if ( ! $user || is_wp_error( $user ) ) {

			$this->error_page(
				__( 'WordPress User Error', 'linkedin-login-by-sakibweb' ),
				__( 'Unable to create or locate your WordPress account.', 'linkedin-login-by-sakibweb' )
			);
		}

		// Update profile information.
		$update = array( 'ID' => $user->ID );

		if ( $given_name ) {
			$update['first_name'] = $given_name;
		}

		if ( $family_name ) {
			$update['last_name'] = $family_name;
		}

		if ( $name ) {
			$update['display_name'] = $name;
		}

		wp_update_user( $update );

		if ( $picture ) {
			update_user_meta( $user->ID, self::META_PIC, $picture );
		}

		update_user_meta( $user->ID, self::META_ID, $linkedin_id );

		// Login user.
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, true, is_ssl() );

		// Core login hook, so other plugins react as for a normal login.
		do_action( 'wp_login', $user->user_login, $user ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook.

		/**
		 * Fires after a successful LinkedIn login.
		 *
		 * @param int   $user_id WordPress user ID.
		 * @param array $profile LinkedIn userinfo.
		 */
		do_action( 'sakibll_linkedin_login', $user->ID, $profile );

		/**
		 * Filters where the user is sent after logging in.
		 *
		 * @param string  $redirect Redirect URL.
		 * @param WP_User $user     Logged in user.
		 */
		$redirect = apply_filters( 'sakibll_login_redirect', home_url( '/' ), $user );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Create WordPress user.
	 *
	 * @param string $linkedin_id LinkedIn subject ID.
	 * @param string $email       Email.
	 * @param string $name        Full name.
	 * @param string $given_name  First name.
	 * @param string $family_name Last name.
	 */
	private function create_user( $linkedin_id, $email, $name, $given_name, $family_name ) {

		$username_base = $given_name ? $given_name : $name;

		if ( ! $username_base ) {
			$username_base = 'linkedin-user';
		}

		$username_base = sanitize_user( $username_base, true );

		if ( ! $username_base ) {
			$username_base = 'linkedin-user';
		}

		$username = $username_base;
		$count    = 1;

		while ( username_exists( $username ) ) {
			$username = $username_base . $count;
			++$count;
		}

		$user_id = wp_create_user(
			$username,
			wp_generate_password( 32, true, true ),
			$email
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$user = new WP_User( $user_id );

		update_user_meta( $user_id, self::META_ID, $linkedin_id );

		// Mark as a Tutor LMS student when Tutor LMS is active.
		if ( function_exists( 'tutor' ) ) {
			update_user_meta( $user_id, '_is_tutor_student', time() );
		}

		return $user;
	}

	/**
	 * Error page.
	 *
	 * @param string $title   Title.
	 * @param string $message Message.
	 */
	private function error_page( $title, $message ) {

		wp_die(
			'<h1>' . esc_html( $title ) . '</h1>' .
			'<p>' . esc_html( $message ) . '</p>' .
			'<p><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Return to website', 'linkedin-login-by-sakibweb' ) . '</a></p>',
			esc_html( $title ),
			array( 'response' => 400 )
		);
	}

	/**
	 * Frontend styles.
	 */
	public function frontend_styles() {

		if ( is_user_logged_in() ) {
			return;
		}

		wp_register_style( 'sakibll-style', false, array(), '1.0.0' );
		wp_enqueue_style( 'sakibll-style' );

		$css = '
			.sakibll-wrapper{margin:15px 0}
			.sakibll-button{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:46px;padding:0 20px;background:#0a66c2;color:#fff !important;border-radius:6px;text-decoration:none !important;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;font-size:15px;font-weight:600;box-sizing:border-box;transition:opacity .2s ease,transform .2s ease}
			.sakibll-button:hover{opacity:.9;transform:translateY(-1px);color:#fff !important}
			.sakibll-linkedin-icon{width:23px;height:23px;display:inline-flex;align-items:center;justify-content:center;background:#fff;color:#0a66c2;border-radius:3px;font-size:16px;font-weight:800;line-height:1}
			.sakibll-login-page{margin-top:18px}
			.sakibll-error{padding:12px;background:#fff3cd;border:1px solid #ffecb5;color:#664d03}
			.sakibll-logged-in{padding:10px 0}
		';

		wp_add_inline_style( 'sakibll-style', $css );
	}
}

new Sakibll_LinkedIn_Login();
