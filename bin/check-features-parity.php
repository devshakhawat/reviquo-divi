<?php // phpcs:ignore
/**
 * Verify that the marketplace feature policy matches the source policy's API.
 *
 * build-marketplace.sh swaps includes/features.php for marketplace/features.php.
 * The two files are deliberately different policies - one gates Pro features
 * behind a license, the other unlocks everything - but callers across the
 * plugin use the same class. If the source class gains a constant or a public
 * method the marketplace class lacks, the marketplace package fatals at runtime
 * on a site the author cannot reproduce locally.
 *
 * This compares the public surface of both classes: constants (names and
 * values) and public methods (names and required/total parameter counts). Only
 * the bodies are allowed to differ.
 *
 * Usage: php bin/check-features-parity.php <source-features.php> <marketplace-features.php>
 *
 * @package Sktpr_Customer_Review
 */

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * Reflect one Features file and print its public surface as JSON.
 *
 * Each file declares SKTPREVIEW\Features, so the two cannot be loaded into one
 * process. The parent process re-invokes this script with --dump per file.
 *
 * @param string $file Path to a features.php file.
 * @return void
 */
function sktpr_dump_features_surface( $file ) {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', __DIR__ . '/' );
	}

	require_once $file;

	if ( ! class_exists( '\\SKTPREVIEW\\Features' ) ) {
		fwrite( STDERR, "check-features-parity: {$file} does not declare SKTPREVIEW\\Features.\n" );
		exit( 1 );
	}

	$reflection = new ReflectionClass( '\\SKTPREVIEW\\Features' );
	$surface    = array(
		'constants' => array(),
		'methods'   => array(),
	);

	foreach ( $reflection->getReflectionConstants() as $constant ) {
		if ( ! $constant->isPublic() ) {
			continue;
		}
		$surface['constants'][ $constant->getName() ] = $constant->getValue();
	}

	foreach ( $reflection->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
		$surface['methods'][ $method->getName() ] = array(
			'static'   => $method->isStatic(),
			'required' => $method->getNumberOfRequiredParameters(),
			'total'    => $method->getNumberOfParameters(),
		);
	}

	ksort( $surface['constants'] );
	ksort( $surface['methods'] );

	echo wp_json_encode_fallback( $surface );
}

/**
 * json_encode wrapper, because WordPress is not loaded here.
 *
 * @param mixed $data Data to encode.
 * @return string
 */
function wp_json_encode_fallback( $data ) {
	return (string) json_encode( $data ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/**
 * Abort with a message on stderr.
 *
 * @param string $message Reason for aborting.
 * @return void
 */
function sktpr_parity_fail( $message ) {
	fwrite( STDERR, "check-features-parity: {$message}\n" );
	exit( 1 );
}

if ( ( $argv[1] ?? '' ) === '--dump' ) {
	sktpr_dump_features_surface( $argv[2] );
	exit( 0 );
}

$source_file      = $argv[1] ?? '';
$marketplace_file = $argv[2] ?? '';

if ( '' === $source_file || '' === $marketplace_file ) {
	sktpr_parity_fail( 'usage: check-features-parity.php <source-features.php> <marketplace-features.php>' );
}

/**
 * Run this script's --dump mode for one file and decode the result.
 *
 * @param string $file Path to a features.php file.
 * @return array
 */
function sktpr_read_surface( $file ) {
	if ( ! is_readable( $file ) ) {
		sktpr_parity_fail( "cannot read '{$file}'." );
	}

	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --dump ' . escapeshellarg( $file );
	$output  = shell_exec( $command ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	$decoded = json_decode( (string) $output, true );

	if ( ! is_array( $decoded ) ) {
		sktpr_parity_fail( "could not reflect '{$file}'." );
	}

	return $decoded;
}

$source      = sktpr_read_surface( $source_file );
$marketplace = sktpr_read_surface( $marketplace_file );
$problems    = array();

foreach ( $source['constants'] as $name => $value ) {
	if ( ! array_key_exists( $name, $marketplace['constants'] ) ) {
		$problems[] = "marketplace/features.php is missing constant {$name}.";
		continue;
	}

	if ( $marketplace['constants'][ $name ] !== $value ) {
		$problems[] = sprintf(
			'constant %s differs (source %s, marketplace %s).',
			$name,
			var_export( $value, true ), // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			var_export( $marketplace['constants'][ $name ], true ) // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		);
	}
}

foreach ( $source['methods'] as $name => $signature ) {
	if ( ! array_key_exists( $name, $marketplace['methods'] ) ) {
		$problems[] = "marketplace/features.php is missing public method {$name}().";
		continue;
	}

	$other = $marketplace['methods'][ $name ];

	if ( $other['static'] !== $signature['static'] ) {
		$problems[] = "method {$name}() differs in staticness.";
	}

	// A marketplace override may add optional parameters but must accept every
	// call the source class accepts.
	if ( $other['required'] > $signature['required'] || $other['total'] < $signature['total'] ) {
		$problems[] = sprintf(
			'method %s() has an incompatible signature (source %d-%d parameters, marketplace %d-%d).',
			$name,
			$signature['required'],
			$signature['total'],
			$other['required'],
			$other['total']
		);
	}
}

if ( $problems ) {
	foreach ( $problems as $problem ) {
		fwrite( STDERR, "check-features-parity: {$problem}\n" );
	}
	exit( 1 );
}

echo "Feature policy parity OK (" . count( $source['methods'] ) . " methods, " . count( $source['constants'] ) . " constants).\n";
