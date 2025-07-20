<?php

/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('HELLO_ELEMENTOR_VERSION', '3.4.3');
define('EHP_THEME_SLUG', 'hello-elementor');

define('HELLO_THEME_PATH', get_template_directory());
define('HELLO_THEME_URL', get_template_directory_uri());
define('HELLO_THEME_ASSETS_PATH', HELLO_THEME_PATH . '/assets/');
define('HELLO_THEME_ASSETS_URL', HELLO_THEME_URL . '/assets/');
define('HELLO_THEME_SCRIPTS_PATH', HELLO_THEME_ASSETS_PATH . 'js/');
define('HELLO_THEME_SCRIPTS_URL', HELLO_THEME_ASSETS_URL . 'js/');
define('HELLO_THEME_STYLE_PATH', HELLO_THEME_ASSETS_PATH . 'css/');
define('HELLO_THEME_STYLE_URL', HELLO_THEME_ASSETS_URL . 'css/');
define('HELLO_THEME_IMAGES_PATH', HELLO_THEME_ASSETS_PATH . 'images/');
define('HELLO_THEME_IMAGES_URL', HELLO_THEME_ASSETS_URL . 'images/');

if (! isset($content_width)) {
	$content_width = 800; // Pixels.
}

if (! function_exists('hello_elementor_setup')) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup()
	{
		if (is_admin()) {
			hello_maybe_update_theme_version_in_db();
		}

		if (apply_filters('hello_elementor_register_menus', true)) {
			register_nav_menus(['menu-1' => esc_html__('Header', 'hello-elementor')]);
			register_nav_menus(['menu-2' => esc_html__('Footer', 'hello-elementor')]);
		}

		if (apply_filters('hello_elementor_post_type_support', true)) {
			add_post_type_support('page', 'excerpt');
		}

		if (apply_filters('hello_elementor_add_theme_support', true)) {
			add_theme_support('post-thumbnails');
			add_theme_support('automatic-feed-links');
			add_theme_support('title-tag');
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);
			add_theme_support('align-wide');
			add_theme_support('responsive-embeds');

			/*
			 * Editor Styles
			 */
			add_theme_support('editor-styles');
			add_editor_style('editor-styles.css');

			/*
			 * WooCommerce.
			 */
			if (apply_filters('hello_elementor_add_woocommerce_support', true)) {
				// WooCommerce in general.
				add_theme_support('woocommerce');
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support('wc-product-gallery-zoom');
				// lightbox.
				add_theme_support('wc-product-gallery-lightbox');
				// swipe.
				add_theme_support('wc-product-gallery-slider');
			}
		}
	}
}
add_action('after_setup_theme', 'hello_elementor_setup');

function hello_maybe_update_theme_version_in_db()
{
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option($theme_version_option_name);

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if (! $hello_theme_db_version || version_compare($hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<')) {
		update_option($theme_version_option_name, HELLO_ELEMENTOR_VERSION);
	}
}

if (! function_exists('hello_elementor_display_header_footer')) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer()
	{
		$hello_elementor_header_footer = true;

		return apply_filters('hello_elementor_header_footer', $hello_elementor_header_footer);
	}
}

