<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\Tests\Unit\WPBakery;

use Apermo\WPBakeryToGutenberg\WPBakery\Converter;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcColumnTextHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\RowConverter;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Converter orchestrator.
 */
class ConverterTest extends TestCase {

	/**
	 * Simple inner converter stub for testing.
	 *
	 * @var RowConverter
	 */
	private RowConverter $row_converter;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$inner_converter = static function ( string $content ): string {
			$content = \trim( $content );
			if ( $content === '' ) {
				return '';
			}

			return "<!-- wp:paragraph -->\n<p>{$content}</p>\n<!-- /wp:paragraph -->";
		};

		$this->row_converter = new RowConverter(
			$inner_converter,
			[
				new VcColumnTextHandler(),
			],
		);
	}

	/**
	 * Pre-convert replaces WPBakery rows with placeholders.
	 *
	 * @return void
	 */
	public function test_pre_convert_replaces_rows_with_placeholders(): void {
		$converter = new Converter( $this->row_converter );
		$content   = "Some text.\n\n[vc_row][vc_column][vc_column_text]Hello[/vc_column_text][/vc_column][/vc_row]\n\nMore text.";

		$result = $converter->pre_convert( $content );

		$this->assertStringNotContainsString( '[vc_row]', $result );
		$this->assertStringContainsString( '<!-- vc:placeholder:', $result );
		$this->assertStringContainsString( 'Some text.', $result );
		$this->assertStringContainsString( 'More text.', $result );
	}

	/**
	 * Post-convert swaps placeholders with converted block markup.
	 *
	 * @return void
	 */
	public function test_post_convert_swaps_placeholders(): void {
		$converter = new Converter( $this->row_converter );

		// Simulate pre_convert to populate stored blocks.
		$converter->pre_convert(
			'[vc_row][vc_column][vc_column_text]Hello[/vc_column_text][/vc_column][/vc_row]',
		);

		// Simulate what the pipeline would produce: placeholder wrapped in html block.
		$pipeline_output = "<!-- wp:html -->\n<!-- vc:placeholder:0 -->\n<!-- /wp:html -->";

		$result = $converter->post_convert( $pipeline_output );

		$this->assertStringNotContainsString( 'vc:placeholder', $result );
		$this->assertStringContainsString( '<!-- wp:paragraph -->', $result );
		$this->assertStringContainsString( 'Hello', $result );
	}

	/**
	 * Post-convert swaps placeholders wrapped in paragraph blocks (wpautop behavior).
	 *
	 * @return void
	 */
	public function test_post_convert_swaps_paragraph_wrapped_placeholders(): void {
		$converter = new Converter( $this->row_converter );

		$converter->pre_convert(
			'[vc_row][vc_column][vc_column_text]Hello[/vc_column_text][/vc_column][/vc_row]',
		);

		// Simulate wpautop wrapping the comment in <p>, then ParagraphConverter wrapping in block.
		$pipeline_output = "<!-- wp:paragraph -->\n<p><!-- vc:placeholder:0 --></p>\n<!-- /wp:paragraph -->";

		$result = $converter->post_convert( $pipeline_output );

		$this->assertStringNotContainsString( 'vc:placeholder', $result );
		$this->assertStringContainsString( 'Hello', $result );
		// The outer paragraph wrapper from the pipeline must be gone.
		$this->assertStringNotContainsString( '<p><!-- wp:', $result );
	}

	/**
	 * Multiple rows get separate placeholders.
	 *
	 * @return void
	 */
	public function test_multiple_rows(): void {
		$converter = new Converter( $this->row_converter );

		$content = '[vc_row][vc_column][vc_column_text]First[/vc_column_text][/vc_column][/vc_row]'
			. "\n\n"
			. '[vc_row][vc_column][vc_column_text]Second[/vc_column_text][/vc_column][/vc_row]';

		$result = $converter->pre_convert( $content );

		$this->assertStringContainsString( '<!-- vc:placeholder:0 -->', $result );
		$this->assertStringContainsString( '<!-- vc:placeholder:1 -->', $result );
	}

	/**
	 * Inner rows (vc_row_inner) are handled inside vc_row, not at top level.
	 *
	 * @return void
	 */
	public function test_inner_rows_not_matched_at_top_level(): void {
		$converter = new Converter( $this->row_converter );

		// vc_row_inner should only appear inside vc_row, not standalone.
		// But if it does, it should NOT be matched by the top-level regex.
		$content = 'Text before [vc_row_inner][vc_column_inner][/vc_column_inner][/vc_row_inner] text after';

		$result = $converter->pre_convert( $content );

		// Inner rows at top level are left as-is (they're malformed if standalone).
		$this->assertStringNotContainsString( 'vc:placeholder', $result );
	}

	/**
	 * Content without WPBakery shortcodes passes through unchanged.
	 *
	 * @return void
	 */
	public function test_no_wpbakery_content_unchanged(): void {
		$converter = new Converter( $this->row_converter );
		$content   = '<p>Regular content without WPBakery.</p>';

		$result = $converter->pre_convert( $content );

		$this->assertSame( $content, $result );
	}

	/**
	 * State is reset between conversions.
	 *
	 * @return void
	 */
	public function test_state_resets_between_conversions(): void {
		$converter = new Converter( $this->row_converter );

		// First conversion.
		$converter->pre_convert(
			'[vc_row][vc_column][vc_column_text]A[/vc_column_text][/vc_column][/vc_row]',
		);
		$converter->post_convert(
			"<!-- wp:html -->\n<!-- vc:placeholder:0 -->\n<!-- /wp:html -->",
		);

		// Second conversion should start fresh.
		$result = $converter->pre_convert(
			'[vc_row][vc_column][vc_column_text]B[/vc_column_text][/vc_column][/vc_row]',
		);

		$this->assertStringContainsString( '<!-- vc:placeholder:0 -->', $result );
	}

	/**
	 * Nested pre_convert calls (from inner ContentConverter) are skipped.
	 *
	 * @return void
	 */
	public function test_nested_pre_convert_is_noop(): void {
		$converter = new Converter( $this->row_converter );

		// Simulate outer pre_convert.
		$converter->pre_convert(
			'[vc_row][vc_column][vc_column_text]Outer[/vc_column_text][/vc_column][/vc_row]',
		);

		// Simulate nested pre_convert (from inner converter firing the filter).
		$nested_result = $converter->pre_convert( 'Inner content without rows' );

		// Nested call should pass through unchanged.
		$this->assertSame( 'Inner content without rows', $nested_result );

		// Simulate nested post_convert.
		$nested_post = $converter->post_convert( 'inner result' );
		$this->assertSame( 'inner result', $nested_post );

		// Outer post_convert should still work.
		$result = $converter->post_convert(
			"<!-- wp:html -->\n<!-- vc:placeholder:0 -->\n<!-- /wp:html -->",
		);

		$this->assertStringContainsString( 'Outer', $result );
		$this->assertStringNotContainsString( 'vc:placeholder', $result );
	}

	/**
	 * Orphan post_convert (without preceding pre_convert) is a no-op.
	 *
	 * @return void
	 */
	public function test_orphan_post_convert_is_noop(): void {
		$converter = new Converter( $this->row_converter );

		$result = $converter->post_convert( 'some content' );

		$this->assertSame( 'some content', $result );
	}

	/**
	 * Depth never goes negative even with mismatched calls.
	 *
	 * @return void
	 */
	public function test_depth_stays_non_negative(): void {
		$converter = new Converter( $this->row_converter );

		// Two orphan post_convert calls.
		$converter->post_convert( 'a' );
		$converter->post_convert( 'b' );

		// pre_convert should still work normally after mismatched calls.
		$result = $converter->pre_convert(
			'[vc_row][vc_column][vc_column_text]Works[/vc_column_text][/vc_column][/vc_row]',
		);

		$this->assertStringContainsString( '<!-- vc:placeholder:0 -->', $result );
	}
}
