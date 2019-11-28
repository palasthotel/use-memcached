<?php


namespace Palasthotel\WordPress\UseMemcached;

use Memcached;

/**
 * @property \Memcached cache
 */
class Store {

	var $buckets = array();

	/**
	 * Store constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct($plugin) {

		// do nothing if class doesnt exist
		if(!class_exists("Memcached")) return;

		// get all bucket definitions
		$servers = apply_filters(Plugin::FILTER_SERVERS,
			array(
				array('127.0.0.1', 1121)
			)
		);

		// init Memcache buckets
		$this->cache = new Memcached();
		foreach ( $servers as $server  ) {
			$this->cache->addServer($server[0], $server[1]);
		}

	}

	function set($id, $data, $expire = 0) {
		return $this->cache->set($id, $data, $expire);
	}

	function get($id){
		return $this->cache->get($id);
	}


	/**
	 * build cache key
	 * @param string $name
	 * @param string $type
	 *
	 * @return string
	 */
	public function getKey($name, $type){
		$blog_prefix = "";
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			global $blog_id;
			$blog_prefix = "blog-$blog_id-";
		}
		return "$blog_prefix-$type-$name";
	}

	/**
	 * on failure callback
	 * @param $host
	 * @param $port
	 */
	public function failure_callback($host, $port){
		error_log("Memcache error with $host:$port");
	}

}