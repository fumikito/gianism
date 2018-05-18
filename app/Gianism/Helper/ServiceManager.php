<?php

namespace Gianism\Helper;


use Gianism\Pattern\AppBase;
use Gianism\Pattern\Singleton;

/**
 * Class ServiceManager
 * @package Gianism
 */
class ServiceManager extends Singleton  {

	use i18n, ExtensionManager;

	/**
	 * @var array
	 */
	protected $service_classes = [];

	/**
	 * @var array
	 */
	protected $default_services = [];

	/**
	 * @var array
	 */
	protected $default_plugins = [];

	/**
	 * @var array
	 */
	protected $plugin_classes = [];

	/**
	 * ServiceManager constructor.
	 *
	 * @param array $argument
	 */
	final protected function __construct( array $argument ) {
		// Do nothing
	}

	/**
	 * ServiceManager initializer
	 */
	public function init() {
		$default_services = [
			'facebook'  => 'Facebook',
		    'twitter'   => 'Twitter',
		    'google'    => 'Google',
		    'instagram' => 'Instagram',
			'line'      => 'Line',
		];
		foreach ( $default_services as $key => $class_name ) {
			$this->default_services[ $key ] = 'Gianism\\Service\\' . $class_name;
		}
		/**
		 * Register service class
		 *
		 * @filter gianism_additional_service_classes
		 * @param array $additional_services
		 */
		$additional_services = apply_filters( 'gianism_additional_service_classes', [] );
		// Register everything.
		$services = array_merge( $this->default_services, $additional_services );
		foreach ( $services as $service => $class_name ) {
			if ( $this->is_callable( $class_name, 'Gianism\\Service\\AbstractService' ) ) {
				$this->service_classes[ $service ] = $class_name;
			}
		}
		// Register plugins
		// Fire plugins.
		$plugins = [];
		foreach ( [
			'analytics' => 'Gianism\\Plugins\\Analytics',
		    'bot'       => 'Gianism\\Plugins\\Bot',
		] as $name => $class_name ) {
			$this->default_plugins[] = $name;
			$plugins[ $name ] = $class_name;
		}
		/**
		 * Hook to register plugin files.
		 *
		 * @filter gianism_plugin_classes
		 * @since 3.0.0
		 * @param array $plugins plugin name as key, plugin class name as value.
		 * @return array
		 */
		$this->plugin_classes = apply_filters( 'gianism_plugin_classes', $plugins );
		foreach ( $this->plugin_classes as $class_name ) {
			if ( $this->is_callable( $class_name, 'Gianism\\Plugins\\PluginBase' ) ) {
				$class_name::get_instance();
			}
		}
	}

	/**
	 * Get registered services
	 *
	 * @return array
	 */
	public function all_services() {
		return array_keys( $this->service_classes );
	}

	/**
	 * Return registered services
	 *
	 * @return array
	 */
	public function service_list() {
		$services = [];
		foreach ( $this->all_services() as $service ) {
			$instance = $this->get( $service );
			$services[] = [
				'name'    => $service,
				'label'   => $instance->verbose_service_name,
			    'enabled' => $instance->enabled,
			    'default' => array_key_exists( $service, $this->default_services ),
			];
		}
		return $services;
	}

	/**
	 * Get service instance
	 *
	 * @param string $service Service name
	 *
	 * @return null|\Gianism\Service\AbstractService
	 */
	public function get( $service ) {
		if ( ! isset( $this->service_classes[ $service ] ) ) {
			return null;
		} else {
			$class_name = $this->service_classes[ $service ];
			return $class_name::get_instance();
		}
	}

	/**
	 * Returns if this is a default plugins
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function is_default_plugin( $name ) {
		return array_key_exists( strtolower( $name ), $this->plugin_classes );
	}

	/**
	 * Get all plugins
	 *
	 * @return array
	 */
	public function get_plugins() {
		$plugins = [];
		foreach ( $this->plugin_classes as $class_name ) {
			if ( $this->is_callable( $class_name, 'Gianism\\Plugins\\PluginBase' ) ) {
				$plugins[] = $class_name::get_instance();
			}
		}
		return $plugins;
	}

}
