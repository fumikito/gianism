<?php

namespace Gianism\Helper;


use Gianism\Pattern\AppBase;
use Gianism\Pattern\Singleton;

/**
 * Class ServiceManager
 * @package Gianism\Helper
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
	 * ServiceManager constructor.
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		$default_services = [
			'facebook'  => 'Facebook',
		    'twitter'   => 'Twitter',
		    'google'    => 'Google',
		    'instagram' => 'Instagram',
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



}
