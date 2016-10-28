<?php

namespace Gianism;
use Gianism\Service\Twitter;

/**
 * Command for gianism
 *
 * @package Gianism
 */
class Command extends \WP_CLI_Command {

	/**
	 * Try tweet as twitter app's account
	 *
	 * @synopsis <message>
	 * @param array $args
	 */
	public function tweet( $args ) {
		list( $message ) = $args;
		$twitter = Twitter::get_instance();
		$tweet = $twitter->tweet( $message );
		print_r( $tweet );
		\WP_CLI::success( 'Tweet has been sent. Response message is above.' );
	}

	/**
	 * Get recent mentions.
	 *
	 * ## Options
	 *
	 * [--raw]
	 *   : If set, displays raw response.
	 *
	 * @synopsis [--raw]
	 * @param array $args
	 * @param array $assoc
	 */
	public function mentions( $args, $assoc ) {
		$raw = isset( $assoc['raw'] ) && $assoc['raw'];
		$twitter = Twitter::get_instance();
		$response = $twitter->get_mentions();
		foreach ( $response as $res ) {
			if ( $raw ) {
				print_r( $res );
			} else {
				\WP_CLI::line( $res->text );
				\WP_CLI::line( '' );
			}
		}
		\WP_CLI::success( sprintf( 'Got %d response.', count( $response ) ) );
	}
}
