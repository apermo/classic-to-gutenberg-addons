<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Apermo\WPBakeryToGutenberg\WPBakery\ShortcodeParser;
use Closure;

/**
 * Converts [vc_raw_html] to a core/html block.
 *
 * WPBakery stores raw HTML as base64 encoded content.
 */
class VcRawHtmlHandler implements VcElementHandlerInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_tag(): string {
		return 'vc_raw_html';
	}

	/**
	 * Convert [vc_raw_html] to core/html.
	 *
	 * @param string  $shortcode       The full shortcode string.
	 * @param Closure $inner_converter Converts inner HTML to Gutenberg blocks.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $shortcode, Closure $inner_converter ): string {
		$content = \trim( ShortcodeParser::extract_content( $shortcode, 'vc_raw_html' ) );

		if ( $content === '' ) {
			return '';
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- WPBakery stores raw HTML as base64.
		$decoded = \base64_decode( $content, true );

		if ( $decoded !== false && $decoded !== '' ) {
			$content = $decoded;
		}

		return "<!-- wp:html -->\n{$content}\n<!-- /wp:html -->";
	}
}
