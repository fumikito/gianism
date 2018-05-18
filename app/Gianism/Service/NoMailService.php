<?php

namespace Gianism\Service;
use Gianism\Helper\PseudoPhpMailer;


/**
 * Service which doesn't provide email address
 *
 * @package Gianism
 * @since 2.0.0
 * @author Takahashi Fumiki
 */
abstract class NoMailService extends AbstractService {

	/**
	 * Pseudo domain
	 * @var string
	 */
	protected $pseudo_domain = '';

	/**
	 * Register pseudo mail action
	 *
	 */
	protected function init_action() {
		add_filter( 'wp_mail', array( $this, 'mail_handler' ) );
	}

	/**
	 * Returns if given mail address is pseudo.
	 *
	 * @param string $mail
	 *
	 * @return boolean
	 */
	public function is_pseudo_mail( $mail ) {
		return ! empty( $this->pseudo_domain ) && ( false !== strpos( $mail, '@' . $this->pseudo_domain ) );
	}

	/**
	 * Alternative wp_mail
	 *
	 * @param int $user_id
	 * @param string $subject
	 * @param string $message
	 * @param array|string $headers
	 * @param string $attachment
	 *
	 * @return void
	 */
	protected function wp_mail( $user_id, $subject, $message, $headers = '', $attachment = '' ) {
		/**
		 * Fallback action for no mail
		 *
		 * @since 3.0.0
		 * @action gianism_mail_fallback
		 * @param int    $user_id
		 * @param string $subject
		 * @param string $message
		 * @param string $headers
		 * @param string $attachment
		 */
		do_action( 'gianism_mail_fallback', $user_id, $subject, $message, $headers, $attachment );
	}

	/**
	 * Override default wp_mai
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	final public function mail_handler( $args ) {
		if ( $this->is_pseudo_mail( $args['to'] ) && ( $user_id = email_exists( $args['to'] ) ) ) {
			// Send mail
			$this->wp_mail( $user_id, $args['subject'], $args['message'], $args['headers'], $args['attachments'] );
			add_action( 'phpmailer_init', [ $this, 'hijack_php_mailer' ] );
		}
		return $args;
	}

	/**
	 * Hijack php mailer to pseudo
	 *
	 * @param \PHPMailer $php_mailer
	 */
	public function hijack_php_mailer( &$php_mailer ) {
		$instance = new PseudoPhpMailer( true );
		/**
		 * You can replace pseudo mail instance
		 *
		 * @filter gianism_pseudo_mailer_instance
		 * @param PseudoPhpMailer $instance
		 * @return \Gianism\Pattern\DummyPhpMailer
		 */
		$php_mailer = apply_filters( 'gianism_pseudo_mailer_instance', $instance );
		remove_action( 'phpmailer_init', [ $this, 'hijack_php_mailer' ] );
	}

	/**
	 * Get pseudo email.
	 *
	 * @param mixed $prefix
	 *
	 * @return string
	 */
	protected function create_pseudo_email( $prefix ) {
		return sprintf( '%s@%s', $prefix, $this->pseudo_domain );
	}
}
