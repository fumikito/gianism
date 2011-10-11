<?php
/**
 * @package wp_gianism
 * @version 0.8
 */
/*
Plugin Name: WP Gianism
Plugin URI: http://hametuha.co.jp
Description: Connect user accounts with major web services like Facebook, twitter, etc.
Author: Hametuha inc.
Version: 0.8
Author URI: http://hametuha.co.jp
*/

//ユーティリティクラスの読み込み
require_once dirname(__FILE__).DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR."Hametuha_Library.php";
require_once dirname(__FILE__).DIRECTORY_SEPARATOR."WP_Gianism.php";

/**
 * @var $gianism WP_Gianism
 */
$gianism = new WP_Gianism(__FILE__, "0.8", "wp-gianism");
