<?php
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
	 * By default when a list of ticket products is obtained they will be returned as an
	 * array of product objects. Setting this property so it evaluates as false changes
	 * this behaviour so that an array of WP_Post objects are returned instead.
	 *
	 * This may be useful in some situation where we can make some efficiency savings
	 * and don't require all the extras that a bona fide product object can offer.
	 *
	 * @var bool
	 */
	public static $load_products_as_products = true;


	/**
	 * Returns the event post (if one can be found) relating to the specified product,
	 * otherwise it returns boolean false.
	 *
	 * @param mixed $product should be a valid WooCommerce product object or integer post ID
	 * @return bool|WP_Post
	 */
	public static function get_event_from_product( $product ) {
		// Ensure we've got a product object we can work with/check if it was loaded already
		if ( self::could_be_post_id( $product ) ) $product_id = $product;
		if ( is_object( $product ) && isset( $product->id ) ) $product_id = $product->id;

		if ( ! isset( $product_id ) ) return false;
		if ( isset( self::$events[$product->id] ) ) return self::$events[$product->id];

		// Is an event related to the product?
		$event_id = get_post_meta( $product->id, self::PRODUCT_TO_EVENT, true );
		if ( empty( $event_id ) ) return false;

		// Load the event
		$events = tribe_get_events( array( 'p' => $event_id ) );
		wp_reset_postdata();

		if ( 1 !== count( $events ) || ! isset( $events[0] ) ) return false;
		$event = $events[0];

		// Save the event for re-use then return
		self::$events[$product->id] = $event;
		return $event;
	}


	/**
	 * If the event has associated tickets that ought to be available for sale, but have
	 * run out of inventory, this function returns true.
	 *
	 * It does not assess tickets that are not currently onsale (ie we are outwith their
	 * selling date range). Note also that a negative result does not guarantee that there
	 * are indeed any associated products.
	 *
	 * @param $event
	 * @return bool
	 */
	public static function has_sold_out( $event ) {
		$inventory = self::total_inventory_for_event( $event );
		return ( 0 == $inventory );
	}


	/**
	 * Returns the ticket inventory for the specified event. Note that an event with no
	 * associated ticket products will return 0, as will an event that *does* have associated
	 * ticket products but has simply sold out.
	 *
	 * @param $event
	 * @return int
	 */
	public static function total_inventory_for_event( $event ) {
		// We need to load products as products here, but can temporarily change the setting to suit
		$load_as_products_setting = self::$load_products_as_products;
		self::$load_products_as_products = true;

		// Load the products!
		$products = self::get_onsale_products_from_event( $event );
		if ( false === $products ) return 0;

		// Check our inventory levels
		$inventory = 0;
		foreach ($products as $product)	$inventory += $product->get_stock_quantity();

		// Restore the load products as products setting then return inventory level
		self::$load_products_as_products = $load_as_products_setting;
		return absint( $inventory );
	}


	/**
	 * Returns an array of any products (representing tickets) which are currently
	 * on sale and linked the specified event or, if none can be found, boolean false.
	 *
	 * @param $event WP_Post object for an event or integer post ID
	 * @return bool|array (array of WP_Post objects)
	 */
	public static function get_onsale_products_from_event( $event ) {
		// Load the products!
		$products = self::get_products_from_event( $event );
		if ( false === $products ) return false;

		// Revise the list so we only have those currently on sale
		$revised_list = array();
		$now = date( 'Y-m-d H:i:s' );

		foreach ($products as $product) {
			$start = get_post_meta( $product->id, '_ticket_start_date', true );
			$end = get_post_meta( $product->id, '_ticket_end_date', true );

			if ( empty( $start ) || empty( $end ) ) continue;
			if ( $start <= $now && $end >= $now ) $revised_list[] = $product;
		}

		// Return false if none were found or else return the revised list
		return ( 0 === count( $revised_list ) ) ? false : $revised_list;
	}


	/**
	 * Returns an array of any products (representing tickets) linked to a specific
	 * event or, if none can be found, boolean false.
	 *
	 * @param $event WP_Post object for an event or integer post ID
	 * @return bool|array (array of WP_Post objects)
	 */
	public static function get_products_from_event( $event ) {
		// Ensure we've got an event object we can work with/check if it was loaded already
		if ( self::could_be_post_id( $event ) ) $event_id = $event;
		elseif ( is_object( $event ) && isset( $event->ID ) ) $event_id = $event->ID;

		if ( ! isset( $event_id ) ) return false;
		if ( isset( self::$products[$event_id] ) ) return self::$products[$event_id];

		// Are any products related to the event?
		$products = get_posts( array(
			'post_type' => 'product',
			'meta_key' => self::PRODUCT_TO_EVENT,
			'meta_value' => $event_id
		) );

		wp_reset_postdata();
		if ( empty( $products ) || ! is_array( $products ) ) return false;

		// Convert into product objects?
		if ( self::$load_products_as_products ) self::productify( $products );

		// Save the products for re-use then return
		self::$products[$event_id] = $products;
		return $products;
	}


	/**
	 * Determines if the specified value might be a valid post ID.
	 *
	 * @param $value
	 * @return bool
	 */
	protected static function could_be_post_id( $value ) {
		$cast_version = absint( $value );
		return ( $cast_version == $value );
	}


	/**
	 * Takes an array of WP_Post objects and converts them to WooCommerce
	 * product objects.
	 *
	 * Using WooCommerce's get_product() function ensures the correct type
	 * of product object (such as WC_Product_Simple) is used.
	 *
	 * @param array &$posts WP_Post objects
	 */
	protected static function productify( array &$posts ) {
		foreach ( $posts as &$post ) $post = get_product( $post );
	}
}