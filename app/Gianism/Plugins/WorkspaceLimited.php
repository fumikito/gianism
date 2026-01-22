<?php

namespace Gianism\Plugins;

use Gianism\Service\Google;

/**
 * Workspace Limited Mode
 *
 * Restrict site access to users from specific Google Workspace domains.
 *
 * @package Gianism\Plugins
 * @since 5.4.0
 * @property-read Google $google
 */
class WorkspaceLimited extends PluginBase {

	/**
	 * Return plugin description.
	 *
	 * @return string
	 */
	public function plugin_description() {
		return __( 'Restrict login to specific Google Workspace domains.', 'wp-gianism' );
	}

	/**
	 * Check if plugin is enabled.
	 *
	 * @return bool
	 */
	public function plugin_enabled() {
		return $this->google->enabled && $this->google->ggl_workspace_mode;
	}

	/**
	 * Constructor.
	 *
	 * @param array $argument
	 */
	protected function __construct( array $argument = [] ) {
		parent::__construct( $argument );
		if ( ! $this->plugin_enabled() ) {
			return;
		}
		// Hook into Google login validation.
		add_filter( 'gianism_google_login_allowed', [ $this, 'validate_email_domain' ], 10, 3 );
	}

	/**
	 * Validate email domain on Google login.
	 *
	 * @param bool   $allowed Whether login is allowed.
	 * @param string $email   User's email address.
	 * @param array  $profile User's profile data from Google.
	 * @return bool
	 */
	public function validate_email_domain( $allowed, $email, $profile ) {
		if ( ! $allowed ) {
			// Already rejected by another filter.
			return false;
		}
		$allowed_domains = $this->get_allowed_domains();
		if ( empty( $allowed_domains ) ) {
			// No domains configured, allow all.
			return true;
		}
		$email_domain = $this->extract_domain( $email );
		return in_array( $email_domain, $allowed_domains, true );
	}

	/**
	 * Extract domain from email address.
	 *
	 * @param string $email Email address.
	 * @return string Domain part of email.
	 */
	private function extract_domain( $email ) {
		$parts = explode( '@', $email );
		return isset( $parts[1] ) ? strtolower( $parts[1] ) : '';
	}

	/**
	 * Get allowed domains.
	 *
	 * @return array List of allowed domains.
	 */
	public function get_allowed_domains() {
		$domains = $this->google->ggl_workspace_allowed_domains;
		if ( empty( $domains ) ) {
			return [];
		}
		// Split by newline or comma, trim, and filter empty values.
		$domains = preg_split( '/[\r\n,]+/', $domains );
		$domains = array_map( 'trim', $domains );
		$domains = array_map( 'strtolower', $domains );
		$domains = array_filter( $domains );
		return array_values( $domains );
	}

	/**
	 * Getter.
	 *
	 * @param string $name Property name.
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'google':
				return $this->service->get( 'google' );
			default:
				return parent::__get( $name );
		}
	}
}
