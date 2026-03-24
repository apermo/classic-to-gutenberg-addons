<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\Tests\Integration;

use Apermo\WPBakeryToGutenberg\Plugin;
use WP_UnitTestCase;

/**
 * Verifies the WordPress integration test environment is functional.
 */
class ExampleIntegrationTest extends WP_UnitTestCase {

	/**
	 * WordPress is loaded and available.
	 *
	 * @return void
	 */
	public function test_wordpress_is_loaded(): void {
		$this->assertTrue( \function_exists( 'do_action' ) );
	}

	/**
	 * The plugin is active.
	 *
	 * @return void
	 */
	public function test_project_is_active(): void {
		$this->assertNotEmpty(
			Plugin::VERSION,
			'Plugin version should be set.',
		);
	}
}
