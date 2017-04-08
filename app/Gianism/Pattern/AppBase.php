<?php

namespace Gianism\Pattern;

use Gianism\Helper\MessageHelper;
use Gianism\Helper\i18n;
use Gianism\Helper\Option;
use Gianism\Helper\Input;
use Gianism\Helper\ServiceManager;
use Gianism\Helper\Session;

/**
 * Application base trait
 * @package Gianism
 * @property \wpdb          $db      Database controller
 * @property string         $url     Base URL of plugin.
 * @property string         $dir     Base directory of plugin.
 * @property Option         $option  Option instance.
 * @property Session        $session Session instance
 * @property Input          $input   Input instance.
 * @property ServiceManager $service ServiceManager instance
 */
trait AppBase {

	use i18n, MessageHelper;

	protected $name = 'gianism';

	/**
	 * Version number
	 *
	 * @var string
	 */
	protected $version = \GIANISM_VERSION;

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'db':
				global $wpdb;
				return $wpdb;
				break;
			case 'url':
				return plugin_dir_url( dirname( dirname( dirname( __FILE__ ) ) ) );
				break;
			case 'dir':
				return plugin_dir_path( dirname( dirname( dirname( __FILE__ ) ) ) );
				break;
			case 'option':
				return Option::get_instance();
				break;
			case 'input':
				return Input::get_instance();
				break;
			case 'session':
				return Session::get_instance();
				break;
			case 'service':
				return ServiceManager::get_instance();
				break;
			default:
				return null;
				break;
		}
	}
}
