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
	'analytics',
	'oauth2',
	'plus',
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

$dir = dirname( __DIR__ ) . '/vendor/google/apiclient-services/src/Google/Service';

if ( ! is_dir( $dir ) ) {
	throw new Exception( sprintf( 'Composer directory %s does not exist.', $dir ), 404 );
}

$deleted = 0;

foreach ( scandir( $dir ) as $file ) {
	if ( in_array( $file, [ '.', '..' ] ) ) {
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
