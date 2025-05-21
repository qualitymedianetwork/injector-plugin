<?php
/**
 * Registers the scripts and styles for the backend
 *
 * @package usc_injector
 */

// check if WordPress is loaded.
if ( ! defined( 'ABSPATH' ) ) {
    return;
}

/**
 * Registers the backend scripts
 * 
 * @wp-hook admin_enqueue_scripts
 * 
 * @return  void
 */
function usci_register_backend_scripts(): void {
    // metabox tabs
    wp_register_script(
        'usci-backend-metabox-tabs-script',
        USCI_PLUGIN_BASEURL . '/assets/backend/js/metabox-tabs.js',
        [],
        '0.0.2',
        true
    );
    wp_enqueue_script( 'usci-backend-metabox-tabs-script' );
}

/**
 * Registers the newsletter posts edit styles
 * 
 * @wp-hook admin_enqueue_scripts
 * 
 * @return  void
 */
function usci_register_backend_styles(): void {
    // metabox tabs
    wp_register_style(
        'usci-backend-metabox-tabs-style',
        USCI_PLUGIN_BASEURL . '/assets/backend/css/metabox-tabs.css',
        [],
        '0.0.2'
    );
    wp_enqueue_style( 'usci-backend-metabox-tabs-style' );
}
