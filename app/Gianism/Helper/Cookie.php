<?php

namespace Gianism\Helper;


use Gianism\Pattern\Singleton;

class Cookie extends Singleton {

	/**
	 * Cookie constructor.
	 *
	 * @param array $argument
	 */
	public function __construct( array $argument = [] ) {
		parent::__construct( $argument );
	}


}