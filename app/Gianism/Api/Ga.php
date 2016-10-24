<?php

namespace Gianism\Api;

use Gianism\Service\Google;

/**
 * Class GA
 * @package Gianism\Api
 * @property-read \Gianism\Service\Google $google
 * @property-read \Google_Service_Analytics $ga
 * @property-read array $profile
 * @property-read string $view_id
 */
abstract class Ga extends Ajax
{
	/**
	 * Should return array as result
	 *
	 * @return array
	 */
	protected function get_result() {
		 return $this->fetch($this->start_date(), $this->end_date(), $this->get_metrics(), $this->get_params());
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
	protected function start_date(){
		if( preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->get('from') ) ){
			return $this->get('from');
		}else{
			return date_i18n('Y-m-d', strtotime('1 month ago', current_time('timestamp')));
		}
	}

	/**
	 * End date
	 *
	 * Get $_GET['to'] or now.
	 *
	 * @return string
	 */
	protected function end_date(){
		if( preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $this->get('to') ) ){
			return $this->get('to');
		}else{
			return date_i18n('Y-m-d');
		}
	}


	/**
	 * Fetch data from Google Analytics API
	 *
	 * @param string $start_date Date string
	 * @param string $end_date Date string
	 * @param string $metrics CSV of metrics E.g., 'ga:visits,ga:pageviews'
	 * @param array $params Option params below
	 *
	 * @opt_param string dimensions A comma-separated list of Analytics dimensions. E.g., 'ga:browser,ga:city'.
	 * @opt_param string filters A comma-separated list of dimension or metric filters to be applied to Analytics data.
	 * @opt_param int max-results The maximum number of entries to include in this feed.
	 * @opt_param string segment An Analytics advanced segment to be applied to data.
	 * @opt_param string sort A comma-separated list of dimensions or metrics that determine the sort order for Analytics data.
	 * @opt_param int start-index An index of the first entity to retrieve. Use this parameter as a pagination mechanism along with the max-results parameter.
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function fetch($start_date, $end_date, $metrics, $params = array()){
		if( !$this->ga || !$this->view_id ){
			throw new \Exception('Google Analytics is not connected.', 500);
		}
		$result = $this->ga->data_ga->get('ga:'.$this->view_id, $start_date, $end_date, $metrics, $params);
		if( $result && count($result->rows) > 0 ){
			return $result->rows;
		}else{
			return array();
		}
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return array|\Gianism\Pattern\Singleton|\Google_Service_Analytics|mixed|null
	 */
	public function __get($name){
		switch( $name ){
			case 'ga':
				try{
					return $this->google->ga;
				}catch (\Exception $e){
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
				return Google::get_instance();
				break;
			default:
				return parent::__get($name);
				break;
		}
	}
}
