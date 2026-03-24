<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery;

/**
 * Utility for parsing WPBakery shortcode strings.
 */
class ShortcodeParser {

	/**
	 * Parse attributes from a shortcode opening tag.
	 *
	 * @param string $shortcode The shortcode string (at minimum the opening tag).
	 *
	 * @return array<string, string> Key-value attribute pairs.
	 */
	public static function parse_attrs( string $shortcode ): array {
		$attrs = [];

		if ( \preg_match_all( '/(\w[\w-]*)=["\']([^"\']*)["\']/', $shortcode, $matches, \PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$attrs[ $match[1] ] = $match[2];
			}
		}

		return $attrs;
	}

	/**
	 * Extract inner content from a paired shortcode.
	 *
	 * @param string $shortcode The full shortcode string.
	 * @param string $tag       The shortcode tag name.
	 *
	 * @return string The inner content, or empty string if self-closing.
	 */
	public static function extract_content( string $shortcode, string $tag ): string {
		$pattern = '/\[' . \preg_quote( $tag, '/' ) . '(?:\s[^\]]*)?](.*)\[\/' . \preg_quote( $tag, '/' ) . ']/s';

		if ( \preg_match( $pattern, $shortcode, $match ) ) {
			return $match[1];
		}

		return '';
	}

	/**
	 * Find all occurrences of a given shortcode tag in content.
	 *
	 * Returns the full shortcode strings including opening/closing tags.
	 *
	 * @param string $content The content to search.
	 * @param string $tag     The shortcode tag to find.
	 *
	 * @return string[] List of full shortcode strings.
	 */
	public static function find_shortcodes( string $content, string $tag ): array {
		$pattern = '/\[' . \preg_quote( $tag, '/' ) . '(?:\s[^\]]*)?](?:.*?\[\/' . \preg_quote( $tag, '/' ) . '])?/s';

		if ( \preg_match_all( $pattern, $content, $matches ) ) {
			return $matches[0];
		}

		return [];
	}

	/**
	 * Convert a WPBakery fractional width to a percentage string.
	 *
	 * @param string $fraction Fractional width like "1/2", "2/3", etc.
	 *
	 * @return string Percentage string like "50%", or empty string if invalid.
	 */
	public static function fraction_to_percent( string $fraction ): string {
		if ( ! \preg_match( '/^(\d+)\/(\d+)$/', $fraction, $match ) ) {
			return '';
		}

		$numerator   = (int) $match[1];
		$denominator = (int) $match[2];

		if ( $denominator === 0 ) {
			return '';
		}

		$percent = ( $numerator / $denominator ) * 100;

		if ( $percent === 100.0 ) {
			return '100%';
		}

		$truncated = \floor( $percent * 100 ) / 100;

		return \rtrim( \rtrim( \number_format( $truncated, 2, '.', '' ), '0' ), '.' ) . '%';
	}
}
