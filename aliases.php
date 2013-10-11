<?php
/**
 * Determines if tickets for the current or specified event have sold out.
 *
 * Note that an event that does not have any associated ticket products will
 * return false, since it is not available for sale in any case.
 *
 * @param mixed $event int|object = null
 * @return bool
 */
function woot_has_soldout($event = null) {
	if (null === $event) $event = TribeEvents::instance()->postIdHelper();
	return (Woot_Library::has_sold_out($event) && woot_has_tickets($event));
}

/**
 * Checks if the current or specified events ticket stock levels are below or
 * equal to the threshold level (defaults to 10).
 *
 * @param int $threshold = 10
 * @param mixed $event int|object = null
 * @return bool
 */
function woot_low_stock($threshold = 10, $event = null) {
	if (null === $event) $event = TribeEvents::instance()->postIdHelper();
	$levels = Woot_Library::total_inventory_for_event($event);
	return ($levels <= $threshold && woot_has_tickets($event));
}

/**
 * Determines if any tickets are associated with the current (or specified)
 * event ... this does not necessarily mean they are available for sale,
 * however, as they may have sold out or be outwith the sale dates.
 *
 * The same test can be performed, but counting only those ticket products
 * within sale dates, using woot_currently_has_tickets().
 *
 * @param mixed $event int|object = null
 * @return false
 */
function woot_has_tickets($event = null) {
	if (null === $event) $event = TribeEvents::instance()->postIdHelper();
	$tickets = woot_get_products(true, $event);
	return (false === $tickets) ? false : (1 <= count($tickets));
}

/**
 * Determines if any tickets are associated with the current (or specified)
 * event and are within the sale dates (start/end sale) ... this does not
 * necessarily mean they are actually available for sale, however, as they
 * may have sold out.
 *
 * @param mixed $event int|object = null
 * @return false
 */
function woot_currently_has_tickets($event = null) {
	if (null === $event) $event = TribeEvents::instance()->postIdHelper();
	$tickets = woot_get_products(false, $event);
	return (false === $tickets) ? false : (1 <= count($tickets));
}

/**
 * Determines if the current or specified product functions as an event ticket.
 *
 * @param null $product
 * @return bool
 */
function woot_is_event_ticket($product = null) {
	return (false === woot_get_event($product)) ? false : true;
}

/**
 * Returns the event associated with the current product (this can explicitly
 * be specified as either a post ID or object or else not be supplied, and
 * the current post in the loop will be assumed).
 *
 * Returns the event post object, if one is found, or else boolean false.
 *
 * @param $product mixed int|object = null
 * @return bool|WP_Post
 */
function woot_get_event($product = null) {
	if (null === $product) $product = get_the_ID();
	return Woot_Library::get_event_from_product($product);
}

/**
 * Returns the event start date associated with the current product (or a
 * specific product ID/object can be explicitly passed in). It varies from
 * tribe_get_event_date() in that it will by default return that date only
 * and not the time (but see woot_get_event_time()).
 *
 * A specific date format string can optionally be passed.
 *
 * @param null $product
 * @param string $format
 * @return bool|string
 */
function woot_get_event_date($product = null, $format = '') {
	$event = woot_get_event($product);
	if (false === $event) return false;
	return tribe_get_start_date($event, false, $format);
}

/**
 * Returns the event start time associated with the current product (or a
 * specific product ID/object can be explicitly passed in).
 *
 * A specific date format string can optionally be passed if there's a need
 * to deviate from the default format.
 *
 * @param null $product
 * @param string $format
 * @return bool|string
 */
function woot_get_event_time($product = null, $format = '') {
	$event = woot_get_event($product);
	if (false === $event) return false;
	if (empty($format)) return tribe_get_start_date($event, true, ' ');
	else return tribe_get_start_date($event, false, ' ' . $format);
}

/**
 * Returns ticket products associated with the current event (this can
 * explicitly be specified as either a post ID or object or else not be
 * supplied, and the current post in the loop will be assumed).
 *
 * Alias for woot_get_products().
 *
 * @param bool $onsale = true
 * @param mixed $event = null int|object
 * @return bool|array
 * @see woot_get_products()
 */
function woot_get_tickets($onsale = true, $event = null) {
	return woot_get_products($onsale, $event);
}

/**
 * Returns ticket products associated with the current event (this can
 * explicitly be specified as either a post ID or object or else not be
 * supplied, and the current post in the loop will be assumed).
 *
 * If as by default optional param $onsale is set to true then only those
 * products currently on sale will be returned. This can however be set
 * to false in order to retrieve all tickets, both those that are no longer
 * available and those that will be available for sale in the future.
 *
 * Returns an array of product objects, if any are found, or else boolean false.
 *
 * @param bool $onsale = true
 * @param mixed $event = null int|object
 * @return bool|array
 */
function woot_get_products($onsale = true, $event = null) {
	if (null === $event) $event = TribeEvents::instance()->postIdHelper();
	if ($onsale) return Woot_Library::get_onsale_products_from_event($event);
	else return Woot_Library::get_products_from_event($event);
}

