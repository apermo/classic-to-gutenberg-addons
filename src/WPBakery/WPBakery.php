<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery;

use Apermo\ClassicToGutenberg\Plugin as CorePlugin;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcBtnHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcColumnTextHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcEmptySpaceHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcRawHtmlHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcRowInnerHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcSeparatorHandler;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcSingleImageHandler;

/**
 * Convenience class for registering WPBakery converters.
 */
class WPBakery {

	/**
	 * Register WPBakery converters on the pre/post_convert filter hooks.
	 *
	 * Uses the core plugin's ContentConverter for inner HTML content.
	 * The re-entrancy guard in Converter prevents recursion when the inner
	 * converter fires pre_convert/post_convert filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		$inner_converter = CorePlugin::create_content_converter();
		$inner_closure   = static fn( string $html ): string => $inner_converter->convert( $html );

		$row_converter = new RowConverter(
			$inner_closure,
			[
				new VcColumnTextHandler(),
				new VcSeparatorHandler(),
				new VcEmptySpaceHandler(),
				new VcRawHtmlHandler(),
				new VcSingleImageHandler(),
				new VcBtnHandler(),
			],
		);

		// VcRowInnerHandler needs to call RowConverter::convert() recursively.
		$row_converter->add_handler(
			new VcRowInnerHandler(
				static fn( string $shortcode ): string => $row_converter->convert( $shortcode ),
			),
		);

		$converter = new Converter( $row_converter );

		add_filter( 'classic_to_gutenberg_pre_convert', [ $converter, 'pre_convert' ] );
		add_filter( 'classic_to_gutenberg_post_convert', [ $converter, 'post_convert' ] );
	}
}
