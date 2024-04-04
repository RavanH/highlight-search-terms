<?php
/**
 * Plugin Name: Highlight Search Terms
 * Plugin URI: http://status301.net/wordpress-plugins/highlight-search-terms
 * Description: Wraps search terms in the HTML5 mark tag. Read <a href="http://wordpress.org/extend/plugins/highlight-search-terms/#faq">FAQ's</a> for instructions and examples for styling the highlights. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=Highlight%20Search%20Terms&no_shipping=0&tax=0&bn=PP%2dDonationsBF&charset=UTF%2d8&lc=us" title="Thank you!">Tip jar</a>.
 * Version: 1.8.3
 * Author: RavanH
 * Author URI: http://status301.net/
 * Text Domain: highlight-search-terms
 *
 * @package Highlight Search Terms
 */

/*
	Copyright 2024  RavanH  (email : ravanhagen@gmail.com)

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
const VERSION = '1.8.3';

/* Node selectors the script will search for. Use filter 'hlst_selectors' to change or override these. */
const NODE_SELECTORS = array(
	'#groups-dir-list', // BuddyPress.
	'#members-dir-list', // BuddyPress.
	'div.bbp-topic-content,div.bbp-reply-content,li.bbp-forum-info,.bbp-topic-title,.bbp-reply-title', // bbPress.
	'article',
	'div.hentry',
	'div.post',
	'div.post-content',
	'div.content',
	'div.page-content',
	'div.page',
	'div.wp-block-query', // Gutenberg query block.
	'main',
	'#content',
	'#main',
	'#middle',
	'#container',
	'div.container',
	'#wrapper',
	'body', // Last, but not least.
);

/* DOM events the script will initiate on. Use filter 'hlst_events' to append or override these. */
const EVENT_LISTENERS = array(
	'DOMContentLoaded',
	'post-load',
);

/* Query variables the plugin will try for. Use filter 'hlst_query_vars' to change or override these. */
const QUERY_VARS = array(
	'search_terms',
	'bbp_search',
);

/* Input get arguments the plugin will try for. Use filter 'hlst_input_get_args' to change or override these. */
const INPUT_GET_ARGS = array(
	'hilite' => FILTER_SANITIZE_ENCODED,
	// maybe FILTER_SANITIZE_SPECIAL_CHARS or FILTER_SANITIZE_STRING with FILTER_FLAG_NO_ENCODE_QUOTES flag?
	// see https://www.php.net/manual/en/filter.filters.sanitize.php for more.
);

require_once __DIR__ . '/inc/class-terms.php';

// -- HOOKING INTO WP -- //
// Append search query string to results permalinks.
// 'wp' is the earliest hook where get_query_var('search_terms') will return results.

/**
 * Add URL filters.
 */
function add_url_filters() {
	// abort if admin or singular or no search terms.
	if ( \is_admin() || empty( Terms::get() ) ) {
		return;
	}

	\add_filter( 'post_link', __NAMESPACE__ . '\add_search_query_arg' );
	\add_filter( 'post_type_link', __NAMESPACE__ . '\add_search_query_arg' );
	\add_filter( 'page_link', __NAMESPACE__ . '\add_search_query_arg' );
	// TODO test replace with \add_filter( 'the_permalink', __NAMESPACE__ . '\\add_search_query_arg' );.

	// for bbPress search result links.
	\add_filter( 'bbp_get_topic_permalink', __NAMESPACE__ . '\add_search_query_arg' );
	\add_filter( 'bbp_get_reply_url', __NAMESPACE__ . '\add_search_query_arg' );
}
\add_action( 'wp', __NAMESPACE__ . '\add_url_filters' );

/**
 * Add search query arguments.
 *
 * @param string $url The URL.
 */
function add_search_query_arg( $url ) {
	// we need in_the_loop() check here to prevent apending query to menu links. But it breaks bbPress url support...
	if ( \in_the_loop() && ! \strpos( $url, 'hilite=' ) ) {
		$terms = array();
		foreach ( Terms::get() as $term ) {
			// $term = str_replace( ' ', '+', $term );
			$terms[] = \strpos( $term, ' ' ) ? \rawurlencode( '"' . $term . '"' ) : \rawurlencode( $term );
		}
		if ( ! empty( $terms ) ) {
			$url = \add_query_arg( 'hilite', \implode( '+', $terms ), $url );
		}
	}

	return $url;
}

/**
 * Enqueue main script.
 */
function enqueue_script() {
	static $script_enqueued = false;

	// abort if no search terms or script was already enqueued.
	if ( $script_enqueued || empty( Terms::get() ) ) {
		return;
	}

	// \wp_enqueue_script( 'hlst-extend', \plugins_url( 'hlst-extend' . ( \defined('\WP_DEBUG') && true == \WP_DEBUG ? '' : '.min' ) . '.js', __FILE__ ), array(), VERSION, true );
	\wp_enqueue_script( 'mark', \plugins_url( 'js/mark' . ( \defined( '\WP_DEBUG' ) && true == \WP_DEBUG ? '' : '.min' ) . '.js', __FILE__ ), array(), '9.0.0', true );

	$terms = array();
	foreach ( Terms::get() as $term ) {
		$terms[] = html_entity_decode( wptexturize( $term ) );
	}
	$terms     = \wp_json_encode( $terms );
	$selectors = \wp_json_encode( (array) \apply_filters( 'hlst_selectors', NODE_SELECTORS ) );
	$events    = (array) \apply_filters( 'hlst_events', EVENT_LISTENERS );

	$script = '/* Highlight Search Terms ' . VERSION . ' ( RavanH - http://status301.net/wordpress-plugins/highlight-search-terms/ ) */' . \PHP_EOL;
	// $script .= "const hlst = function(){window.hilite({$terms},{$selectors},true,true)};" . \PHP_EOL;
	$script .= "(function(){const t={$terms},m={$selectors}," . \PHP_EOL;
	$script .= 'hlst=function(){for(let n in m){let o=document.querySelectorAll(m[n]);if(!o.length){continue;}for(let i=0;i<o.length;i++){for(let s in t){var j=new Mark(o[i]);j.mark(t[s],{"className":"hilite term-"+s,"exclude":["script","style","title","head","html","mark","iframe","input","textarea"],"separateWordSearch":false});}}if(o.length){break;}}if(typeof Cufon=="function")Cufon.refresh();}' . \PHP_EOL;
	foreach ( $events as $event ) {
		$script .= "window.addEventListener('{$event}',hlst);";
	}
	$script .= '})()';

	$script = \apply_filters( 'hlst_inline_script', $script );
	// \wp_add_inline_script( 'hlst-extend', $script );
	\wp_add_inline_script( 'mark', $script );

	$script_enqueued = true;
}
\add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_script' );

/**
 * Plugin compatibilities
 *
 * @since v.1.8.0
 */
function load_compat() {
	// Make sure is_plugin_active() is available.
	include_once ABSPATH . 'wp-admin/includes/plugin.php';

	// Search & Filter Pro compatibility.
	if ( \is_plugin_active( 'search-filter-pro/search-filter-pro.php' ) ) {
		include_once __DIR__ . '/inc/search-filter-pro.php';
	}

	// FacetWP compatibility TODO.

	// WooCommerce compatibility.
	if ( \is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		include_once __DIR__ . '/inc/woocommerce.php';
	}
}
\add_action( 'wp_loaded', __NAMESPACE__ . '\load_compat' );
