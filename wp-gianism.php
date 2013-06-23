<?php
/*
Plugin Name: Gianism
Plugin URI: http://wordpress.org/extend/plugins/gianism/
Description: Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants!
Author: Takahashi Fumiki
Version: 1.3.1
Author URI: http://takahashifumiki.com
Text Domain: wp-gianism
Domain Path: /language/
Lisence: GPL2
*/
/*  
    Copyright 2010 Takahashi Fumiki (email : takahashi.fumiki@hametuha.co.jp)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// Don't allow plugin to be loaded directory
defined( 'ABSPATH' ) OR exit;


/**
 * Instance of Gianism
 *
 * @var WP_Gianism
 */
$gianism = null;


// Add action after plugins are loaded.
add_action( 'plugins_loaded', '_gianism_setup_after_plugins_loaded');


/**
 * Instanciate plugin
 * 
 * @global WP_Gianism $gianism
 */
function _gianism_setup_after_plugins_loaded(){
	
	global $gianism;
	
	// Load Utility Classes
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR."WP_Gianism.php";

	// Instanciate
	$gianism = new WP_Gianism(__FILE__, "1.3.1");

	// Load global functions
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR."functions.php";
	
}
