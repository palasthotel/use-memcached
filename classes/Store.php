<?php


namespace Palasthotel\WordPress\UseMemcached;

use Memcache;

/**
 * @property \Memcache cache
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
		$servers = apply_filters(Plugin::FILTER_SERVERS, array('127.0.0.1:11211'));

		// init Memcache buckets
		$this->cache = new Memcache();
		foreach ( $servers as $server  ) {
			if ( 'unix://' == substr( $server, 0, 7 ) ) {
				$node = $server;
				$port = 0;
			} else {
				list ( $node, $port ) = explode(':', $server);
				if ( !$port )
					$port = ini_get('memcache.default_port');
				$port = intval($port);
				if ( !$port )
					$port = 11211;
			}
			$this->cache->addServer($node, $port, true, 1, 1, 15, true, array($this, 'failure_callback'));
			$this->cache->setCompressThreshold(20000, 0.2);
		}

	}

	function set($id, $data, $group = 'default', $expire = 0) {
		$key = $this->key($id, $group);
		if ( isset($this->cache[$key]) && ('checkthedatabaseplease' === $this->cache[$key]) )
			return false;

		if ( is_object($data) )
			$data = clone $data;

		$this->cache[$key] = $data;

		if ( in_array($group, $this->no_mc_groups) )
			return true;

		$expire = ($expire == 0) ? $this->default_expiration : $expire;
		$mc =& $this->get_mc($group);
		$result = $mc->set($key, $data, false, $expire);

		return $result;
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