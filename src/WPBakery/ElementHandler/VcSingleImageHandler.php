<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Apermo\ClassicToGutenberg\Converter\BlockMarkup;
use Apermo\WPBakeryToGutenberg\WPBakery\ShortcodeParser;
use Closure;

/**
 * Converts [vc_single_image] to a core/image block.
 *
 * When wp_get_attachment_image() is available (WordPress loaded), the image
 * block includes a resolved src and alt. Otherwise produces a placeholder
 * with just the attachment ID.
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

		if ( $image === '' || ! \ctype_digit( $image ) ) {
			return '';
		}

		$attachment_id = (int) $image;
		$block_attrs   = [ 'id' => $attachment_id ];

		$img_html = $this->resolve_image( $attachment_id );

		return BlockMarkup::wrap( 'image', "<figure class=\"wp-block-image\">{$img_html}</figure>", $block_attrs );
	}

	/**
	 * Resolve image HTML from an attachment ID.
	 *
	 * @param int $attachment_id The WordPress attachment ID.
	 *
	 * @return string The <img> tag with src/alt, or a placeholder.
	 */
	private function resolve_image( int $attachment_id ): string {
		$img_class = "wp-image-{$attachment_id}";

		if ( \function_exists( 'wp_get_attachment_image' ) ) {
			$image_html = wp_get_attachment_image( $attachment_id, 'full', false, [ 'class' => $img_class ] );

			if ( $image_html !== '' ) {
				return $image_html;
			}
		}

		return "<img class=\"{$img_class}\"/>";
	}
}
