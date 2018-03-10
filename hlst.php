<?php
/*
Plugin Name: Highlight Search Terms
Plugin URI: http://status301.net/wordpress-plugins/highlight-search-terms
Description: Wraps search terms in the HTML5 mark tag when referrer is a non-secure search engine or within wp search results. Read <a href="http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/">Other Notes</a> for instructions and examples for styling the highlights. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=Highlight%20Search%20Terms&item_number=1%2e4&no_shipping=0&tax=0&bn=PP%2dDonationsBF&charset=UTF%2d8&lc=us" title="Thank you!">Tip jar</a>.
Version: 1.5
Author: RavanH
Author URI: http://status301.net/
Text Domain: highlight-search-terms
*/

/*  Copyright 2018  RavanH  (email : ravanhagen@gmail.com)

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

if(!empty($_SERVER['SCRIPT_FILENAME']) && basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']))
	die('You can not access this page directly!');

/* -----------------
 *      CLASS
 * ----------------- */

class HighlightSearchTerms {

	/**
	* Plugin variables
	*/

	// plugin version
	private static $version = '1.5';

	// filtered search terms
	private static $search_terms = null;

	// Change or extend this to match themes content div ID or classes.
	// The hilite script will test div ids/classes and use the first one it finds.
	// When referencing an *ID name*, just be sure to begin with a '#'.
	// When referencing a *class name*, try to put the tag in front,
	// followed by a '.' and then the class name to *improve script speed*.
	static $areas = array(
			'#groups-dir-list', '#members-dir-list', // BuddyPress compat
			'div.bbp-topic-content,div.bbp-reply-content,li.bbp-forum-info,.bbp-topic-title,.bbp-reply-title', // bbPress compat
			'article',
			'div.hentry',
			'div.post',
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
		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'enqueue_script') );

		// Set query string as js variable in footer
		//add_action( 'wp_footer', array(__CLASS__, 'print_script') );

		// append search query string to results permalinks
		add_action( 'parse_query', array(__CLASS__,'add_url_filters') );

		// text domain
		//if ( is_admin() )
		//	add_action('plugins_loaded', array(__CLASS__, 'load_textdomain'));
	}

	public static function load_textdomain() {
		load_plugin_textdomain( 'highlight-search-terms' );
	}

	public static function add_url_filters() {
		if ( is_search() ) {
			add_filter('post_link', array(__CLASS__,'append_search_query') );
			add_filter('post_type_link', array(__CLASS__,'append_search_query') );
			add_filter('page_link', array(__CLASS__,'append_search_query') );
			add_filter('bbp_get_topic_permalink', array(__CLASS__,'append_search_query') );
		}
		// for bbPress search result links, but prevent bbp_is_search on admin triggered by Gravity Forms
		if ( function_exists('bbp_is_search') && !is_admin() && bbp_is_search() ) {
			add_filter('bbp_get_topic_permalink', array(__CLASS__,'append_search_query') );
			add_filter('bbp_get_reply_url', array(__CLASS__,'append_search_query') );
		}

	}

	public static function append_search_query( $url ) {
		// do we need in_the_loop() check here ? (it breaks bbPress url support)
		if ( self::have_search_terms() ) {
			$url = add_query_arg('hilite', urlencode( "'" . implode("','",self::$search_terms) . "'" ), $url);
		}
		return esc_url( $url );
	}

	public static function enqueue_script() {
		if ( defined('WP_DEBUG') && true == WP_DEBUG )
			wp_enqueue_script( 'hlst-extend', plugins_url('hlst-extend.js', __FILE__), array('jquery'), self::$version, true );
		else
			wp_enqueue_script( 'hlst-extend', plugins_url('hlst-extend.min.js', __FILE__), array('jquery'), self::$version, true );

		$script =  '/* Highlight Search Terms ' . self::$version . ' ( RavanH - http://status301.net/wordpress-plugins/highlight-search-terms/ ) */' . PHP_EOL;
		$script .= 'var hlst_query = ';
		$script .= self::have_search_terms() ? wp_json_encode( (array) self::$search_terms ) : '[]';
		$script .= '; var hlst_areas = ' . wp_json_encode( (array) self::$areas) . ';';
		wp_add_inline_script( 'hlst-extend', $script, 'before' );
	}

	public static function split_search_terms( $search ) {
		$return = array();
		if ( preg_match_all('/([^\s"\',\+]+)|"([^"]*)"|\'([^\']*)\'/', stripslashes(urldecode($search)), $terms) ) {
			foreach($terms[0] as $term) {
				$term = trim(str_replace(array('"','\'','%22','%27'), '', $term));
				if ( !empty($term) )
					$return[] = $term;
			}
		}
		return $return;
	}

	private static function have_search_terms() {
		// did we not look for search terms before?
		if ( !isset( self::$search_terms ) ) {
			// try regular parsed WP search terms
			if ( $searches = get_query_var( 'search_terms', false ) )
				self::$search_terms = $searches;
			// try for bbPress search or click-through from WP search results page
			elseif ( $search = get_query_var( 'bbp_search', false ) OR ( isset($_GET['hilite']) AND $search = $_GET['hilite'] ) )
				self::$search_terms = self::split_search_terms( $search );
			// nothing? then just leave empty array
			else
				self::$search_terms = array();
		}

		return empty( self::$search_terms ) ? false : true;
	}
}

HighlightSearchTerms::init();
