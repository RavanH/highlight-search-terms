<?php
/*
Plugin Name: Highlight Search Terms
Plugin URI: http://status301.net/wordpress-plugins/highlight-search-terms
Description: Wraps search terms in the HTML5 mark tag when referrer is a non-secure search engine or within wp search results. Read <a href="http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/">Other Notes</a> for instructions and examples for styling the highlights. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=Highlight%20Search%20Terms&no_shipping=0&tax=0&bn=PP%2dDonationsBF&charset=UTF%2d8&lc=us" title="Thank you!">Tip jar</a>.
Version: 1.7.0
Author: RavanH
Author URI: http://status301.net/
Text Domain: highlight-search-terms
*/

/*  Copyright 2021  RavanH  (email : ravanhagen@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, <http://www.gnu.org/licenses/> or
	write to the Free Software Foundation Inc., 59 Temple Place,
	Suite 330, Boston, MA  02111-1307  USA.

	The GNU General Public License does not permit incorporating this
	program into proprietary programs.
*/

namespace HLST;

\defined( '\WPINC' ) || die;

/* Plugin version. */
const VERSION = '1.7.0';

/* Node selectors the script will search for. Use filter 'hlst_selectors' to change or override these. */
const NODE_SELECTORS = array (
	'#groups-dir-list', '#members-dir-list', // BuddyPress compat
	'div.bbp-topic-content,div.bbp-reply-content,li.bbp-forum-info,.bbp-topic-title,.bbp-reply-title', // bbPress compat
	'article',
	'div.hentry',
	'div.post',
	'div.post-content',
	'div.content',
	'div.page-content',
	'div.page',
	'div.wp-block-query', // gutenberg query block
	'main',
	'#content',
	'#main',
	'#middle',
	'#container',
	'div.container',
	'#wrapper',
	'body'
);

/* DOM events the script will initiate on. Use filter 'hlst_events' to append or override these. */
const EVENT_LISTENERS = array (
	'DOMContentLoaded',
	'post-load'
);

/* Query variables the plugin will try for. Use filter 'hlst_query_vars' to change or override these. */
const QUERY_VARS = array (
	'search_terms',
	'bbp_search'
);

/* Input get arguments the plugin will try for. Use filter 'hlst_input_get_args' to change or override these. */
const INPUT_GET_ARGS = array (
	'hilite'  => FILTER_SANITIZE_ENCODED
	// maybe FILTER_SANITIZE_SPECIAL_CHARS or FILTER_SANITIZE_STRING with FILTER_FLAG_NO_ENCODE_QUOTES flag?
	// see https://www.php.net/manual/en/filter.filters.sanitize.php for more.
);

/* ----------------- *
 *       CLASS       *
 * ----------------- */

class Terms {

	/**
	* Search terms.
	*/

	private static $terms = null;

	/**
	* Public methods.
	*/

	public static function get( $texturize = false ) {
		// Did we look for search terms before?
		if ( ! isset( self::$terms ) ) {
			self::try();
		}

		return self::$terms;
	}

	/**
	* Private methods.
	*/
	
	private static function try() {
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

	private static function split( $search ) {
		if ( \is_array( $search ) ) return $search;
	
		$return = array();
	
		if ( \preg_match_all( '/([^\s",\+]+)|"([^"]*)"|\'([^\']*)\'/', \stripslashes( \urldecode( $search ) ), $terms ) ) {
			foreach( $terms[0] as $term ) {
				$term = \trim( \str_replace( array( '"','%22','%27' ), '', $term ) );
				if ( ! empty( $term ) )
					$return[] = $term;
			}
		}
	
		return $return;
	}

	private function __construct() {
		// Nothing to do - there are no instances.
	}
	
}

/* ----------------- *
 *     FUNCTIONS     *
 * ----------------- */

// -- HOOKING INTO WP -- //
// Append search query string to results permalinks.
// 'wp' is the earliest hook where get_query_var('search_terms') will return results.
function add_url_filters() {
	// abort if admin or singular or no search terms.
	if ( \is_admin() || empty( Terms::get() ) ) return;

	\add_filter( 'post_link', __NAMESPACE__.'\add_search_query_arg' );
	\add_filter( 'post_type_link', __NAMESPACE__.'\add_search_query_arg' );
	\add_filter( 'page_link', __NAMESPACE__.'\add_search_query_arg' );
	// for bbPress search result links.
	\add_filter( 'bbp_get_topic_permalink', __NAMESPACE__.'\add_search_query_arg' );
	\add_filter( 'bbp_get_reply_url', __NAMESPACE__.'\add_search_query_arg' );
}
\add_action( 'wp', __NAMESPACE__.'\add_url_filters' );

function add_search_query_arg( $url ) {
	// we need in_the_loop() check here to prevent apending query to menu links. But it breaks bbPress url support...
	if ( \in_the_loop() && ! \strpos( $url, 'hilite=' ) ) {
		$terms = array();
		foreach ( Terms::get() as $term ) {
			//$term = str_replace( ' ', '+', $term );
			$terms[] = \strpos( $term, ' ' ) ? \urlencode( '"' . $term . '"' ) : \urlencode( $term );
		}
		if ( ! empty( $terms ) ) {
			$url = \add_query_arg( 'hilite', \implode( '+', $terms ), $url );
		}
	}

	return $url;
}

// Enqueue main script.
function enqueue_script() {
	static $script_enqueued = false;

	// abort if no search terms or script was already enqueued.
	if ( $script_enqueued || empty( Terms::get() ) ) return;

	//\wp_enqueue_script( 'hlst-extend', \plugins_url( 'hlst-extend' . ( \defined('\WP_DEBUG') && true == \WP_DEBUG ? '' : '.min' ) . '.js', __FILE__ ), array(), VERSION, true );
	\wp_enqueue_script( 'mark', \plugins_url( 'js/mark' . ( \defined('\WP_DEBUG') && true == \WP_DEBUG ? '' : '.min' ) . '.js', __FILE__ ), array(), '9.0.0', true );

	$terms = array();
	foreach( Terms::get() as $term ) {
		$terms[] = html_entity_decode( wptexturize( $term ) );
	}
	$terms = \wp_json_encode( $terms );
	$selectors = \wp_json_encode( (array) \apply_filters( 'hlst_selectors', NODE_SELECTORS ) );
	$events = (array) \apply_filters( 'hlst_events', EVENT_LISTENERS );

	$script = '/* Highlight Search Terms '.VERSION.' ( RavanH - http://status301.net/wordpress-plugins/highlight-search-terms/ ) */' . \PHP_EOL;
	//$script .= "const hlst = function(){window.hilite({$terms},{$selectors},true,true)};" . \PHP_EOL;
	$script .= "(function(){const t={$terms},m={$selectors}," . \PHP_EOL;
	$script .= 'hlst=function(){for(let n in m){let o=document.querySelectorAll(m[n]);if(!o.length){continue;}for(let i=0;i<o.length;i++){for(let s in t){var j=new Mark(o[i]);j.mark(t[s],{"className":"hilite term-"+s,"separateWordSearch":false});}}if(o.length){break;}}if(typeof Cufon=="function")Cufon.refresh();}' . \PHP_EOL;
	foreach ( $events as $event ) {
		$script .= "window.addEventListener('{$event}',hlst);";
	}
	$script .= '})()';

	$script = \apply_filters( 'hlst_inline_script', $script );
	//\wp_add_inline_script( 'hlst-extend', $script );
	\wp_add_inline_script( 'mark', $script );

	$script_enqueued = true;
}
\add_action( 'wp_enqueue_scripts', __NAMESPACE__.'\enqueue_script' );