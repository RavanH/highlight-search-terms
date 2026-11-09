<?php
/**
 * Plugin Name: Highlight Search Terms
 * Plugin URI: http://status301.net/wordpress-plugins/highlight-search-terms
 * Description: Wraps search terms in the HTML5 mark tag. Read <a href="http://wordpress.org/extend/plugins/highlight-search-terms/#faq">FAQ's</a> for instructions and examples for styling the highlights. <a href="https://donate.stripe.com/6oUbJ10Nz8xYfF7b5qdEs01" title="Thank you!">Tip jar</a>.
 * Version: 1.9.0
 * Author: RavanH
 * Author URI: http://status301.net/
 * Text Domain: highlight-search-terms
 * License: GPLv2 or later
 *
 * @package Highlight Search Terms
 */

/*
	Copyright 2026  RavanH  (email : ravanhagen@gmail.com)

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

defined( '\WPINC' ) || die;

if ( is_admin() ) {
	return;
}

require_once __DIR__ . '/loader.php';
