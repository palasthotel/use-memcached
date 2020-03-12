<?php

// this file was copied here by use-memcached plugin

// always count up if file changed
define( 'USE_MEMCACHED_OBJECT_CACHE_SCRIPT_VERSION', 24 );
// this file needs to exist. otherwise we will fall back to core WP_Object_Cache
define( 'USE_MEMCACHED_OBJECT_CACHE_SCRIPT_ENABLED_FILE', WP_CONTENT_DIR . "/uploads/use-memcached.enabled" );
define( 'USE_MEMCACHED_FREISTIL_SETTINGS_FILE', ABSPATH . "/../config/drupal/settings-d8-memcache.php");

// --------------------------------------------------------------------
// UseMemcached configuration
// --------------------------------------------------------------------
class UseMemcachedConfiguration{

	private $config = array();

	private $blackList = [
		"notoptions",
		"alloptions",
	];

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	function isBlacklisted($key){
		return in_array($key, $this->blackList);
	}

	/**
	 * UseMemcachedConfiguration constructor.
	 */
	public function __construct() {
		$this->fetch();
	}

	/**
	 * check if file exists
	 * @return bool
	 */
	function isEnabled(){
		return is_file(USE_MEMCACHED_OBJECT_CACHE_SCRIPT_ENABLED_FILE);
	}

	/**
	 * @param boolean $isEnabled
	 */
	function setEnabled( $isEnabled){
		if ( $isEnabled ){
			$this->persist();
		} else {
			unlink(USE_MEMCACHED_OBJECT_CACHE_SCRIPT_ENABLED_FILE);
		}
	}

	/**
	 * @param int $seconds
	 *
	 * @return \UseMemcachedConfiguration
	 */
	function setExpires($seconds){
		$this->config["expires"] = ( $seconds > 0 )? intval($seconds): 0;
		return $this;
	}

	/**
	 * @return int
	 */
	function getExpires(){
		return intval($this->config["expires"]);
	}

	function isFreistil(){
		return is_file(USE_MEMCACHED_FREISTIL_SETTINGS_FILE);
	}

	function getFreistilSettings(){
		include USE_MEMCACHED_FREISTIL_SETTINGS_FILE;
		return $settings;
	}

	/**
	 * loads and overwrites configuration that was persisted
	 */
	function fetch(){
		$config = null;
		if($this->isEnabled()){
			$config = json_decode(file_get_contents(USE_MEMCACHED_OBJECT_CACHE_SCRIPT_ENABLED_FILE), true);
		}
		$tmp = (is_array($config))? $config : array();
		$this->config = array_merge(
			array(
				"expires" => 30,
			),
			$this->config,
			$tmp
		);
	}

	/**
	 * persists configuration
	 * @return bool|int
	 */
	function persist(){
		return file_put_contents(
			USE_MEMCACHED_OBJECT_CACHE_SCRIPT_ENABLED_FILE,
			json_encode($this->config)
		);
	}

	private static $instance;

	/**
	 * @return \UseMemcachedConfiguration
	 */
	public static function instance(){
		if(self::$instance == null){
			self::$instance = new UseMemcachedConfiguration();
		}
		return self::$instance;
	}

}

/**
 * @return \UseMemcachedConfiguration
 */
function use_memcached_get_configuration(){
	return UseMemcachedConfiguration::instance();
}


