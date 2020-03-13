<?php

/**
 * Plugin Name: Use Memcached
 * Plugin URI: https://github.com/palasthotel/use-memcached
 * Description: Adds memcached support for WP_Object_Cache.
 * Version: 1.0.4
 * Text Domain: use-memcached
 * Domain Path: /languages
 * Author: Palasthotel <rezeption@palasthotel.de> (in person: Edward Bock)
 * Author URI: http://www.palasthotel.de
 * Requires at least: 5.0
 * Tested up to: 5.3.2
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @copyright Copyright (c) 2019, Palasthotel
 * @package Palasthotel\WordPress\UseMemcached
 */

namespace Palasthotel\WordPress\UseMemcached;

// ------------------------------------------------------------------------
// plugin domain
// ------------------------------------------------------------------------
const DOMAIN = "use-memcached";

//------------------------------------------------------------------------
// remember to always update version in object-cache.php too
//------------------------------------------------------------------------
const OBJECT_CACHE_SCRIPT_VERSION = 24; // needs to be the same version like template file
const ENABLE_OBJECT_CACHE_FILE    = WP_CONTENT_DIR . "/uploads/use-memcached.enabled";
const DESTINATION_FILE            = WP_CONTENT_DIR . "/object-cache.php";

//------------------------------------------------------------------------
// js ajax api stuff
//------------------------------------------------------------------------
const HANDLE_ADMIN_JS               = "use-memcached-admin-js";
const AJAX_ACTION_DISABLE           = "use_memcached_disable";
const AJAX_ACTION_DISABLE_ARG       = "disable_memcached";
const AJAX_ACTION_DISABLE_ARG_VALUE = "please-do-so";
const AJAX_ACTION_FLUSH             = "use_memcached_flush";
const AJAX_ACTION_STATS             = "use_memcached_stats";

/**
 * @property ObjectCacheFileHandler objectCacheFileHandler
 * @property AdminBar adminBar
 * @property Memcache memcache
 * @property string url
 * @property string path
 * @property string templatesPath
 * @property Ajax ajax
 * @property Assets assets
 * @property AdminNotices adminNotices
 * @property Tools tools
 */
class Plugin {

	/**
	 * Plugin constructor.
	 */
	private function __construct() {

		$this->url           = plugin_dir_url( __FILE__ );
		$this->path          = plugin_dir_path( __FILE__ );
		$this->templatesPath = $this->path . "/templates/";

		load_plugin_textdomain(
			DOMAIN,
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		require_once dirname( __FILE__ ) . "/vendor/autoload.php";

		$this->objectCacheFileHandler = new ObjectCacheFileHandler( $this );
		$this->memcache               = new Memcache( $this );
		$this->ajax                   = new Ajax( $this );
		$this->assets                 = new Assets( $this );
		$this->adminBar               = new AdminBar( $this );
		$this->adminNotices           = new AdminNotices( $this );
		$this->tools                  = new Tools( $this );

		register_activation_hook( __FILE__, array( $this, "activation" ) );
		register_deactivation_hook( __FILE__, array( $this, "deactivation" ) );
	}

	/**
	 * on plugin activation
	 */
	function activation() {
		// nothing yet
		$this->memcache->flush();
	}

	/**
	 * on deactivation
	 */
	function deactivation() {
		$this->memcache->flush();
		// delete file that enables use of use memcached object-cache.php file
		$this->memcache->setEnabled( false );
		// if we can identify object-cache.php file as ours
		if ( $this->objectCacheFileHandler->isOurObjectCacheFile() ) {
			// try to delete it on deactivation
			unlink( DESTINATION_FILE );
		}
	}

	private static $instance = NULL;

	/**
	 * @return Plugin
	 */
	public static function instance() {
		if ( self::$instance === NULL ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}
}

Plugin::instance();

require_once dirname( __FILE__ ) . "/cli/wp-cli.php";