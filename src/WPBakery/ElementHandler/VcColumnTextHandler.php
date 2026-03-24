<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Apermo\WPBakeryToGutenberg\WPBakery\ShortcodeParser;
use Closure;

/**
 * Converts [vc_column_text] to Gutenberg blocks.
 *
 * Extracts the inner HTML and delegates to the content converter.
 */
class VcColumnTextHandler implements VcElementHandlerInterface {

	/**
	 * {@inheritDoc}
	 */
	public function get_tag(): string {
		return 'vc_column_text';
	}

	/**
	 * Convert [vc_column_text] to Gutenberg blocks.
	 *
	 * @param string  $shortcode       The full shortcode string.
	 * @param Closure $inner_converter Converts inner HTML to Gutenberg blocks.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $shortcode, Closure $inner_converter ): string {
		$html = \trim( ShortcodeParser::extract_content( $shortcode, 'vc_column_text' ) );

		if ( $html === '' ) {
			return '';
		}

		return $inner_converter( $html );
	}
}
