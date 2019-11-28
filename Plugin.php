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


/**
 * @property string path
 * @property string url
 * @property Store store
 */
class Plugin {

	const FILTER_SERVERS = "use_memcached_servers";

	public function __construct() {

		$this->path = plugin_dir_path(__FILE__);
		$this->url = plugin_dir_url(__FILE__);

		require_once dirname(__FILE__)."/vendor/autoload.php";

//		$this->store = new Store($this);

	}

}
new Plugin();

require_once dirname(__FILE__)."/public-functions.php";