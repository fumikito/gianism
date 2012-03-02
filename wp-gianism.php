<?php
/**
 * @package wp_gianism
 * @version 1.1.2
 */
/*
Plugin Name: Gianism
Plugin URI: http://wordpress.org/extend/plugins/gianism/
Description: Connect user accounts with major web services like Facebook, twitter, etc. Stand on the shoulders of giants!
Author: Takahashi Fumiki
Version: 1.1.2
Author URI: http://takahashifumiki.com
Text Domain: wp-gianism
Domain Path: /language/
*/

//Load Utility Classes
require_once dirname(__FILE__).DIRECTORY_SEPARATOR."WP_Gianism.php";

/**
 * @var $gianism WP_Gianism
 */
$gianism = new WP_Gianism(__FILE__, "1.1.2");

//Load global functions
require_once dirname(__FILE__).DIRECTORY_SEPARATOR."functions.php";