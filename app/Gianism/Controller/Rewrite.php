<?php

namespace Gianism\Controller;


use Gianism\Pattern\AbstractController;
use Gianism\Service\AbstractService;

/**
 * Rewrite rule controller
 *
 * @package Gianism
 */
class Rewrite extends AbstractController {

	/**
	 * Rewrite rules.
	 *
	 * Initialized on constructor
	 *
	 * @var array
	 */
	private $rewrites = [];

	/**
	 * URL prefixes
	 *
	 * @var array
	 */
	private $prefixes = [];

	/**
	 * @var array
	 */
	protected $query_vars = [ 'gianism_service', 'gianism_action' ];

	/**
	 * Rewrite constructor.
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		parent::__construct( $argument );
		// Add query vars
		add_filter( 'query_vars', [ $this, 'filter_vars' ] );
		// Instance all instances
		// and build rewrite rules
		// Prefixes
		foreach ( $this->service->all_services() as $service ) {
			$instance = $this->service->get( $service );
			if ( $instance && $instance->enabled ) {
				$this->prefixes[ $service ] = $instance->url_prefix;
			}
		}
		// Register rewrite rules
		if ( ! empty( $this->prefixes ) ) {
			$preg     = implode( '|', $this->prefixes );
			$rewrites = [
				"^({$preg})/?$"         => 'index.php?gianism_service=$matches[1]&gianism_action=default',
				"^({$preg})/([^/]+)/?$" => 'index.php?gianism_service=$matches[1]&gianism_action=$matches[2]',
			];
			$prefix   = $this->option->get_formatted_prefix();
			if ( $prefix ) {
				$rewrites[ "^{$prefix}/({$preg})/?$" ]         = 'index.php?gianism_service=$matches[1]&gianism_action=default';
				$rewrites[ "^{$prefix}/({$preg})/([^/]+)/?$" ] = 'index.php?gianism_service=$matches[1]&gianism_action=$matches[2]';
			}
			/**
			 * Rewrite rules array for Gianism
			 *
			 * @since 3.0.0
			 * @filter
			 * @param array $rewrites Rewrite rules array for Gianism
			 * @return array
			 */
			$this->rewrites = apply_filters( 'gianism_rewrite_rules', $rewrites );
			// Hook for rewrite rules
			add_filter( 'rewrite_rules_array', [ $this, 'rewrite_rules_array' ] );
			// Check if rewrite rules are satisfied
			add_action( 'admin_init', [ $this, 'check_rewrite' ] );
			// WP_Query
			add_action( 'pre_get_posts', [ $this, 'hijack_query' ] );
		}
	}

	/**
	 * Add custom query vars
	 *
	 * @param array $original_vars
	 *
	 * @return array
	 */
	public function filter_vars( $original_vars ) {
		/**
		 * Register custom query vars
		 *
		 * @filter
		 * @since 3.0.0
		 * @param array $additional_vars
		 * @param array $original_vars
		 * @return array
		 */
		$additional_vars = apply_filters( 'gianism_filter_vars', $this->query_vars, $original_vars );
		return array_merge( $original_vars, $additional_vars );
	}

	/**
	 * Customize rewrite rules
	 *
	 * @param array $rules
	 *
	 * @return array
	 */
	public function rewrite_rules_array( array $rules ) {
		if ( ! empty( $this->rewrites ) ) {
			foreach ( $this->rewrites as $rewrite => $regexp ) {
				if ( ! isset( $rules[ $rewrite ] ) ) {
					$rules = array_merge(
						[
							$rewrite => $regexp,
						],
						$rules
					);
				}
			}
		}

		return $rules;
	}

	/**
	 * Check rewrite rules and flush if required
	 *
	 */
	public function check_rewrite() {
		$registered_rewrites = $this->option->get( 'rewrite_rules' );
		foreach ( $this->rewrites as $reg => $replaced ) {
			if ( ! isset( $registered_rewrites[ $reg ] ) || $replaced !== $registered_rewrites[ $reg ] ) {
				flush_rewrite_rules();
			}
		}
	}

	/**
	 * If endpoint matched, do parse request.
	 *
	 * @param \WP_Query $wp_query
	 */
	public function hijack_query( \WP_Query &$wp_query ) {
		$service = $wp_query->get( 'gianism_service' );
		$action  = $wp_query->get( 'gianism_action' );
		if ( ! is_admin() && $wp_query->is_main_query() && $service && $action ) {
			/**
			 * Convert rewrite rule to service name
			 *
			 * @since 3.0.0
			 * @param string $service
			 */
			$filtered_service = apply_filters( 'gianism_filter_service_prefix', $service );
			if ( in_array( $service, $this->prefixes, true ) && ( $this->service->get( $filtered_service ) ) ) {
				nocache_headers();
				/** @var AbstractService $instance */
				// Parse Request
				$this->service->get( $filtered_service )->parse_request( $action, $wp_query );
			} else {
				$wp_query->set_404();
			}
		}
	}
}
