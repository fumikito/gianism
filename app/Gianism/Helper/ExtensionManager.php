<?php

namespace Gianism\Helper;

/**
 * Extension manager
 * @package Gianism
 */
trait ExtensionManager {
	/**
	 * Assure that class is appropriate class.
	 *
	 * @param string $class_name
	 * @param string $subclass
	 *
	 * @return bool
	 */
	private function is_callable( $class_name, $subclass ) {
		$reflection = new \ReflectionClass( $class_name );
		return ! $reflection->isAbstract() && $reflection->isSubclassOf( $subclass );
	}
}
