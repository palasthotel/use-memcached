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
		add_action( 'wp_ajax_no_priv' . AJAX_ACTION_FLUSH, array( $this, 'flush' ) );
		add_action( 'wp_ajax_' . AJAX_ACTION_DISABLE, array(
			$this,
			'disable',
		) );
		add_action( 'wp_ajax_' . AJAX_ACTION_STATS, array( $this, 'stats' ) );
	}

	/**
	 * disable or enable cache
	 */
	function disable() {

		if(!current_user_can("manage_options")){
			wp_die(
				"No access.",
				"Restricted area",
				array(
					"response" => 401
				));
		}
		$this->plugin->memcache->toggleEnabled();
		wp_send_json_success();

	}

	/**
	 * flush memcached ajax response
	 */
	function flush() {
		if(!current_user_can("manage_options")){
			wp_die(
				"No access.",
				"Restricted area",
				array(
				"response" => 401
			));
		}
		wp_send_json_success( array(
			"response" => $this->plugin->memcache->flush(),
		) );
	}


	/**
	 * get memcached stats ajax response
	 */
	function stats() {

		if(!current_user_can("manage_options")){
			wp_die(
				"No access.",
				"Restricted area",
				array(
					"response" => 401
				));
		}

		wp_send_json_success( array(
			"response" => $this->plugin->memcache->stats( true ),
		) );
	}


}