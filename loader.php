<?php
/**
 * Front-end functionality for Highlight Search Terms
 *
 * @package Highlight Search Terms
 */

namespace HLST;

\defined( '\WPINC' ) || die;

/* Node selectors the script will search for. Use filter 'hlst_selectors' to change or override these. */
const NODE_SELECTORS = array(
	'article',
	'.hentry',
	'div.wp-block-query', // Gutenberg query block.
	'div.entry-content',
	'div.post',
	'div.post-content',
	'div.content',
	'div.page-content',
	'div.page',
	'main',
	'#content',
	'#main',
	'#middle',
	'#container',
	'div.container',
	'#wrapper',
	'body', // Last, but not least.
);

// -- HOOKING INTO WP -- //
// Append search query string to results permalinks.
// 'wp' is the earliest hook where get_query_var('search_terms') will return results.

/**
 * Split search string into terms.
 *
 * @param string|array $search The search string to split up into terms.
 *
 * @return array
 */

function split( $search ) {
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

/**
 * Plugin compatibilities
 *
 * @since v.1.8.0
 */

function load_compat() {
	// Get active plugins.
	$active_plugins = (array) \get_option( 'active_plugins', array() );

	// BuddyPress compatibility.
	if ( in_array( 'buddypress/bp-loader.php', $active_plugins ) ) {
		include_once __DIR__ . '/inc/buddypress.php';
	}

	// bbPress compatibility.
	if ( in_array( 'bbpress/bbpress.php', $active_plugins ) ) {
		include_once __DIR__ . '/inc/bbpress.php';
	}

	// Search & Filter Pro compatibility.
	if ( in_array( 'search-filter-pro/search-filter-pro.php', $active_plugins ) ) {
		include_once __DIR__ . '/inc/search-filter-pro.php';
	}

	// FacetWP compatibility TODO.

	// WooCommerce compatibility.
	if ( in_array( 'woocommerce/woocommerce.php', $active_plugins ) ) {
		include_once __DIR__ . '/inc/woocommerce.php';
	}
}
\add_action( 'wp_loaded', __NAMESPACE__ . '\load_compat' );

/**
 * Add the search highlighter script to the footer.
 *
 * @since v.1.9.0
 */

function search_highlighter_script() {
	// Register Mark.js as a first-class script dependency (printed in the
	// footer) instead of injecting a <script> node at runtime. Dynamically
	// appended scripts cannot carry a nonce or SRI and are hostile to a strict
	// Content-Security-Policy; a normally enqueued script is not.
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;

	$is_debug  = \defined( '\WP_DEBUG' ) && \WP_DEBUG;
	$mark_file = 'js/mark' . ( $is_debug ? '' : '.min' ) . '.js';

	\wp_register_script(
		'hlst-mark',
		\plugins_url( $mark_file, __FILE__ ),
		array(),
		'1.9.0', // Keep in sync with the plugin version.
		true      // In the footer, so it runs after the DOM it marks.
	);
	\wp_enqueue_script( 'hlst-mark' );

	// JSON placed inside a <script> element must escape <, >, & and quotes so
	// no value (here: a node selector) can ever break out of the script tag
	// with a stray </script>. wp_json_encode() does NOT do this by default.
	$selectors = \apply_filters( 'hlst_selectors', NODE_SELECTORS );
	$selectors_json = \wp_json_encode(
		(array) $selectors,
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	$script = <<<JS
(function () {
	function getSearchParam() {
		const currentUrl = new URL(window.location.href);
		let searchTerm = currentUrl.searchParams.get('s');

		if (searchTerm) {
			return searchTerm.trim();
		}

		if (document.referrer) {
			try {
				const referrerUrl = new URL(document.referrer);
				if (referrerUrl.hostname === currentUrl.hostname) {
					searchTerm = referrerUrl.searchParams.get('s');
					if (searchTerm) {
						return searchTerm.trim();
					}
				}
			} catch (e) {
				// Invalid referer URL, ignore.
			}
		}

		return null;
	}

	function splitSearchTerms(search) {
		if (Array.isArray(search)) return search;
		if (typeof search !== 'string' || !search.trim()) return [];

		const returnTerms = [];
		let decodedSearch = '';
		try {
			decodedSearch = decodeURIComponent(search);
		} catch (e) {
			decodedSearch = search;
		}

		const regex = /([^\s",\+]+)|"([^"]*)"|'([^']*)'/g;
		const matches = decodedSearch.matchAll(regex);

		for (const match of matches) {
			let term = match[0];
			term = term.replace(/["']|%22|%27/g, '').trim();
			if (term !== "") {
				returnTerms.push(term);
			}
		}
		return returnTerms;
	}

	async function highlightSearchTerms() {
		const rawSearchString = getSearchParam();
		if (!rawSearchString) return;

		const termsArray = splitSearchTerms(rawSearchString);
		if (!termsArray || termsArray.length === 0) return;

		const NODE_SELECTORS = {$selectors_json};

		let context = null;
		for (const selector of NODE_SELECTORS) {
			if (document.querySelector(selector)) {
				context = selector;
				break;
			}
		}

		if (!context) return;

		// Mark.js is enqueued as a dependency, so the global Mark is already
		// defined and no runtime <script> injection is needed.
		const instance = new Mark(context);

		termsArray.forEach((term, i) => {
			instance.mark(term, {
				"className": "hilite term-" + i,
				"accuracy": "partially",
				"separateWordSearch": false,
				"diacritics": true,
				"exclude": ["script", "style", "title", "head", "html", "mark", "iframe", "input", "textarea", ".navigation", ".comment-list"]
			});
		});
	}

	document.addEventListener("DOMContentLoaded", highlightSearchTerms);
})();
JS;

	\wp_add_inline_script( 'hlst-mark', $script );
}
// Enqueue on wp_enqueue_scripts, not wp_footer: wp_print_footer_scripts is
// itself an action on wp_footer at priority 20, so enqueuing from a wp_footer
// callback runs too late and the script would never print. wp_enqueue_scripts
// fires before output, and the in-footer registration below places the script
// (and its inline highlight logic) at the end of the page where it belongs.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\search_highlighter_script' );
