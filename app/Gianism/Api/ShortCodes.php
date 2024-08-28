<?php

namespace Gianism\Api;

use Gianism\Pattern\Application;

/**
 * Short code API
 * @package gianism
 * @since 4.3.4
 */
class ShortCodes extends Application {

	/**
	 * ShortCodes constructor.
	 *
	 * @param array $argument
	 */
	public function __construct( array $argument = [] ) {
		parent::__construct( $argument );
		add_shortcode( 'gianism_login', [ $this, 'login_short_code' ] );
		add_shortcode( 'gianism_connection', [ $this, 'profile_connection_short_code' ] );
	}

	/**
	 * Display gianism login form in post content.
	 *
	 * @param array  $attrs
	 * @param string $content
	 * @return string
	 */
	public function login_short_code( $attrs = [], $content = '' ) {
		if ( is_user_logged_in() ) {
			return $content;
		}
		$attrs = shortcode_atts(
			[
				'before'      => '',
				'after'       => '',
				'redirect_to' => get_permalink(),
			],
			$attrs,
			'gianism_login'
		);
		ob_start();
		gianism_login( $attrs['before'], $attrs['after'], $attrs['redirect_to'] );
		$form = ob_get_contents();
		ob_end_clean();
		return $form;
	}

	/**
	 * Display gianism login form in post content.
	 *
	 * @param array  $attrs
	 * @param string $content
	 * @return string
	 */
	public function profile_connection_short_code( $attrs = [], $content = '' ) {
		if ( ! is_user_logged_in() ) {
			return $content;
		}
		ob_start();
		gianism_connection( wp_get_current_user() );
		$form = ob_get_contents();
		ob_end_clean();
		return $form;
	}
}
