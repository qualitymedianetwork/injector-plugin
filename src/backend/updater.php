<?php
/**
 * Loads the custom updater
 *
 * @package usc_injector
 */

// check if WordPress is loaded.
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

add_filter( 'plugins_api', 'usci_plugin_updater_info', 20, 3 );
add_filter( 'site_transient_update_plugins', 'usci_plugin_updater' );

function usci_plugin_updater_info( $res, $action, $args ) {

	print_r($res);
	print_r($action);
	print_r($args);
	exit;

	// do nothing if you're not getting plugin information right now
	if( 'plugin_information' !== $action ) {
		return $res;
	}

	// do nothing if it is not our plugin
	if( USCI_PLUGIN_SLUG !== $args->slug ) {
		return $res;
	}

	// get updates
	$remote = usci_plugin_updater_request();

	if( ! $remote ) {
		return $res;
	}

	$res = new stdClass();

	$res->name = $remote->name;
	$res->slug = $remote->slug;
	$res->version = $remote->version;
	$res->tested = $remote->tested;
	$res->requires = $remote->requires;
	$res->author = $remote->author;
	$res->author_profile = $remote->author_profile;
	$res->download_link = $remote->download_url;
	$res->trunk = $remote->download_url;
	$res->requires_php = $remote->requires_php;
	$res->last_updated = $remote->last_updated;

	$res->sections = array(
		'description' => $remote->sections->description
	);

	if( ! empty( $remote->banners ) ) {
		$res->banners = array(
			'low' => $remote->banners->low,
			'high' => $remote->banners->high
		);
	}

	return $res;
}

function usci_plugin_updater( $transient ) {

	if ( empty( $transient->checked ) ) {
		return $transient;
	}

	$remote = usci_plugin_updater_request();
	if (
		$remote
		&& version_compare( USCI_PLUGIN_VERSION, $remote->version, '<' )
		&& version_compare( $remote->requires, get_bloginfo( 'version' ), '<' )
		&& version_compare( $remote->requires_php, PHP_VERSION, '<' )
	) {
		$res = new stdClass();
		$res->slug = $remote->slug;
		$res->plugin = USCI_PLUGIN_BASENAME;
		$res->new_version = $remote->version;
		$res->tested = $remote->tested;
		$res->package = $remote->download_url;
		$transient->response[ $res->plugin ] = $res;
		
		$transient->checked[$res->plugin] = $remote->version;
	}
 
	return $transient;

}

function usci_plugin_updater_request() {

	$remote = wp_remote_get( 
		'https://git.upsidecode.dev/usc/wp-plugin-injector/raw/branch/main/plugin.json',
		array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json'
				)
			)
	);

	if ( 
		is_wp_error( $remote )
		|| 200 !== wp_remote_retrieve_response_code( $remote )
		|| empty( wp_remote_retrieve_body( $remote ) )
	) {
		return false;	
	}

	$remote = json_decode( wp_remote_retrieve_body( $remote ) );
	return $remote;
}