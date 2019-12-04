<?php


namespace Palasthotel\WordPress\UseMemcached;


/**
 * @property Plugin plugin
 */
class ObjectCacheFileHandler {

	/**
	 * @var bool
	 */
	private $wasCopied = false;


	/**
	 * ObjectCacheFileHandler constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		add_action( 'admin_init', array(
			$this,
			"copy_object_cache_template",
		) );
	}


	/**
	 * check if an object-cache.php file exists
	 *
	 * @return bool
	 */
	function fileExists() {
		return file_exists( DESTINATION_FILE );
	}

	/**
	 * check if the object-cache.php file is from this plugin
	 */
	function isOurObjectCacheFile() {
		return $this->fileExists() && defined( 'USE_MEMCACHED_OBJECT_CACHE_SCRIPT_VERSION' );
	}

	/**
	 * get version of object-cache.php in wp-content folder
	 *
	 * @return bool|int
	 */
	function getActiveObjectCacheFileVersion() {
		return ( ! $this->isOurObjectCacheFile() ) ? false : USE_MEMCACHED_OBJECT_CACHE_SCRIPT_VERSION;
	}

	/**
	 * check for version match of template and actual object-cache.php file
	 *
	 * @return bool
	 */
	function objectCacheVersionMatches() {
		return $this->isOurObjectCacheFile()
		       &&
		       $this->getActiveObjectCacheFileVersion() === OBJECT_CACHE_SCRIPT_VERSION;
	}

	/**
	 * check if file was copied in this exact request so its code is not
	 * available yet
	 *
	 * @return bool
	 */
	function fileWasCopiedInThisRequest() {
		return $this->wasCopied;
	}

	/**
	 *  copy the object-cache.php template if not exists
	 */
	function copy_object_cache_template() {

		if ( $this->fileExists() && $this->isOurObjectCacheFile() && ! $this->objectCacheVersionMatches() ) {
			// not the correct object cache version, so delete it
			unlink( DESTINATION_FILE );
		}

		if ( ! $this->fileExists() ) {
			// if there is no object-cache.php file create it from template
			$contents = file_get_contents( $this->plugin->templatesPath . "object-cache.php" );
			if ( $contents !== false ) {
				$this->wasCopied = file_put_contents( DESTINATION_FILE, $contents );
				chmod( DESTINATION_FILE, 0644 );
			}
		}

	}


}