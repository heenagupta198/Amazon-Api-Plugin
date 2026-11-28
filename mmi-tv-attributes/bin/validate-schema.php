<?php
/**
 * CLI check: unique slugs, slug length, unique labels.
 *
 * php mmi-tv-attributes/bin/validate-schema.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$root = dirname( __DIR__ );
require $root . '/includes/class-schema.php';

$attributes = MMI_TV_Schema::attributes();
$slugs      = array();
$labels     = array();
$errors     = array();

foreach ( $attributes as $attr ) {
	$slug  = $attr['slug'];
	$label = $attr['label'];

	if ( 0 !== strpos( $slug, 'tv-' ) ) {
		$errors[] = "Slug must start with tv-: {$slug}";
	}
	if ( strlen( $slug ) > 28 ) {
		$errors[] = "Slug too long ({$slug}) length " . strlen( $slug );
	}
	if ( isset( $slugs[ $slug ] ) ) {
		$errors[] = "Duplicate slug: {$slug}";
	}
	if ( isset( $labels[ $label ] ) ) {
		$errors[] = "Duplicate label: {$label}";
	}
	$slugs[ $slug ]   = true;
	$labels[ $label ] = true;

	foreach ( $attr['terms'] as $term ) {
		if ( '' === trim( $term ) ) {
			$errors[] = "Empty term on {$slug}";
		}
	}
}

echo 'Attributes: ' . count( $attributes ) . PHP_EOL;
echo 'Groups: ' . implode( ', ', array_keys( MMI_TV_Schema::groups() ) ) . PHP_EOL;

if ( $errors ) {
	fwrite( STDERR, implode( PHP_EOL, $errors ) . PHP_EOL );
	exit( 1 );
}

echo "Schema OK\n";
exit( 0 );
