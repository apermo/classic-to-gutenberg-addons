<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\Tests\Unit\WPBakery;

use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcColumnTextHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcRowInnerHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\RowConverter;
use Closure;
use PHPUnit\Framework\TestCase;

/**
 * Tests for RowConverter.
 */
class RowConverterTest extends TestCase {

	/**
	 * Inner content converter stub that wraps text in paragraph blocks.
	 *
	 * @var Closure
	 */
	private Closure $inner_converter;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->inner_converter = static function ( string $content ): string {
			$content = \trim( $content );
			if ( $content === '' ) {
				return '';
			}

			return "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->";
		};
	}

	/**
	 * Create a RowConverter with standard handlers for testing.
	 *
	 * @return RowConverter
	 */
	private function create_converter(): RowConverter {
		$converter = new RowConverter(
			$this->inner_converter,
			[
				new VcColumnTextHandler(),
			],
		);

		$converter->add_handler(
			new VcRowInnerHandler(
				static fn( string $shortcode ): string => $converter->convert( $shortcode ),
			),
		);

		return $converter;
	}

	/**
	 * Single full-width column unwraps to inner content blocks.
	 *
	 * @return void
	 */
	public function test_single_full_width_column_unwraps(): void {
		$shortcode = '[vc_row][vc_column][vc_column_text]Hello world[/vc_column_text][/vc_column][/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertStringContainsString( '<!-- wp:paragraph -->', $result );
		$this->assertStringContainsString( 'Hello world', $result );
		$this->assertStringNotContainsString( '<!-- wp:columns -->', $result );
	}

	/**
	 * Single column with explicit 1/1 width unwraps.
	 *
	 * @return void
	 */
	public function test_single_column_explicit_full_width_unwraps(): void {
		$shortcode = '[vc_row][vc_column width="1/1"][vc_column_text]Content[/vc_column_text][/vc_column][/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertStringNotContainsString( '<!-- wp:columns -->', $result );
		$this->assertStringContainsString( 'Content', $result );
	}

	/**
	 * Two columns produce a columns block.
	 *
	 * @return void
	 */
	public function test_two_columns(): void {
		$shortcode = '[vc_row]'
			. '[vc_column width="1/2"][vc_column_text]Left[/vc_column_text][/vc_column]'
			. '[vc_column width="1/2"][vc_column_text]Right[/vc_column_text][/vc_column]'
			. '[/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertStringContainsString( '<!-- wp:columns -->', $result );
		$this->assertStringContainsString( '<!-- /wp:columns -->', $result );
		$this->assertStringContainsString( '"width":"50%"', $result );
		$this->assertStringContainsString( 'Left', $result );
		$this->assertStringContainsString( 'Right', $result );
	}

	/**
	 * Three equal columns.
	 *
	 * @return void
	 */
	public function test_three_columns(): void {
		$shortcode = '[vc_row]'
			. '[vc_column width="1/3"][vc_column_text]A[/vc_column_text][/vc_column]'
			. '[vc_column width="1/3"][vc_column_text]B[/vc_column_text][/vc_column]'
			. '[vc_column width="1/3"][vc_column_text]C[/vc_column_text][/vc_column]'
			. '[/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertStringContainsString( '<!-- wp:columns -->', $result );
		$this->assertSame( 3, \substr_count( $result, '<!-- wp:column' ) - \substr_count( $result, '<!-- /wp:column' ) + 3 );
		$this->assertStringContainsString( '"width":"33.33%"', $result );
	}

	/**
	 * Asymmetric column widths (2/3 + 1/3).
	 *
	 * @return void
	 */
	public function test_asymmetric_columns(): void {
		$shortcode = '[vc_row]'
			. '[vc_column width="2/3"][vc_column_text]Wide[/vc_column_text][/vc_column]'
			. '[vc_column width="1/3"][vc_column_text]Narrow[/vc_column_text][/vc_column]'
			. '[/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertStringContainsString( '"width":"66.66%"', $result );
		$this->assertStringContainsString( '"width":"33.33%"', $result );
	}

	/**
	 * Nested row (vc_row_inner) is handled.
	 *
	 * @return void
	 */
	public function test_nested_row_inner(): void {
		$shortcode = '[vc_row][vc_column][vc_row_inner]'
			. '[vc_column_inner width="1/2"][vc_column_text]A[/vc_column_text][/vc_column_inner]'
			. '[vc_column_inner width="1/2"][vc_column_text]B[/vc_column_text][/vc_column_inner]'
			. '[/vc_row_inner][/vc_column][/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertStringContainsString( '<!-- wp:columns -->', $result );
		$this->assertStringContainsString( 'A', $result );
		$this->assertStringContainsString( 'B', $result );
	}

	/**
	 * Empty row produces empty string.
	 *
	 * @return void
	 */
	public function test_empty_row(): void {
		$shortcode = '[vc_row][vc_column][/vc_column][/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertSame( '', $result );
	}

	/**
	 * Row with attributes preserves conversion.
	 *
	 * @return void
	 */
	public function test_row_with_css_attribute(): void {
		$shortcode = '[vc_row css=".vc_custom_123{background-color: #fff !important;}"]'
			. '[vc_column][vc_column_text]Styled[/vc_column_text][/vc_column]'
			. '[/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertStringContainsString( 'Styled', $result );
	}

	/**
	 * Column without width defaults to equal distribution (no explicit width attribute).
	 *
	 * @return void
	 */
	public function test_columns_without_explicit_width(): void {
		$shortcode = '[vc_row]'
			. '[vc_column][vc_column_text]A[/vc_column_text][/vc_column]'
			. '[vc_column][vc_column_text]B[/vc_column_text][/vc_column]'
			. '[/vc_row]';

		$converter = $this->create_converter();
		$result    = $converter->convert( $shortcode );

		$this->assertStringContainsString( '<!-- wp:columns -->', $result );
		// Without explicit widths, no width attribute in block JSON.
		$this->assertStringNotContainsString( '"width"', $result );
	}
}
