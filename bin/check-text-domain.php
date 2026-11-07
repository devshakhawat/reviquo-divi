<?php // phpcs:ignore
/**
 * Verify every translation call in a directory uses the expected text domain.
 *
 * Usage: php bin/check-text-domain.php <directory> <text-domain>
 *
 * Unlike a regular expression, this walks the PHP token stream, so it knows
 * which argument holds the domain for each translation function and ignores
 * matching text inside comments and unrelated strings.
 *
 * @package Sktpr_Customer_Review
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$directory = $argv[1] ?? '';
$expected  = $argv[2] ?? '';

if ( '' === $directory || '' === $expected || ! is_dir( $directory ) ) {
	fwrite( STDERR, "Usage: php bin/check-text-domain.php <directory> <text-domain>\n" );
	exit( 2 );
}

// Translation function => zero-based index of its text-domain argument.
$functions = array(
	'__'             => 1,
	'_e'             => 1,
	'esc_html__'     => 1,
	'esc_html_e'     => 1,
	'esc_attr__'     => 1,
	'esc_attr_e'     => 1,
	'_x'             => 2,
	'_ex'            => 2,
	'esc_html_x'     => 2,
	'esc_attr_x'     => 2,
	'_n'             => 3,
	'_n_noop'        => 2,
	'_nx'            => 4,
	'_nx_noop'       => 3,
);

$problems = array();

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ) );

foreach ( $files as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	$tokens = token_get_all( file_get_contents( $file->getPathname() ) );
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		$token = $tokens[ $i ];

		if ( ! is_array( $token ) || T_STRING !== $token[0] || ! isset( $functions[ $token[1] ] ) ) {
			continue;
		}

		// Skip method calls and declarations: $obj->__(), function __(), Foo::__().
		$previous = null;
		for ( $back = $i - 1; $back >= 0; $back-- ) {
			if ( is_array( $tokens[ $back ] ) && T_WHITESPACE === $tokens[ $back ][0] ) {
				continue;
			}
			$previous = $tokens[ $back ];
			break;
		}
		if ( is_array( $previous ) && in_array( $previous[0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW ), true ) ) {
			continue;
		}

		// The next meaningful token must open the argument list.
		$open = null;
		for ( $ahead = $i + 1; $ahead < $count; $ahead++ ) {
			if ( is_array( $tokens[ $ahead ] ) && T_WHITESPACE === $tokens[ $ahead ][0] ) {
				continue;
			}
			$open = $ahead;
			break;
		}
		if ( null === $open || '(' !== $tokens[ $open ] ) {
			continue;
		}

		// Collect the top-level arguments of this call.
		$depth     = 0;
		$argument  = 0;
		$arguments = array();
		$line      = $token[2];

		for ( $scan = $open; $scan < $count; $scan++ ) {
			$current = $tokens[ $scan ];

			if ( '(' === $current || '[' === $current ) {
				$depth++;
				continue;
			}

			if ( ')' === $current || ']' === $current ) {
				$depth--;
				if ( 0 === $depth ) {
					break;
				}
				continue;
			}

			if ( ',' === $current && 1 === $depth ) {
				$argument++;
				continue;
			}

			if ( 1 === $depth && is_array( $current ) && T_CONSTANT_ENCAPSED_STRING === $current[0] && ! isset( $arguments[ $argument ] ) ) {
				$arguments[ $argument ] = trim( $current[1], "'\"" );
			}
		}

		$position = $functions[ $token[1] ];
		$domain   = $arguments[ $position ] ?? null;

		$relative = ltrim( str_replace( realpath( $directory ), '', realpath( $file->getPathname() ) ), '/' );

		if ( null === $domain ) {
			$problems[] = sprintf( '%s:%d %s() has no literal text domain argument.', $relative, $line, $token[1] );
			continue;
		}

		if ( $expected !== $domain ) {
			$problems[] = sprintf( "%s:%d %s() uses text domain '%s'.", $relative, $line, $token[1], $domain );
		}
	}
}

if ( $problems ) {
	sort( $problems );
	fwrite( STDERR, implode( "\n", $problems ) . "\n" );
	exit( 1 );
}

exit( 0 );
