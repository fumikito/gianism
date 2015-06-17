<?php
/*
Plugin Name: Gianism
Plugin URI: http://wordpress.org/extend/plugins/gianism/
Description: Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants! Notice: PHP5.3 required.
Author: Takahashi Fumiki
Version: 2.2.6
Author URI: http://takahashifumiki.com
Text Domain: wp-gianism
Domain Path: /language/
License: GPL2 or Later
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
 * Plugin version
 *
 * @const string
 */
define('GIANISM_VERSION', '2.2.6');

/**
 * Domain for i18n
 *
 * @const string
 */
define('GIANISM_DOMAIN', 'wp-gianism');

/**
 * Documented date
 *
 * @const string
 */
define('GIANISM_DOC_UPDATED', '2015-04-12');


// For PoEdit scraping
if(false){
    __('Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants! Notice: PHP5.3 required.', GIANISM_DOMAIN);
}


// Add action after plugins are loaded.
add_action( 'plugins_loaded', '_gianism_setup_after_plugins_loaded');


/**
 * Start plugin
 * 
 * @ignore
 */
function _gianism_setup_after_plugins_loaded(){
    //Add i18n
    load_plugin_textdomain(GIANISM_DOMAIN, false, 'gianism/language');
    // Check PHP version is 5.3.0 or later
    if ( version_compare(phpversion(), "5.3.0", ">=") ) {
        // Load global functions
        require_once dirname(__FILE__).DIRECTORY_SEPARATOR."functions.php";
        // Register auto loader
        spl_autoload_register('_gianism_autoloader');
        // Avoiding syntax error, call Bootstrap
        call_user_func(array('\\Gianism\\Bootstrap', 'get_instance'));
    } else {
        // Too old.
        add_action('admin_notices', '_gianism_php_error');
    }

}

/**
 * Show error message on admin screen
 *
 * @ignore
 */
function _gianism_php_error(){
    printf(
        '<div class="error"><p><strong>[Gianism] </strong>%s</p></div>',
        sprintf(__('PHP <code>5.3.0</code> is required, but your version is <code>%s</code>. So this plugin is still in silence. Please conatct server administrator.', GIANISM_DOMAIN), phpversion())
    );
}

/**
 * For PoEdit
 *
 * @ignore
 * @return string
 */
function gianism_description(){
    return __('Gianism let your site\'s users login or register in few steps, with their SNS account. Currently Facebook, twitter, Google, Yahoo! Japan and mixi is supported. They have no need to remember their password(vulnerable or complecated!) nor to ask you to reset password. Stand on the shoulders of giants!', GIANISM_DOMAIN);
}

