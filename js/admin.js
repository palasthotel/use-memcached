
(function($, Settings){

	const $counter = $("#use-memcached-add-count");
	const $flushLoading = $("#use-memcached-loading");
	const $flushResponse = $("#use-memcached-response");

	$(function(){
		addEventHandlers();
	});

	function addEventHandlers(){
		$("#wp-admin-bar-use-memcached-flush").click(ajax_flush_request);
	}

	/**
	 * flush memcached
	 */
	let isFlushing = false;
	function ajax_flush_request(){
		console.log("flush");
		if(isFlushing) return;
		setLoading(true);
		isFlushing = true;
		$.post(Settings.ajaxUrl,{action: Settings.actions.flush}, ajax_flush_response);
	}
	let _flushResponseTimeout = null;
	function ajax_flush_response(response){
		isFlushing = false;
		if(response.success && response.data.response){
			$counter.text(0);
			$flushResponse.text("👍");
		} else {
			$flushResponse.text("🚨");
		}
		// clearTimeout(_flushResponseTimeout);
		// _flushResponseTimeout = setTimeout(function(){
		// 	$flushResponse.text("");
		// }, 1500);
		setLoading(false);
	}

	/**
	 * memcached stats
	 */
	let isRequestingStats = false;
	function ajax_stats_request(){
		if(isRequestingStats) return;
		isRequestingStats =  true;
		$.post(Settings.ajaxUrl, {action: Settings.actions.stats}, ajax_stats_response);
	}
	function ajax_stats_response(response){
		isRequestingStats = false;
		console.log(response.data);
	}

	/**
	 * memcache set disabled
	 * @type {null}
	 * @private
	 */
	let isRequestingSetDisabled = false;
	function ajax_set_disabled(disabled){
		if(isRequestingSetDisabled) return;
		isRequestingSetDisabled = true;
		const args = Settings.args
		$.post(Settings.ajaxUrl, {
			action: Settings.actions.disable,
		})
	}

	let _isLoadingInterval = null;
	function setLoading(isLoading){

		if(isLoading && _isLoadingInterval != null) return;

		if(isLoading){
			_isLoadingInterval = setInterval(function(){
				switch ($flushLoading.text()) {
					case "/":
						$flushLoading.text("–");
						break;
					case "–":
						$flushLoading.text("\\");
						break;
					case "\\":
					default:
						$flushLoading.text("/");
						break;

				}
			},150);
		} else {
			clearInterval(_isLoadingInterval);
			_isLoadingInterval = null;
			$flushLoading.text("");
		}
	}

	// ------------------------------
	// expose api to public
	// ------------------------------
	Settings.api = {
		flush: ajax_flush_request,
		stats:ajax_stats_request,
	};

})(jQuery, UseMemcached);
