<?php // phpcs:ignore
/**
 * Propagate the plugin version from the source plugin header to every file that repeats it.
 *
 * The version currently lives in five places, and they had already drifted: the
 * plugin header said 3.0.6 while the SKTPR_VERSION constant, both readmes and
 * package.json said 3.0.5. build-marketplace.sh rejects a mismatched package,
 * so this is the tool that fixes it rather than only reporting it.
 *
 * The plugin header in product-reviews.php is the single source of truth. Bump
 * it, run this, and everything else follows.
 *
 * Usage:
 *   php bin/sync-version.php            Rewrite every target to match the header.
 *   php bin/sync-version.php --check    Report drift and exit non-zero. For CI.
 *   php bin/sync-version.php 3.1.0      Set the header to 3.1.0, then sync.
 *
 * @package Sktpr_Customer_Review
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions

$root = dirname( __DIR__ );

/**
 * Abort with a message on stderr.
 *
 * @param string $message Reason for aborting.
 * @return void
 */
function sktpr_version_fail( $message ) {
	fwrite( STDERR, "sync-version: {$message}\n" );
	exit( 1 );
}

/**
 * Read a file or abort.
 *
 * @param string $path File path.
 * @return string
 */
function sktpr_version_read( $path ) {
	if ( ! is_readable( $path ) ) {
		sktpr_version_fail( "cannot read '{$path}'." );
	}

	return (string) file_get_contents( $path );
}

$check_only  = in_array( '--check', array_slice( $argv, 1 ), true );
$explicit    = null;
$plugin_file = $root . '/product-reviews.php';

foreach ( array_slice( $argv, 1 ) as $argument ) {
	if ( '--check' === $argument ) {
		continue;
	}

	if ( ! preg_match( '/^\d+\.\d+(\.\d+)*$/', $argument ) ) {
		sktpr_version_fail( "'{$argument}' is not a version number." );
	}

	$explicit = $argument;
}

if ( null !== $explicit && $check_only ) {
	sktpr_version_fail( '--check cannot be combined with an explicit version.' );
}

$plugin_source = sktpr_version_read( $plugin_file );

if ( null !== $explicit ) {
	$plugin_source = preg_replace_callback(
		'/^(\s*\*\s*Version:\s*).*$/m',
		static function ( $matches ) use ( $explicit ) {
			return $matches[1] . $explicit;
		},
		$plugin_source,
		1
	);
}

if ( ! preg_match( '/^\s*\*\s*Version:\s*([0-9.]+)\s*$/m', $plugin_source, $matches ) ) {
	sktpr_version_fail( 'the plugin header has no Version field.' );
}

$version = $matches[1];

/*
 * Each target is a file plus the patterns that carry the version in it. A
 * pattern's first capture group is the prefix that is kept; the version is
 * written after it.
 */
$targets = array(
	'product-reviews.php'        => array(
		'/^(\s*\*\s*Version:\s*)[0-9.]+\s*$/m',
		"/(define\( 'SKTPR_VERSION', ')[0-9.]+(?=' \))/",
	),
	'marketplace/readme.txt'     => array( '/^(Stable tag:\s*)[0-9.]+\s*$/m' ),
	'package.json'               => array( '/("version":\s*")[0-9.]+(?=")/' ),
);

$drifted = array();
$written = array();

foreach ( $targets as $relative => $patterns ) {
	$path     = $root . '/' . $relative;
	$original = ( 'product-reviews.php' === $relative ) ? $plugin_source : sktpr_version_read( $path );
	$updated  = $original;

	foreach ( $patterns as $pattern ) {
		$matched = preg_replace_callback(
			$pattern,
			static function ( $found ) use ( $version ) {
				return $found[1] . $version;
			},
			$updated,
			-1,
			$count
		);

		if ( null === $matched || 0 === $count ) {
			sktpr_version_fail( "no version to update in '{$relative}' (pattern {$pattern})." );
		}

		$updated = $matched;
	}

	if ( $updated === $original && ! ( 'product-reviews.php' === $relative && $plugin_source !== sktpr_version_read( $path ) ) ) {
		continue;
	}

	$drifted[] = $relative;

	if ( $check_only ) {
		continue;
	}

	if ( false === file_put_contents( $path, $updated ) ) {
		sktpr_version_fail( "could not write '{$relative}'." );
	}

	$written[] = $relative;
}

if ( $check_only ) {
	if ( $drifted ) {
		fwrite( STDERR, "sync-version: version {$version} is out of sync in: " . implode( ', ', $drifted ) . "\n" );
		fwrite( STDERR, "sync-version: run 'npm run version:sync' to fix.\n" );
		exit( 1 );
	}

	echo "Version {$version} is consistent across all files.\n";
	exit( 0 );
}

if ( $written ) {
	echo "Version {$version} written to: " . implode( ', ', $written ) . "\n";
} else {
	echo "Version {$version} was already consistent.\n";
}

/*
 * A release with no changelog entry is usually an unfinished bump rather than a
 * deliberate one, so say so - but do not fail, because the entry is normally
 * written after the version is set.
 */
foreach ( array( 'marketplace/readme.txt' ) as $readme ) {
	$contents = sktpr_version_read( $root . '/' . $readme );

	if ( ! preg_match( '/^=\s*' . preg_quote( $version, '/' ) . '\s/m', $contents ) ) {
		fwrite( STDERR, "sync-version: warning - {$readme} has no '= {$version} =' changelog entry.\n" );
	}
}

