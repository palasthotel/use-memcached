jQuery(function($){
	console.log("use-memcached admin.js");
	let isFlushing = false;
	$("#wp-admin-bar-use-memcached-flush").click(function(){
		console.log("clicked");
		if(isFlushing) return;
		isFlushing = true;
		console.log("flushing");
		$.post("/wp-admin/admin-ajax.php",{action:"use_memcached_flush"}, function(res){
			isFlushing = false;
			console.log(res);
		});
	});
});