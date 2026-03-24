<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Apermo\WPBakeryToGutenberg\WPBakery\ShortcodeParser;
use Closure;

/**
 * Converts [vc_btn] to core/buttons + core/button blocks.
 */
class VcBtnHandler implements VcElementHandlerInterface {

	/**
	 * Parse WPBakery link attribute format.
	 *
	 * WPBakery encodes links as "url:https%3A%2F%2Fexample.com|title:Text|target:_blank".
	 *
	 * @param string $link The raw link attribute value.
	 *
	 * @return string The decoded URL, or empty string.
	 */
	private static function parse_vc_link( string $link ): string {
		if ( $link === '' ) {
			return '';
		}

		$parts = \explode( '|', $link );

		foreach ( $parts as $part ) {
			if ( \str_starts_with( $part, 'url:' ) ) {
				return \urldecode( \substr( $part, 4 ) );
			}
		}

		return '';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_tag(): string {
		return 'vc_btn';
	}

	/**
	 * Convert [vc_btn] to core/buttons + core/button.
	 *
	 * @param string  $shortcode       The full shortcode string.
	 * @param Closure $inner_converter Converts inner HTML to Gutenberg blocks.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $shortcode, Closure $inner_converter ): string {
		$attrs = ShortcodeParser::parse_attrs( $shortcode );
		$title = $attrs['title'] ?? '';
		$link  = $attrs['link'] ?? '';

		if ( $title === '' ) {
			return '';
		}

		$href = self::parse_vc_link( $link );

		$safe_title = \htmlspecialchars( $title, \ENT_QUOTES | \ENT_HTML5, 'UTF-8' );
		$safe_href  = $href !== '' ? \htmlspecialchars( $href, \ENT_QUOTES | \ENT_HTML5, 'UTF-8' ) : '';
		$href_attr  = $safe_href !== '' ? " href=\"{$safe_href}\"" : '';

		$button = "<!-- wp:button -->\n"
			. "<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\"{$href_attr}>{$safe_title}</a></div>\n"
			. '<!-- /wp:button -->';

		return "<!-- wp:buttons -->\n"
			. "<div class=\"wp-block-buttons\">{$button}</div>\n"
			. '<!-- /wp:buttons -->';
	}
}
