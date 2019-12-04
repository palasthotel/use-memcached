<?php


namespace Palasthotel\WordPress\UseMemcached;


/**
 * @property Plugin plugin
 */
class Ajax {

	/**
	 * Ajax constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		add_action( 'wp_ajax_' . AJAX_ACTION_FLUSH, array( $this, 'flush' ) );
		add_action( 'wp_ajax_' . AJAX_ACTION_STATS, array( $this, 'stats' ) );
	}


	/**
	 * flush memcached ajax response
	 */
	function flush() {
		wp_send_json_success( array(
			"response" => $this->plugin->memcache->flush(),
		) );
	}


	/**
	 * get memcached stats ajax response
	 */
	function stats() {
		wp_send_json_success( array(
			"response" => $this->plugin->memcache->stats( true ),
		) );
	}


}