<?php
/**
 * Migrate Import Export WooCommerce Store with Excel
 *
 * @version 3.0.0
 * @since   3.0.0
 *
 * @author  WPFactory
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPFactory_WC_PROCM' ) ) :

final class WPFactory_WC_PROCM {

	/**
	 * Plugin version.
	 *
	 * @var   string
	 * @since 3.0.0
	 */
	public $version = WPFACTORY_WC_PROCM_VERSION;

	/**
	 * @var   WPFactory_WC_PROCM The single instance of the class
	 * @since 3.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main WPFactory_WC_PROCM Instance.
	 *
	 * Ensures only one instance of WPFactory_WC_PROCM is loaded or can be loaded.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 *
	 * @static
	 * @return  WPFactory_WC_PROCM - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * WPFactory_WC_PROCM Constructor.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 *
	 * @access  public
	 */
	function __construct() {

		// Check for active WooCommerce plugin
		if ( ! function_exists( 'WC' ) ) {
			return;
		}

	}

	/**
	 * plugin_url.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 *
	 * @return  string
	 */
	function plugin_url() {
		return untrailingslashit( plugin_dir_url( WPFACTORY_WC_PROCM_FILE ) );
	}

	/**
	 * plugin_path.
	 *
	 * @version 3.0.0
	 * @since   3.0.0
	 *
	 * @return  string
	 */
	function plugin_path() {
		return untrailingslashit( plugin_dir_path( WPFACTORY_WC_PROCM_FILE ) );
	}

}

endif;
