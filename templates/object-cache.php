<?php
/**
 * based on https://github.com/Automattic/wp-memcached/blob/master/object-cache.php
 */
// this file was copied here by use-memcached plugin

// always count up if file changed
define( 'USE_MEMCACHED_OBJECT_CACHE_SCRIPT_VERSION', 25 );
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


	function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
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
		use_memcached()->flush();
	}

	function wp_cache_get( $key, $group = '', $force = false, &$found = NULL ) {
		return use_memcached()->get( $key, $group, $force, $found );
	}

	/**
	 * Retrieve multiple cache entries
	 *
	 * @param array $groups Array of arrays, of groups and keys to retrieve
	 * @return mixed
	 */
	function wp_cache_get_multi( $groups ) {
		return use_memcached()->get_multi( $groups );
	}

	function wp_cache_init() {
		global $wp_object_cache;
		$wp_object_cache = new WP_Object_Cache();
	}

	function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
		return use_memcached()->replace( $key, $data, $group, $expire );
	}

	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		if ( defined( 'WP_INSTALLING' ) == false ) {
			return use_memcached()->set( $key, $data, $group, $expire );
		} else {
			return use_memcached()->delete( $key, $group );
		}
	}

	function wp_cache_switch_to_blog( $blog_id ) {
		use_memcached()->switch_to_blog( $blog_id );
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

		public $global_groups = array( 'WP_Object_Cache_global' );

		public $no_mc_groups = array();

		public $cache = array();

		/**
		 * @var \Memcached[]
		 */
		public $mc = array();
		public $stats = array();

		public $cache_hits = 0;
		public $cache_misses = 0;
		public $group_ops = array();

		public $flush_number = array();
		public $global_flush_number = null;

		public $cache_enabled = true;
		public $default_expiration = 0;
		public $max_expiration = 2592000; // 30 days

		public $stats_callback = null;

		public $connection_errors = array();

		public $global_prefix = '';
		public $blog_prefix   = '';

		public $key_salt = '';

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

				foreach ( $servers as $server  ) {
					if ( 'unix://' == substr( $server, 0, 7 ) ) {
						$node = $server;
						$port = 0;
					} else {
						list ( $node, $port ) = explode( ':', $server );

						if ( empty($port) || ! $port ) {
							$port = ini_get( 'memcache.default_port' );
						}

						$port = intval( $port );

						if ( ! $port ) {
							$port = 11211;
						}
					}

					$this->mc[ $bucket ]->addServer( $node, $port, true, 1, 1, 15, true, array( $this, 'failure_callback' ) );
					$this->mc[ $bucket ]->setCompressThreshold( 20000, 0.2 );
				}
			}

			global $blog_id, $table_prefix;
			$this->global_prefix = '';
			$this->blog_prefix  = '';
			if ( function_exists( 'is_multisite' ) ) {
				$this->global_prefix = ( is_multisite() || defined( 'CUSTOM_USER_TABLE' ) && defined( 'CUSTOM_USER_META_TABLE' ) ) ? '' : $table_prefix;
				$this->blog_prefix   = ( is_multisite() ? $blog_id : $table_prefix );
			}

			$this->global_prefix = $this->freistil_prefix.$this->global_prefix;
			$this->blog_prefix = $this->freistil_prefix.$this->blog_prefix;

			$this->salt_keys( WP_CACHE_KEY_SALT );

			$this->stats =  array(
				'get'        => 0,
				'get_multi'  => 0,
				'add'        => 0,
				'set'        => 0,
				'delete'     => 0,
			);

			$this->cache_hits   =& $this->stats['get'];
			$this->cache_misses =& $this->stats['add'];

		}

		// --------------------------------------------------------------------
		// WP_Object_Cache methods
		// --------------------------------------------------------------------
		function add( $id, $data, $group = 'default', $expire = 0 ) {
			$key = $this->key( $id, $group );

			// if in cache ignore!
			if( array_key_exists($this->cache, $key) ) return false;

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			if ( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted( $id )  ) {

				// if no mc groups or blacklist only cache in memory
				$this->cache[ $key ] = $data;

				return true;
			}

			$mc =& $this->get_mc( $group );

			$expire = intval( $expire );
			if ( 0 === $expire || $expire > $this->max_expiration ) {
				$expire = $this->default_expiration;
			}

			$result = $mc->add( $key, $data, $expire);

			$this->log("add $id", $data);

			if ( false !== $result ) {
				++$this->stats['add'];

				$this->cache[$key] = $data;
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

			$incremented = $mc->increment( $key, $n);
			if($incremented !== false) $this->cache[ $key ] = $incremented;

			$this->log("incr $id", $incremented);

			return $this->cache[ $key ][ 'value' ];
		}

		function decr( $id, $n = 1, $group = 'default' ) {
			$key                 = $this->key( $id, $group );
			$mc                  =& $this->get_mc( $group );

			$decremented = $mc->decrement( $key, $n );
			if($decremented !== false) $this->cache[ $key ] = $decremented;

			$this->log("decr $id", $decremented);

			return $this->cache[ $key ][ 'value' ];
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
			unset( $this->cache[ $key ] );

			$this->log("delete $id");

			++$this->stats['delete'];

			$this->group_ops[ $group ][] = "delete $id";

			return $result;
		}

		function flush() {
			// Do not use the memcached flush method. It acts on an
			// entire memcached server, affecting all sites.
			// Flush is also unusable in some setups, e.g. twemproxy.
			// Instead, rotate the key prefix for the current site.
			// Global keys are rotated when flushing on the main site.
			$this->cache = array();

			$this->rotate_site_keys();

			if ( is_main_site() ) {
				$this->rotate_global_keys();
			}
		}

		function rotate_site_keys() {
			$this->add( 'flush_number', intval( microtime( true ) * 1e6 ), 'WP_Object_Cache' );

			$this->flush_number[ $this->blog_prefix ] = $this->incr( 'flush_number', 1, 'WP_Object_Cache' );
		}

		function rotate_global_keys() {
			$this->add( 'flush_number', intval( microtime( true ) * 1e6 ), 'WP_Object_Cache_global' );

			$this->global_flush_number = $this->incr( 'flush_number', 1, 'WP_Object_Cache_global' );
		}

		function get( $id, $group = 'default', $force = false, &$found = null ) {
			$key = $this->key( $id, $group );
			$mc =& $this->get_mc( $group );

			if( array_key_exists($key, $this->cache) ){
				$found = true;
				if( is_object($this->cache[$key]) ){
					return clone $this->cache[$key];
				}
				return $this->cache[$key];
			} else if( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted($id) ){
				$found = false;
				return false;
			}

			$found = false;
			$flags = false;
			$value = $mc->get( $key, null, $flags );

			$this->log("get $id", $value);

			if( false === $flags){
				$found = false;
				return false;
			}

			$found = true;
			$this->cache[$key] = $value;

			++$this->stats['get'];

			$this->group_ops[ $group ][] = "get $id";

			return $value;
		}

		function get_multi( $groups ) {
			/*
			format: $get['group-name'] = array( 'key1', 'key2' );
			*/
			$return = array();
			$ids = array();

			foreach ( $groups as $group => $ids ) {

				foreach ( $ids as $id ) {
					$value = $this->get($id, $group);
					$key = $this->key( $id, $group );
					$return[$key] = $value;
				}

				$this->group_ops[ $group ][] = "get_multi $id";
			}

			++$this->stats['get_multi'];

			$this->log("getMulti ".implode(", ", $ids), $return);

			return $return;
		}

		function flush_prefix( $group ) {
			if ( 'WP_Object_Cache' === $group || 'WP_Object_Cache_global' === $group ) {
				// Never flush the flush numbers.
				$number = '_';
			} elseif ( false !== array_search( $group, $this->global_groups ) ) {
				if ( ! isset( $this->global_flush_number ) ) {
					$this->global_flush_number = intval( $this->get( 'flush_number', 'WP_Object_Cache_global' ) );
				}

				if ( 0 === $this->global_flush_number ) {
					$this->rotate_global_keys();
				}

				$number = $this->global_flush_number;
			} else {
				if ( ! isset( $this->flush_number[ $this->blog_prefix ] ) ) {
					$this->flush_number[ $this->blog_prefix ] = intval( $this->get( 'flush_number', 'WP_Object_Cache' ) );
				}

				if ( 0 === $this->flush_number[ $this->blog_prefix ] ) {
					$this->rotate_site_keys();
				}

				$number = $this->flush_number[ $this->blog_prefix ];
			}

			return $number . ':';
		}

		function key( $key, $group ) {
			if ( empty( $group ) ) {
				$group = 'default';
			}

			$prefix = $this->key_salt;

			$prefix .= $this->flush_prefix( $group );

			if ( false !== array_search( $group, $this->global_groups ) ) {
				$prefix .= $this->global_prefix;
			} else {
				$prefix .= $this->blog_prefix;
			}

			return preg_replace( '/\s+/', '', "$prefix:$group:$key" );
		}

		function replace( $id, $data, $group = 'default', $expire = 0 ) {
			$key    = $this->key( $id, $group );

			$expire = intval( $expire );
			if ( 0 === $expire || $expire > $this->max_expiration ) {
				$expire = $this->default_expiration;
			}

			$mc     =& $this->get_mc( $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			$result = $mc->replace( $key, $data, $expire );
			$this->cache[$key] = $data;

			$this->log("replace $id", $data);

			return $result;
		}

		function set( $id, $data, $group = 'default', $expire = 0 ) {
			$key = $this->key( $id, $group );

			if ( is_object( $data ) ) {
				$data = clone $data;
			}

			$this->cache[ $key ] = $data;

			if ( in_array( $group, $this->no_mc_groups ) || $this->config->isBlacklisted($id)) {
				return true;
			}

			$expire = intval( $expire );
			if ( 0 === $expire || $expire > $this->max_expiration ) {
				$expire = $this->default_expiration;
			}

			$mc     =& $this->get_mc( $group );
			$result = $mc->set( $key, $data, $expire );

			$this->log("set $id", $data);

			++$this->stats[ 'set' ];
			$this->group_ops[$group][] = "set $id";

			return $result;
		}

		function switch_to_blog( $blog_id ) {
			global $table_prefix;

			$blog_id = (int) $blog_id;

			$this->blog_prefix = ( is_multisite() ? $blog_id : $table_prefix );
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

		function failure_callback( $host, $port ) {
			$this->connection_errors[] = array(
				'host' => $host,
				'port' => $port,
			);
		}

		function salt_keys( $key_salt ) {
			if ( strlen( $key_salt ) ) {
				$this->key_salt = $key_salt . ':';
			} else {
				$this->key_salt = '';
			}
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
