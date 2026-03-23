<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenbergAddons\WPBakery;

use Apermo\ClassicToGutenberg\ContentConverter;
use Apermo\ClassicToGutenberg\Converter\BlockConverterFactory;
use Apermo\ClassicToGutenberg\Converter\HeadingConverter;
use Apermo\ClassicToGutenberg\Converter\HtmlBlockConverter;
use Apermo\ClassicToGutenberg\Converter\ImageConverter;
use Apermo\ClassicToGutenberg\Converter\ListConverter;
use Apermo\ClassicToGutenberg\Converter\ParagraphConverter;
use Apermo\ClassicToGutenberg\Converter\PreformattedConverter;
use Apermo\ClassicToGutenberg\Converter\QuoteConverter;
use Apermo\ClassicToGutenberg\Converter\SeparatorConverter;
use Apermo\ClassicToGutenberg\Converter\ShortcodeConverter;
use Apermo\ClassicToGutenberg\Converter\TableConverter;
use Apermo\ClassicToGutenberg\Parser\TopLevelSplitter;

/**
 * Convenience class for registering WPBakery converters.
 */
class WPBakery {

	/**
	 * Register WPBakery converters on the pre/post_convert filter hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		$inner_converter = self::create_inner_converter();
		$row_converter   = new RowConverter(
			static fn( string $html ): string => $inner_converter->convert( $html ),
		);
		$converter = new Converter( $row_converter );

		add_filter( 'classic_to_gutenberg_pre_convert', [ $converter, 'pre_convert' ] );
		add_filter( 'classic_to_gutenberg_post_convert', [ $converter, 'post_convert' ] );
	}

	/**
	 * Create a standalone ContentConverter for inner WPBakery content.
	 *
	 * Uses standard core converters only (no addon converters) to avoid
	 * infinite recursion.
	 *
	 * @return ContentConverter
	 */
	private static function create_inner_converter(): ContentConverter {
		$factory = new BlockConverterFactory( new HtmlBlockConverter() );
		$factory->register( new ParagraphConverter() );
		$factory->register( new HeadingConverter() );
		$factory->register( new SeparatorConverter() );
		$factory->register( new PreformattedConverter() );
		$factory->register( new ListConverter() );
		$factory->register( new QuoteConverter() );
		$factory->register( new TableConverter() );
		$factory->register( new ImageConverter() );
		$factory->register( new ShortcodeConverter() );

		return new ContentConverter(
			$factory,
			new TopLevelSplitter(),
			static fn( string $content ): string => wpautop( $content ),
		);
	}
}
