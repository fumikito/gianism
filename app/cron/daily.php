<?php

namespace Gianism\Cron;

use Gianism\Pattern\Singleton;
use Gianism\Service\Google;


/**
 * Abstract class for google
 *
 * @package Gianism\Cron
 * @property-read \Gianism\Service\Google $google
 * @property-read \Google_Service_Analytics $ga
 * @property-read array $profile
 * @property-read string $view_id
 * @property-read string $table
 * @property-read string $action
 */
abstract class Daily extends Singleton
{

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
    protected function __construct( array $argument = array() ){
        if( !wp_next_scheduled($this->action) ){
            wp_schedule_event($this->build_timestamp(), static::INTERVAL, $this->action);
        }
        add_action($this->action, array($this, 'do_cron'));
    }

    /**
     * Create cron timestamp
     *
     * @return int
     */
    public function build_timestamp(){
        $now = date_i18n('Y-m-d ').$this->time;
        return (int)get_gmt_from_date($now, 'U');

    }

    /**
     * Return today string in Y-m-d
     *
     * @return string
     */
    protected function today(){
        return date_i18n('Y-m-d', current_time('timestamp'));
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
     * @return array
     */
    public function fetch($start_date, $end_date, $metrics, $params = array()){
        try{
            if( !$this->ga || !$this->view_id ){
                throw new \Exception('Google Analytics is not connected.', 500);
            }
            $result = $this->ga->data_ga->get('ga:'.$this->view_id, $start_date, $end_date, $metrics, $params);
            if( $result && count($result->rows) > 0 ){
                return $result->rows;
            }else{
                return array();
            }
        }  catch ( \Exception $e ){
            error_log(sprintf('[Gianism GA Error %s] %s', $e->getCode(), $e->getMessage()));
            return array();
        }
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
     * @return void
     */
    abstract protected function parse_row($result);

    /**
     * Do cron and save data.
     */
    public function do_cron(){
        if( !static::SKIP_CRON ){
            foreach( $this->get_results() as $result ){
                $this->parse_row($result);
            }
        }
    }

    /**
     * Save to mysql
     *
     * @param string $date
     * @param int $id
     * @param int $value
     */
    protected function save($date, $id, $value){
        global $wpdb;
        $wpdb->insert($this->table, array(
            'category' => static::CATEGORY,
            'object_id' => $id,
            'object_value' => $value,
            'calc_date' => $date,
        ), array('%s', '%d', '%d', '%s'));
    }

    /**
     * Getter
     *
     * @param string $name
     * @return array|Singleton|\Google_Service_Analytics|mixed|null|string
     */
    public function __get( $name ){
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
            case 'table':
                return $this->google->ga_table;
                break;
            case 'google':
                return Google::get_instance();
                break;
            case 'action':
                return get_called_class().'_'.static::CATEGORY.'_'.static::INTERVAL;
                break;
            default:
                return null;
                break;
        }
    }
}
