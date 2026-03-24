<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenbergAddons\Tests\Integration\WPBakery;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Converter\BlockConverterFactory;
use Apermo\ClassicToGutenberg\Converter\HeadingConverter;
use Apermo\ClassicToGutenberg\Converter\HtmlBlockConverter;
use Apermo\ClassicToGutenberg\Converter\ImageConverter;
use Apermo\ClassicToGutenberg\Converter\ListConverter;
use Apermo\ClassicToGutenberg\Converter\MoreConverter;
use Apermo\ClassicToGutenberg\Converter\NextpageConverter;
use Apermo\ClassicToGutenberg\Converter\ParagraphConverter;
use Apermo\ClassicToGutenberg\Converter\PreformattedConverter;
use Apermo\ClassicToGutenberg\Converter\QuoteConverter;
use Apermo\ClassicToGutenberg\Converter\SeparatorConverter;
use Apermo\ClassicToGutenberg\Converter\Shortcode\CaptionHandler;
use Apermo\ClassicToGutenberg\Converter\Shortcode\GalleryHandler;
use Apermo\ClassicToGutenberg\Converter\ShortcodeConverter;
use Apermo\ClassicToGutenberg\Converter\TableConverter;
use Apermo\ClassicToGutenberg\Parser\TopLevelSplitter;
use Apermo\ClassicToGutenbergAddons\WPBakery\WPBakery;
use Generator;
use WP_UnitTestCase;

/**
 * Integration tests for WPBakery conversion using fixture data.
 *
 * Requires WordPress loaded for wpautop(), filters, etc.
 */
class WPBakeryConverterTest extends WP_UnitTestCase {

	/**
	 * The content converter under test.
	 *
	 * @var ContentConverter
	 */
	private ContentConverter $converter;

	/**
	 * Data provider for fixture-based tests.
	 *
	 * @return \Generator<string, array{string, string}>
	 */
	public static function fixture_provider(): Generator {
		$fixtures_dir = \dirname( __DIR__, 2 ) . '/fixtures';
		$inputs       = \glob( $fixtures_dir . '/wpbakery-*.html' );

		if ( $inputs === false ) {
			return;
		}

		foreach ( $inputs as $input_file ) {
			$basename = \basename( $input_file, '.html' );
			if ( \str_ends_with( $basename, '.expected' ) ) {
				continue;
			}

			$expected_file = $fixtures_dir . '/' . $basename . '.expected.html';
			if ( ! \file_exists( $expected_file ) ) {
				continue;
			}

			// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local fixtures
			yield $basename => [
				(string) \file_get_contents( $input_file ),
				\trim( (string) \file_get_contents( $expected_file ) ),
			];
			// phpcs:enable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}
	}

	/**
	 * Set up the converter with WPBakery hooks active.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		// Register WPBakery hooks.
		WPBakery::register();

		// Build full factory with standard converters (same as core).
		$factory = new BlockConverterFactory( new HtmlBlockConverter() );
		$factory->register( new ParagraphConverter() );
		$factory->register( new HeadingConverter() );
		$factory->register( new SeparatorConverter() );
		$factory->register( new PreformattedConverter() );
		$factory->register( new MoreConverter() );
		$factory->register( new NextpageConverter() );
		$factory->register( new ListConverter() );
		$factory->register( new QuoteConverter() );
		$factory->register( new TableConverter() );
		$factory->register( new ImageConverter() );
		$factory->register(
			new ShortcodeConverter(
				[
					new CaptionHandler(),
					new GalleryHandler(),
				],
			),
		);

		$this->converter = new ContentConverter(
			$factory,
			new TopLevelSplitter(),
			static fn( string $content ): string => wpautop( $content ),
		);
	}

	/**
	 * Clean up filter hooks after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'classic_to_gutenberg_pre_convert' );
		remove_all_filters( 'classic_to_gutenberg_post_convert' );
		parent::tear_down();
	}

	/**
	 * Test fixture conversion matches expected output.
	 *
	 * @param string $input    WPBakery content.
	 * @param string $expected Expected Gutenberg block markup.
	 *
	 * @dataProvider fixture_provider
	 *
	 * @return void
	 */
	public function test_fixture_conversion( string $input, string $expected ): void {
		$result = $this->converter->convert( $input );
		$this->assertSame( $expected, $result );
	}
}
