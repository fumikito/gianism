<?php

namespace Gianism\Commands;
use Gianism\Helper\i18n;
use Gianism\Plugins\AnalyticsFetcher;
use Gianism\Service\Twitter;
use cli\Table;

/**
 * Command for gianism
 *
 * @package Gianism
 */
class TestCommand extends \WP_CLI_Command {

	use i18n;

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
		\WP_CLI::success( printf( $this->_( 'Tweet has been sent as %s. Response message is above.' ), $twitter->tw_screen_name ) );
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
		\WP_CLI::success( sprintf( $this->_( 'Got %d response.' ), count( $response ) ) );
	}

	/**
	 * Get analytics
	 *
	 *
	 * @synopsis [--from=<from>] [--to=<to>]
	 * @param $args
	 * @param $assoc
	 */
	public function analytics( $args, $assoc ) {
		$fetch = AnalyticsFetcher::get_instance();
		if ( ! $fetch->ga ) {
			\WP_CLI::error( $this->_( 'Google Analytics is not connected.' ) );
		}
		$from = isset( $assoc['from'] ) ? $assoc['from'] : date_i18n( 'Y-m-d', strtotime( '7 days ago' ) );
		$to   = isset( $assoc['to'] ) ? $assoc['to'] : date_i18n( 'Y-m-d', strtotime( 'Yesterday' ) );
		try {
			\WP_CLI::line( sprintf( $this->_( 'Get popular page from %s to %s' ), $from, $to ) );
			$table = new Table();
			$table->setHeaders( [ 'Page Path', 'PV' ] );
			$table->setRows( $fetch->fetch( $from, $to, 'ga:pageviews', [
				'dimensions'  => 'ga:PagePath',
				'sort'        => '-ga:pageviews',
				'max-results' => 10,
			] ) );
			$table->display();
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
	}
}
