<?php

namespace Palasthotel\WordPress\UseMemcached;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class CLI{


	public function __construct() {
	}

	/**
	 * Flush memcache
	 *
	 * ## EXAMPLES
	 *
	 *     wp memcache flush
	 *
	 * @when after_wp_load
	 */
	public function flush(){
		if(!flush()){
			\WP_CLI::error( "Could not flush Memcached!\n\r" );
		}
		\WP_CLI::success( "Flush Memcached succeeded!\n\r" );
	}

	/**
	 * Memcache stats
	 *
	 * ## EXAMPLES
	 *
	 *     wp memcache stats
	 *
	 * @when after_wp_load
	 */
	public function stats(){
		\WP_CLI::log(stats());
	}

}

\WP_CLI::add_command(
	"memcache",
	__NAMESPACE__."\CLI",
	array(
		'shortdesc' => 'Memcache cli commands.',
	)
);