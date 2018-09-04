<?php

namespace MyPlugin;

/*
Plugin Name: MyPlugin
Plugin URI:   http://automattic.com/
Description:  This is a test of a WordPress plugin file
Version:      1.0.0
Author:       Whoever
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wporg
Domain Path:  /languages
*/
class CoolPlugin {
	/**
	 * Plugin class constructor.
	 *
	 * @access public
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) ); // WordPressSymbol
	}

	/**
	 * Hook actions and filters, set options.
	 *
	 * @access public
	 */
	public function init() {
		$current_user = wp_get_current_user(); // WordPressSymbol
		load_plugin_textdomain( 'myplugin', false, basename( dirname( __FILE__ ) ) . '/languages' ); // WordPressSymbol
		add_action( 'admin_menu', array( $this, 'add_admin_pages' ) ); // WordPressSymbol
		add_filter( 'the_posts', array( $this, 'the_posts_intercept' ) ); // WordPressSymbol
		do_action( 'some_filter' ); // WordPressSymbol
		apply_filters( 'some_filter', 'hello' ); // WordPressSymbol
		some_unimported_function(); // unimported
	}

	public function test_constants() {
		if (DOING_AJAX) { // WordPressSymbol
			return 1;
		}
		if (XMLRPC_REQUEST) { // WordPressSymbol
			return 2;
		}
		if (file_exists(WP_PLUGIN_URL . '/foo/bar')) { // WordPressSymbol
			return 3;
		}
		$output = $this->test_classes();
		if (is_wp_error($output)) { // WordPressSymbol
			return 4;
		}
		$count = HOUR_IN_SECONDS + 10; // WordPressSymbol
		return $count;
	}

	public function test_classes() {
		if (WP_USE_THEMES) { // WordPressSymbol
			return new WP_Error(); // unimported
		}
		return new WP_Post(); // unimported
	}

	public function test_additional_constants() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT data.*
			FROM {$wpdb->data} bar
			LEFT JOIN {$wpdb->data_meta} foo
			ON foo.id = bar.id
			LIMIT 1",
			OBJECT_K // WordPressSymbol
		);
	}
}
