=== Highlight Search Terms ===
Contributors: RavanH
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=ravanhagen%40gmail%2ecom&amp;item_name=Highlight%20Search%20Terms&amp;item_number=0%2e6&amp;no_shipping=0&amp;tax=0&amp;bn=PP%2dDonationsBF&amp;charset=UTF%2d8
Tags: mark, highlight, hilite, search, term, terms, jquery
Requires at least: 2.7
Tested up to: 4.5
Stable tag: 1.4

Very lightweight jQuery script that wraps search terms in an HTML5 mark tag within wp search results or when referrer is a non-secure search engine.


== Description ==

Highlights search using jQuery terms within WordPress generated search results _or_ when referrer is a non-secure search engine. This plugin is light weight and has no options. It started as very simple fusion between <a href="http://weblogtoolscollection.com/archives/2009/04/10/how-to-highlight-search-terms-with-jquery/">How to Highlight Search Terms with jQuery - theme hack by Thaya Kareeson</a> and <a href="http://wordpress.org/extend/plugins/google-highlight/">Search Hilite by Ryan Boren</a>. It has since evolved with many optimizations, HTML5 and bbPress support.

Development, bug reports and contributions on https://github.com/RavanH/highlight-search-terms

**Features**

- BuddyPress / bbPress compatibility: highlighting within forum searches
- Caching (WP Super Cache) compatibility
- Click through highlights: Highlights not only on WP search results page but also one click deeper inside any of the found pages
- Search terms wrapped in double qoutes now considered as single term
- Support for many more search engines: Google, Bing, Yahoo, Lycos, Ask, AOL, Baidu, Youdao, Dogpile, Search.com, Goodsearch.com, Mywebsearch.com, Webcrawler.com, Info.com

