<?php
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