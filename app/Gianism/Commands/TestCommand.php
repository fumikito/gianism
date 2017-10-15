<?php

namespace Gianism\Commands;
use Facebook\GraphNodes\GraphNode;
use Gianism\Helper\i18n;
use Gianism\Plugins\AnalyticsFetcher;
use Gianism\Service\Twitter;
use cli\Table;

/**
 * Command for API test of gianism
 *
 * @package Gianism
 */
class TestCommand extends \WP_CLI_Command {

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
		\WP_CLI::success( printf( __( 'Tweet has been sent as %s. Response message is above.', 'wp-gianism' ), $twitter->tw_screen_name ) );
	}

	/**
	 * Upload media to twitter
	 *
	 * @since 3.0.7
	 * @synopsis <url_or_id> <status>
	 * @param array $args
	 */
	public function twitter_upload_media( $args ) {
		list( $path_or_id, $status ) = $args;
		$result = Twitter::get_instance()->tweet_with_media( $status, [ $path_or_id ] );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		} else {
			print_r( $result );
			\WP_CLI::success( __( 'Media is successfully uploaded.', 'wp-gianism' ) );
		}
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
		\WP_CLI::success( sprintf( _x( 'Got %d response.', 'CLI', 'wp-gianism' ), count( $response ) ) );
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
			\WP_CLI::error( __( 'Google Analytics is not connected.', 'wp-gianism' ) );
		}
		$from = isset( $assoc['from'] ) ? $assoc['from'] : date_i18n( 'Y-m-d', strtotime( '7 days ago' ) );
		$to   = isset( $assoc['to'] ) ? $assoc['to'] : date_i18n( 'Y-m-d', strtotime( 'Yesterday' ) );
		try {
			\WP_CLI::line( sprintf( __( 'Get popular pages from %1$s to %2$s.', 'wp-gianism' ), $from, $to ) );
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

	/**
	 * Get current page info
	 */
	public function get_fb_page_info() {
		$api = gianism_fb_page_api();
		if ( is_wp_error( $api ) ) {
			\WP_CLI::error( $api->get_error_message() );
		}
		try {
			$page = $api->get( '/me' )->getGraphPage();
			$table = new Table( [ 'Key', 'Value' ], [
				[ 'Name', $page->getName() ],
				[ 'ID', $page->getId() ],
				[ 'Category', $page->getCategory() ?: '---' ],
			] );
			$table->display();
		} catch ( \Exception $e ) {
			\WP_CLI::error( sprintf( '%s: %s', $e->getCode(), $e->getMessage() ) );
		}
	}

	/**
	 * Get instant articles information.
	 *
	 * ## OPTIONS
	 *
	 * : [--offset=<offset>]
	 *   Optional. Pagination ID for offset. You can get one after execution.
	 *
	 * : [--develop]
	 *   Optional. If set, development content will be retrieved.
	 *
	 * @synopsis [--offset=<offset>] [--develop]
	 * @param array $args
	 * @param array $assoc
	 */
	public function fb_instant_articles( $args, $assoc ) {
		try {
			$is_develop = isset( $assoc['develop'] ) && $assoc['develop'];
			$offset  = isset( $assoc['offset'] ) ? $assoc['offset'] : 0;
			$api = gianism_fb_page_api();
			$arguments = [
				'access_token' => $api->getDefaultAccessToken()->getValue(),
				'development_mode' => $is_develop,
			];
			if ( $offset ) {
				$arguments['after'] = $offset;
			}
			$edge = $api->get( add_query_arg( $args, 'me/instant_articles' ) )->getGraphEdge();
			$table = new Table();
			$table->setHeaders( [ 'Facebook ID', __( 'Post ID', 'wp-gianism' ), __( 'Post Title', 'wp-gianism' ), 'URL' ] );
			foreach ( $edge->getIterator() as $node ) {
				/* @var GraphNode $node */
				$url = $node->getField( 'canonical_url', '---' );
				$post_id = url_to_postid( $url );
				$table->addRow( [
					$node->getField( 'id' ),
					$post_id,
					get_the_title( $post_id ),
					$url,
				] );
			}
			$table->display();
			// Show paging information.
			$next_page = $edge->getCursor( 'after' );
			$line = __( 'Successfully retrieved instant articles!', 'wp-gianism' );
			if ( $next_page ) {
				\WP_CLI::success( $line . ' ' . sprintf( __( 'If you need more instant articles, set --after=%s', 'wp-gianism' ), $next_page ) );
			} else {
				\WP_CLI::success( $line );
			}
		} catch ( \Exception $e ) {
			\WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Get instant article name.
	 *
	 * ## OPTIONS
	 *
	 * : <url_or_id>
	 *   URL or ID of post.
	 *
	 * : [--develop]
	 *   Optional. If set, development content will be retrieved.
	 *
	 * @synopsis <url_or_id> [--develop]
	 * @param array $args
	 * @param array $assoc
	 */
	public function fb_instant_article_status( $args, $assoc ) {
		list( $url_or_id ) = $args;
		$is_develop = isset( $assoc['develop'] ) && $assoc['develop'];
		$result = gianism_fb_instant_article_status( $url_or_id, $is_develop );
		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}
		\WP_CLI::line( '' );
		\WP_CLI::line( '=====HTML====' );
		\WP_CLI::line( '' );
		echo $result['html_source'];
		\WP_CLI::line( '' );
		\WP_CLI::line( '=====HTML====' );
		\WP_CLI::line( '' );
		\WP_CLI::success( sprintf( '%s: %s',  $result['id'], $result['url'] ) );
	}
}