**NOTE:** to make the highlights visible in browsers that do not support HTML5 like Internet Explorer 8 or older you will have to define at least one CSS hilite styling! Read on below **So what do I need to do?** and [Installation](http://wordpress.org/extend/plugins/highlight-search-terms/installation/) for more detailed instructions. You can find CSS examples in [Other Notes](http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/).

= What does it do? =

This low impact plugin uses only a few action hooks to define some variables and to add the hilite jQuery extension to your page source code. The jQuery extension that runs after the page has loaded, finds all search terms on that page inside each div with class `hentry` (or ID `content`, `main` or `wrapper`...) and wraps them in `<mark class="hilite term-N"> ... </mark>` tags. Note that N is a number starting with 0 for the first term used in the search phrase increasing 1 for each additional term used. Any part of a search phrase wrapped in quotes is considered as a single term.

= What does it NOT do? =

There are no CSS style rules set for highlighting. You are free to use any styling you wish but to make the highlights visible in browsers that do not support HTML5 like Internet Explorer 8 or older you absolutely *need to define at least one rule*. Modern HTML5 browsers will use their own highlighting style by default, which usually is a yellow marker style background.

= So what do I need to do? =

In most cases, it should just work. But you can do two things to ensure backward browser and theme compatibility:

**1. Define CSS rules:** There are _no_ configuration options and there is _no_ predefined highlight styling. You are completely free to define any CSS styling rules in your themes **main stylesheet (style.css)** or use any **Custom CSS plugin** like [Custom CSS](http://wordpress.org/extend/plugins/safecss/) to get a result that fits your theme best. You can find basic instructions and CSS examples under the [Other Notes](http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/) tab.

**2. Check your theme:** In most up to date themes (including WP's own default theme) post and page content is shown inside a div with class `hentry`. This means search terms found in post and page content will be highlighted but not similar terms that accidentaly show in the page header, sidebar or footer. If your current theme does not use the `hentry` class (yet), this plugin will look for IDs `content`, `main` and finally `wrapper` but if none of those are found, it will not work for you out of the box. See the last of the [FAQ's](http://wordpress.org/extend/plugins/highlight-search-terms/faq/) for ways to make it work.


== Installation ==

To make it work, you will need to take up to three steps, depending on your wishes and WordPress theme. (I) A normal installation and activation procedure; (II) adding CSS styling rules to get highlighting for older browsers that do not support HTML5 like Internet Explorer 8 and below; _and_ (III) Make sure your theme uses any of the recognized classes or ID's for the post content div so that the script knows where and where not to look for search terms.

**I.** [Install now](http://coveredwebservices.com/wp-plugin-install/?plugin=highlight-search-terms) _or_ use the slick search and install feature (Plugins > Add New and search for "highlight search terms") in your WP2.7+ admin section _or_ follow these basic steps.

1. Download archive and unpack.
2. Upload (and overwrite) the /highlight-search-terms/ folder and its content to the /plugins/ folder.
3. Activate plugin on the Plug-ins page

**II.** Optionally add at least _one_ new rule to your themes stylesheet (style.css or a custom stylesheet or a custom css plugin) to style highlightable text.

For example use `.hilite { background:#D3E18A; }` to get a moss green background on search terms found in the content section (not header, sidebar or footer; assuming your Theme uses a div with class "hentry").

Please find more examples under the [Other Notes](http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/) tab.

**III.** In most up to date themes (including WP's own Default theme) post and page content is shown inside a div with class `hentry`. This class is recognized by the hilite script, which means search terms found in post and page content will be highlighted but *not* similar terms that coïncidentaly reside in the page header, sidebar or footer. If your current theme does not use the `hentry` class (yet), this plugin will look for IDs `content`, `main` and finally `wrapper` (which might include the header, sidebar and footer areas) but if *none* of those are found, it will not work for you out of the box. See the last of the [FAQ's](http://wordpress.org/extend/plugins/highlight-search-terms/faq/) for ways to make it work.


== Frequently Asked Questions ==

= I do not see any highlighting! =

This plugin has _no_ configuration options page and there is _no_ predefined highlight styling. For any highlighting to become visible in browsers that do not support HTML5 like Internet Explorer 8 or older, you have to complete step II of the installation process. Edit your themes stylesheet (style.css) to contain a rule that will give you exactly the styling that fits your theme.

Don't want to edit your themes stylesheet? I can highly recommend Automattics own [Custom CSS](http://wordpress.org/extend/plugins/safecss/) plugin or [Jetpack](http://wordpress.org/extend/plugins/jetpack/) with the Custom CSS module activated!

= I want to customize the highlighting but have no idea what to put in my stylesheet. Can you give me some examples? =

Sure! See tab [Other Notes](http://wordpress.org/extend/plugins/highlight-search-terms/other_notes/) for instructions and some examples to get you started.

= I still do not see any highlighting! =

Due to a problem with jQuery's `$('body')` call in combination with many other scripts (like Google Ads, Analytics, Skype Check and other javascript) in the ever increasingly popular Firefox browser, I have had to limit the script search term wrapping to a particular div instead of the whole document body. I chose div with class "hentry" since that is the most commonly used content layer class in WordPress themes. If that is not available, the script will look for divs #content then #main then #wrapper. However, in your particular theme, none of these divs might be available...

Let's suppose your theme's index.php or single.php has no `<div <?php post_class() ?> ... >` but wraps the post/page content in a `<div id="common" class="content"> ... </div>`. You can do two things to solve this:

A. Change your theme and stylesheet so the post/page content div has either `class="hentry"` or `<?php post_class() ?>`. TIP: Take a look at how it is done in the Default theme included in each WordPress release. But this might involve some real timeconsuming tinkering with your stylesheet and several theme template files.

B. Change the source of wp-content/plugins/highlight-search-terms/hlst.php so that the array starting on line 55 contains your main content ID or class name. In the above example that can be either `'#common',` or `'.content',` where a prefix '#' is used for ID and '.' for class.

C. Switch to a theme that does abide by the current WordPress conventions :)


== Screenshots ==

1. An example image provided by [How to Highlight Search Terms with jQuery](http://weblogtoolscollection.com/archives/2009/04/10/how-to-highlight-search-terms-with-jquery/ "How to Highlight Search Terms with jQuery | Weblog Tools Collection") on which this plugin is largely based.

== Other Notes ==

Many blogs are already top-heavy with all kinds of resource hungry plugins that require a lot of options to be set and subsequently more database queries. The Highlight Search Terms plugin for WordPress is constructed to be as low impact / low resource demanding as possible, keeping server response and page load times low. This is done by going without any back-end options page, no filtering of post content and no extra database entries. Just two action hooks are used: wp_footer and wp_head. The rest is done by jQuery javascript extention and your own CSS.

To get you started with your own CSS styling that fits your theme, see the following examples.

= CSS Instructions =

Go in your WP admin section to Themes > Edit and find your stylesheet. Scroll all the way to the bottom and add one of the examples (or your modification of it) on a fresh new line.

= Basic CSS Examples =

    .hilite { background-color:#D3E18A }

For a moss green background highlighting.

    .hilite { background-color:yellow }

Yellow background highlighting.

    .hilite { background-color:#9CD4FF; font-weight:bold }

A light blue background with bold font.

    .hilite { background-color:#FFCA61; color:#000000 }

Orange background with black font.

For more intricate styling, see the advanced example below.

= Advanced CSS Example =

If you want to give different terms used in a search phrase a different styling, use the class "term-N" where N is a number starting with 0, increasing 1 with each additional search term, to define your CSS rules. The below example will make every instance of any term used in the query show up in bold text and a yellow background except for any instance of a second, third and fourth term which will have respectively a light green, light blue and orange background.

    .hilite { background-color:yellow; font-weight:bold } /* default */
    .term-1 { background-color:#D3E18A } /* second search term only */
    .term-2 { background-color:#9CD4FF } /* third search term only */
    .term-3 { background-color:#FFCA61 } /* fourth search term only */

Keep in mind that for the _first_ search term the additional class "term-0" is used, not "term-1"!

= Known issues =

1. If your theme does not wrap the main content section of your pages in a div with class "hentry" or HTML5 article tags, this plugin might not work for you out of the box. However, you can _make_ it work. See the last of the [FAQ's](http://wordpress.org/extend/plugins/highlight-search-terms/faq/) for an explanation.

2. [Josh](http://theveganpost.com) pointed out a conflict with the [ShareThis button](http://sharethis.com/wordpress). I have no clue why this happens but since version 0.5 jQuery is used in so called NoConflict mode. Please let me know if the problem still exists. Thanks!

3. When search engine referrer is using SSL (notice the https:// in the URL, usually when logged in as Google user) then the search terms cannot be determined. There is no way to get around that issue.

Thank you, [Jason](http://wordpress.org/support/profile/412612) for pointing out a bug for IE7+, fixed in 0.2.

Please provide me with a bug report, suggestion, question on https://github.com/RavanH/highlight-search-terms/issues if you run into any problems!

== Upgrade Notice ==

= 1.4 =

Better click-through highlighting and updated bbPress compatibility.

== Changelog ==

= 1.4 =

Date 2016-04-10
Dev time: 6h

* Better search results click though highlighting
* Improved bbPress compatibility
* Renamed Minified js

= 1.3.9.1 =

Date 2015-10-13
Dev time: 1h

* FIX: Missing terms not wrapped in P tag (reported by zmokin)

= 1.3.9 =

Date 2014-09-04
Dev time: 5h

* WordPress 3.8 search compatibility allowing less fuzzy search with quotes
* ?sentence=1 query parameter compat
* FIX: XSS vulnerability found by [Rodolfo Godalle, Jr.](http://www.facebook.com/junior.ns1de)

= 1.3 =

Date 2013-09-02
Dev time: 2h

* BuddyPress and bbPress highlighting within forum searches
* FIX: jQuery 1.10 compatibility

= 1.2.5 =

Date 2013-07-28
Dev time: 1h

* FIX: Combine-JS compatibility (Thanks to Joshua Hoke)
* More possible classes and ids to look for

= 1.2.4 =

Date 2012-12-16
Dev time: 1h

* BUGFIX: non-western languages in URL not decoded

= 1.2.3 =

Date 2012-12-06
Dev time: 1h

* BUGFIX: javascript infinite loop

= 1.2.2 =

Date 2012-10-11
Dev time: 6h

* Search terms wrapped in double quotes now considered as single term
* Caching compatible
* Second click highlighting: not only on the search results page but also inside any of the found pages
* Speed improvement
* More search engines supported
* BUGFIX: Undefined variable: cache_compat

= 1.2.1 =

Date 2012-07-27
Dev time: 1h

* BUGFIX: non HTML5 browser (IE8 and older) support failing

= 1.2 =

Date 2012-07-25
Dev time: 8h

* NEW: Caching (WP Super Cache) compatibility
* NEW: Highlights not only on WP search results page but also one click deeper inside any of the found pages
* NEW: Search terms wrapped in double quotes now considered as single term
* support many more search engines: AOL, Dogpile, Search.com, Goodsearch.com, Mywebsearch.com, Webcrawler.com, Info.com
* rebuilt as Class
* plugin speed improvements

= 0.8 =

Date 2012-04-18
Dev time: 2h

* HTML5 mark tag support
* No more word boundary limit for non-latin based languages compatibility

= 0.7 =

Date: 2011-01-03
Dev time: 1h

* BUGFIX: conflict with Cufon script

= 0.6 =

Date: 2010-09-06
Dev time: 2h

* limit highlighted search terms to word boundary
* added Bing, Ask, Baidu and Youdao search engines
* now automatically check for and highlights in multiple theme div areas
* BUGFIX: cloning first result excerpt to all excerpts

= 0.5 =

Date: 2010-08-07
Dev time: 2h

* using jQuery in no-conflict mode and $hlst instead of $ to avoid conflict with prototype
* split variables and moved js extension in compacted form to static file
* moved jQuery and extention to footer + only when actually needed for faster page load

= 0.4 =

Date: 2010-04-07
Dev time: 1h

* fixed Regular Expression to allow parts of words to be highlighted
* search term wrapping limited to .hentry divs

= 0.3 =

Date: 2009-04-16
Dev time: 1h

* Bugfix for Firefox 2+ (forcefully highlights now limited to div#content)

= 0.2 =

Date: 2009-04-15
Dev time: 1h

* Allowing for multiple search term styling + Bugfix for IE7 / IE8

= 0.1 =

Date: 2009-04-14
Dev time: 3h

- Basic plugin aimed at low impact / low resource demand on your WP installation using client side javascript.
