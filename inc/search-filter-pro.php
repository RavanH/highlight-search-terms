<?php
/**
 * Search & Filter Pro compatibility
 *
 * @package Highlight Search Terms
 *
 * @since v.1.8.0
 */

add_filter(
	'hlst_input_get_args',
	function ( $args ) {
		// Known input GET variables with appropriate sanitize filters to test for.
		$search_filter_args = array(
			'_sf_s' => FILTER_SANITIZE_ENCODED,
			// maybe FILTER_SANITIZE_SPECIAL_CHARS or FILTER_SANITIZE_STRING with FILTER_FLAG_NO_ENCODE_QUOTES flag?
			// see https://www.php.net/manual/en/filter.filters.sanitize.php for more.
		);
		return array_merge( (array) $args, $search_filter_args );
	}
);

add_filter(
	'hlst_selectors',
	function ( $selectors ) {
		// Prepend known search result list selector.
		return array_merge( array( 'div.sf_result' ), (array) $selectors );
	}
);
