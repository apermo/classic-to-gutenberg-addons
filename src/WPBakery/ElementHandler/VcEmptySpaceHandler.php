<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Apermo\ClassicToGutenberg\Converter\BlockMarkup;
use Apermo\WPBakeryToGutenberg\WPBakery\ShortcodeParser;
use Closure;

/**
 * Converts [vc_empty_space] to a core/spacer block.
 */
class VcEmptySpaceHandler implements VcElementHandlerInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_tag(): string {
		return 'vc_empty_space';
	}

	/**
	 * Convert [vc_empty_space] to core/spacer.
	 *
	 * @param string  $shortcode       The full shortcode string.
	 * @param Closure $inner_converter Converts inner HTML to Gutenberg blocks.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $shortcode, Closure $inner_converter ): string {
		$attrs  = ShortcodeParser::parse_attrs( $shortcode );
		$height = $attrs['height'] ?? '32px';

		return BlockMarkup::self_closing( 'spacer', [ 'height' => $height ] );
	}
}
