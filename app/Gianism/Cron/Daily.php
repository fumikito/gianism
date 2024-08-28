<?php

namespace Gianism\Cron;

use Gianism\Pattern\Singleton;
use Gianism\Plugins\AnalyticsFetcher;


/**
 * Abstract class for google
 *
 * @package  Gianism\Cron
 *
 */
abstract class Daily extends AnalyticsFetcher {

	/**
	 * Interval for cron
	 *
	 * @const string
	 */
	const INTERVAL = 'daily';

	/**
	 * Category name for table
	 */
	const CATEGORY = 'general';

	/**
	 * If true, cron doesn't work
	 *
	 * @const bool
	 */
	const SKIP_CRON = false;

	/**
	 * Cron start time
	 *
	 * Default is midnite. If you want to change to typical time, override this.
	 * This will be converted to GMT.
	 *
	 * @var string
	 */
	protected $time = '00:00:00';

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = array() ) {
		parent::__construct( $argument );
		if ( ! wp_next_scheduled( $this->get_action() ) ) {
			wp_schedule_event( $this->build_timestamp(), static::INTERVAL, $this->get_action() );
		}
		add_action( $this->get_action(), array( $this, 'do_cron' ) );
	}

	/**
	 * Create cron timestamp
	 *
	 * @return int
	 */
	public function build_timestamp() {
		$now = date_i18n( 'Y-m-d ' ) . $this->time;

		return (int) get_gmt_from_date( $now, 'U' );
	}

	/**
	 * Get result
	 *
	 * @return array
	 */
	abstract public function get_results();

	/**
	 * Applied for each result
	 *
	 * @param $result
	 *
	 * @return void
	 */
	abstract protected function parse_row( $result );

	/**
	 * Do cron and save data.
	 */
	public function do_cron() {
		if ( ! static::SKIP_CRON ) {
			foreach ( $this->get_results() as $result ) {
				$this->parse_row( $result );
			}
		}
	}

	/**
	 * Get action name
	 *
	 * @return string
	 */
	protected function get_action() {
		return get_called_class() . '_' . static::CATEGORY . '_' . static::INTERVAL;
	}
}
