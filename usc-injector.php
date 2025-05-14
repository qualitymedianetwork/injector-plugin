<?php
/**
 * USC Injector
 *
 * @package      usc_injector
 * @author       Laura Herzog
 * @copyright    2025 Upsidecode GmbH
 * @license      GPL v3 or later
 *
 * Plugin Name:  USC Injector
 * Description:  Injects content on a specific position
 * Version:      0.0.1
 * Author:       Upsidecode GmbH
 * Text Domain:  usc-injector
 * License:      GPL v3 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    return;
}

define( 'USCI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'USCI_PLUGIN_BASEURL', plugin_dir_url( __FILE__ ) );
define( 'USCI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize plugin.
 *
 * @wp-hook plugins_loaded
 * 
 * @return void
 */
function usci_init(): void {
    // load the textdomain.
    require_once __DIR__ . '/src/common/load-plugin-textdomain.php';
    add_action( 'init', 'usci_load_plugin_textdomain' );

    // custom post type
    require_once __DIR__ . '/src/common/cpt-injection.php';
    add_action( 'init', 'usci_register_cpt_injection' );

    // stuff only needed in frontend.
    if ( ! is_admin() ) {
        // Injection
        require_once __DIR__ . '/src/frontend/injection.php';
        add_action( 'wp_head', 'usci_maybe_inject_head_footer_content' );
        add_action( 'wp_footer', 'usci_maybe_inject_head_footer_content' );
        add_filter( 'the_content', 'usci_maybe_inject_the_content' );
    }

    // stuff only needed in admin.
    if ( is_admin() ) {
        // Admin Scripts and styles
        require_once __DIR__ . '/src/backend/scripts-and-styles.php';
        add_action( 'admin_enqueue_scripts', 'usci_register_backend_scripts' );
        add_action( 'admin_enqueue_scripts', 'usci_register_backend_styles' );

        // Metabox - Injection
        require_once __DIR__ . '/src/backend/metabox.php';
        add_action( 'add_meta_boxes', 'uscn_register_injection_metabox' );
        add_action( 'save_post_usc_injection', 'usci_save_injection_metabox' );
    }
} add_action( 'plugins_loaded', 'usci_init' );