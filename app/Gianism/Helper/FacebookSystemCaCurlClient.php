<?php
/**
 * Custom Facebook cURL HTTP client that uses system CA certificates
 *
 * @package Gianism\Helper
 * phpcs:ignoreFile WordPress.NamingConventions.ValidVariableName
 */

namespace Gianism\Helper;

use Facebook\HttpClients\FacebookCurlHttpClient;

/**
 * FacebookSystemCaCurlClient
 *
 * Extends Facebook's cURL client to use system CA certificates
 * instead of the bundled DigiCert certificate.
 */
class FacebookSystemCaCurlClient extends FacebookCurlHttpClient {

	/**
	 * Opens a new curl connection with system CA certificates.
	 *
	 * @param string $url     The endpoint to send the request to.
	 * @param string $method  The request method.
	 * @param string $body    The body of the request.
	 * @param array  $headers The request headers.
	 * @param int    $timeOut The timeout in seconds for the request.
	 */
	public function openConnection( $url, $method, $body, array $headers, $timeOut ) {
		// Call parent to set up basic options
		parent::openConnection( $url, $method, $body, $headers, $timeOut );

		// Priority 1: Use bundled updated CA certificate
		$bundled_ca = gianism_root_dir() . '/certs/cacert.pem';
		if ( file_exists( $bundled_ca ) ) {
			$this->facebookCurl->setopt( CURLOPT_CAINFO, $bundled_ca );
			return;
		}

		// Priority 2: Use system CA bundle if available
		$ca_info = ini_get( 'curl.cainfo' );
		if ( $ca_info && file_exists( $ca_info ) ) {
			$this->facebookCurl->setopt( CURLOPT_CAINFO, $ca_info );
			return;
		}

		// Priority 3: Fallback to common CA bundle locations
		$ca_paths = [
			'/etc/pki/tls/certs/ca-bundle.crt',           // RHEL/CentOS/Amazon Linux
			'/etc/ssl/certs/ca-certificates.crt',         // Debian/Ubuntu
			'/etc/ssl/ca-bundle.pem',                     // OpenSUSE
			'/usr/local/share/certs/ca-root-nss.crt',     // FreeBSD
		];

		foreach ( $ca_paths as $path ) {
			if ( file_exists( $path ) ) {
				$this->facebookCurl->setopt( CURLOPT_CAINFO, $path );
				return;
			}
		}
	}
}
