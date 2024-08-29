<?php

namespace Gianism\Controller;


use Gianism\Pattern\AbstractController;

/**
 * Admin user list controller
 */
class UserList extends AbstractController {

	/**
	 * {@inheritDoc}
	 */
	public function __construct( array $argument = [] ) {
		parent::__construct( $argument );
		add_filter( 'manage_users_columns', [ $this, 'add_columns' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'render_column' ], 10, 3 );
	}


	/**
	 * Add column for users list
	 *
	 * @param array{string,string} $columns Column name and label
	 * @return array
	 */
	public function add_columns( $columns ) {
		$new_columns = [];
		foreach ( $columns as $key => $label ) {
			if ( 'posts' === $key ) {
				$new_columns['gianism'] = __( 'SNS', 'wp-gianism' );
			}
			$new_columns[ $key ] = $label;
		}
		return $new_columns;
	}

	/**
	 * Render column of User list table..
	 *
	 * @param string $column  Column name.
	 * @param int    $user_id User ID.
	 *
	 * @return string
	 */
	public function render_column( $content, $column, $user_id ) {
		switch ( $column ) {
			case 'gianism':
				$connected = [];
				// Get user's connected SNS
				foreach ( $this->service->service_list() as $service ) {
					if ( ! $service['enabled'] ) {
						continue 1;
					}
					$controller = $this->service->get( $service['name'] );
					if ( $controller->is_connected( $user_id ) ) {
						$connected[] = sprintf(
							'<i class="lsf lsf-%s" title="%s"></i>',
							esc_attr( $service['name'] ),
							sprintf(
								// translators: %s is service name
								esc_attr__( 'Connected with %s', 'wp-gianism' ),
								esc_attr( $controller->verbose_service_name )
							)
						);
						$controller->disconnect_button( $user_id );
					}
				}
				return empty( $connected ) ? '<span style="color:lightgray;">---</span>' : implode( ' ', $connected );
			default:
				return $content;
		}
	}
}
