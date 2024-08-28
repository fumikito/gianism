<?php

namespace Gianism\Controller;

use Gianism\Pattern\AbstractController;

/**
 * Profile page controller
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 */
class Profile extends AbstractController {

	/**
	 * Constructor
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		parent::__construct( $argument );
		// If not enabled, skip.
		if ( ! $this->option->is_enabled() ) {
			return;
		}
		if ( $this->option->is_network_activated() && $this->network->is_child_site() ) {
			// If this is network child, display parent site link.
			add_action( 'show_user_profile', [ $this, 'parent_site_link' ] );
		} else {
			add_action( 'show_user_profile', [ $this, 'admin_connect_buttons' ] );
			add_action( 'profile_update', [ $this, 'profile_updated' ], 10, 2 );
		}
		// Show connection button on admin screen.
	}

	/**
	 * Update password and delete meta
	 *
	 * @param int $user_id
	 * @param object $old_user_data
	 */
	public function profile_updated( $user_id, $old_user_data ) {
		$current_user = get_userdata( $user_id );
		// Check if password is on your own.
		if ( $this->profile_checker->is_password_unknown( $user_id ) && $current_user->user_pass !== $old_user_data->user_pass ) {
			// Password changed
			delete_user_meta( $user_id, '_wpg_unknown_password' );
			$this->add_message( __( 'Your password is now on your own!', 'wp-gianism' ) );
		}
		// Check if email is proper and old one is pseudo
		if ( $old_user_data->user_email !== $current_user->user_email && $this->profile_checker->is_pseudo_mail( $old_user_data->user_email ) ) {
			if ( $this->profile_checker->is_pseudo_mail( $current_user->user_email ) ) {
				// email isn't changed.
				$this->add_message( $this->_( 'You mail address is still pseudo one! Please change it to valid one.' ), true );
			} else {
				// O.K.
				$this->add_message( $this->_( 'Your email seems to be valid now.' ) );
			}
		}
	}

	/**
	 * Show connect buttons
	 *
	 * @param \WP_User $user
	 */
	public function admin_connect_buttons( \WP_User $user ) {
		$notices = $this->profile_notices( $user );
		?>
		<h3 class="wpg-connect-header">
			<i class="lsf lsf-link"></i> <?php $this->e( 'Connection with SNS' ); ?>
		</h3>
		<?php if ( ! empty( $notices->get_error_messages() ) ) : ?>
			<ul class="wpg-notice">
				<?php foreach ( $notices->get_error_messages() as $msg ) : ?>
					<li><?php echo wp_kses_post( $msg ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<table class="form-table wpg-connect-table">
			<tbody>
			<?php do_action( 'gianism_user_profile', $user ); ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get error message to display.
	 *
	 * @param \WP_User $user
	 * @return \WP_Error
	 */
	public function profile_notices( $user ) {
		$error = new \WP_Error();
		// show password notice
		if ( get_user_meta( $user->ID, '_wpg_unknown_password', true ) ) {
			$error->add( 'unknown_password', __( 'Your password is automatically generated. Please <strong><a href="#pass1">update password</a> to your own</strong> before disconnecting your account.', 'wp-gianism' ) );
		}
		// Check if mail address is pseudo
		if ( $this->profile_checker->is_pseudo_mail( $user->user_email ) ) {
			$error->add( 'invalid_email', __( 'Your mail address is automatically generated and is pseudo. <a href="#email">Changing it</a> to valid mail address is highly recommended, else <strong>you might be unable to log in</strong>.', 'wp-gianism' ) );
		}
		return apply_filters( 'gianism_user_profile_notices', $error, $user );
	}

	/**
	 * Display parent site link.
	 *
	 * @param \WP_User $user
	 */
	public function parent_site_link( \WP_User $user ) {
		?>
		<h3 class="wpg-connect-header">
			<?php $this->e( 'Connection with SNS' ); ?>
		</h3>
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
				// translators: %s is admin url.
					__( 'Please setup SNS connection at <a href="%s">parent site\'s profile page</a>.', 'wp-gianism' ),
					get_admin_url( $this->option->get_parent_blog_id(), 'profile.php' )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Detect if mail address contains @pseudo
	 *
	 * @param string $email
	 * @deprecated 4.2.0
	 *
	 * @return bool
	 */
	private function has_pseudo_segment( $email ) {
		return false !== strpos( $email, '@pseudo.' );
	}
}
