<?php
/**
 * Plugin name: Woot Library
 * Plugin URI: https://github.com/barryhughes/woot-library
 * Description: Simple helper library - to help you build customizations relating to your WooCommerce Tickets powered website.
 * Author: Barry Hughes
 * Version: 1.0
 * Author URI: http://codingkills.me
 */


class Woot_Library
{
	/**
	 * Meta key used to link products back to the parent event.
	 */
	const PRODUCT_TO_EVENT = '_tribe_wooticket_for_event';

	/**
	 * Container for any events we load (indexed by the product IDs they are related to).
	 * This is a one-to-one relationship and so a flat array structure.
	 *
	 * @var array
	 */
	protected static $events = array();

	/**
	 * Container for any products we load (indexed by the event IDs they are related to).
	 * One event may relate to many products and so each element will itself be an array
	 * of all related products.
	 *
	 * @var array
	 */
	protected static $products = array();


	/**
	 * Returns the event post (if one can be found) relating to the specified product,
	 * otherwise it returns boolean false.
	 *
	 * @param $product should be a valid WooCommerce product object
	 * @return bool|WP_Post
	 */
	public static function get_event_from_product($product) {
		// Ensure we've got a product object we can work with/check if it was loaded already
		if (!is_object($product) || !isset($product->id)) return false;
		if (isset(self::$events[$product->id])) return self::$events[$product->id];

		// Is an event related to the product?
		$event_id = get_post_meta($product->id, self::PRODUCT_TO_EVENT, true);
		if (empty($event_id)) return false;

		// Load the event
		$events = tribe_get_events(array('p' => $event_id));
		wp_reset_postdata();

		if (1 !== count($events) || !isset($events[0])) return false;
		$event = $events[0];

		// Save the event for re-use then return
		self::$events[$product->id] = $event;
		return $event;
	}


	/**
	 * Returns an array of any products (representing tickets) linked to a specific
	 * event or, if none can be found, boolean false.
	 *
	 * @param $event
	 * @return bool|array (array of WP_Post objects)
	 */
	public static function get_products_from_event($event) {
		// Ensure we've got an event object we can work with/check if it was loaded already
		if (!is_object($event) || !isset($event->ID)) return false;
		if (isset(self::$products[$event->ID])) return self::$products[$event->ID];

		// Are any products related to the event?
		$products = get_posts(array(
			'post_type' => 'product',
			'meta_key' => self::PRODUCT_TO_EVENT,
			'meta_value' => $event->ID
		));

		wp_reset_postdata();
		if (empty($products) || !is_array($products)) return false;

		// Save the products for re-use then return
		self::$products[$event->ID] = $products;
		return $products;
	}
}