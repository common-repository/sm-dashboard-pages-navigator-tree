<?php
/**
 * Main App File
 *
 * @package    WordPress
 * @subpackage Sm_dashboard_pages_navigator_tree
 */

namespace SM\Pages_Navigator\Admin;

/**
 * Class App
 */
class App {

	/**
	 * Plugins class object installed directory on the server.
	 *
	 * @var string $installed_dir Installed server directory.
	 */
	public static $installed_dir;

	/**
	 * Plugins URL for access to any static files or assets like css, js, or media.
	 *
	 * @var string $installed_url Installed URL.
	 */
	public static $installed_url;

	/**
	 * If plugin_data is built, this represents the version number defined the the main plugin file meta.
	 *
	 * @var string $version Version.
	 */
	public static $version;

	/**
	 * Add auth'd/admin functionality via new Class() instantiation, add_action() and add_filter() in this method.
	 *
	 * @param string $installed_dir Installed server directory.
	 * @param string $installed_url Installed URL.
	 * @param string $version       Version.
	 */
	public function __construct( $installed_dir, $installed_url, $version ) {
		static::$installed_dir = $installed_dir;
		static::$installed_url = $installed_url;
		static::$version       = $version;

		// Register the new dashboard widget and dependencies.
		add_action( 'wp_dashboard_setup', [ '\\SM\\Pages_Navigator\\Admin\\Navigator', 'register_widgets' ] );
		add_action( 'admin_print_styles', [ get_called_class(), 'sm_pagetree_admin_styles' ] );
		add_action( 'admin_enqueue_scripts', [ get_called_class(), 'sm_pagetree_admin_scripts' ] );

	}

	/**
	 * Enqueue admin styles
	 */
	public static function sm_pagetree_admin_styles() {
		if ( did_action( 'wp_dashboard_setup' ) > 0 ) {
			wp_enqueue_style( 'sm-pagetree-admin-styles', static::$installed_url . 'assets/css/sm-pagetree-admin-styles.css', [], '1.0.0', 'all' );
		}
	}

	/**
	 * Enqueue admin javascript
	 */
	public static function sm_pagetree_admin_scripts() {
		if ( did_action( 'wp_dashboard_setup' ) > 0 ) {
			wp_enqueue_script( 'sm-pagetree-admin-scripts', static::$installed_url . 'assets/js/sm-pagetree-admin-scripts.js', [ 'jquery' ], static::$version, true );
			wp_enqueue_script( 'simple-tree-view', static::$installed_url . 'assets/js/jquery.simpletreeview.js', [ 'jquery' ], static::$version, true );
		}
	}
}
