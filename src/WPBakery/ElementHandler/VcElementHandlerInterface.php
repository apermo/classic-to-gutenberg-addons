<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Closure;

/**
 * Interface for WPBakery element type handlers.
 *
 * Each handler converts a specific WPBakery shortcode into Gutenberg block markup.
 */
interface VcElementHandlerInterface {

	/**
	 * Get the shortcode tag this handler processes (without brackets).
	 *
	 * @return string E.g. "vc_column_text", "vc_single_image".
	 */
	public function get_tag(): string;

	/**
	 * Convert a shortcode string to Gutenberg block markup.
	 *
	 * @param string  $shortcode       The full shortcode string.
	 * @param Closure $inner_converter Converts inner HTML to Gutenberg blocks.
	 *
	 * @return string Gutenberg block markup, or empty string if nothing to convert.
	 */
	public function convert( string $shortcode, Closure $inner_converter ): string;
}
