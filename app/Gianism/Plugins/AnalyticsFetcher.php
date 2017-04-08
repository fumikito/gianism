<?php

namespace Gianism\Plugins;

use Gianism\Pattern\Singleton;

/**
 * Ability to fetch Google analytics data
 *
 * @package Gianism
 * @property \Gianism\Plugins\Analytics $google
 * @property \Google_Service_Analytics $ga
 * @property array  $profile
 * @property \wpdb  $db
 * @property string $view_id
 * @property string $table
 */
class AnalyticsFetcher extends Singleton {

	/**
	 * Category name for table
	 */
	const CATEGORY = 'general';

	/**
	 * Return today string in Y-m-d
	 *
	 * @return string
	 */
	protected function today() {
		return date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
	}

	/**
	 * Save to mysql
	 *
	 * @param string $date
	 * @param int $id
	 * @param int $value
	 */
	protected function save( $date, $id, $value ) {
		global $wpdb;
		$wpdb->insert( $this->table, array(
			'category'     => static::CATEGORY,
			'object_id'    => $id,
			'object_value' => $value,
			'calc_date'    => $date,
		), array( '%s', '%d', '%d', '%s' ) );
	}

	/**
	 * Fetch data from Google Analytics API
	 *
	 * @param string $start_date Date string
	 * @param string $end_date Date string
	 * @param string $metrics CSV of metrics E.g., 'ga:visits,ga:pageviews'
	 * @param array  $params Option params below
	 * @param bool   $throw If set to true, throws exception
	 *
	 * @opt_param string dimensions A comma-separated list of Analytics dimensions. E.g., 'ga:browser,ga:city'.
	 * @opt_param string filters A comma-separated list of dimension or metric filters to be applied to Analytics data.
	 * @opt_param int max-results The maximum number of entries to include in this feed.
	 * @opt_param string segment An Analytics advanced segment to be applied to data.
	 * @opt_param string sort A comma-separated list of dimensions or metrics that determine the sort order for Analytics data.
	 * @opt_param int start-index An index of the first entity to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function fetch( $start_date, $end_date, $metrics, $params = [], $throw = false ) {
		try {
			if ( ! $this->ga || ! $this->view_id ) {
				throw new \Exception( 'Google Analytics is not connected.', 500 );
			}
			$result = $this->ga->data_ga->get( 'ga:' . $this->view_id, $start_date, $end_date, $metrics, $params );
			if ( $result && count( $result->rows ) > 0 ) {
				return $result->rows;
			} else {
				return [];
			}
		} catch ( \Exception $e ) {
			if ( $throw ) {
				throw $e;
			} else {
				error_log( sprintf( '[Gianism GA Error %s] %s', $e->getCode(), $e->getMessage() ) );
			}

			return array();
		}
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
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
			case 'table':
				return $this->google->ga_table;
				break;
			case 'db':
				global $wpdb;
				return $wpdb;
				break;
			case 'google':
				return Analytics::get_instance();
				break;
			default:
				return null;
				break;
		}
	}

}
