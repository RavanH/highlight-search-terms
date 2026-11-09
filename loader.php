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
	?>
	<script type="text/javascript">
	function loadMarkJs() {
		return new Promise((resolve, reject) => {
			if (typeof Mark !== 'undefined') {
				resolve();
				return;
			}
			const script = document.createElement('script');
			script.src = '<?php echo \plugins_url( 'js/mark' . ( \defined( '\WP_DEBUG' ) && true == \WP_DEBUG ? '' : '.min' ) . '.js', __FILE__ ); ?>';
			script.type = 'text/javascript';
			script.defer = true;
			script.onload = () => resolve();
			script.onerror = () => reject(new Error('Mark.js could not be loaded.'));
			document.head.appendChild(script);
		});
	}

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

	async function highlightReferrerSearchTerms() {
		const rawSearchString = getSearchParam();
		if (!rawSearchString) return;

		const termsArray = splitSearchTerms(rawSearchString);
		if (!termsArray || termsArray.length === 0) return;

		const NODE_SELECTORS = <?php echo \wp_json_encode( (array) \apply_filters( 'hlst_selectors', NODE_SELECTORS ) ); ?>;

		let context = null;
		for (const selector of NODE_SELECTORS) {
			if (document.querySelector(selector)) {
				context = selector;
				break;
			}
		}

		if (!context) return;

		try {
			await loadMarkJs();
		} catch (error) {
			console.error(error);
			return;
		}

		const jsonArrayString = JSON.stringify(termsArray);
		const finalTermsToMark = JSON.parse(jsonArrayString);

		const instance = new Mark(context);
		let i = 0;

		finalTermsToMark.forEach(term => {
			instance.mark(term, {
				"className": "hilite term-" + i,
				"accuracy": "partially",
				"separateWordSearch": false,
				"diacritics": true,
				"exclude": ["script", "style", "title", "head", "html", "mark", "iframe", "input", "textarea", ".navigation", ".comment-list"]
			});
			i++;
		});
	}

	document.addEventListener("DOMContentLoaded", highlightReferrerSearchTerms);
	</script>
	<?php
}
add_action('wp_footer', __NAMESPACE__ . '\search_highlighter_script', 20);
