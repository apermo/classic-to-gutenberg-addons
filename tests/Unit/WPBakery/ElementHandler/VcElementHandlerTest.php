<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\Tests\Unit\WPBakery\ElementHandler;

use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcBtnHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcColumnTextHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcEmptySpaceHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcRawHtmlHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcSeparatorHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcSingleImageHandler;
use Closure;
use PHPUnit\Framework\TestCase;

/**
 * Tests for individual WPBakery element handlers.
 */
class VcElementHandlerTest extends TestCase {

	/**
	 * Passthrough inner converter for testing.
	 *
	 * @var \Closure
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
	 * VcColumnTextHandler extracts inner HTML and delegates to converter.
	 *
	 * @return void
	 */
	public function test_column_text_converts_inner_html(): void {
		$handler = new VcColumnTextHandler();
		$result  = $handler->convert(
			'[vc_column_text]Hello world[/vc_column_text]',
			$this->inner_converter,
		);

		$this->assertStringContainsString( '<!-- wp:paragraph -->', $result );
		$this->assertStringContainsString( 'Hello world', $result );
	}

	/**
	 * VcColumnTextHandler returns empty for empty content.
	 *
	 * @return void
	 */
	public function test_column_text_empty_content(): void {
		$handler = new VcColumnTextHandler();
		$result  = $handler->convert(
			'[vc_column_text][/vc_column_text]',
			$this->inner_converter,
		);

		$this->assertSame( '', $result );
	}

	/**
	 * VcSeparatorHandler produces a core/separator block.
	 *
	 * @return void
	 */
	public function test_separator(): void {
		$handler = new VcSeparatorHandler();

		$this->assertSame( 'vc_separator', $handler->get_tag() );

		$result = $handler->convert( '[vc_separator]', $this->inner_converter );

		$this->assertStringContainsString( '<!-- wp:separator -->', $result );
		$this->assertStringContainsString( '<hr', $result );
		$this->assertStringContainsString( '<!-- /wp:separator -->', $result );
	}

	/**
	 * VcEmptySpaceHandler produces a core/spacer block.
	 *
	 * @return void
	 */
	public function test_empty_space_default(): void {
		$handler = new VcEmptySpaceHandler();

		$this->assertSame( 'vc_empty_space', $handler->get_tag() );

		$result = $handler->convert( '[vc_empty_space]', $this->inner_converter );

		$this->assertStringContainsString( '<!-- wp:spacer', $result );
		$this->assertStringContainsString( '/-->', $result );
	}

	/**
	 * VcEmptySpaceHandler with custom height.
	 *
	 * @return void
	 */
	public function test_empty_space_custom_height(): void {
		$handler = new VcEmptySpaceHandler();
		$result  = $handler->convert(
			'[vc_empty_space height="50px"]',
			$this->inner_converter,
		);

		$this->assertStringContainsString( '"height":"50px"', $result );
	}

	/**
	 * VcRawHtmlHandler decodes base64 content and wraps in html block.
	 *
	 * @return void
	 */
	public function test_raw_html(): void {
		$handler = new VcRawHtmlHandler();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- testing WPBakery's base64 format.
		$encoded = \base64_encode( '<div class="custom">Hello</div>' );

		$this->assertSame( 'vc_raw_html', $handler->get_tag() );

		$result = $handler->convert(
			"[vc_raw_html]{$encoded}[/vc_raw_html]",
			$this->inner_converter,
		);

		$this->assertStringContainsString( '<!-- wp:html -->', $result );
		$this->assertStringContainsString( '<div class="custom">Hello</div>', $result );
		$this->assertStringContainsString( '<!-- /wp:html -->', $result );
	}

	/**
	 * VcRawHtmlHandler passes through non-base64 content.
	 *
	 * @return void
	 */
	public function test_raw_html_non_base64(): void {
		$handler = new VcRawHtmlHandler();
		$result  = $handler->convert(
			'[vc_raw_html]<p>Plain HTML</p>[/vc_raw_html]',
			$this->inner_converter,
		);

		$this->assertStringContainsString( '<p>Plain HTML</p>', $result );
	}

	/**
	 * VcSingleImageHandler produces an image block.
	 *
	 * @return void
	 */
	public function test_single_image(): void {
		$handler = new VcSingleImageHandler();

		$this->assertSame( 'vc_single_image', $handler->get_tag() );

		$result = $handler->convert(
			'[vc_single_image image="42" img_size="full"]',
			$this->inner_converter,
		);

		$this->assertStringContainsString( '<!-- wp:image', $result );
		$this->assertStringContainsString( '"id":42', $result );
		$this->assertStringContainsString( '<!-- /wp:image -->', $result );
	}

	/**
	 * VcSingleImageHandler without image attribute produces empty string.
	 *
	 * @return void
	 */
	public function test_single_image_no_id(): void {
		$handler = new VcSingleImageHandler();
		$result  = $handler->convert(
			'[vc_single_image]',
			$this->inner_converter,
		);

		$this->assertSame( '', $result );
	}

	/**
	 * VcBtnHandler produces a buttons + button block.
	 *
	 * @return void
	 */
	public function test_btn(): void {
		$handler = new VcBtnHandler();

		$this->assertSame( 'vc_btn', $handler->get_tag() );

		$result = $handler->convert(
			'[vc_btn title="Click me" link="url:https%3A%2F%2Fexample.com"]',
			$this->inner_converter,
		);

		$this->assertStringContainsString( '<!-- wp:buttons -->', $result );
		$this->assertStringContainsString( '<!-- wp:button -->', $result );
		$this->assertStringContainsString( 'Click me', $result );
		$this->assertStringContainsString( 'https://example.com', $result );
	}

	/**
	 * VcBtnHandler with plain URL.
	 *
	 * @return void
	 */
	public function test_btn_plain_url(): void {
		$handler = new VcBtnHandler();
		$result  = $handler->convert(
			'[vc_btn title="Go" link="url:https://example.com|title:Example|target:_blank"]',
			$this->inner_converter,
		);

		$this->assertStringContainsString( 'https://example.com', $result );
		$this->assertStringContainsString( 'Go', $result );
	}
}
