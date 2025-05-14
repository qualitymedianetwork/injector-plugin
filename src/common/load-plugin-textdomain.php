<?php
/**
 * Loads the textdomain
 *
 * @package usc_injector
 */

// check if WordPress is loaded.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Loads the plugins textdomain
 *
 * @wp-hook init
 *
 * @return  void
 */
function usci_load_plugin_textdomain() {
    load_plugin_textdomain( 'usc-injector', false, dirname( USCI_PLUGIN_BASENAME ) . '/languages' );
}