if (! function_exists('hello_elementor_scripts_styles')) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles()
	{
		$min_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

		if (apply_filters('hello_elementor_enqueue_style', true)) {
			wp_enqueue_style(
				'hello-elementor',
				get_template_directory_uri() . '/style' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if (apply_filters('hello_elementor_enqueue_theme_style', true)) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				get_template_directory_uri() . '/theme' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if (hello_elementor_display_header_footer()) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				get_template_directory_uri() . '/header-footer' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action('wp_enqueue_scripts', 'hello_elementor_scripts_styles');

if (! function_exists('hello_elementor_register_elementor_locations')) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations($elementor_theme_manager)
	{
		if (apply_filters('hello_elementor_register_elementor_locations', true)) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action('elementor/theme/register_locations', 'hello_elementor_register_elementor_locations');

if (! function_exists('hello_elementor_content_width')) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width()
	{
		$GLOBALS['content_width'] = apply_filters('hello_elementor_content_width', 800);
	}
}
add_action('after_setup_theme', 'hello_elementor_content_width', 0);

if (! function_exists('hello_elementor_add_description_meta_tag')) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag()
	{
		if (! apply_filters('hello_elementor_description_meta_tag', true)) {
			return;
		}

		if (! is_singular()) {
			return;
		}

		$post = get_queried_object();
		if (empty($post->post_excerpt)) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($post->post_excerpt)) . '">' . "\n";
	}
}
add_action('wp_head', 'hello_elementor_add_description_meta_tag');

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if (! function_exists('hello_elementor_customizer')) {
	// Customizer controls
	function hello_elementor_customizer()
	{
		if (! is_customize_preview()) {
			return;
		}

		if (! hello_elementor_display_header_footer()) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action('init', 'hello_elementor_customizer');

if (! function_exists('hello_elementor_check_hide_title')) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title($val)
	{
		if (defined('ELEMENTOR_VERSION')) {
			$current_doc = Elementor\Plugin::instance()->documents->get(get_the_ID());
			if ($current_doc && 'yes' === $current_doc->get_settings('hide_title')) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter('hello_elementor_page_title', 'hello_elementor_check_hide_title');

// Custom roles: Manager and Lector
function custom_add_user_roles()
{
	add_role('manager', 'Manager', [
		'read' => true,
		'edit_posts' => true,
		'edit_pages' => true,
		'edit_others_posts' => true,
		'publish_posts' => true,
		'upload_files' => true,
		'list_users' => true,
		'edit_users' => true,
	]);

	add_role('lector', 'Lector', [
		'read' => true,
		'edit_posts' => true,
		'upload_files' => true,
		'delete_posts' => false,
	]);

	// Remove default roles except Administrator
	remove_role('editor');
	remove_role('author');
	remove_role('contributor');
	remove_role('subscriber');
}
add_action('init', 'custom_add_user_roles');

// Register a [logout_button] shortcode
add_shortcode('logout_button', function ($atts) {
	// Set up defaults; redirect defaults to the site’s home_url()
	$atts = shortcode_atts([
		'redirect' => home_url(),        // ← defaults here!
		'class'    => 'elementor-button',
		'label'    => 'Logout',
	], $atts, 'logout_button');

	// Build the logout URL
	$url = wp_logout_url($atts['redirect']);

	// Return a styled link
	return sprintf(
		'<a href="%s" class="%s">%s</a>',
		esc_url($url),
		esc_attr($atts['class']),
		esc_html($atts['label'])
	);
});

// 1a. Redirect on wrong credentials
add_action('wp_login_failed', 'custom_frontend_login_fail', 10, 2);
function custom_frontend_login_fail($username, $error)
{
	$login_page = home_url('/login/');
	$referrer   = wp_get_referer();
	// Only intercept front-end attempts (not wp-login.php or wp-admin)
	if (
		$referrer
		&& ! stristr($referrer, 'wp-login.php')
		&& ! stristr($referrer, 'wp-admin')
	) {
		wp_redirect(add_query_arg('login', 'failed', $login_page));
		exit;
	}
}

// 1b. Redirect on empty username/password
add_action('authenticate', 'custom_frontend_login_empty', 1, 3);
function custom_frontend_login_empty($user, $username, $password)
{
	if (empty($username) || empty($password)) {
		$login_page = home_url('/login/');
		wp_redirect(add_query_arg('login', 'empty', $login_page));
		exit;
	}
	return $user;
}

// 2. Shortcode to show our error box
add_shortcode('login_errors', function () {
	if (! empty($_GET['login'])) {
		$icon = '❗ '; // or just '!'
		if ($_GET['login'] === 'failed') {
			return '<div class="elementor-message elementor-message-danger">'
				. $icon
				. 'Invalid username or password. Please try again.'
				. '</div>';
		}
		if ($_GET['login'] === 'empty') {
			return '<div class="elementor-message elementor-message-danger">'
				. $icon
				. 'Both fields are required.'
				. '</div>';
		}
	}
	return '';
});

// 1) Display the front-end lost password form
add_shortcode('custom_lostpassword_form', function () {
	if (is_user_logged_in()) {
		return '<p class="elementor-message elementor-message-info">You are already logged in.</p>';
	}

	ob_start();

	// 1) Grab & decode the raw error text (if any)

	$raw_error = ! empty($_GET['lp_error'])
		? strip_tags(urldecode(wp_unslash($_GET['lp_error'])))
		: '';

	$raw_success = ! empty($_GET['lp_success']);

	// Then display:
	if ($raw_error) {
		echo '<div class="elementor-message elementor-message-danger">'
			. esc_html($raw_error)
			. '</div>';
	} elseif ($raw_success) {
		echo '<div class="elementor-message elementor-message-success">'
			. 'Check your email for the password reset link.'
			. '</div>';
	}


	// …the rest of your form HTML follows unchanged…
?>
	<form name="lostpasswordform" id="lostpasswordform"
		action="<?php echo esc_url(home_url('/lost-password/')); ?>"
		method="post">
		<p>
			<label for="user_login">Username or Email Address</label><br>
			<input type="text" name="user_login" id="user_login"
				class="elementor-field elementor-size-sm" size="20" required>
		</p>
		<?php do_action('lostpassword_form'); ?>
		<div class="elementor-field-group elementor-column elementor-field-type-submit elementor-col-100">
			<button type="submit" class="elementor-size-sm elementor-button" name="wp-submit">
				<span class="elementor-button-text">Reset Password</span>
			</button>
		</div>
	</form>
<?php

	return ob_get_clean();
});


// 2) Process the form and send WP’s reset link
add_action('template_redirect', function () {
	if (
		'POST' === $_SERVER['REQUEST_METHOD']
		&& is_page('lost-password')     // adjust slug if yours differs
		&& isset($_POST['user_login'])
	) {
		// Pass the submitted username/email into retrieve_password()
		$errors = retrieve_password(sanitize_text_field(wp_unslash($_POST['user_login'])));

		if (is_wp_error($errors)) {
			// URL‐encode the real WP_Error message
			$msg = urlencode($errors->get_error_message());
			wp_redirect(add_query_arg('lp_error', $msg, home_url('/lost-password/')));
		} else {
			wp_redirect(add_query_arg('lp_success', '1', home_url('/lost-password/')));
		}
		exit;
	}
});


// 3) Point all “Lost your password?” links to our page
add_filter('lostpassword_url', function ($url, $redirect) {
	return home_url('/lost-password/');
}, 10, 2);

// 4) Block any direct wp-login.php?action=lostpassword calls
add_action('login_form_lostpassword', function () {
	wp_redirect(home_url('/lost-password/'));
	exit;
});


add_filter('nav_menu_css_class', function ($classes, $item) {
	// check by your CSS class
	if (in_array('menu-login', $classes) && is_user_logged_in()) {
		$classes[] = 'hide';
	}
	if (in_array('user-menu-toggle', $classes) && ! is_user_logged_in()) {
		$classes[] = 'hide';
	}
	return $classes;
}, 10, 2);

// hide via CSS
add_action('wp_enqueue_scripts', function () {
	echo "<style>.hide { display: none !important; }</style>";
});

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if (! function_exists('hello_elementor_body_open')) {
	function hello_elementor_body_open()
	{
		wp_body_open();
	}
}

require HELLO_THEME_PATH . '/theme.php';

HelloTheme\Theme::instance();
