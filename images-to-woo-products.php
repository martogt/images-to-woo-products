<?php
/**
 * Plugin Name: Images to Woo Products (Uploader Edition)
 * Plugin URI:  https://github.com/martogt/images-to-woo-products
 * Description: Upload/select images and convert them into WooCommerce Simple products (bulk UI, SKU generator, categories, tags). WP.org‑ready structured version.
 * Version: 2.3.2
 * Requires at least: 6.0
 * Tested up to: 6.8.3
 * Requires PHP: 7.4
 * Author: Marv
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: images-to-woo-products
 * Domain Path: /languages
 *
 * @package ImToWOOPro
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'IMTOWOOPRO_VERSION', '2.0.9' );
define( 'IMTOWOOPRO_PLUGIN_FILE', __FILE__ );
define( 'IMTOWOOPRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMTOWOOPRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load text domain.
add_action( 'init', function() {
    load_plugin_textdomain( 'images-to-woo-products', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Require uploader (ported from v2.0.9).
require IMTOWOOPRO_PLUGIN_DIR . 'includes/skin.php';
require IMTOWOOPRO_PLUGIN_DIR . 'includes/uploader.php';

// Bootstrap.
add_action( 'plugins_loaded', function(){
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function(){
            echo '<div class="notice notice-error"><p>' . esc_html__( 'ImToWOOPro requires WooCommerce to be installed and active.', 'images-to-woo-products' ) . '</p></div>';
        } );
        return;
    }
    if ( class_exists( 'ITPWC_Uploader' ) && method_exists( 'ITPWC_Uploader', 'boot' ) ) {
        ITPWC_Uploader::boot();
    }
} );
