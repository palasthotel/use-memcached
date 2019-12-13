<?php


namespace Palasthotel\WordPress\UseMemcached;


class Tools {
	const SLUG = "use_memcached";

	/**
	 * Tools constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action('admin_init', array($this, 'save_settings'));
	}

	/**
	 * get url to tools page
	 * @return string
	 */
	public function getUrl(){
		return admin_url("tools.php?page=use_memcached");
	}

	public function admin_menu() {
		add_management_page(
			__("Tools › Use Memcached", DOMAIN),
			__("Use Memcached", DOMAIN),
			"manage_options",
			self::SLUG,
			array( $this, 'render' )
		);
	}

	function save_settings(){
		if(
			isset($_POST['use_memcached_disable_toggle'])
			&&
			$_POST['use_memcached_disable_toggle'] == "yes"
		){
			$this->plugin->memcache->toggleDisabled();
			wp_redirect($this->getUrl());
		}
	}

	public function render(){
		echo "<div class='wrap'>";
		echo sprintf("<h2>%s</h2>", __("Use Memcached", DOMAIN));

		$buttonText = ($this->plugin->memcache->isDisabled())?
			__("Memcached is disabled. Enable memcached!", DOMAIN)
			:
			__("Memcache is enabled. Disable memcached!", DOMAIN);

		$primaryClass = ($this->plugin->memcache->isDisabled())?
			"": "button-primary";

		echo "<form method='post'>";
			echo "<input type='hidden' name='use_memcached_disable_toggle' value='yes' />";
			echo "<p style='text-align: center;'>";
			echo "<button class='button $primaryClass button-hero'>$buttonText</button>";
			echo "</p>";
		echo "</form>";

		if(!$this->plugin->memcache->isDisabled()){
			$this->renderStats();
		} else {
			printf(
				"<p style='text-align: center;' class='description'>%s</p>",
				__("No info available because Memcached is disabled.", DOMAIN)
			);
		}

		echo "</div>";
	}

	private function renderStats(){
		$stats = $this->plugin->memcache->stats();

		echo "<p>";
		echo "Freistil Prefix: ".$this->plugin->memcache->getGlobalPrefix()."<br/>";
		echo "Global Prefix: ".$this->plugin->memcache->getGlobalPrefix()."<br/>";
		echo "Blog Prefix: ".$this->plugin->memcache->getBlogPrefix();
		echo "</p>";


		echo "<textarea style='width: 100%;' rows='15'>$stats</textarea>";
	}
}