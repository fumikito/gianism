<?php
/**
 * Composer package cleaner.
 *
 * The PHP library "google/api-client" is too huge and the files are so many.
 * This script removes unwanted libraries written in ``REQUIRED_LIBS.
 * These command lines are executed in `bin/build.sh`.
 */

const REQUIRED_LIBS = [
	// 'adsense',
	// 'calendar',
	'Analytics',
	'Oauth2',
	// 'youtube',
];

/**
 * Delete directory recursively.
 *
 * @param string $path
 *
 * @return bool
 */
function rmdir_recursive( $path ) {
	if ( is_dir( $path ) ) {
		$deleted = 0;
		$files   = array_diff( scandir( $path ), [ '.', '..' ] );
		foreach ( $files as $file ) {
			$deleted += rmdir_recursive( $path . '/' . $file );
		}
		if ( rmdir( $path ) ) {
			// $deleted++;
		}
		return $deleted;
	} else {
		return unlink( $path ) ? 1 : 0;
	}
}

$dir = dirname( __DIR__ ) . '/vendor/google/apiclient-services/src';

if ( ! is_dir( $dir ) ) {
	throw new Exception( sprintf( 'Composer directory %s does not exist.', $dir ), 404 );
}

$deleted = 0;

foreach ( scandir( $dir ) as $file ) {
	if ( in_array( $file, [ '.', '..' ], true ) ) {
		continue;
	}
	// Check if files are in white list.
	foreach ( REQUIRED_LIBS as $lib ) {
		if ( preg_match( "/{$lib}/ui", $file ) ) {
			printf( '%s saved.' . PHP_EOL, $file );
			continue 2;
		}
	}
	$deleted += rmdir_recursive( $dir . '/' . $file );
}

// Total removed files.
printf( 'Removed %s files.' . PHP_EOL, number_format( $deleted ) );

// Fix PHP 8.0 syntax in google/auth for PHP 7.4 compatibility
$typed_item_path = dirname( __DIR__ ) . '/vendor/google/auth/src/Cache/TypedItem.php';
if ( file_exists( $typed_item_path ) ) {
	$content = file_get_contents( $typed_item_path );

	// Fix typed properties
	$content = preg_replace( '/private mixed \$value;/', 'private $value;', $content );
	$content = preg_replace( '/private \?\\\\DateTimeInterface \$expiration;/', 'private $expiration;', $content );
	$content = preg_replace( '/private bool \$isHit/', 'private $isHit', $content );

	// Fix constructor property promotion
	$content = preg_replace( '/private string \$key/', '$key', $content );

	// Fix return type 'static' (PHP 8.0+) and 'mixed' (PHP 8.0+)
	$content = preg_replace( '/\): static/', ')', $content );
	$content = preg_replace( '/\): mixed/', ')', $content );

	// Fix parameter type 'mixed' (PHP 8.0+)
	$content = preg_replace( '/\(mixed \$/', '($', $content );

	if ( file_put_contents( $typed_item_path, $content ) ) {
		printf( 'Fixed PHP 8.0 syntax in TypedItem.php' . PHP_EOL );
	}
}
