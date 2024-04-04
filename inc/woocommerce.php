<?php
/**
 * WooCommerce compatibility
 *
 * @package Highlight Search Terms
 *
 * @since v.1.8.0
 */

add_filter(
	'hlst_selectors',
	function ( $selectors ) {
		// Prepend known product search result and product page selector.
		return array_merge( array( 'div.woocommerce-container .product' ), (array) $selectors );
	}
);
