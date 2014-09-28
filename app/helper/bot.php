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
	 * Post meta key for Cron limit
	 *
	 * @var string
	 */
	private $limit_key = '_tweet_cron_limit';

	/**
	 * Post meta key for
	 *
	 * @var string
	 */
	private $time_key = '_tweet_cron_time';

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = array() ) {
		add_action('init', array($this, 'register_post_type'));
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_filter('enter_title_here', array($this, 'enter_title_here'), 10, 2);
		add_action('edit_form_after_title', array($this, 'edit_form_after_title'), 10, 1);
		add_action('save_post', array($this, 'save_post'), 10, 2);
		add_action("manage_{$this->post_type}_posts_custom_column", array($this, 'custom_columns'), 10, 2);
		add_filter( "manage_edit-tweet-bots_columns", array( $this, 'get_columns' ));
		add_filter('post_updated_messages', array($this, 'post_updated_messages'));
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
			'capability_type' => 'page',
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
		// Clear all data
		$this->clear_schedule($post);

		// Save data
		if( $this->post('gianism_bot_schedule') && is_array($this->post('gianism_bot_schedule')) ){
			foreach( $this->post('gianism_bot_schedule') as $time => $dates ){
				foreach( $dates as $date ){
					add_post_meta($post->ID, $this->time_key.'_'.$date, $time);
				}
			}
		}

		// Save end date time
		update_post_meta($post->ID, $this->limit_key, $this->post('tweet_ends'));

		// Clear object cache
		wp_cache_delete('schedule_'.$post->ID, 'gianism');
	}

	/**
	 * Enqueue assets
	 *
	 * @param string $suffix
	 */
	public function admin_enqueue_scripts($suffix){
		if( false !== array_search($suffix, array('post.php', 'post-new.php')) ){
			$screen = get_current_screen();
			if( $this->post_type == $screen->post_type ){
				wp_enqueue_script('gianism-twitter-bot-helper', $this->url.'assets/compass/js/admin-twitter-bot-helper'.( WP_DEBUG ? '' : '.min' ).'.js', array('jquery-effects-highlight'), $this->version, true);
				wp_localize_script('gianism-twitter-bot-helper', 'GianismBotLabel', array(
					'delete' => $this->_('Are you sure to delete this period? You can\'t undo this operation.'),
					'duplicate' => $this->_('This period is duplicated.'),
				));
			}
		}
	}

	/**
	 * Edit form
	 *
	 * @param \WP_Post $post
	 */
	public function edit_form_after_title($post) {
		wp_nonce_field( 'gianism_twitter_bot', '_gianismnonce', false );
		include $this->dir . '/templates/edit/bot.php';
	}

	/**
	 * @param $post
	 *
	 * @return array
	 */
	public function get_schedule($post){
		/** @var \wpdb $wpdb */
		global $wpdb;
		$post = get_post($post);
		$times = wp_cache_get('schedule_'.$post->ID, 'gianism');
		if( false === $times ){
			$query = <<<SQL
				SELECT * FROM {$wpdb->postmeta}
				WHERE post_id = %d
				  AND meta_key LIKE %s
				ORDER BY meta_key ASC, meta_value ASC
SQL;
			$results = $wpdb->get_results($wpdb->prepare($query, $post->ID, $this->time_key.'_%'));
			$times = array();
			for( $i = 1; $i <= 7; $i++ ){
				$times[$this->time_key.'_'.$i] = array();
			}
			foreach( $results as $result ){
				if( isset($times[$result->meta_key]) ){
					$times[$result->meta_key][] = $result->meta_value;
				}
			}
			wp_cache_set('schedule_'.$post->ID, $times, 'gianism');
		}
		return $times;
	}

	/**
	 * Get time line
	 *
	 * @param $post
	 *
	 * @return array
	 */
	public function get_time_line($post){
		$times = $this->get_schedule($post);
		$lines = array();
		foreach( $times as $key => $time ){
			foreach( $time as $t ){
				if( !isset($lines[$t]) ){
					$lines[$t] = array();
				}
				$lines[$t][] = substr($key, -1, 1);
			}
		}
		ksort($lines);
		return $lines;
	}

	/**
	 * Clear all schedule
	 *
	 * @param int|\WP_Post $post
	 */
	private function clear_schedule($post){
		/** @var \wpdb $wpdb */
		global $wpdb;
		$post = get_post($post);
		$query = <<<SQL
			DELETE FROM {$wpdb->postmeta}
			WHERE post_id = %d
			  AND meta_key LIKE %s
SQL;
		$wpdb->query($wpdb->prepare($query, $post->ID, $this->time_key.'_%'));
	}

	/**
	 * Returns cron limit
	 *
	 * @param int|\WP_Post $post
	 *
	 * @return false|string
	 */
	public function cron_limit($post){
		$post = get_post($post);
		$limit = get_post_meta($post->ID, $this->limit_key, true);
		return $limit ?: false;
	}

	public function get_columns($columns){
		$new_columns = array();
		foreach($columns as $key => $column){
			$new_columns[$key] = $column;
			if( 'date' == $key ){
				$new_columns['end_date'] = $this->_('End Date');
			}
		}
		return $new_columns;
	}

	public function custom_columns($column_name, $post_id){
		switch( $column_name ){
			case 'end_date':
				$limit = $this->cron_limit($post_id);
				if( preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/u', $limit) ){
					echo mysql2date(get_option('date_format'), $limit.' 00:00:00');
				}else{
					echo '---';
				}
				break;
			default:
				// Do nothing
				break;
		}
	}

	/**
	 * Update message
	 *
	 * @param $message
	 *
	 * @return mixed
	 */
	public function post_updated_messages($message){
		global $post;
		$message[$this->post_type] = array(
			1 => $this->_('Tweet updated.'),
			4 => $this->_('Tweet updated.'),
			6 => $this->_( 'Tweet published, thus bot is enabled.'),
			7 => $this->_('Tweet saved.'),
			8 => $this->_('Tweet submitted.'),
			9 => sprintf( $this->_('Tweet scheduled for: <strong>%1$s</strong>. '), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
			10 => $this->_('Tweet draft updated.'),
		);
		return $message;
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
