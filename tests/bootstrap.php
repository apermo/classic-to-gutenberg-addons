<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( $wp_tests_dir === false ) {
	$vendor_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
	if ( is_dir( $vendor_dir ) ) {
		$wp_tests_dir = $vendor_dir;
	}
}

if ( $wp_tests_dir !== false && is_dir( $wp_tests_dir ) ) {
	if ( getenv( 'WP_MULTISITE' ) ) {
		define( 'WP_TESTS_MULTISITE', true );
	}

	require_once $wp_tests_dir . '/includes/functions.php';

	tests_add_filter( 'muplugins_loaded', 'classic_to_gutenberg_addons_tests_load_project' );

	require_once $wp_tests_dir . '/includes/bootstrap.php';
}

/**
 * Load the plugin under test.
 *
 * @return void
 */
function classic_to_gutenberg_addons_tests_load_project(): void {
	require dirname( __DIR__ ) . '/plugin.php';
}
