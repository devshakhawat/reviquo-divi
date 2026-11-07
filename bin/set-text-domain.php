<?php // phpcs:ignore
/**
 * Rewrite the text domain of a staged plugin package.
 *
 * Usage: php bin/set-text-domain.php <directory> <from-domain> <to-domain>
 *
 * The Divi and WooCommerce Marketplace editions ship under their own product slug,
 * so their translation calls, plugin header, block.json and compiled editor bundle must
 * carry that slug as the text domain. The source tree keeps the WordPress.org
 * domain; this runs against the staged copy only.
 *
 * PHP files are rewritten by walking the token stream (as check-text-domain.php
 * does), so only the domain argument of a translation call is touched. Other
 * string literals that happen to equal the old domain - the admin page hook
 * names, the theme template folder - are left alone.
 *
 * @package Sktpr_Customer_Review
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$directory = $argv[1] ?? '';
$from      = $argv[2] ?? '';
$to        = $argv[3] ?? '';

if ( '' === $directory || '' === $from || '' === $to || ! is_dir( $directory ) ) {
	fwrite( STDERR, "Usage: php bin/set-text-domain.php <directory> <from-domain> <to-domain>\n" );
	exit( 2 );
}

// Translation function => zero-based index of its text-domain argument.
$functions = array(
	'__'                         => 1,
	'_e'                         => 1,
	'esc_html__'                 => 1,
	'esc_html_e'                 => 1,
	'esc_attr__'                 => 1,
	'esc_attr_e'                 => 1,
	'_x'                         => 2,
	'_ex'                        => 2,
	'esc_html_x'                 => 2,
	'esc_attr_x'                 => 2,
	'_n'                         => 3,
	'_n_noop'                    => 2,
	'_nx'                        => 4,
	'_nx_noop'                   => 3,
	'wp_set_script_translations' => 1,
	'load_plugin_textdomain'     => 0,
	'load_textdomain'            => 0,
);

$counts = array(
	'php'    => 0,
	'header' => 0,
	'json'   => 0,
	'js'     => 0,
);

/**
 * Rewrite translation-call domain arguments in one PHP source string.
 *
 * @param string $source    PHP source.
 * @param array  $functions Function => domain argument index map.
 * @param string $from      Domain to replace.
 * @param string $to        Replacement domain.
 * @param int    $replaced  Incremented per rewritten argument.
 * @return string
 */
function sktpr_rewrite_php_domains( $source, array $functions, $from, $to, &$replaced ) {
	$tokens = token_get_all( $source );
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

		// Locate the first string literal of each top-level argument.
		$depth     = 0;
		$argument  = 0;
		$positions = array();

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

			if ( 1 === $depth && is_array( $current ) && T_CONSTANT_ENCAPSED_STRING === $current[0] && ! isset( $positions[ $argument ] ) ) {
				$positions[ $argument ] = $scan;
			}
		}

		$index = $functions[ $token[1] ];
		if ( ! isset( $positions[ $index ] ) ) {
			continue;
		}

		$literal = $tokens[ $positions[ $index ] ][1];
		$quote   = $literal[0];

		if ( trim( $literal, "'\"" ) === $from ) {
			$tokens[ $positions[ $index ] ][1] = $quote . $to . $quote;
			$replaced++;
		}
	}

	$output = '';
	foreach ( $tokens as $token ) {
		$output .= is_array( $token ) ? $token[1] : $token;
	}

	return $output;
}

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ) );

foreach ( $files as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}

	$path      = $file->getPathname();
	$extension = strtolower( $file->getExtension() );
	$original  = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$updated   = $original;

	switch ( $extension ) {
		case 'php':
			$updated = sktpr_rewrite_php_domains( $updated, $functions, $from, $to, $counts['php'] );

			// Plugin header field.
			$updated = preg_replace(
				'/^(\s*\*\s*Text Domain:\s*)' . preg_quote( $from, '/' ) . '\s*$/m',
				'${1}' . $to,
				$updated,
				-1,
				$header_hits
			);
			$counts['header'] += (int) $header_hits;
			break;

		case 'json':
			// block.json / module.json "textdomain" field.
			$updated = preg_replace(
				'/("textdomain"\s*:\s*")' . preg_quote( $from, '/' ) . '(")/',
				'${1}' . $to . '${2}',
				$updated,
				-1,
				$json_hits
			);
			$counts['json'] += (int) $json_hits;
			break;

		case 'js':
			// Compiled bundles: the domain is always the last, quoted argument
			// of a wp.i18n call, e.g. t("Try again","product-reviews").
			$updated = preg_replace(
				'/(,\s*)(["\'])' . preg_quote( $from, '/' ) . '\2(\s*\))/',
				'${1}${2}' . $to . '${2}${3}',
				$updated,
				-1,
				$js_hits
			);
			$counts['js'] += (int) $js_hits;
			break;

		default:
			continue 2;
	}

	if ( $updated !== $original && false === file_put_contents( $path, $updated ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		fwrite( STDERR, "set-text-domain: could not write '{$path}'.\n" );
		exit( 1 );
	}
}

if ( 0 === $counts['php'] ) {
	fwrite( STDERR, "set-text-domain: no translation call used the domain '{$from}'; nothing was rewritten.\n" );
	exit( 1 );
}

printf(
	"Text domain '%s' -> '%s': %d PHP calls, %d header field(s), %d JSON field(s), %d JS calls.\n",
	$from,
	$to,
	$counts['php'],
	$counts['header'],
	$counts['json'],
	$counts['js']
);
