<?php

namespace Gianism\Api;

use Gianism\Helper\Input;
use Gianism\Plugins\Analytics;
use Gianism\Service\Google;

/**
 * Class GA
 * @package Gianism
 * @property \Gianism\Plugins\Analytics $google
 * @property \Google_Service_Analytics $ga
 * @property array $profile
 * @property string $view_id
 * @property Input $input
 */
abstract class Ga extends Ajax {
	/**
	 * Should return array as result
	 *
	 * @return array
	 */
	protected function get_result() {
		return $this->fetch( $this->start_date(), $this->end_date(), $this->get_metrics(), $this->get_params() );
	}

	/**
	 * Should return metrics
	 *
	 * @see https://developers.google.com/analytics/devguides/reporting/core/dimsmets
	 * @return string CSV of metrics E.g., 'ga:visits,ga:pageviews'
	 */
	abstract protected function get_metrics();

	/**
	 * Should return parameters
	 *
	 * @see self::fetch
	 * @return array
	 */
	abstract protected function get_params();

	/**
	 * Start date
	 *
	 * Get $_GET['from'] or 1 month ago.
	 *
	 * @return string
	 */
	protected function start_date() {
		if ( preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->input->get( 'from' ) ) ) {
			return $this->input->get( 'from' );
		} else {
			return date_i18n( 'Y-m-d', strtotime( '1 month ago', current_time( 'timestamp' ) ) );
		}
	}

	/**
	 * End date
	 *
	 * Get $_GET['to'] or now.
	 *
	 * @return string
	 */
	protected function end_date() {
		if ( preg_match( '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->input->get( 'to' ) ) ) {
			return $this->input->get( 'to' );
		} else {
			return date_i18n( 'Y-m-d' );
		}
	}

	/**
	 * Deprecated function
	 *
	 * @deprecated 3.0.0
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	protected function get( $name ) {
		return $this->input->get( $name );
	}

	/**
	 * Deprecated function
	 *
	 * @deprecated 3.0.0
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	protected function post( $name ) {
		return $this->input->post( $name );
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return array|\Gianism\Pattern\Singleton|\Google_Service_Analytics|mixed|null
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'ga':
				try {
					return $this->google->ga;
				} catch ( \Exception $e ) {
					return null;
				}
				break;
			case 'profile':
				return $this->google->ga_profile;
				break;
			case 'view_id':
				return $this->profile['view'];
				break;
			case 'google':
				return Analytics::get_instance();
				break;
			case 'input':
				return Input::get_instance();
				break;
			default:
				return parent::__get( $name );
				break;
		}
	}
}
