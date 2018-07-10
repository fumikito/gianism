<?php
/**
 * Plugin Name: Gianism
 * Plugin URI: https://wordpress.org/extend/plugins/gianism/
 * Description: Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants! Notice: PHP5.4 required.
 * Author: Takahashi_Fumiki
 * Version: 3.3.0
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

// Add action after plugins are loaded.
add_action( 'plugins_loaded', 'gianism_setup_after_plugins_loaded' );

/**
 * Start plugin
 *
 * @ignore
 */
function gianism_setup_after_plugins_loaded() {
	// Add i18n for here for other plugins.
	load_plugin_textdomain( 'wp-gianism', false, 'gianism/language' );
	// Check PHP version is 5.4.0 or later.
	try {
		if ( ! version_compare( phpversion(), GIANISM_PHP_VERSION, '>=' ) ) {
			// translators: %1$s is required PHP version, %2$s is current PHP version.
			throw new Exception( sprintf( __( '[Gianism] PHP <code>%1$s</code> is required, but your version is <code>%2$s</code>. So this plugin is still in silence. Please contact server administrator.', 'wp-gianism' ), GIANISM_PHP_VERSION, phpversion() ) );
		}
		// Load composer.
		$auto_loader = dirname( __FILE__ ) . '/vendor/autoload.php';
		if ( ! file_exists( $auto_loader ) ) {
			// translators: %s is file path, %2$s is composer command.
			throw new Exception( sprintf( esc_html( __( '[Gianism] missing composer\'s auto loader at %1$s. Did you run %2$s?', 'wp-gianism' ) ), dirname( __FILE__ ) . '/vendor/autoload.php', '<code>composer install</code>' ) );
		}
		// Load auto loader.
		require $auto_loader;
		// Avoiding syntax error, call Bootstrap.
		if ( ! class_exists( 'Gianism\\Bootstrap' ) ) {
			throw new Exception( esc_html( __( '[Gianism] Bootstrap file not found.', 'wp-gianism' ) ) );
		}
		// Load functions.
		require __DIR__ . '/functions.php';
		// Load all functions and hooks.
		foreach ( array( 'functions', 'hooks' ) as $dir ) {
			$dir_path = __DIR__ . '/' . $dir ;
			if ( ! is_dir( $dir_path ) ) {
				continue;
			}
			foreach ( scandir( $dir_path ) as $file ) {
				if ( preg_match( '#^[^._].*\.php$#u', $file ) ) {
					require $dir_path . '/' . $file;
				}
			}
		}
		if ( defined( 'GIANISM_SKIP_DEPRECATED' ) ) {
			require __DIR__ . '/functions-deprecated.php';
		}
		// Call bootstrap.
		call_user_func( array( 'Gianism\\Bootstrap', 'init' ) );
	} catch ( Exception $e ) {
		gianism_internal_error( $e->getMessage() );
		add_action( 'admin_notices', 'gianism_internal_notice' );
	} // End try().
}

/**
 * Display admin error message.
 *
 * @since 3.1.0
 * @ignore
 */
function gianism_internal_notice() {
	printf( '<div class="error"><p>%s</p></div>', esc_html( gianism_internal_error() ) );
}

/**
 * Error message holder.
 *
 * @since 3.1.0
 * @ignore
 * @param string $message Message string which will be saved if not empty.
 * @return string
 */
function gianism_internal_error( $message = '' ) {
	static $store = '';
	if ( $message ) {
		$store = $message;
	}
	return $store;
}
