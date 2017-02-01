<?php
/*
Plugin Name: Highlight Search Terms
Plugin URI: http://status301.net/wordpress-plugins/highlight-search-terms
Description: Wraps search terms in the HTML5 mark tag when referrer is a non-secure search engine or within wp search results. Read <a href="http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/">Other Notes</a> for instructions and examples for styling the highlights. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=Highlight%20Search%20Terms&item_number=1%2e4&no_shipping=0&tax=0&bn=PP%2dDonationsBF&charset=UTF%2d8&lc=us" title="Thank you!">Tip jar</a>.
Version: 1.4.3
Author: RavanH
Author URI: http://status301.net/
Text Domain: highlight-search-terms
*/

/*  Copyright 2016  RavanH  (email : ravanhagen@gmail.com)

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
	private static $version = null;

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

	public static function get_version() {
		if ( null === self::$version ) { // "=== null" is twice as fast in PHP 5 while "is_null()" is slightly faster in PHP 7
			$data = get_file_data( __FILE__ , array('Version' => 'Version') );
			self::$version = isset($data['Version']) ? $data['Version'] : false;
		}
		return self::$version;
	}

	public static function init() {
		// -- HOOKING INTO WP -- //
		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'enqueue_script') );

		// Set query string as js variable in footer
		add_action( 'wp_footer', array(__CLASS__, 'print_script') );

		// append search query string to results permalinks
		add_action( 'parse_query', array(__CLASS__,'add_url_filters') );
	}

	public static function add_url_filters() {
		if ( is_search() ) {
			add_filter('post_link', array(__CLASS__,'append_search_query') );
			add_filter('post_type_link', array(__CLASS__,'append_search_query') );
			add_filter('page_link', array(__CLASS__,'append_search_query') );
			add_filter('bbp_get_topic_permalink', array(__CLASS__,'append_search_query') );
		}
		// for bbPress search result links
		if ( function_exists('bbp_is_search') && bbp_is_search() ) {
			add_filter('bbp_get_topic_permalink', array(__CLASS__,'append_search_query') );
			add_filter('bbp_get_reply_url', array(__CLASS__,'append_search_query') );
		}

	}

	public static function append_search_query( $url ) {
		// do we need in_the_loop() check here ? (it breaks bbPress url support)
		if ( self::have_search_terms() ) {
			$url = add_query_arg('hilite', urlencode( implode(' ',self::$search_terms)), $url);
		}
		return $url;
	}

	public static function enqueue_script() {
		if ( defined('WP_DEBUG') && true == WP_DEBUG )
			wp_enqueue_script('hlst-extend', plugins_url('hlst-extend.js', __FILE__), array('jquery'), self::get_version(), true);
		else
			wp_enqueue_script('hlst-extend', plugins_url('hlst-extend.min.js', __FILE__), array('jquery'), self::get_version(), true);
	}

	public static function split_search_terms( $search ) {
		$return = array();
		if ( preg_match_all('/([^\s"\']+)|"([^"]*)"|\'([^\']*)\'/', stripslashes(urldecode($search)), $terms) ) {
			foreach($terms[0] as $term) {
				$term = esc_attr(trim(str_replace(array('"','\'','%22'), '', $term)));
				if ( !empty($term) )
					$return[] = esc_attr($term);
			}
		}
		return $return;
	}

	private static function have_search_terms() {
		// did we get search terms before?
		if ( isset( self::$search_terms ) )
			return empty( self::$search_terms ) ? false : true;
		
		// prepare js array
		self::$search_terms = array();
		
		// check for click-through from search results page
		if ( isset($_GET['hilite']) ) {
			self::$search_terms = self::split_search_terms( $_GET['hilite'] );
		} 
		// try regular parsed WP search terms
		elseif ( $searches = get_query_var( 'search_terms', false ) ) { 
			foreach ( (array)$searches as $search ) {
				self::$search_terms[] = esc_attr( $search );
			}
		} 
		// try for bbPress search
		elseif ( $search = get_query_var( 'bbp_search', false ) ) {		
			self::$search_terms = self::split_search_terms( $search );
		}
						
		return empty( self::$search_terms ) ? false : true;
	}

	// Get query variables and print footer script
	public static function print_script() {
		$terms = self::have_search_terms() ? implode('","',self::$search_terms) : '';

		echo '
<!-- Highlight Search Terms ' . self::get_version() . ' ( RavanH - http://status301.net/wordpress-plugins/highlight-search-terms/ ) -->
<script type="text/javascript">
var hlst_query = new Array("' . $terms . '");
var hlst_areas = new Array("' . implode('","',self::$areas) . '");
</script>
';
	}

}

HighlightSearchTerms::init();
