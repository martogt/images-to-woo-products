<?php
/**
 * Plugin Name: ImToWOOPro – Images to WooCommerce Products
 * Plugin URI:  https://github.com/USERNAME/im-to-woo-pro
 * Description: Upload/select images and convert them into WooCommerce Simple products (bulk UI, SKU generator, categories, tags). WP.org‑ready structured version.
 * Version: 2.0.9
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Author: Marv
 * Author URI: https://example.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: im-to-woo-pro
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
    load_plugin_textdomain( 'im-to-woo-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Require uploader (ported from v2.0.9).
require IMTOWOOPRO_PLUGIN_DIR . 'includes/skin.php';
require IMTOWOOPRO_PLUGIN_DIR . 'includes/uploader.php';

// Bootstrap.
add_action( 'plugins_loaded', function(){
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function(){
            echo '<div class="notice notice-error"><p>' . esc_html__( 'ImToWOOPro requires WooCommerce to be installed and active.', 'im-to-woo-pro' ) . '</p></div>';
        } );
        return;
    }
    if ( class_exists( 'ITPWC_Uploader' ) && method_exists( 'ITPWC_Uploader', 'boot' ) ) {
        ITPWC_Uploader::boot();
    }
} );
