<?php
/**
 * Plugin Name: Gianism
 * Plugin URI: https://wordpress.org/extend/plugins/gianism/
 * Description: Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants! Notice: PHP5.4 required.
 * Author: Takahashi_Fumiki
 * Version: 3.0.9
 * PHP Version: 5.4.0
 * Author URI: https://gianism.info
 * Text Domain: wp-gianism
 * Domain Path: /language/
 * License: GPL2 or Later
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

// Don't allow plugin to be loaded directory.
defined( 'ABSPATH' ) || die( 'Do not load directly.' );

// Get data from header.
$info = get_file_data( __FILE__, array(
	'version'     => 'Version',
	'php_version' => 'PHP Version',
	'text_domain' => 'Text Domain',
) );

/**
 * Plugin version
 *
 * @const string
 */
define( 'GIANISM_VERSION', $info['version'] );

/**
 * Domain for i18n
 *
 * @const string
 */
define( 'GIANISM_DOMAIN', $info['text_domain'] );

/**
 * Gianism PHP Version
 *
 * @const string
 */
define( 'GIANISM_PHP_VERSION', $info['php_version'] );

// For PoEdit scraping
if ( false ) {
	__( 'Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants! Notice: PHP5.4 required.', 'wp-gianism' );
}
// Add action after plugins are loaded.
add_action( 'plugins_loaded', '_gianism_setup_after_plugins_loaded' );

/**
 * Start plugin
 *
 * @ignore
 */
function _gianism_setup_after_plugins_loaded() {
	//Add i18n for here for other plugins.
	load_plugin_textdomain( GIANISM_DOMAIN, false, 'gianism/language' );
	// Check PHP version is 5.4.0 or later
	try {
		if ( ! version_compare( phpversion(), GIANISM_PHP_VERSION, '>=' ) ) {
			throw new Exception( sprintf( __( '[Gianism] PHP <code>%1$s</code> is required, but your version is <code>%2$s</code>. So this plugin is still in silence. Please contact server administrator.', GIANISM_DOMAIN ), GIANISM_PHP_VERSION, phpversion() ) );
		}
		// Load global functions
		$auto_loader = dirname( __FILE__ ) . '/vendor/autoload.php';
		if ( ! file_exists( $auto_loader ) ) {
			throw new Exception( sprintf( esc_html( __( '[Gianism] missing composer\'s auto loader at %s. Did you run %s?', GIANISM_DOMAIN ) ), dirname( __FILE__ ) . '/vendor/autoload.php', '<code>composer install</code>' ) );
		}
		// Load auto loader.
		require $auto_loader;
		// Avoiding syntax error, call Bootstrap.
		if ( ! class_exists( 'Gianism\\Bootstrap' ) ) {
			throw new Exception( esc_html( __( '[Gianism] Bootstrap file not found.', GIANISM_DOMAIN ) ) );
		}
		// Load functions
		require __DIR__ . '/functions.php';
		foreach ( scandir( __DIR__ . '/functions' ) as $file ) {
			if ( preg_match( '#^[^._].*\.php$#u', $file ) ) {
				require __DIR__ . '/functions/' . $file;
			}
		}
		if ( defined( 'GIANISM_SKIP_DEPRECATED' ) ) {
			require __DIR__ . '/functions-deprecated.php';
		}
		// Call bootstrap
		call_user_func( array( 'Gianism\\Bootstrap', 'init' ) );
	} catch ( Exception $e ) {
		$error = sprintf( '<div class="error"><p>%s</p></div>', $e->getMessage() );
		add_action( 'admin_notices', create_function( '', sprintf( 'echo \'%s\';', str_replace( '\'', '\\\'', $error ) ) ) );
	}
}
