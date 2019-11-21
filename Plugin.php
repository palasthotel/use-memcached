<?php


namespace Palasthotel\WordPress\UseMemcached;


/**
 * @property string path
 * @property string url
 */
class Plugin {

	const FILTER_SERVERS = "use_memcached_servers";

	public function __construct() {

		$this->path = plugin_dir_path(__FILE__);
		$this->url = plugin_dir_url(__FILE__);

		require_once dirname(__FILE__)."/vendor/autoload.php";


	}

}
new Plugin();