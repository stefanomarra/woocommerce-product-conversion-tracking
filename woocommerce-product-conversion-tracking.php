<?php
/*
Plugin Name: WooCommerce Product Conversion Tracking
Plugin URI: https://www.stefanomarra.com
Description: Add support to woocommerce product specific conversion tracking
Version: 1.0.0
Author: Stefano Marra
Author URI: https://www.stefanomarra.com
License: GPL2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define WCA
if ( ! defined( 'SM_WCPCT_PLUGIN_FILE' ) ) {
	define( 'SM_WCPCT_PLUGIN_FILE', __FILE__ );
}

// Define WCA
if ( ! defined( 'SM_WCPCT_TEXT_DOMAIN' ) ) {
	define( 'SM_WCPCT_TEXT_DOMAIN', 'woocommerce-product-conversion-tracking' );
}

if ( ! class_exists( 'SM_WCPCT' ) ) :

	class SM_WCPCT {

		const PLUGIN_ID = 'sm-woocommerce-product-conversion-tracking';
		const PLUGIN_NAME = 'WooCommerce Product Conversion Tracking';

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $_instance;

		/**
		 * Initialize
		 */
		function __construct() {
			add_action( 'woocommerce_init', array( $this, 'setup' ) );
			add_action( 'plugins_loaded', array( $this, 'load_text_domain' ) );
		}

		/**
		 * Get the plugin url.
		 *
		 * @return string
		 */
		public function plugin_url() {
			return plugin_dir_url( SM_WCPCT_PLUGIN_FILE );
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return plugin_dir_path( SM_WCPCT_PLUGIN_FILE );
		}

		/**
		 * Return the plugin base name
		 *
		 * @return string
		 */
		public function plugin_basename() {
			return plugin_basename( SM_WCPCT_PLUGIN_FILE );
		}

		/**
		 * Load tex domain
		 */
		public function load_text_domain() {
			load_plugin_textdomain( SM_WCPCT_TEXT_DOMAIN, false, dirname( $this->plugin_basename() ) . '/languages' );
		}

		/**
		 * Plugin Setup
		 */
		public function setup() {
			$this->includes();
			$this->init();
		}

		/**
		 * Include required plugin files
		 */
		public function includes() {
			require_once $this->plugin_path() . 'includes/class-wc-product.php';
		}

		/**
		 * Init
		 */
		public function init() {
			add_action( 'wp_head', array(SM_WCPCT_Product(), 'display_tracking_codes'), 100 );
			add_action( 'woocommerce_checkout_order_processed', array(SM_WCPCT_Product(), 'checkout_order_processed'), 10, 3 );

			add_action( 'wp_ajax_sm_wcpct_complete_convertion', array(SM_WCPCT_Product(), 'handle_ajax_conversion_completed') );
			add_action( 'wp_ajax_nopriv_sm_wcpct_complete_convertion', array(SM_WCPCT_Product(), 'handle_ajax_conversion_completed') );

			if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				add_action( 'woocommerce_product_options_general_product_data', array(SM_WCPCT_Product(), 'display_option_group') );
				add_action( 'woocommerce_process_product_meta', array(SM_WCPCT_Product(), 'save_fields'), 10, 2 );

			}
		}

		/**
		 * Return class instance
		 *
		 * @static
		 * @return SM_WCPCT
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

	}

endif;

function SM_WCPCT() {
	return SM_WCPCT::instance();
}

/**
 * WooCommerce fallback notice.
 *
 * @since 1.0.0
 * @return string
 */
function wca_missing_wc_notice() {
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'WooCommerce Product Conversione Tracking requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-product-conversion-tracking' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return SM_WCPCT();
}
else {
	add_action( 'admin_notices', 'wca_missing_wc_notice' );
}