if (
	! use_memcached_get_configuration()->isEnabled()
	||
	! class_exists( 'Memcached' )
) {

	// --------------------------------------------------------------------
	// Fallback to core object cache
	// --------------------------------------------------------------------

	if ( function_exists( 'wp_using_ext_object_cache' ) ) {
		wp_using_ext_object_cache( false );
	} else {
		wp_die( 'Memcached class not available.' );
	}


} else {

	// --------------------------------------------------------------------
	// UseMemcached initialization
	// --------------------------------------------------------------------

	// if we are here we will load our object cache logic
	define( 'USE_MEMCACHED_OBJECT_CACHE_WAS_LOADED', true );

	if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
		// you can define that in wp-config.php
		define( 'WP_CACHE_KEY_SALT', 'salt-and-pepper' );
	}

	/**
	 * @return WP_Object_Cache
	 */
	function use_memcached() {
		global $wp_object_cache;

		return $wp_object_cache;
	}


	function wp_cache_add( $key, $data, $group = '', $expire = 30 ) {
		return use_memcached()->add( $key, $data, $group, $expire );
	}

	function wp_cache_incr( $key, $n = 1, $group = '' ) {
		return use_memcached()->incr( $key, $n, $group );
	}

	function wp_cache_decr( $key, $n = 1, $group = '' ) {
		return use_memcached()->decr( $key, $n, $group );
	}

	function wp_cache_close() {
		use_memcached()->close();
	}

	function wp_cache_delete( $key, $group = '' ) {
		return use_memcached()->delete( $key, $group );
	}

	function wp_cache_flush() {
		return use_memcached()->flush();
	}

	function wp_cache_get( $key, $group = '', $force = false, &$found = NULL ) {
		return use_memcached()->get( $key, $group, $force, $found );
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
		return use_memcached()->get_multi( $key_and_groups, $bucket );
	}

	/**
	 *
	 * @param array $items array(
	 *      array( 'key', 'data', 'group' ),
	 *      array( 'key', 'data' )
	 * );
	 * @param int $expire
	 * @param string $group
	 */
	function wp_cache_set_multi( $items, $expire = 30, $group = 'default' ) {
		use_memcached()->set_multi( $items, $expire, $group );
	}

	function wp_cache_init() {
		global $wp_object_cache;
		$wp_object_cache = new WP_Object_Cache();
	}

	function wp_cache_replace( $key, $data, $group = '', $expire = 30 ) {
		return use_memcached()->replace( $key, $data, $group, $expire );
	}

	function wp_cache_set( $key, $data, $group = '', $expire = 30 ) {
		if ( defined( 'WP_INSTALLING' ) == false ) {
			return use_memcached()->set( $key, $data, $group, $expire );
		} else {
			return use_memcached()->delete( $key, $group );
		}
	}

	function wp_cache_add_global_groups( $groups ) {
		use_memcached()->add_global_groups( $groups );
	}

	function wp_cache_add_non_persistent_groups( $groups ) {
		use_memcached()->add_non_persistent_groups( $groups );
	}

	// --------------------------------------------------------------------
	// WP_Object_Cache implementation
	// --------------------------------------------------------------------
	class WP_Object_Cache {

		public $global_groups = array(); // (was private)
		public $no_mc_groups = array(); // (was private)
		public $cache = array(); // (was private)
		/**
		 * @var \Memcached[]
		 */
		public $mc = array(); // (was private)
		public $stats = array( 'add'       => 0,
		                       'delete'    => 0,
		                       'get'       => 0,
		                       'get_multi' => 0,
		); // (was private)
		public $group_ops = array(); // (was private)
		public $memcache_debug = array(); // added for ElasticPress compatibility
		public $cache_enabled = true; // modified to allow wordpress to properly disable object cache in wp-activate.php +22 (was private)
		private $default_expiration = 30;
		/**
		 * @var \UseMemcachedConfiguration
		 */
		private $config;

		// --------------------------------------------------------------------
		// WP_Object_Cache constructor
		// --------------------------------------------------------------------
		function __construct() {

			$this->config = new UseMemcachedConfiguration();
			$this->default_expiration = $this->config->getExpires();


			global $memcached_servers;
			$this->freistil_prefix = "";


			if ( isset( $memcached_servers ) ) {
				$buckets = $memcached_servers;
			} else {

				// check if we are on Freistil infrastructure
				if ( $this->config->isFreistil() ) {
					$settings = $this->config->getFreistilSettings();

					/**
					 * @var array $settings
					 */
					if ( is_array( $settings ) ) {

						if (
							isset( $settings["memcache_servers"] )
							&&
							is_array( $settings["memcache_servers"] )
						) {
							$buckets = array_keys( $settings["memcache_servers"] );
						}

						if (
							isset( $settings["memcache"] )
							&&
							is_array( $settings["memcache"] )
							&&
							isset( $settings["memcache"]["key_prefix"] )
						) {
							$this->freistil_prefix = $settings["memcache"]["key_prefix"];
						}

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
				if(defined('WP_DEBUG') && WP_DEBUG){
					$this->mc[ $bucket ] ->setOption(Memcached::OPT_COMPRESSION,false);
				}

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
			$this->global_prefix = '';
			$this->blog_prefix   = '';
			if ( function_exists( 'is_multisite' ) ) {
				$this->global_prefix = ( is_multisite() || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) ) ? '' : $table_prefix;
				$this->blog_prefix   = ( is_multisite() ? $blog_id : $table_prefix ) . ':';
			}

			$this->global_prefix = $this->freistil_prefix.$this->global_prefix;
			$this->blog_prefix = $this->freistil_prefix.$this->blog_prefix;


			$this->cache_hits   =& $this->stats['get'];
			$this->cache_misses =& $this->stats['add'];
		}

		// --------------------------------------------------------------------
		// WP_Object_Cache methods
		// --------------------------------------------------------------------
		function add( $id, $data, $group = 'default', $expire = 30 ) {

			$key = $this->key( $id, $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			if ( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted( $id ) ) {
				$this->cache[ $key ] = $data;

				return true;
			} elseif ( isset( $this->cache[ $key ] ) && $this->cache[ $key ] !== false ) {
				return false;
			}

			$mc     =& $this->get_mc( $group );
			$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
			$result = $mc->add( $key, $data, $expire );

			$this->log("add ".$id, $data);

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

			$this->log("incr ".$id);

			return $this->cache[ $key ];
		}

		function decr( $id, $n = 1, $group = 'default' ) {
			$key                 = $this->key( $id, $group );
			$mc                  =& $this->get_mc( $group );
			$this->cache[ $key ] = $mc->decrement( $key, $n );

			$this->log("decr ".$id);

			return $this->cache[ $key ];
		}

		function close() {
			foreach ( $this->mc as $bucket => $mc ) {
				$mc->quit();
			}
		}

		function delete( $id, $group = 'default' ) {
			$key = $this->key( $id, $group );

			if ( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted( $id )  ) {
				unset( $this->cache[ $key ] );

				return true;
			}

			$mc =& $this->get_mc( $group );

			$result = $mc->delete( $key );

			$this->log("delete ".$id);

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
				$this->log("flush ".$group);
			}

			return $ret;
		}

		function get( $id, $group = 'default', $force = false, &$found = NULL ) {
			$key = $this->key( $id, $group );
			$mc  =& $this->get_mc( $group );

			if ( NULL !== $found ) {
				$found = true;
			}

			if ( isset( $this->cache[ $key ] ) && ( ! $force || in_array( $group, $this->no_mc_groups ) ) ) {
				if ( is_object( $this->cache[ $key ] ) ) {
					$value = clone $this->cache[ $key ];
				} else {
					$value = $this->cache[ $key ];
				}
			} else if ( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted( $id )  ) {
				$this->cache[ $key ] = $value = false;
			} else {
				$value = $mc->get( $key );

				$this->log("get ".$id, $value);

				if ( $mc->getResultCode() == Memcached::RES_NOTFOUND ) {
					$value = false;
					if ( NULL !== $found ) {
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
			$ids = array();
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

				} else if ( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted($id) ) {
					$return[ $key ] = false;

				} else {
					$gets[ $key ] = $key;
					$ids[] = $id;
				}
			}

			if ( ! empty( $gets ) ) {
				$null    = NULL;
				$results = $mc->getMulti( $gets, $null, Memcached::GET_PRESERVE_ORDER );
				$joined  = array_combine( array_keys( $gets ), array_values( $results ) );
				$return  = array_merge( $return, $joined );

				$this->log("getMulti ".implode(", ", $ids), $results);
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

		function replace( $id, $data, $group = 'default', $expire = 30 ) {
			$key    = $this->key( $id, $group );
			$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
			$mc     =& $this->get_mc( $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			$result = $mc->replace( $key, $data, $expire );

			$this->log("replace ".$id, $data);

			if ( false !== $result ) {
				$this->cache[ $key ] = $data;
			}

			return $result;
		}

		function set( $id, $data, $group = 'default', $expire = 30 ) {
			$key = $this->key( $id, $group );
			if ( isset( $this->cache[ $key ] ) && ( 'checkthedatabaseplease' === $this->cache[ $key ] ) ) {
				return false;
			}

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			$this->cache[ $key ] = $data;

			if ( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted($id) ) {
				return true;
			}

			$expire = ( $expire == 0 ) ? $this->default_expiration : $expire;
			$mc     =& $this->get_mc( $group );
			$result = $mc->set( $key, $data, $expire );

			$this->log("set ".$id, $data);

			return $result;
		}

		function set_multi( $items, $expire = 30, $group = 'default' ) {
			$sets   = array();
			$ids = array();
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

				if ( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted($id) ) {
					continue;
				}

				$sets[ $key ] = $data;
				$ids[] = $id;

			}

			if ( ! empty( $sets ) ) {
				$mc->setMulti( $sets, $expire );

				$this->log("setMulti ".implode(", ", $ids), array_values($sets));
			}
		}

		function colorize_debug_line( $line ) {
			$colors = array(
				'get'    => 'green',
				'set'    => 'purple',
				'add'    => 'blue',
				'delete' => 'red',
			);

			$cmd = substr( $line, 0, strpos( $line, ' ' ) );

			$cmd2 = "<span style='color:{$colors[$cmd]}'>$cmd</span>";

			return $cmd2 . substr( $line, strlen( $cmd ) ) . "\n";
		}

		/**
		 * @param bool $asArray
		 *
		 * @return string|array
		 */
		function stats( $asArray = false ) {
			$stats_text = '';
			$stats_arr  = array();
			foreach ( $this->mc as $bucket => $mc ) {
				$stats = $mc->getStats();
				if ( $asArray ) {
					$stats_arr[] = $stats;
				} else {
					foreach ( $stats as $key => $details ) {
						$stats_text .= 'memcached: ' . $key . "\n\r";
						foreach ( $details as $name => $value ) {
							$stats_text .= $name . ': ' . $value . "\n\r";
						}
						$stats_text .= "\n\r";
					}
				}

			}

			return ( $asArray ) ? $stats_arr : $stats_text;
		}

		function &get_mc( $group ) {
			if ( isset( $this->mc[ $group ] ) ) {
				return $this->mc[ $group ];
			}

			return $this->mc['default'];
		}

		function log($key, $value = null){
			if(
				(
					(defined('WP_DEBUG') && WP_DEBUG == true)
					||
					(defined('USE_MEMCACHED_PROCESS_LOG') && USE_MEMCACHED_PROCESS_LOG == true)
				)
				&&
				function_exists('process_log_write')
			){
				try {
					process_log_write( function ( $log ) use ( $value, $key ) {
						/**
						 * @var \Palasthotel\ProcessLog\ProcessLog $log
						 */
						$log->setEventType( "memcache" );
						$log->setChangedDataField( $key );
						if ( $value != NULL ) {
							$log->setChangedDataValueNew( $value );
						}

						return $log;
					} );
				} catch ( Exception $e ) {
					error_log($e->getMessage());
				}
			}
		}
	}




}
