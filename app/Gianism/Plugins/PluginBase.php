<?php

namespace Gianism\Plugins;


use Gianism\Pattern\Application;

/**
 * PluginBase
 *
 * Override this class to make plugin or add-ons.
 *
 * @package Gianism
 * @property string $plugin_name
 */
abstract class PluginBase extends Application {

	/**
	 * Return plugin description.
	 *
	 * @return string
	 */
	abstract public function plugin_description();

	/**
	 * Check plugin is active.
	 *
	 * @return bool
	 */
	abstract public function plugin_enabled();



	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'plugin_name':
				$class_name = explode( '\\', get_called_class() );
				return $class_name[ count( $class_name ) - 1 ];
				break;
			default:
				return parent::__get( $name );
				break;
		}
	}
}
