<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\Tests\Unit\WPBakery;

use Apermo\WPBakeryToGutenberg\WPBakery\ShortcodeParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ShortcodeParser utility.
 */
class ShortcodeParserTest extends TestCase {

	/**
	 * Parses quoted attributes from a shortcode tag.
	 *
	 * @return void
	 */
	public function test_parse_attrs_quoted(): void {
		$attrs = ShortcodeParser::parse_attrs( '[vc_column width="1/2" el_class="my-class"]' );

		$this->assertSame( '1/2', $attrs['width'] );
		$this->assertSame( 'my-class', $attrs['el_class'] );
	}

	/**
	 * Returns empty array for shortcode without attributes.
	 *
	 * @return void
	 */
	public function test_parse_attrs_empty(): void {
		$attrs = ShortcodeParser::parse_attrs( '[vc_row]' );

		$this->assertSame( [], $attrs );
	}

	/**
	 * Handles CSS attribute with special characters.
	 *
	 * @return void
	 */
	public function test_parse_attrs_css_with_special_chars(): void {
		$attrs = ShortcodeParser::parse_attrs(
			'[vc_row css=".vc_custom_123{background-color: #fff !important;}"]',
		);

		$this->assertSame( '.vc_custom_123{background-color: #fff !important;}', $attrs['css'] );
	}

	/**
	 * Extracts inner content from a paired shortcode.
	 *
	 * @return void
	 */
	public function test_extract_content(): void {
		$content = ShortcodeParser::extract_content(
			'[vc_column_text]Hello <strong>world</strong>[/vc_column_text]',
			'vc_column_text',
		);

		$this->assertSame( 'Hello <strong>world</strong>', $content );
	}

	/**
	 * Returns empty string for self-closing shortcode.
	 *
	 * @return void
	 */
	public function test_extract_content_self_closing(): void {
		$content = ShortcodeParser::extract_content( '[vc_empty_space]', 'vc_empty_space' );

		$this->assertSame( '', $content );
	}

	/**
	 * Extracts inner content preserving nested shortcodes.
	 *
	 * @return void
	 */
	public function test_extract_content_with_nested_shortcodes(): void {
		$shortcode = '[vc_column][vc_column_text]Hello[/vc_column_text][/vc_column]';

		$content = ShortcodeParser::extract_content( $shortcode, 'vc_column' );

		$this->assertSame( '[vc_column_text]Hello[/vc_column_text]', $content );
	}

	/**
	 * Finds all direct child shortcodes of a given tag inside content.
	 *
	 * @return void
	 */
	public function test_find_shortcodes(): void {
		$content = '[vc_column width="1/2"][vc_column_text]A[/vc_column_text][/vc_column]'
			. '[vc_column width="1/2"][vc_column_text]B[/vc_column_text][/vc_column]';

		$columns = ShortcodeParser::find_shortcodes( $content, 'vc_column' );

		$this->assertCount( 2, $columns );
		$this->assertStringContainsString( 'A', $columns[0] );
		$this->assertStringContainsString( 'B', $columns[1] );
	}

	/**
	 * Converts fractional width to percentage string.
	 *
	 * @return void
	 */
	public function test_fraction_to_percent(): void {
		$this->assertSame( '50%', ShortcodeParser::fraction_to_percent( '1/2' ) );
		$this->assertSame( '33.33%', ShortcodeParser::fraction_to_percent( '1/3' ) );
		$this->assertSame( '66.66%', ShortcodeParser::fraction_to_percent( '2/3' ) );
		$this->assertSame( '25%', ShortcodeParser::fraction_to_percent( '1/4' ) );
		$this->assertSame( '75%', ShortcodeParser::fraction_to_percent( '3/4' ) );
		$this->assertSame( '16.66%', ShortcodeParser::fraction_to_percent( '1/6' ) );
		$this->assertSame( '83.33%', ShortcodeParser::fraction_to_percent( '5/6' ) );
	}

	/**
	 * Full width returns 100%.
	 *
	 * @return void
	 */
	public function test_fraction_to_percent_full_width(): void {
		$this->assertSame( '100%', ShortcodeParser::fraction_to_percent( '1/1' ) );
	}

	/**
	 * Invalid fraction returns empty string.
	 *
	 * @return void
	 */
	public function test_fraction_to_percent_invalid(): void {
		$this->assertSame( '', ShortcodeParser::fraction_to_percent( 'invalid' ) );
	}
}
