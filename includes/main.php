<?php
/**
 * Main Class
 *
 * @package SimpleCalendar
 */
namespace SimpleCalendar;

use SimpleCalendar\Admin\License_Manager;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Simple Calendar plugin.
 */
final class Plugin
{
	/**
	 * Plugin name.
	 *
	 * @access public
	 * @var string
	 */
	public static $name = 'Simple Calendar';

	/**
	 * Plugin version.
	 *
	 * @access public
	 * @var string
	 */
	public static $version = SIMPLE_CALENDAR_VERSION;

	/**
	 * Plugin homepage.
	 *
	 * @access public
	 * @var string
	 */
	protected static $homepage = 'https://simplecalendar.io';

	/**
	 * Locale.
	 *
	 * @access public
	 * @var string
	 */
	public $locale = 'en_US';

	/**
	 * Objects factory.
	 *
	 * @access public
	 * @var Objects
	 */
	public $objects = null;

	/**
	 * The single instance of this class.
	 *
	 * @access protected
	 * @var Plugin
	 */
	protected static $_instance = null;

	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone()
	{
		_doing_it_wrong(__FUNCTION__, 'Cloning the main instance of this plugin is forbidden.', '1.0.0');
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup()
	{
		_doing_it_wrong(__FUNCTION__, 'Unserializing instances of this plugin is forbidden.', '1.0.0');
	}

	/**
	 * Plugin constructor.
	 *
	 * @final
	 */
	public function __construct()
	{
		// XTEC ************ AFEGIT - Load localization
		// 2016.06.20 @sarjona
		load_plugin_textdomain('google-calendar-events', false, dirname(plugin_basename(SIMPLE_CALENDAR_MAIN_FILE)) . '/i18n/');
		// ************ FI

		// Load plugin.
		require_once 'autoload.php';
		$this->locale = apply_filters('plugin_locale', get_locale(), 'google-calendar-events');
		$this->load();

		// Installation hooks.
		register_activation_hook(SIMPLE_CALENDAR_MAIN_FILE, ['SimpleCalendar\Installation', 'activate']);
		register_deactivation_hook(SIMPLE_CALENDAR_MAIN_FILE, ['SimpleCalendar\Installation', 'deactivate']);

		// Do update call here.
		add_action('admin_init', [$this, 'update'], 999);

		// Init hooks.
		add_action('init', [$this, 'init'], 5);
		add_action('admin_init', [$this, 'register_settings'], 5);

		//Oauth Helper init
		add_action('init', [$this, 'oauth_helper_init'], 5);

		// Upon plugin loaded action hook.
		do_action('simcal_loaded');
	}

	/**
	 * Load plugin.
	 *
	 * @since 3.0.0
	 */
	public function load()
	{
		// Functions shared in both back end and front end.
		include_once 'functions/shared.php';

		// Init custom post types and taxonomies.
		new Post_Types();

		// Load back end.
		if (is_admin()) {
			$this->load_admin();
		} else {
			// Load front end scripts and styles.
			new Assets();
		}

		// Front facing ajax callbacks.
		new Ajax();

		// Add Shortcodes.
		new Shortcodes();

		// Add Widgets.
		new Widgets();

		// Deprecated functions for backwards compatibility.
		include_once 'functions/deprecated.php';
	}

	/**
	 * Load plugin admin.
	 *
	 * @since 3.0.0
	 */
	public function load_admin()
	{
		// Back end only svg functions.
		include_once 'functions/admin-svg.php';

		// Back end only functions.
		include_once 'functions/admin.php';

		// Display admin notices.
		new Admin\Notices();

		// Load back end scripts and styles.
		new Admin\Assets();

		// Custom content handling.
		new Admin\Post_Types();

		// Init menus and settings.
		new Admin\Menus();

		if (defined('DOING_AJAX')) {
			// Admin ajax callbacks.
			new Admin\Ajax();
		}
	}

	/**
	 * Init plugin when WordPress initializes.
	 *
	 * @since 3.0.0
	 */
	public function init()
	{
		// Before init action hook.
		do_action('before_simcal_init');

		// Set up localization.
		add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);

		// Init objects factory.
		$this->objects = new Objects();

		// Upon init action hook.
		do_action('simcal_init');
	}

	/**
	 * Loads the plugin textdomain for translation.
	 *
	 * @since 3.1.3
	 */
	public function load_plugin_textdomain()
	{
		load_plugin_textdomain(
			'google-calendar-events',
			false,
			dirname(plugin_basename(SIMPLE_CALENDAR_MAIN_FILE)) . '/i18n/'
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 3.0.0
	 */
	public function register_settings()
	{
		if (
			(!empty($_POST) && is_admin() && !defined('DOING_AJAX')) ||
			(isset($_GET['page']) && 'simple-calendar_settings' === $_GET['page'])
		) {
			$settings = new Admin\Pages();
			$settings->register_settings($settings->get_settings());
		}
	}

	/**
	 * Register plugin settings.
	 *
	 * @since 3.4.1
	 */
	public function oauth_helper_init()
	{
		if (defined('SIMPLE_CALENDAR_GOOGLE_PRO_VERSION') || defined('SIMPLE_CALENDAR_APPOINTMENT_VERSION')) {
			if (defined('SC_OAUTH_HELPER_VERSION')) {
				add_action('admin_notices', function () {
					echo '<div class="error"><p>' .
						sprintf(
							__(
								'The Simple Calendar plugin now includes the features previously provided by the Google Calendar OAuth Helper add-on. Please deactivate and remove the OAuth Helper add-on to avoid redundancy.',
								'google-calendar-events'
							),
							simcal_ga_campaign_url(simcal_get_url('addons'), 'core-plugin', 'admin-notice')
						) .
						'</p></div>';
				});
			} else {
				require plugin_dir_path(__FILE__) . 'oauthhelper/oauth-service-actions.php';
				require plugin_dir_path(__FILE__) . 'oauthhelper/class-oauth-service.php';
			}
		}
	}
	/**
	 * Get Ajax URL.
	 *
	 * @since  3.0.0
	 *
	 * @return string
	 */
	public function ajax_url()
	{
		return admin_url('admin-ajax.php', 'relative');
	}

	/**
	 * Get URL.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $case Requested url.
	 *
	 * @return string
	 */
	public function get_url($case)
	{
		switch ($case) {
			case 'codex':
			case 'apidocs':
				return 'http://codex.simplecalendar.io';
			case 'addons':
				return self::$homepage . '/addons/';
			case 'gcal-pro':
				return self::$homepage . '/addons/google-calendar-pro/';
			case 'fullcal':
				return self::$homepage . '/addons/full-calendar/';
			case 'docs':
				return 'http://docs.simplecalendar.io';
			case 'github':
				return 'https://github.com/Xtendify/Simple-Calendar';
			case 'support':
				return 'https://wordpress.org/support/plugin/google-calendar-events';
			case 'gdev-console':
				return 'https://console.developers.google.com';
			case 'home':
			default:
				return self::$homepage;
		}
	}

	/**
	 * Run upgrade scripts.
	 *
	 * @since 3.0.0
	 */
	public static function update()
	{
		$update = new Update(SIMPLE_CALENDAR_VERSION);
	}
}

/**
 * Simple Calendar.
 *
 * @return Plugin
 */
function plugin()
{
	return Plugin::get_instance();
}

plugin();
