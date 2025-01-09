<?php
/**
 * Plugin Name: SE FIELDS  - WooCommerce Extra Product Options
 * Plugin URI: https://se.lt
 * Description: Offer extra options like personal engraving, print-on-demand items, gifts, custom canvas prints, and personalized products.
 * Version: 1.4.0
 * Author: Martynas Beržinskas
 * Author URI: https://se.lt
 * Text Domain: Extra product fields
 * WC requires at least: 3.0.0
 * WC tested up to: 9.3.1
 * Domain Path: /i18n/languages/
 */

namespace YayExtra;

use YayExtra\Init\Settings;

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'YayExtra\\plugins_loaded' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	require_once ABSPATH . WPINC . '/pluggable.php';
	deactivate_plugins( plugin_basename( __FILE__ ) );
	require_once plugin_dir_path( __FILE__ ) . 'includes/UpdateVersion.php';
	return;
}

if ( ! defined( 'YAYE_PLUGIN_FILE' ) ) {
	define( 'YAYE_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'YAYE_PREFIX' ) ) {
	define( 'YAYE_PREFIX', 'YAYE_' );
}

if ( ! defined( 'YAYE_URL' ) ) {
	define( 'YAYE_URL', plugin_dir_url( YAYE_PLUGIN_FILE ) );
}

if ( ! defined( 'YAYE_PATH' ) ) {
	define( 'YAYE_PATH', plugin_dir_path( YAYE_PLUGIN_FILE ) );
}

if ( ! defined( 'YAYE_BASENAME' ) ) {
	define( 'YAYE_BASENAME', plugin_basename( YAYE_PLUGIN_FILE ) );
}

if ( ! defined( 'YAYE_VERSION' ) ) {
	define( 'YAYE_VERSION', '1.4.0' );
}

if ( ! defined( 'YAYE_SITE_URL' ) ) {
	define( 'YAYE_SITE_URL', site_url() );
}

require __DIR__ . '/autoloader.php';

/**
 * Callback for plugins_loaded action
 *
 * @return void
 */
if ( ! function_exists( 'YayExtra\\plugins_loaded' ) ) {
	function plugins_loaded() {
		\YayExtra\YayCommerceMenu\RegisterMenu::get_instance();
		I18n::getInstance();
		Settings::get_instance();
		add_action( 'admin_notices', 'YayExtra\\required_woocommerce_notice' );
	}
}

if ( ! function_exists( 'required_woocommerce_notice' ) ) {
	function required_woocommerce_notice() {
		if ( ! function_exists( 'WC' ) ) :
			?>
				<div class="error">
					<p>
					<?php
					// translators: %s: search WooCommerce plugin link
					printf( 'YayExtra ' . esc_html__( 'is enabled but not effective. It requires %1$sWooCommerce%2$s in order to work.', 'yayextra' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '">', '</a>' );
					?>
					</p>
				</div>
			<?php
		endif;
	}
}



add_action(
	'plugins_loaded',
	'YayExtra\\plugins_loaded'
);
