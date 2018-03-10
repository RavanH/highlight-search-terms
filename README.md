# Highlight Search Terms
Very lightweight jQuery script that wraps search terms in an HTML5 mark tag when referer is a search engine or within wp search results.

Highlights search terms within WordPress generated search results _or_ when referrer is a non-secure search engine, both on the search results page _and_ on the post page itself.

This plugin is light weight and has no options. It started as very simple fusion between <a href="http://weblogtoolscollection.com/archives/2009/04/10/how-to-highlight-search-terms-with-jquery/">How to Highlight Search Terms with jQuery - theme hack by Thaya Kareeson</a> and <a href="http://wordpress.org/extend/plugins/google-highlight/">Search Hilite by Ryan Boren</a>. It has since evolved with many optimizations, HTML5 and bbPress support.

Many WordPress sites are already top-heavy with all kinds of resource hungry plugins that require a lot of options to be set and subsequently more database queries. The Highlight Search Terms plugin for WordPress is constructed to be as low impact / low resource demanding as possible, keeping server response and page load times low.
This is done by going without any back-end options page, no filtering of post content and no extra database entries. A limited amount of hooks are used. The rest is done by jQuery javascript extention and your own CSS.

**Features**

- Click through highlights: Highlights not only on WP search results page but also one click deeper inside any of the found pages
- Character and case insensitive (lenient) highlighting
- BuddyPress / bbPress compatibility: highlighting within forum searches
- Caching (WP Super Cache) compatibility
- Search terms wrapped in double quotes now considered as single term

Read more on https://wordpress.org/plugins/highlight-search-terms/
