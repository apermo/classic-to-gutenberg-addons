<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Apermo\WPBakeryToGutenberg\WPBakery\ShortcodeParser;
use Closure;

/**
 * Converts [vc_single_image] to a core/image block.
 *
 * Produces a placeholder image block with the attachment ID.
 * The actual image URL is resolved at render time by WordPress.
 */
class VcSingleImageHandler implements VcElementHandlerInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_tag(): string {
		return 'vc_single_image';
	}

	/**
	 * Convert [vc_single_image] to core/image.
	 *
	 * @param string  $shortcode       The full shortcode string.
	 * @param Closure $inner_converter Converts inner HTML to Gutenberg blocks.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $shortcode, Closure $inner_converter ): string {
		$attrs = ShortcodeParser::parse_attrs( $shortcode );
		$image = $attrs['image'] ?? '';

		if ( $image === '' ) {
			return '';
		}

		$block_attrs = [ 'id' => (int) $image ];

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- unit-testable without WP.
		$attrs_json = (string) \json_encode( $block_attrs, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE );

		return "<!-- wp:image {$attrs_json} -->\n"
			. "<figure class=\"wp-block-image\"><img class=\"wp-image-{$image}\"/></figure>\n"
			. '<!-- /wp:image -->';
	}
}
