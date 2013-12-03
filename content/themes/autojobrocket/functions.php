<?php
/**
 * The functions file is used to initialize everything in the theme.  It controls how the theme is loaded and
 * sets up the supported features, default actions, and default filters.  If making customizations, users
 * should create a child theme and make changes to its functions.php file (not this one).  Friends don't let
 * friends modify parent theme files. ;)
 *
 * Child themes should do their setup on the 'after_setup_theme' hook with a priority of 11 if they want to
 * override parent theme features.  Use a priority of 9 if wanting to run before the parent theme.
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License as published by the Free Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU General Public License along with this program; if not, write
 * to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package AutoJobRocket
 * @subpackage Functions
 * @version 0.1
 * @author Patrick Daly <pdaly@autojobrocket.com>
 * @copyright Copyright (c) 2013, AutoJobRocket
 * @link https://github.com/AutoJobRocket/AutoJobRocket.com
 */

/* Do theme setup on the 'after_setup_theme' hook. */
add_action( 'after_setup_theme', 'autojobrocket_theme_setup_theme' );

/**
 * Theme setup function.  This function adds support for theme features and defines the default theme
 * actions and filters.
 *
 * @since 0.1.0
 */
function autojobrocket_theme_setup_theme() {

	add_action( 'wp_enqueue_scripts', 'autojobrocket_theme_enqueue' );

}

/**
 * Queues scripts and styles to load.
 *
 * @since 0.1.0
 */
function autojobrocket_theme_enqueue() {

	wp_enqueue_style( 'style', get_template_directory_uri() .'/style.css' );
}