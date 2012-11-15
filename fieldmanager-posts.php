<?php
/**
 * @package Fieldmanager
 * @subpackage Posts
 * @version 0.1
 */
/*
Plugin Name: Fieldmanager Posts
Plugin URI: http://github.com/bcampeau/fieldmanager-terms
Description: Adds automatic related post matching for Fieldmanager post-based fields
Author: Bradford Campeau-Laurion
Version: 0.1
Author URI: http://www.alleyinteractive.com/
*/

require_once( dirname( __FILE__ ) . '/php/class-fieldmanager-posts.php' );
require_once( dirname( __FILE__ ) . '/php/class-plugin-dependency.php' );

function fieldmanager_posts_dependency() {
	$fieldmanager_dependency = new Plugin_Dependency( 'Fieldmanager Posts', 'Fieldmanager', 'https://github.com/netaustin/wordpress-fieldmanager' );
	if( !$fieldmanager_dependency->verify() ) {
		// Cease activation
	 	die( $fieldmanager_dependency->message() );
	}
}
register_activation_hook( __FILE__, 'fieldmanager_posts_dependency' );

/**
 * Get the base URL for this plugin.
 * @return string URL pointing to Fieldmanager Posts top directory.
 */
function fieldmanager_posts_get_baseurl() {
	return plugin_dir_url( __FILE__ );
}