<?php


namespace Palasthotel\WordPress\UseMemcached;


/**
 * @property Plugin plugin
 */
class AdminBar {

	/**
	 * AdminBar constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 40 );
	}

	/**
	 * admin bar
	 */
	function admin_bar() {
		/**
		 * @var \WP_Admin_Bar $wp_admin_bar
		 */
		global $wp_admin_bar;

		$allServersConnected = $this->plugin->memcache->areAllServersConnected();
		$style = "background-color: #4CAF50;";
		$title = __("Memcache is active", DOMAIN);
		if($this->plugin->memcache->isDisabled()){
			$title = __("Memcache is disabled", DOMAIN);
			$style = "background-color: #90A4AE";
		} else if ( ! $allServersConnected ) {
			$title = __("Memcache is not working", DOMAIN);
			$style = "background-color: #F44336;";
		}

		$wp_admin_bar->add_node( array(
			'id'    => "use-memcached-info",
			'title' => "<div style='$style;margin-left: -10px;margin-right: -10px;padding: 0 10px;' title='$title'>💾 Cache</div>",
		) );

		$wp_admin_bar->add_node( array(
			'id'     => 'use-memcached-settings',
			'title'  => '<div style="cursor: pointer;">' .
			            __( '🛠 Settings', DOMAIN ) .
			            '</div>',
			'parent' => "use-memcached-info",
		) );

		if($allServersConnected){
			$wp_admin_bar->add_node( array(
				'id'     => 'use-memcached-flush',
				'title'  => '<div style="cursor: pointer;">' .
				            __( '🗑 Flush cache ', DOMAIN ) .
				            '<span id="use-memcached-loading"> </span>'.
				            '<span id="use-memcached-response"> </span>'.
				            '</div>',
				'parent' => "use-memcached-info",
			) );
		} else {
			$wp_admin_bar->add_node( array(
				'id'     => 'use-memcached-flush-not-working',
				'title'  => '<div style="opacity: 0.7; cursor: not-allowed;">' .
				            __( '🗑 Flush cache ', DOMAIN ) .
				            '</div>',
				'parent' => "use-memcached-info",
			) );
		}


		$this->plugin->assets->enqueueAdminJS();

	}


}