<?php

namespace Gianism\Pattern;

/**
 * Singleton Pattern
 *
 * @package Gianism
 * @author Takahashi Fumiki
 * @since 2.0
 */
abstract class Singleton {

	/**
	 * Instance holder
	 *
	 * @var array
	 */
	protected static $instances = [];

	/**
	 * Constructor
	 *
	 * @param array $argument
	 * @phpstan-ignore constructor.unusedParameter
	 */
	protected function __construct( array $argument = [] ) {
		// Override something.
	}

	/**
	 * Get instance
	 *
	 * @param array $argument
	 *
	 * @return static
	 */
	final public static function get_instance( array $argument = [] ) {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name( $argument );
		}

		return self::$instances[ $class_name ];
	}
}
