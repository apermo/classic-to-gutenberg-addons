<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Apermo\ClassicToGutenberg\Converter\BlockMarkup;
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
	 * @return array{url: string, target: string} Parsed link parts.
	 */
	private static function parse_vc_link( string $link ): array {
		$result = [
			'url'    => '',
			'target' => '',
		];

		if ( $link === '' ) {
			return $result;
		}

		$parts = \explode( '|', $link );

		foreach ( $parts as $part ) {
			if ( \str_starts_with( $part, 'url:' ) ) {
				$result['url'] = \urldecode( \substr( $part, 4 ) );
			} elseif ( \str_starts_with( $part, 'target:' ) ) {
				$result['target'] = \substr( $part, 7 );
			}
		}

		return $result;
	}

	/**
	 * Sanitize a URL for use in an href attribute.
	 *
	 * Uses esc_url() when available (WordPress loaded), otherwise falls back
	 * to protocol validation + htmlspecialchars.
	 *
	 * @param string $href The raw URL.
	 *
	 * @return string Sanitized URL, or empty string if unsafe.
	 */
	private static function sanitize_url( string $href ): string {
		if ( $href === '' ) {
			return '';
		}

		if ( \function_exists( 'esc_url' ) ) {
			return esc_url( $href );
		}

		// Reject dangerous protocols when esc_url is unavailable.
		if ( \preg_match( '/^(javascript|data|vbscript):/i', $href ) ) {
			return '';
		}

		return \htmlspecialchars( $href, \ENT_QUOTES | \ENT_HTML5, 'UTF-8' );
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

		$parsed = self::parse_vc_link( $link );

		$safe_title  = \htmlspecialchars( $title, \ENT_QUOTES | \ENT_HTML5, 'UTF-8' );
		$safe_href   = self::sanitize_url( $parsed['url'] );
		$href_attr   = $safe_href !== '' ? " href=\"{$safe_href}\"" : '';
		$target_attr = $parsed['target'] !== '' ? ' target="' . \htmlspecialchars( $parsed['target'], \ENT_QUOTES | \ENT_HTML5, 'UTF-8' ) . '" rel="noreferrer noopener"' : '';

		$button = BlockMarkup::wrap(
			'button',
			"<div class=\"wp-block-button\"><a class=\"wp-block-button__link wp-element-button\"{$href_attr}{$target_attr}>{$safe_title}</a></div>",
		);

		return BlockMarkup::wrap( 'buttons', "<div class=\"wp-block-buttons\">{$button}</div>" );
	}
}
