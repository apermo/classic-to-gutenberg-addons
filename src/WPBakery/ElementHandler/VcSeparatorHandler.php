<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Apermo\ClassicToGutenberg\Converter\BlockMarkup;
use Closure;

/**
 * Converts [vc_separator] to a core/separator block.
 */
class VcSeparatorHandler implements VcElementHandlerInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_tag(): string {
		return 'vc_separator';
	}

	/**
	 * Convert [vc_separator] to core/separator.
	 *
	 * @param string  $shortcode       The full shortcode string.
	 * @param Closure $inner_converter Converts inner HTML to Gutenberg blocks.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $shortcode, Closure $inner_converter ): string {
		return BlockMarkup::wrap( 'separator', '<hr class="wp-block-separator has-alpha-channel-opacity"/>' );
	}
}
