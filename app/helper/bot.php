<?php

namespace Gianism\Helper;


use Gianism\Pattern\Singleton;
use Gianism\Service\Twitter;

/**
 * Class Bot
 *
 * @package Gianism\Helper
 * @property-read Twitter $twitter
 */
class Bot extends Singleton
{

	/**
	 * @var string
	 */
	protected $post_type = 'tweet-bots';

	/**
	 * @var array
	 */
	protected $codes = array(
		'limit'
	);

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = array() ) {
		add_action('init', array($this, 'register_post_type'));
		add_filter('enter_title_here', array($this, 'enter_title_here'), 10, 2);
		add_action('edit_form_after_title', array($this, 'edit_form_after_title'), 10, 1);
		add_action('save_post', array($this, 'save_post'), 10, 2);

	}

	/**
	 * Register Post Type
	 */
	public function register_post_type(){
		/**
		 * gianism_twitter_bot_post_type_args
		 *
		 * Passded register_post_type
		 *
		 * @filter
		 * @param array $args
		 */
		$args = apply_filters('gianism_twitter_bot_post_type_args', array(
			'description' => $this->_("Twitter Bot by Gianism. Never show on admin screen."),
			'label' => $this->_('Twitter Bots'),
			'labels' => array(
				'name' => $this->_('Twitter Bot'),
			),
			'public' => false,
			'show_ui' => true,
			'capability_type' => 'post',
			'supports' => array('title', 'author'),
			'menu_icon' => 'dashicons-twitter'
		));
		register_post_type($this->post_type, $args);
	}

	/**
	 * Filter title box
	 *
	 * @param string $title
	 * @param \WP_Post $post
	 *
	 * @return string
	 */
	public function enter_title_here($title, $post){
		if( $post->post_type == $this->post_type ){
			$title = $this->_('Enter this bots name. Ex: Promotion Campaign 2014 mid');
		}
		return $title;
	}

	/**
	 * Save post data
	 *
	 * @param int $post_id
	 * @param \WP_Post $post
	 */
	public function save_post($post_id, $post){
		if( wp_is_post_autosave($post) || wp_is_post_revision($post) || $this->post_type != $post->post_type ){
			return;
		}
		if( !wp_verify_nonce($this->post('_gianismnonce'), 'gianism_twitter_bot') ){
			return;
		}
		// Save data

	}

	/**
	 * Edit form
	 *
	 * @param \WP_Post $post
	 */
	public function edit_form_after_title($post){
		wp_nonce_field('gianism_twitter_bot', '_gianismnonce', false);
		include $this->dir.'/templates/edit/bot.php';
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get($name){
		switch( $name ){
			case 'twitter':
				return Twitter::get_instance();
				break;
			case 'short_codes':
				return array_map(function($code){
					return 'gianism_'.$code;
				}, $this->codes);
				break;
			default:
				return parent::__get($name);
				break;
		}
	}
}
