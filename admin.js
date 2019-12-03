
(function($, Settings){

	console.log(Settings);

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
		if(isFlushing) return;
		isFlushing = true;
		$.post(Settings.ajaxUrl,{action: Settings.actions.flush}, ajax_flush_response);
	}
	function ajax_flush_response(response){
		isFlushing = false;
		console.log(response);
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
		console.log(response);
	}

	// ------------------------------
	// expose api to public
	// ------------------------------
	Settings.api = {
		flush: ajax_flush_request,
		stats: ajax_stats_request,
	};

})(jQuery, UseMemcached);
