<?php

namespace Gianism\Helper;


use Gianism\Pattern\Singleton;

/**
 * Class for monitor social network
 *
 * @todo Implement this class
 */
class Monitor extends Singleton {


	/**
	 * Interval Second
	 *
	 * @var int
	 */
	public static $interval = 60;

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = array() ) {
		parent::__construct( $argument );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
	}
}
