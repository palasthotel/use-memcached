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
const OBJECT_CACHE_SCRIPT_VERSION = 3;
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

	$isWorking = true;
	$message = "✅ Working fine";
	if(!objectCacheFileExists()) {
		$isWorking = false;
		$message = "🚨 Missing object-cache.php";
	} else if(!isOurObjectCacheFile()){
		$isWorking = false;
		$message = "🚨 object-cache.php is not from use memcached plugin.";
	} else if(!objectCacheVersionMatches()){
		$isWorking = false;
		$message = "🚨 object-cache.php version is ".getActiveObjectCacheFileVersion()." but need ".OBJECT_CACHE_SCRIPT_VERSION;
	} else if( !function_exists('wp_get_memcached')){
		$isWorking = false;
		$message = "🚨 could not find wp_get_memcached function. Perhaps Memcached class not exists.";
		$message.= ((!class_exists("Memcached"))? "Memcached class not exists.": "");
	}

	$style = "";
	if(!$isWorking){
		$style = "background-color: #F44336;";
	}

	$wp_admin_bar->add_node( array(
		'id'    => "use-memcached-info",
		'title' => "<div style='$style;margin-left: -10px;padding: 0 10px;' title='Use Memcached'>💾 Cache</div>",
	) );

	$wp_admin_bar->add_node(array(
		'id' => 'use-memcached-status',
		'title' => "<div >$message</div>",
		'parent' => "use-memcached-info"
	));

	// TODO: flush cache route
	$wp_admin_bar->add_node(array(
		'id' => 'use-memcached-flush',
		'title' => '<div style="cursor: pointer;">🗑 Flush cache</div>',
		'parent' => "use-memcached-info",
	));
}
add_action( 'admin_bar_menu', __NAMESPACE__.'\admin_bar', 40 );
