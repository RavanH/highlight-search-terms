<?php
/**
 * Terms class
 *
 * @package Highlight Search Terms
 */

namespace HLST;

/**
 * Terms class.
 *
 * @since 1.7
 */
class Terms {
	/**
	 * Search terms.
	 *
	 * @var $terms The found search terms.
	 */
	private static $terms = null;

	/**
	 * Get search terms.
	 */
	public static function get() {
		// Did we look for search terms before?
		if ( ! isset( self::$terms ) ) {
			self::set();
		}

		return self::$terms;
	}

	/**
	 * Set.
	 */
	private static function set() {
		// try know query vars.
		$query_vars = \apply_filters( 'hlst_query_vars', QUERY_VARS );
		foreach ( (array) $query_vars as $qvar ) {
			$search = \get_query_var( $qvar, false );
			if ( $search ) {
				self::$terms = self::split( $search );
				return;
			}
		}

		// try known get parameters.
		$input_get_args = \apply_filters( 'hlst_input_get_args', INPUT_GET_ARGS );
		if ( ! empty( $input_get_args ) ) {
			$inputs = \filter_input_array( INPUT_GET, (array) $input_get_args, false );
			if ( \is_array( $inputs ) ) {
				foreach ( $inputs as $qvar => $qval ) {
					if ( ! empty( $qval ) ) {
						self::$terms = self::split( $qval );
						return;
					}
				}
			}
		}

		// otherwise set empty array.
		self::$terms = array();
	}

	/**
	 * Split.
	 *
	 * @param string|array $search The search string to split up into terms.
	 */
	private static function split( $search ) {
		if ( \is_array( $search ) ) {
			return $search;
		}

		$return = array();

		if ( \preg_match_all( '/([^\s",\+]+)|"([^"]*)"|\'([^\']*)\'/', \stripslashes( \urldecode( $search ) ), $terms ) ) {
			foreach ( $terms[0] as $term ) {
				$term = \trim( \str_replace( array( '"', '%22', '%27' ), '', $term ) );
				if ( ! empty( $term ) ) {
					$return[] = $term;
				}
			}
		}

		return $return;
	}
}
