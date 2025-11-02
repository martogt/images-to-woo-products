<?php
/**
 * Plugin Name: Images to Woo Products (Uploader Edition)
 * Plugin URI:  https://github.com/martogt/images-to-woo-products
 * Description: Bulk convert selected images into WooCommerce products (simple products with featured image, optional SKU prefix).
 * Version: 2.3.2
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Author: Marv
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: images-to-woo-products
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IMTOWOOPRO_VERSION', '2.3.2' );
define( 'IMTOWOOPRO_PLUGIN_FILE', __FILE__ );
define( 'IMTOWOOPRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMTOWOOPRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Не викаме load_plugin_textdomain() – WordPress.org зарежда преводите автоматично (4.6+).

require IMTOWOOPRO_PLUGIN_DIR . 'includes/uploader.php';

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>' .
				esc_html__( 'Images to Woo Products requires WooCommerce to be installed and active.', 'images-to-woo-products' ) .
			'</p></div>';
		} );
		return;
	}

	if ( class_exists( 'ITPWC_Uploader' ) && method_exists( 'ITPWC_Uploader', 'boot' ) ) {
		ITPWC_Uploader::boot();
	}
} );
