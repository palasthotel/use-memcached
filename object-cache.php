<?php

// this file was copied here by use-memcached plugin

define('USE_MEMCACHED_OBJECT_CACHE_SCRIPT_VERSION', 3);

if(!defined('WP_CACHE_KEY_SALT')){
	// you can define that in wp-config.php
	define('WP_CACHE_KEY_SALT', 'salt-and-pepper');
}

if(!class_exists("Memcached")){
	error_log("Could not find class Memcached.\n");
	if(class_exists("Memcache")) error_log("But class Memcache seems to be available.\n");
}

if ( class_exists( 'Memcached' ) ) {

	/**
	 * @return WP_Object_Cache
	 */
	function wp_get_memcached(){
		global $wp_object_cache;
		return $wp_object_cache;
	}


	function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {

		if(function_exists( "\Palasthotel\WordPress\UseMemcached\increment_added_to_cache_count" )) \Palasthotel\WordPress\UseMemcached\increment_added_to_cache_count();

		return wp_get_memcached()->add( $key, $data, $group, $expire );
	}

	function wp_cache_incr( $key, $n = 1, $group = '' ) {
		return wp_get_memcached()->incr( $key, $n, $group );
	}

	function wp_cache_decr( $key, $n = 1, $group = '' ) {
		return wp_get_memcached()->decr( $key, $n, $group );
	}

	function wp_cache_close() {
		wp_get_memcached()->close();
	}

	function wp_cache_delete( $key, $group = '' ) {
		return wp_get_memcached()->delete( $key, $group );
	}

	function wp_cache_flush() {
		return wp_get_memcached()->flush();
	}

	function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
		$value = wp_get_memcached()->get( $key, $group, $force, $found );
		return $value;
	}

	/**
	 * $keys_and_groups = array(
	 *      array( 'key', 'group' ),
	 *      array( 'key', '' ),
	 *      array( 'key', 'group' ),
	 *      array( 'key' )
	 * );
	 *
	 */
	function wp_cache_get_multi( $key_and_groups, $bucket = 'default' ) {
		return wp_get_memcached()->get_multi( $key_and_groups, $bucket );
	}

	/**
	 * $items = array(
	 *      array( 'key', 'data', 'group' ),
	 *      array( 'key', 'data' )
	 * );
	 *
	 */
	function wp_cache_set_multi( $items, $expire = 0, $group = 'default' ) {
		wp_get_memcached()->set_multi( $items, $expire = 0, $group = 'default' );
	}

	function wp_cache_init() {
		global $wp_object_cache;
		$wp_object_cache = new WP_Object_Cache();
	}

	function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
		return wp_get_memcached()->replace( $key, $data, $group, $expire );
	}

	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		if ( defined( 'WP_INSTALLING' ) == false ) {
			return wp_get_memcached()->set( $key, $data, $group, $expire );
		} else {
			return wp_get_memcached()->delete( $key, $group );
		}
	}

	function wp_cache_add_global_groups( $groups ) {
		wp_get_memcached()->add_global_groups( $groups );
	}

	function wp_cache_add_non_persistent_groups( $groups ) {
		wp_get_memcached()->add_non_persistent_groups( $groups );
	}

	function wordpress_memcached_get_stats() {
		return wp_get_memcached()->stats();
	}

	class WP_Object_Cache {

		public $global_groups = array(); // (was private)
		public $no_mc_groups = array(); // (was private)
		public $cache = array(); // (was private)
		/**
		 * @var \Memcached[]
		 */
		public $mc = array(); // (was private)
		public $stats = array( 'add' => 0, 'delete' => 0, 'get' => 0, 'get_multi' => 0, ); // (was private)
		public $group_ops = array(); // (was private)
		public $memcache_debug = array(); // added for ElasticPress compatibility
		public $cache_enabled = true; // modified to allow wordpress to properly disable object cache in wp-activate.php +22 (was private)
		private $default_expiration = 0;

		function add( $id, $data, $group = 'default', $expire = 0 ) {
			$key = $this->key( $id, $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			if ( in_array( $group, $this->no_mc_groups ) ) {
				$this->cache[ $key ] = $data;

				return true;
			} elseif ( isset( $this->cache[ $key ] ) && $this->cache[ $key ] !== false ) {
				return false;
			}

			$mc     =& $this->get_mc( $group );
			$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
			$result = $mc->add( $key, $data, $expire );

			if ( false !== $result ) {
				if ( isset( $this->stats['add'] ) ) {
					++ $this->stats['add'];
				}

				$this->group_ops[ $group ][] = "add $id";
				$this->cache[ $key ]         = $data;
			}

			return $result;
		}

		function add_global_groups( $groups ) {
			if ( ! is_array( $groups ) ) {
				$groups = (array) $groups;
			}

			$this->global_groups = array_merge( $this->global_groups, $groups );
			$this->global_groups = array_unique( $this->global_groups );
		}

		function add_non_persistent_groups( $groups ) {
			if ( ! is_array( $groups ) ) {
				$groups = (array) $groups;
			}

			$this->no_mc_groups = array_merge( $this->no_mc_groups, $groups );
			$this->no_mc_groups = array_unique( $this->no_mc_groups );
		}

		function incr( $id, $n = 1, $group = 'default' ) {
			$key                 = $this->key( $id, $group );
			$mc                  =& $this->get_mc( $group );
			$this->cache[ $key ] = $mc->increment( $key, $n );

			return $this->cache[ $key ];
		}

		function decr( $id, $n = 1, $group = 'default' ) {
			$key                 = $this->key( $id, $group );
			$mc                  =& $this->get_mc( $group );
			$this->cache[ $key ] = $mc->decrement( $key, $n );

			return $this->cache[ $key ];
		}

		function close() {
			foreach ( $this->mc as $bucket => $mc ) {
				$mc->quit();
			}
		}

		function delete( $id, $group = 'default' ) {
			$key = $this->key( $id, $group );

			if ( in_array( $group, $this->no_mc_groups ) ) {
				unset( $this->cache[ $key ] );

				return true;
			}

			$mc =& $this->get_mc( $group );

			$result = $mc->delete( $key );

			if ( isset( $this->stats['delete'] ) ) {
				++ $this->stats['delete'];
			}
			$this->group_ops[ $group ][] = "delete $id";

			if ( false !== $result ) {
				unset( $this->cache[ $key ] );
			}

			return $result;
		}

		function flush() {
			// Don't flush if multi-blog.
			if ( function_exists( 'is_site_admin' ) || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) ) {
				return true;
			}

			$ret = true;
			foreach ( array_keys( $this->mc ) as $group ) {
				$ret &= $this->mc[ $group ]->flush();
			}

			return $ret;
		}

		function get( $id, $group = 'default', $force = false, &$found = null ) {
			$key = $this->key( $id, $group );
			$mc  =& $this->get_mc( $group );

			if ( null !== $found ) {
				$found = true;
			}

			if ( isset( $this->cache[ $key ] ) && ( ! $force || in_array( $group, $this->no_mc_groups ) ) ) {
				if ( is_object( $this->cache[ $key ] ) ) {
					$value = clone $this->cache[ $key ];
				} else {
					$value = $this->cache[ $key ];
				}
			} else if ( in_array( $group, $this->no_mc_groups ) ) {
				$this->cache[ $key ] = $value = false;
			} else {
				$value = $mc->get( $key );
				if ($mc->getResultCode() == Memcached::RES_NOTFOUND) {
					$value = false;
					if ( null !== $found ) {
						$found = false;
					}
				}

				$this->cache[ $key ] = $value;
			}

			if ( isset( $this->stats['get'] ) ) {
				++ $this->stats['get'];
			}

			$this->group_ops[ $group ][] = "get $id";

			if ( 'checkthedatabaseplease' === $value ) {
				unset( $this->cache[ $key ] );
				$value = false;
			}

			return $value;
		}

		function get_multi( $keys, $group = 'default' ) {
			$return = array();
			$gets   = array();
			foreach ( $keys as $i => $values ) {
				$mc     =& $this->get_mc( $group );
				$values = (array) $values;
				if ( empty( $values[1] ) ) {
					$values[1] = 'default';
				}

				list( $id, $group ) = (array) $values;
				$key = $this->key( $id, $group );

				if ( isset( $this->cache[ $key ] ) ) {

					if ( is_object( $this->cache[ $key ] ) ) {
						$return[ $key ] = clone $this->cache[ $key ];
					} else {
						$return[ $key ] = $this->cache[ $key ];
					}

				} else if ( in_array( $group, $this->no_mc_groups ) ) {
					$return[ $key ] = false;

				} else {
					$gets[ $key ] = $key;
				}
			}

			if ( ! empty( $gets ) ) {
				$null    = null;
				$results = $mc->getMulti( $gets, $null, Memcached::GET_PRESERVE_ORDER );
				$joined  = array_combine( array_keys( $gets ), array_values( $results ) );
				$return  = array_merge( $return, $joined );
			}

			@ ++ $this->stats['get_multi'];
			$this->group_ops[ $group ][] = "get_multi $id";
			$this->cache                 = array_merge( $this->cache, $return );

			return array_values( $return );
		}

		function key( $key, $group ) {
			if ( empty( $group ) ) {
				$group = 'default';
			}

			if ( false !== array_search( $group, $this->global_groups ) ) {
				$prefix = $this->global_prefix;
			} else {
				$prefix = $this->blog_prefix;
			}

			return preg_replace( '/\s+/', '', WP_CACHE_KEY_SALT . "$prefix$group:$key" );
		}

		function replace( $id, $data, $group = 'default', $expire = 0 ) {
			$key    = $this->key( $id, $group );
			$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
			$mc     =& $this->get_mc( $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			$result = $mc->replace( $key, $data, $expire );
			if ( false !== $result ) {
				$this->cache[ $key ] = $data;
			}

			return $result;
		}

		function set( $id, $data, $group = 'default', $expire = 0 ) {
			$key = $this->key( $id, $group );
			if ( isset( $this->cache[ $key ] ) && ( 'checkthedatabaseplease' === $this->cache[ $key ] ) ) {
				return false;
			}

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			$this->cache[ $key ] = $data;

			if ( in_array( $group, $this->no_mc_groups ) ) {
				return true;
			}

			$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
			$mc     =& $this->get_mc( $group );
			$result = $mc->set( $key, $data, $expire );

			return $result;
		}

		function set_multi( $items, $expire = 0, $group = 'default' ) {
			$sets   = array();
			$mc     =& $this->get_mc( $group );
			$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;

			foreach ( $items as $i => $item ) {
				if ( empty( $item[2] ) ) {
					$item[2] = 'default';
				}

				list( $id, $data, $group ) = $item;

				$key = $this->key( $id, $group );
				if ( isset( $this->cache[ $key ] ) && ( 'checkthedatabaseplease' === $this->cache[ $key ] ) ) {
					continue;
				}

				if ( is_object( $data ) ) {
					$data = clone $data;
				}

				$this->cache[ $key ] = $data;

				if ( in_array( $group, $this->no_mc_groups ) ) {
					continue;
				}

				$sets[ $key ] = $data;
			}

			if ( ! empty( $sets ) ) {
				$mc->setMulti( $sets, $expire );
			}
		}

		function colorize_debug_line( $line ) {
			$colors = array(
				'get'    => 'green',
				'set'    => 'purple',
				'add'    => 'blue',
				'delete' => 'red'
			);

			$cmd = substr( $line, 0, strpos( $line, ' ' ) );

			$cmd2 = "<span style='color:{$colors[$cmd]}'>$cmd</span>";

			return $cmd2 . substr( $line, strlen( $cmd ) ) . "\n";
		}

		function stats() {
			$stats_text = '';
			foreach ( $this->mc as $bucket => $mc ) {
				$stats = $mc->getStats();
				foreach ( $stats as $key => $details ) {
					$stats_text .= 'memcached: ' . $key . "\n\r";
					foreach ( $details as $name => $value ) {
						$stats_text .= $name . ': ' . $value . "\n\r";
					}
					$stats_text .= "\n\r";
				}
			}

			return $stats_text;
		}

		function &get_mc( $group ) {
			if ( isset( $this->mc[ $group ] ) ) {
				return $this->mc[ $group ];
			}

			return $this->mc['default'];
		}

		function __construct() {
			global $memcached_servers;
			$key_prefix = "";


			if ( isset( $memcached_servers ) ) {
				$buckets = $memcached_servers;
			} else {
				// check if we are on freistil instructure

				$freistilMemcachedSettings = ABSPATH."/../config/drupal/settings-d8-memcache.php";
				if(file_exists($freistilMemcachedSettings)){
					require_once $freistilMemcachedSettings;

					/**
					 * @var array $settings
					 */

					if(is_array($settings)){

						if(
							isset($settings["memcache_servers"])
							&&
							is_array($settings["memcache_servers"])
						)	$buckets = array_keys($settings["memcache_servers"]);

						if(
							isset($settings["memcache"])
							&&
							is_array($settings["memcache"])
							&&
							isset($settings["memcache"]["key_prefix"])
						) $key_prefix = $settings["memcache"]["key_prefix"];

					}


				} else {
					$buckets = array( '127.0.0.1:11211' );
				}
			}

			reset( $buckets );
			if ( is_int( key( $buckets ) ) ) {
				$buckets = array( 'default' => $buckets );
			}

			foreach ( $buckets as $bucket => $servers ) {
				$this->mc[ $bucket ] = new Memcached();

				$instances = array();
				foreach ( $servers as $server ) {
					@list( $node, $port ) = explode( ':', $server );
					if ( empty( $port ) ) {
						$port = ini_get( 'memcache.default_port' );
					}
					$port = intval( $port );
					if ( ! $port ) {
						$port = 11211;
					}

					$instances[] = array( $node, $port, 1 );
				}
				$this->mc[ $bucket ]->addServers( $instances );
			}

			global $blog_id, $table_prefix;
			$this->global_prefix = $key_prefix.'';
			$this->blog_prefix   = $key_prefix.'';
			if ( function_exists( 'is_multisite' ) ) {
				$this->global_prefix = ( is_multisite() || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) ) ? '' : $table_prefix;
				$this->blog_prefix   = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
			}

			$this->cache_hits   =& $this->stats['get'];
			$this->cache_misses =& $this->stats['add'];
		}


	}
} else {

	if ( function_exists( 'wp_using_ext_object_cache' ) ) {
		wp_using_ext_object_cache( false );
	} else {
		wp_die( 'Memcached class not available.' );
	}
}