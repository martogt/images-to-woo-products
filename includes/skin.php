<?php
/**
 * UI Skin for admin page to match the provided mock (blue header, light rows, rounded card, SKU check icon).
 * Loads only on the plugin's admin screen.
 *
 * @package ImToWOOPro
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class ImToWOOPro_Skin {
    public static function init() {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
        add_action( 'admin_head', [ __CLASS__, 'admin_head' ] );
    }

    public static function is_target_screen() : bool {
        // Our submenu screen id (from previous structure)
        $screen = get_current_screen();
        return $screen && $screen->id === 'product_page_imtowoopro-images-products';
    }

    public static function enqueue( $hook ) {
        if ( ! self::is_target_screen() ) return;
        wp_enqueue_style( 'imtowoo-admin-skin', IMTOWOOPRO_PLUGIN_URL . 'assets/css/admin.css', [], IMTOWOOPRO_VERSION );
        wp_enqueue_script( 'imtowoo-admin-skin', IMTOWOOPRO_PLUGIN_URL . 'assets/js/skin.js', [ 'jquery' ], IMTOWOOPRO_VERSION, true );
    }

    public static function admin_head() {
        if ( ! self::is_target_screen() ) return;
        // Small inline style to ensure base fonts look crisp
        echo '<style>.imtowoo-wrap h1{margin-bottom:10px}</style>';
    }
}

ImToWOOPro_Skin::init();
