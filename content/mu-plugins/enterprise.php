<?php
/*
 * Plugin Name: Enterprise
 * Description: A WordPress plugin made for professional websites.
 * Version: 0.1
 * Author: Patrick Daly
 * Author URI: http://developdaly.com
 *
 * Copyright (c) 2013 Develop Daly
 * http://developdaly.com
 */

// Disables updating core/themes/plugins if not developing locally
add_action( 'init', 'enterprise_plugin_disable_updates' );

/**
 * Disables core and plugin updates as well as notifications to update if
 * not in a local environment.
 *
 * @since 0.1.0
 */
function enterprise_plugin_disable_updates() {

	// Disable the disabling in local environments
	if( WP_LOCAL_DEV == true )
		return false;
		
	//Disable Theme Updates
	# 2.8 to 3.0:
	remove_action( 'load-themes.php', 'wp_update_themes' );
	remove_action( 'load-update.php', 'wp_update_themes' );
	remove_action( 'admin_init', '_maybe_update_themes' );
	remove_action( 'wp_update_themes', 'wp_update_themes' );
	add_filter( 'pre_transient_update_themes', create_function( '$a', "return null;" ) );
	wp_clear_scheduled_hook( 'wp_update_themes' );
	
	# 3.0:
	remove_action( 'load-update-core.php', 'wp_update_themes' );
	add_filter( 'pre_site_transient_update_themes', create_function( '$a', "return null;" ) );
	wp_clear_scheduled_hook( 'wp_update_themes' );
	
	
	//Disable Plugin Updates
	# 2.8 to 3.0:
	remove_action( 'load-plugins.php', 'wp_update_plugins' );
	remove_action( 'load-update.php', 'wp_update_plugins' );
	remove_action( 'admin_init', '_maybe_update_plugins' );
	remove_action( 'wp_update_plugins', 'wp_update_plugins' );
	add_filter( 'pre_transient_update_plugins', create_function( '$a', "return null;" ) );
	wp_clear_scheduled_hook( 'wp_update_plugins' );
	
	# 3.0:
	remove_action( 'load-update-core.php', 'wp_update_plugins' );
	add_filter( 'pre_site_transient_update_plugins', create_function( '$a', "return null;" ) );
	wp_clear_scheduled_hook( 'wp_update_plugins' );
	
	
	//Diasable Core Updates
	# 2.8 to 3.0:
	remove_action( 'wp_version_check', 'wp_version_check' );
	remove_action( 'admin_init', '_maybe_update_core' );
	add_filter( 'pre_transient_update_core', create_function( '$a', "return null;" ) );
	wp_clear_scheduled_hook( 'wp_version_check' );
	
	# 3.0:
	add_filter( 'pre_site_transient_update_core', create_function( '$a', "return null;" ) );
	wp_clear_scheduled_hook( 'wp_version_check' );

}