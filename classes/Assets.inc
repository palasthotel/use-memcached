<?php


namespace Palasthotel\WordPress\UseMemcached;


/**
 * @property Plugin plugin
 */
class Assets {

	/**
	 * Assets constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct($plugin) {
		$this->plugin = $plugin;
	}

	public function enqueueAdminJS(){
		wp_enqueue_script(
			HANDLE_ADMIN_JS,
			$this->plugin->url."js/admin.js",
			array("jquery"),
			filemtime($this->plugin->path."js/admin.js"),
			true
		);
		wp_localize_script(
			HANDLE_ADMIN_JS,
			"UseMemcached",
			array(
				"ajaxUrl" => admin_url("admin-ajax.php"),
				"actions" => array(
					"flush" => AJAX_ACTION_FLUSH,
					"stats" => AJAX_ACTION_STATS,
				)
			)
		);
	}
}