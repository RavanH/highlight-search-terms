<?php
/*
Plugin Name: Highlight Search Terms
Plugin URI: http://status301.net/wordpress-plugins/highlight-search-terms
Description: Wraps search terms in the HTML5 mark tag when referrer is a non-secure search engine or within wp search results. Read <a href="http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/">Other Notes</a> for instructions and examples for styling the highlights. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=Highlight%20Search%20Terms&no_shipping=0&tax=0&bn=PP%2dDonationsBF&charset=UTF%2d8&lc=us" title="Thank you!">Tip jar</a>.
Version: 1.6
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

if ( ! defined( 'WPINC' ) ) die;

/* -----------------
 *      CLASS
 * ----------------- */

class HighlightSearchTerms {

	/**
	* Plugin variables
	*/

	// plugin version
	private static $version = '1.6';

	// filtered search terms
	private static $search_terms = null;

	// filtered hilite terms
	private static $hilite_terms = null;

	// Change or extend this to match themes content div ID or classes.
	// The hilite script will test div ids/classes and use the first one it finds.
	// When referencing an *ID name*, just be sure to begin with a '#'.
	// When referencing a *class name*, try to put the tag in front,
	// followed by a '.' and then the class name to *improve script speed*.
	static $selectors = array(
		'#groups-dir-list', '#members-dir-list', // BuddyPress compat
		'div.bbp-topic-content,div.bbp-reply-content,li.bbp-forum-info,.bbp-topic-title,.bbp-reply-title', // bbPress compat
		'article',
		'div.hentry',
		'div.post',
		'div.wp-block-query', // gutenberg query block 
		'#content',
		'#main',
		'div.content',
		'#middle',
		'#container',
		'div.container',
		'div.page',
		'#wrapper',
		'body'
	);

	/**
	* Plugin functions
	*/

	public static function init() {
		// -- HOOKING INTO WP -- //
		// append search query string to results permalinks
		// wp is the earliest hook where get_query_var('search_terms') will return results
		add_action( 'wp', array( __CLASS__, 'add_url_filters' ) );

		// enqueue main script
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_script' ) );

		// text domain
		//if ( is_admin() )
		//	add_action('plugins_loaded', array(__CLASS__, 'load_textdomain'));
	}

	public static function load_textdomain() {
		load_plugin_textdomain( 'highlight-search-terms' );
	}

	public static function add_url_filters() {
		// abort if admin or singular or no search terms.
		if ( is_admin() || ! self::have_search_terms() ) return;

		add_filter( 'post_link', array( __CLASS__, 'append_search_query' ) );
		add_filter( 'post_type_link', array( __CLASS__, 'append_search_query' ) );
		add_filter( 'page_link', array( __CLASS__, 'append_search_query' ) );
		// for bbPress search result links.
		add_filter( 'bbp_get_topic_permalink', array( __CLASS__, 'append_search_query' ) );
		add_filter( 'bbp_get_reply_url', array( __CLASS__, 'append_search_query' ) );
	}

	public static function append_search_query( $url ) {
		// we need in_the_loop() check here to prevent apending query to menu links. But it breaks bbPress url support...
		if ( in_the_loop() && ! strpos( $url, 'hilite=' ) ) {
			$url = add_query_arg( 'hilite', urlencode( "'" . implode( "','", self::get_search_terms() ) . "'" ), $url );
		}

		return $url;
	}

	public static function enqueue_script() {
		// abort if no search the_terms.
		if ( ! self::have_search_terms() && ! self::have_hilite_terms() ) return;

		wp_enqueue_script( 'hlst-extend', plugins_url( 'hlst-extend' . ( defined('WP_DEBUG') && true == WP_DEBUG ? '' : '.min' ) . '.js', __FILE__ ), array(), self::$version, true );

		$terms = wp_json_encode( (array) self::get_terms() );
		$selectors = wp_json_encode( (array) apply_filters( 'hlst_selectors', self::$selectors ) );

		$script = '/* Highlight Search Terms '.self::$version.' ( RavanH - http://status301.net/wordpress-plugins/highlight-search-terms/ ) */' . PHP_EOL;
		$script .= "const hlst = function(){window.hilite({$terms},{$selectors},true,true)};" . PHP_EOL;
		$script .= "window.addEventListener('DOMContentLoaded',hlst);window.addEventListener('post-load',hlst);";

		$script = apply_filters( 'hlst_inline_script', $script );
		wp_add_inline_script( 'hlst-extend', $script );
	}

	private static function split_search_terms( $search ) {
		if ( is_array( $search ) ) return $search;

		$return = array();

		if ( preg_match_all( '/([^\s"\',\+]+)|"([^"]*)"|\'([^\']*)\'/', stripslashes( urldecode( $search ) ), $terms ) ) {
			foreach( $terms[0] as $term ) {
				$term = trim( str_replace( array( '"','\'','%22','%27' ), '', $term ) );
				if ( !empty($term) )
					$return[] = $term;
			}
		}

		return $return;
	}

	private static function try_search_terms() {
		// try know query vars.
		$query_vars = apply_filters( 'hlst_query_vars', array( 'search_terms', 'bbp_search' ) );
		foreach ( (array) $query_vars as $qvar ) {
			$search = get_query_var( $qvar, false );
			if ( $search ) {
				self::$search_terms = self::split_search_terms( $search );
				return;
			}
		}
		// try known get parameters.
		$input_get_args = apply_filters( 'hlst_input_get_args', array() );
		if ( ! empty( $input_get_args ) ) {
			$inputs = filter_input_array( INPUT_GET, (array) $input_get_args, false );
			if ( is_array( $inputs ) ) {
				foreach ( $inputs as $qvar => $qval ) {
					if ( ! empty( $qval ) ) {
						self::$search_terms = self::split_search_terms( $qval );
						return;
					}
				}
			}	
		}
		// otherwise set empty array.
		self::$search_terms = array();
	}

	private static function get_search_terms() {
		// did we not look for search terms before?
		if ( ! isset( self::$search_terms ) ) {
			self::try_search_terms();
		}

		return self::$search_terms;
	}

	private static function have_search_terms() {
		return ! empty( self::get_search_terms() );
	}

	private static function get_hilite_terms() {
		// did we not look for hilite terms before?
		if ( ! isset( self::$hilite_terms ) ) {
			// try hilite get parameter
			$input = filter_input( INPUT_GET, 'hilite', FILTER_SANITIZE_ENCODED );
			if ( ! empty( $input ) ) {
				self::$hilite_terms = self::split_search_terms( $input );
			} else {
				self::$hilite_terms = array();
			}
		}
		return self::$hilite_terms;
	}

	private static function have_hilite_terms() {
		return ! empty( self::get_hilite_terms() );
	}

	private static function get_terms() {
		return array_merge( self::get_search_terms(), self::get_hilite_terms() );
	}
}

HighlightSearchTerms::init();
