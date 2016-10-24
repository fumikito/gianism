<?php

namespace Gianism\Api;

use Gianism\Pattern\Singleton;

/**
 * Ajax Base
 *
 * @package Gianism\Api
 */
abstract class Ajax extends Singleton
{

	/**
	 * Register nopriv Ajax if false
	 */
	const ONLY_MEMBER = true;

	/**
	 * Ajax action name
	 */
	const ACTION = 'gianism_ajax_base';

	/**
	 * Nonce action name
	 */
	const NONCE_ACTION = '';

	/**
	 * Should return array as result
	 *
	 * @return array
	 */
	abstract protected function get_result();

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = array() ) {
		add_action('wp_ajax_'.static::ACTION, array($this, 'ajax'));
		if( !static::ONLY_MEMBER ){
			add_action('wp_ajax_nopriv_'.static::ACTION, array($this, 'ajax'));
		}
	}

	/**
	 * Parse Result
	 *
	 * Override this function if you want to customize
	 * fetched data.
	 *
	 * @param array $result
	 *
	 * @return array
	 */
	protected function parse_result( array $result ){
		return $result;
	}

	/**
	 * Do ajax
	 */
	public function ajax(){
		try{
			if( static::NONCE_ACTION && !wp_verify_nonce($this->get('_wpnonce'), static::NONCE_ACTION) ){
				throw new \Exception($this->_('You have no permission.'), 403);
			}
			$result = $this->get_result();
			if( !is_array($result) ){
				throw new \Exception($this->_('Wrong value is returned.'), 500);
			}
			$result = $this->parse_result($result);
			nocache_headers();
			wp_send_json($result);
		}catch ( \Exception $e ){
			$code = $e->getCode() ?: 500;
			wp_die($e->getMessage(), get_bloginfo('name'), array(
				'response' => $code
			));
		}
	}

	/**
	 * Get Ajax endpoint
	 *
	 * @param array $query_params
	 *
	 * @return string
	 */
	public static function endpoint( array $query_params = array() ){
		$query_params = array_merge(array(
			'action' => static::ACTION,
		), $query_params);
		$endpoint = add_query_arg($query_params, admin_url('admin-ajax.php'));
		if( static::NONCE_ACTION ){
			$endpoint = wp_nonce_url($endpoint, static::NONCE_ACTION);
		}
		return $endpoint;
	}

	/**
	 * Return nonce
	 *
	 * @return string
	 */
	public static function get_nonce(){
		return static::NONCE_ACTION ? wp_create_nonce(static::NONCE_ACTION) : '';
	}
}
