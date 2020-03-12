=== Use Memcached ===
Contributors: edwardbock
Donate link: http://palasthotel.de/
Tags: cache, performance
Requires at least: 5.0
Tested up to: 5.3.2
Stable tag: 1.0.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl

WP_Object_Cache implementation with Memcached.

== Description ==

Use this to optimize your website performance with Memcached instances.

== Installation ==

1. Upload `use-memcached.zip` to the `/wp-content/plugins/` directory
1. Extract the Plugin to a `use-memcached` Folder
1. Activate the plugin through the 'Plugins' menu in WordPress
1. The plugin will copy a object-cache.php file to /wp-content/ folder
1. You can provide custom memcached server settings in the wp-config.php file

== Frequently Asked Questions ==

= Do I have to configure something? =

If you are using a Memcached service with default values host 127.0.0.1 (localhost) and port 11211 or you are hosting with freistil.it than you don't need to configure anything.

With other hosters or service settings you need to configure some php variables in wp-config.php file.


== Screenshots ==



== Changelog ==

= 1.0.4 =
* process logs will only be written on WP_DEBUG sessions

= 1.0.3 =
* ignoring alloptions and notoptions key for performance reasons
* logging with ProcessLog

= 1.0.2 =
* deleted var_dump output

= 1.0.1 =
* First release

= 1.0.0 =
* Submitted to wordpress.org plugin repo version

== Upgrade Notice ==


== Arbitrary section ==

There’s a documentation at https://github.com/palasthotel/use-memcached


