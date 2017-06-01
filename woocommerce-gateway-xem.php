<?php
/*
 * Plugin Name: WooCommerce Xem Gateway Turtorial
 * Plugin URI: https://wordpress.org/plugins/woocommerce-gateway-xem/
 * Description: Take XEM coin payments inn your store.
 * Author: Robin Pedersen
 * Author URI: http://nem.today
 * Version: 2.1.9
 * Text Domain: woocommerce-gateway-xem
 * Domain Path: /languages
 *
 *
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WC_XEM_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_XEM_VERSION', '2.1.9' );
define( 'WC_XEM_MIN_PHP_VER', '5.3.0' );
define( 'WC_XEM_MIN_WC_VER', '2.5.0' );
define( 'WC_XEM_MAIN_FILE', __FILE__ );

if ( ! class_exists( 'WC_Xem' ) ) {

	class WC_Xem {

		/**
		 * @var Singleton The reference the *Singleton* instance of this class
		 */
		private static $instance;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return Singleton The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {}

		/**
		 * Private unserialize method to prevent unserializing of the *Singleton*
		 * instance.
		 *
		 * @return void
		 */
		private function __wakeup() {}

		/**
		 * Notices (array)
		 * @var array
		 */
		public $notices = array();


		protected function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init_gateways' ) );
		}

		/**
		 * Add the gateways to WooCommerce
		 *
		 * @since 1.0.0
		 */
		public function init_gateways() {

			include_once ( plugin_basename('includes/class-wc-gateway-xem.php'));
			include_once ( plugin_basename('includes/class-xem-ajax.php'));
			include_once ( plugin_basename('includes/class-xem-currency.php'));

			/*
			 * Need make woocommerce aware of the Gateway class
			 * */
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );

		}

		/**
		 * Add the gateways to WooCommerce
		 *
		 * @since 1.0.0
		 */
		public function add_gateways( $methods ) {
			$methods[] = 'WC_Gateway_Xem';
			return $methods;
		}
	}
	$GLOBALS['wc_xem'] = WC_Xem::get_instance();
}