<?php
/**
 * Loads the custom post type
 *
 * @package usc_injector
 */

// check if WordPress is loaded.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Register the injection custom post type.
 *
 * @wp-hook init
 */
function usci_register_cpt_injection(): void {
	register_post_type( 'usc_injection', [
		'label'         => __( 'Injections', 'usc-injector' ),
		'public'        => true,
		'show_ui'       => true,
		'supports'      => [ 'title', 'author' ],
		'menu_position' => null,
		'menu_icon'     => 'dashicons-edit-page',
		'labels'        => [
            'name'               => __( 'QMN Injector', 'usc-injector' ),
            'singular_name'      => __( 'Injection', 'usc-injector' ),
            'add_new'            => __( 'Add Injection', 'usc-injector' ),
            'add_new_item'       => __( 'Add Injection', 'usc-injector' ),
            'edit_item'          => __( 'Edit Injection', 'usc-injector' ),
            'new_item'           => __( 'New Injection', 'usc-injector' ),
            'view_item'          => __( 'View Injection', 'usc-injector' ),
            'search_items'       => __( 'Search Injections', 'usc-injector' ),
            'not_found'          => __( 'No Injections found', 'usc-injector' ),
            'not_found_in_trash' => __( 'No Injections found in Trash', 'usc-injector' ),
        ],
	] );
}
