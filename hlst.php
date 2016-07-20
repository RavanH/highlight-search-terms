<?php
/*
Plugin Name: Highlight Search Terms
Plugin URI: http://status301.net/wordpress-plugins/highlight-search-terms
Description: Wraps search terms in the HTML5 mark tag when referrer is a non-secure search engine or within wp search results. Read <a href="http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/">Other Notes</a> for instructions and examples for styling the highlights. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=Highlight%20Search%20Terms&item_number=1%2e4&no_shipping=0&tax=0&bn=PP%2dDonationsBF&charset=UTF%2d8&lc=us" title="Thank you!">Tip jar</a>.
Version: 1.4.1
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

	// Change or extend this to match themes content div ID or classes.
	// The hilite script will test div ids/classes and use the first one it finds.
	// When referencing an *ID name*, just be sure to begin with a '#'.
	// When referencing a *class name*, try to put the tag in front,
	// followed by a '.' and then the class name to *improve script speed*.
	static $areas = array(
			'#groups-dir-list', '#members-dir-list', // BuddyPress compat
			'li.bbp-body', // bbPress compat
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
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_script'));

		// Set query string as js variable in footer
		add_action('wp_footer', array(__CLASS__, 'query') );

		// append query string to results permalinks
		add_filter('post_link', array(__CLASS__,'append_search_query') );
		add_filter('post_type_link', array(__CLASS__,'append_search_query') );
		add_filter('page_link', array(__CLASS__,'append_search_query') );
		// TODO do this for bbPress search result links

	}

	public static function append_search_query( $url ) {
		if ( is_search() && in_the_loop() ) {
				$url = add_query_arg('hlst', urlencode(get_query_var('s')), $url);
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

	// Get query variables and print header script
	public static function query() {
		$filtered = array();
		$searches = array();

		// get terms
		if ( $searches = get_query_var( 'search_terms' ) ) { // regular WP search
			// prepare js array
			foreach ($searches as $search) {
				$filtered[] = esc_attr($search);
			}
		} elseif ( $search = $_GET['hlst'] or $search = get_query_var( 'bbp_search' ) ) {
			// Click-through from search results page or bbPress search
			// Use $_GET here because adding 'hlst' to query_vars will mess with static front page display, showing blog instead
			
			// prepare js array
			$filtered = self::split_search_terms($search);
		} else { // conventional search (keep for pre 3.7 compat?)
			$searches[] = get_search_query();
			// prepare js array
			if ( '1' == get_query_var( 'sentence' ) ) {
				// treat it as a one sentence search, take only the first search term
				$filtered[] = $searches[0];
			} else {
				foreach ($searches as $search) {
					$filtered = self::split_search_terms($search);
				}
			}
		}

		echo '
<!-- Highlight Search Terms ' . self::get_version() . ' ( RavanH - http://status301.net/wordpress-plugins/highlight-search-terms/ ) -->
<script type="text/javascript">
var hlst_query = new Array("' . implode('","',$filtered) . '");
var hlst_areas = new Array("' . implode('","',self::$areas) . '");
</script>
';
	}

}

HighlightSearchTerms::init();
