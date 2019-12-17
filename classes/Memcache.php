<?php


namespace Palasthotel\WordPress\UseMemcached;


/**
 * @property Plugin plugin
 */
class Memcache {

	/**
	 * Memcache constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct($plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @return bool
	 */
	function flush(){
		return wp_cache_flush();
	}

	/**
	 * @param bool $asArray
	 *
	 * @return array|string
	 */
	function stats($asArray = false){
		if(function_exists("use_memcached")){
			return \use_memcached()->stats($asArray);
		}
		return ($asArray)? array(): "";
	}

	function getFreistilPrefix(){
		global $wp_object_cache;
		return isset($wp_object_cache->freistil_prefix)? $wp_object_cache->freistil_prefix:"";
	}

	/**
	 * @return string
	 */
	function getGlobalPrefix(){
		global $wp_object_cache;
		return isset($wp_object_cache->global_prefix) ? $wp_object_cache->global_prefix: "";
	}

	/**
	 * @return string
	 */
	function getBlogPrefix(){
		global $wp_object_cache;
		return isset($wp_object_cache->blog_prefix) ? $wp_object_cache->blog_prefix: "";
	}

	/**
	 * check if memcached was disabled by user in settings
	 * @return bool
	 */
	function isEnabled(){
		if(class_exists("\UseMemcachedConfiguration")){
			return \UseMemcachedConfiguration::instance()->isEnabled();
		}
		return false;
	}

	/**
	 * activate or deactivate use of memcache object-cache.php
	 *
	 * @param $isEnabled
	 */
	function setEnabled( $isEnabled){
		if(class_exists("\UseMemcachedConfiguration")){
			$this->flush();
			\UseMemcachedConfiguration::instance()->setEnabled($isEnabled);
		}
	}

	/**
	 * toggle disabled state
	 */
	function toggleEnabled(){
		$this->setEnabled(!$this->isEnabled());
	}

	/**
	 *
	 */
	function areAllServersConnected(){
		$stats = $this->stats(true);
		if(count($stats) < 1) return false;
		$bucketsConnected = array_map(function($buckets){
			if( is_array($buckets)){
				$serversConnected = array_map(function($server){
					return isset($server['uptime'])  && is_array($server) && $server['uptime'] > 0;
				}, $buckets);
				return array_reduce($serversConnected, function($carry, $bool){
					return $carry && $bool;
				}, true);
			}
			return false;
		}, $stats);

		return array_reduce($bucketsConnected, function($carry, $bool){
			return $carry && $bool;
		}, true);
	}



}