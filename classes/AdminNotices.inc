<?php


namespace Palasthotel\WordPress\UseMemcached;


/**
 * @property Plugin plugin
 * @property array messages
 * @property bool action_added
 */
class AdminNotices {

	const TYPE_ERROR = "notice-error";

	const TYPE_INFO = "notice-info";

	const TYPE_WARNING = "notice-warning";

	/**
	 * AdminNotices constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin       = $plugin;
		$this->messages     = array();
		$this->action_added = false;
		add_action( "admin_init", array( $this, "admin_init" ) );
	}

	public function admin_init() {
		$fh = $this->plugin->objectCacheFileHandler;

		if (
			! $fh->fileExists()
			&&
			! $fh->fileWasCopiedInThisRequest()
		) {
			// file is not existing and also was not copied. Something really went wrong
			$this->enqueue(
				__( "Missing object-cache.php could not be copied to wp-content folder. Please manually copy the template file from plugin folder use-memcached/templates/object-cache.php to wp-content/object-cache.php.", DOMAIN )
			);
		} else if (
		$fh->fileWasCopiedInThisRequest()
		) {
			// file was not loaded but was copied in this request
			$this->enqueue(
				__( "New object-cache.php file was copied to wp-content folder and will be available with next page load.", DOMAIN ),
				self::TYPE_INFO );
		} else if (
			$fh->fileExists()
			&&
			! $fh->fileWasCopiedInThisRequest()
			&&
			! $fh->isOurObjectCacheFile()
		) {
			$this->enqueue(
				__( "There is a foreign object-cache.php file in wp-content folder. Delete it if you want to use this plugin.", DOMAIN )
			);
		} else if (
			$fh->fileExists()
			&&
			! $fh->fileWasCopiedInThisRequest()
			&&
			! $fh->objectCacheVersionMatches()
		) {
			$this->enqueue(
				sprintf(
					__("object-cache.php version is %d but should be %d. This might cause errors. Please have a look at this!", DOMAIN),
					$this->plugin->objectCacheFileHandler->getActiveObjectCacheFileVersion(),
					OBJECT_CACHE_SCRIPT_VERSION
				),
				self::TYPE_WARNING
			);
		} else if ( ! function_exists( 'use_memcached' ) ) {
			$message   = sprintf(
				__( "Could not find %s function.", DOMAIN ),
				"use_memcached"
			);
			$message   .= "<br/>".(
				( ! class_exists( "Memcacheds" ) ) ?
					__( "Memcached class not exists!", DOMAIN )
					:
					__("Check if the wp-content/object-cache.php file equals to the templates/object-cache.php file of this plugin.", DOMAIN )
				);

			$this->enqueue( $message );

		} else if ( ! $this->plugin->memcache->areAllServersConnected() ) {
			$this->enqueue(
				__( "Some memcache servers are not reachable. Please check memcached service and connection settings.", DOMAIN )
			);
		}

	}

	/**
	 * add admin notice if not already done
	 */
	private function add_action() {
		if ( ! $this->action_added ) {
			$this->action_added = true;
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * enqueue message for admin notice
	 *
	 * @param string $message
	 * @param string $type
	 */
	public function enqueue( $message, $type = self::TYPE_ERROR ) {
		if ( ! isset( $this->messages[ $type ] ) ) {
			$this->messages[ $type ] = array();
		}
		$this->messages[ $type ][] = $message;
		$this->add_action();
	}

	/**
	 * display admin notices
	 */
	public function admin_notices() {
		foreach ( $this->messages as $type => $messages ) {
			foreach ( $messages as $msg ) {
				$this->print_message( $msg, $type );
			}
		}
	}

	/**
	 * @param string $message
	 * @param string $type
	 */
	public function print_message( $message, $type = self::TYPE_ERROR ) {
		$class = 'notice ' . $type;
		printf( '<div class="%1$s"><h2>Use Memcached</h2><p>%2$s</p></div>', $class, $message );
	}

}