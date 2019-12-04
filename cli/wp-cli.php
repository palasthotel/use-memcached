<?php

namespace Palasthotel\WordPress\UseMemcached;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class CLI{

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
		if(!Plugin::instance()->memcache->flush()){
			\WP_CLI::error( "Could not flush memcache!\n\r" );
		}
		\WP_CLI::success( "Flush memcache succeeded!\n\r" );
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
		\WP_CLI::log(Plugin::instance()->memcache->stats());
	}

}

\WP_CLI::add_command(
	"memcache",
	__NAMESPACE__."\CLI",
	array(
		'shortdesc' => 'Use memcached cli commands.',
	)
);