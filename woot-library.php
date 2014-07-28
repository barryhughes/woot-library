<?php
/**
 * Plugin name: Woot Library
 * Plugin URI: https://github.com/barryhughes/woot-library
 * Description: Simple helper library - to help you build customizations relating to your WooCommerce Tickets powered website.
 * Author: Barry Hughes
 * Version: 1.3
 * Author URI: http://codingkills.me
 */

function woot_library_loader() {
	global $woocommerce;

	// Preflight checks
	if ( ! class_exists( 'TribeWooTickets' ) ) return;
	if ( ! class_exists( 'TribeEvents' ) || -1 === version_compare( TribeEvents::VERSION, '3.1' ) ) return;
	if ( ! class_exists( 'WooCommerce' ) || -1 === version_compare( $woocommerce->version, '2.0.13' ) ) return;

	// Load
	require dirname( __FILE__ ) . '/aliases.php';
	require dirname( __FILE__ ) . '/library.php';
}

add_action('plugins_loaded', 'woot_library_loader', 50);