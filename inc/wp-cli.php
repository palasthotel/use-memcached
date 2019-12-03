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

	}

}

\WP_CLI::add_command(
	"memcache",
	__NAMESPACE__."\CLI",
	array(
		'shortdesc' => 'Memcache cli commands.',
	)
);