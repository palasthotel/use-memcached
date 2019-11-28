<?php

/**
 * Plugin Name: Use Memcached
 * Plugin URI: https://github.com/palasthotel/use-memcached
 * Description: Adds memcached support and provides memcached api.
 * Version: 0.1
 * Author: Palasthotel <rezeption@palasthotel.de> (in person: Edward Bock)
 * Author URI: http://www.palasthotel.de
 * Requires at least: 4.0
 * Tested up to: 5.3
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @copyright Copyright (c) 2019, Palasthotel
 * @package Palasthotel\WordPress\UseMemcached
 */

namespace Palasthotel\WordPress\UseMemcached;

// remember to always update version in object-cache.php too
const OBJECT_CACHE_SCRIPT_VERSION = 2;
const DESTINATION_FILE = WP_CONTENT_DIR."/object-cache.php";

/**
 * check if an object-cache.php file exists
 * @return bool
 */
function objectCacheFileExists(){
	return file_exists(DESTINATION_FILE);
}

/**
 * check if the object-cache.php file is from this plugin
 */
function isOurObjectCacheFile(){
	return objectCacheFileExists() && defined('USE_MEMCACHED_OBJECT_CACHE_SCRIPT_VERSION');
}

/**
 * get version of object-cache.php in wp-content folder
 * @return bool|int
 */
function getActiveObjectCacheFileVersion(){
	return (!isOurObjectCacheFile())? false: USE_MEMCACHED_OBJECT_CACHE_SCRIPT_VERSION;
}

/**
 * check for version match of template and actual object-cache.php file
 * @return bool
 */
function objectCacheVersionMatches(){
	return isOurObjectCacheFile()
	       &&
	       getActiveObjectCacheFileVersion() === OBJECT_CACHE_SCRIPT_VERSION;
}

/**
 *  copy the object-cache.php template if not exists
 */
function copy_object_cache_template(){

	if( ! objectCacheVersionMatches() ){
		// not the correct object cache version, so delete it
		unlink(DESTINATION_FILE);
	}

	if( !objectCacheFileExists() ){
		// if there is no object-cache.php file create it from template
		$contents = file_get_contents(dirname(__FILE__)."/object-cache.php");
		file_put_contents(DESTINATION_FILE, $contents);
		chmod(DESTINATION_FILE, 0644);
	}

}
add_action('admin_init', __NAMESPACE__."\copy_object_cache_template");

/**
 * admin bar
 */
function admin_bar(){
	/**
	 * @var \WP_Admin_Bar $wp_admin_bar
	 */
	global $wp_admin_bar;

	$color = "";
	$bgColor = "";
	if(!objectCacheFileExists()) {
		$bgColor = "background-color: red;";
		error_log( "Could not find object-cache.php in wp-contents" );
	} else if(!isOurObjectCacheFile()){
		$color="color:black";
		$bgColor = "background-color: yellow;";
		error_log("object-cache.php is not the one from this plugin.");
	} else if(!objectCacheVersionMatches()){
		$color="color:black";
		$bgColor = "background-color: yellow;";
		error_log("object Cache versions not matching:  Need ".OBJECT_CACHE_SCRIPT_VERSION." but is ".getActiveObjectCacheFileVersion());
	} else if( !function_exists('wp_get_memcached')){
		$color="color:black";
		$bgColor = "background-color: yellow;";
		error_log("Could not find wp_get_memcached function which is weired because all other tests succeed...");
	}

	//TODO: more health checks

	$wp_admin_bar->add_node( array(
		'id'    => "use-memcached-info",
		'title' => "<div style='$bgColor$color;margin-left:-10px;padding:0 10px;' title='Use Memcached'>💾 Cache</div>",
	) );

	// TODO: flush cache route
	$wp_admin_bar->add_node(array(
		'id' => 'use-memcached-flush',
		'title' => 'Flush',
		'parent' => "use-memcached-info",
	));
}
add_action( 'admin_bar_menu', __NAMESPACE__.'\admin_bar', 40 );
