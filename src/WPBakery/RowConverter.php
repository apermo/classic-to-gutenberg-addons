<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenbergAddons\WPBakery;

use Closure;

/**
 * Converts WPBakery [vc_row] shortcodes to Gutenberg column blocks.
 *
 * Single full-width columns are unwrapped to just their inner content.
 * Multi-column rows become core/columns + core/column blocks.
 */
class RowConverter {

	/**
	 * Callable that converts inner HTML content to Gutenberg blocks.
	 *
	 * @var Closure(string): string
	 */
	private Closure $inner_converter;

	/**
	 * Create a new row converter.
	 *
	 * @param Closure(string): string $inner_converter Converts HTML to Gutenberg blocks.
	 */
	public function __construct( Closure $inner_converter ) {
		$this->inner_converter = $inner_converter;
	}

	/**
	 * Convert a [vc_row] shortcode to Gutenberg block markup.
	 *
	 * @param string $shortcode The full [vc_row]...[/vc_row] shortcode string.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $shortcode ): string {
		$is_inner  = \str_starts_with( $shortcode, '[vc_row_inner' );
		$row_tag   = $is_inner ? 'vc_row_inner' : 'vc_row';
		$col_tag   = $is_inner ? 'vc_column_inner' : 'vc_column';
		$row_inner = ShortcodeParser::extract_content( $shortcode, $row_tag );

		$columns = ShortcodeParser::find_shortcodes( $row_inner, $col_tag );

		if ( $columns === [] ) {
			return '';
		}

		$parsed_columns = $this->parse_columns( $columns, $col_tag );

		if ( $this->is_single_full_width( $parsed_columns ) ) {
			return $this->convert_inner_content( $parsed_columns[0]['content'] );
		}

		return $this->build_columns_block( $parsed_columns );
	}

	/**
	 * Parse column shortcodes into structured data.
	 *
	 * @param string[] $columns Column shortcode strings.
	 * @param string   $col_tag Column tag name.
	 *
	 * @return array<int, array{width: string, content: string}> Parsed columns.
	 */
	private function parse_columns( array $columns, string $col_tag ): array {
		$parsed = [];

		foreach ( $columns as $column ) {
			$attrs   = ShortcodeParser::parse_attrs( $column );
			$content = ShortcodeParser::extract_content( $column, $col_tag );

			$parsed[] = [
				'width'   => $attrs['width'] ?? '',
				'content' => $content,
			];
		}

		return $parsed;
	}

	/**
	 * Check if this is a single full-width column that should be unwrapped.
	 *
	 * @param array<int, array{width: string, content: string}> $columns Parsed columns.
	 *
	 * @return bool
	 */
	private function is_single_full_width( array $columns ): bool {
		if ( \count( $columns ) !== 1 ) {
			return false;
		}

		$width = $columns[0]['width'];

		return $width === '' || $width === '1/1';
	}

	/**
	 * Build a core/columns block with core/column children.
	 *
	 * @param array<int, array{width: string, content: string}> $columns Parsed columns.
	 *
	 * @return string Gutenberg block markup.
	 */
	private function build_columns_block( array $columns ): string {
		$column_blocks = [];

		foreach ( $columns as $column ) {
			$column_blocks[] = $this->build_column_block( $column['width'], $column['content'] );
		}

		$inner = \implode( "\n\n", $column_blocks );

		return "<!-- wp:columns -->\n<div class=\"wp-block-columns\">{$inner}</div>\n<!-- /wp:columns -->";
	}

	/**
	 * Build a single core/column block.
	 *
	 * @param string $width   Fractional width (e.g. "1/2") or empty for equal distribution.
	 * @param string $content Inner shortcode content.
	 *
	 * @return string Gutenberg block markup.
	 */
	private function build_column_block( string $width, string $content ): string {
		$inner_blocks = $this->convert_inner_content( $content );
		$percent      = $width !== '' ? ShortcodeParser::fraction_to_percent( $width ) : '';
		$attrs_json   = '';
		$style_attr   = '';

		if ( $percent !== '' && $percent !== '100%' ) {
			$attrs_json = ' ' . \json_encode(
				[ 'width' => $percent ],
				\JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE,
			);
			$style_attr = " style=\"flex-basis:{$percent}\"";
		}

		return "<!-- wp:column{$attrs_json} -->\n"
			. "<div class=\"wp-block-column\"{$style_attr}>{$inner_blocks}</div>\n"
			. '<!-- /wp:column -->';
	}

	/**
	 * Convert the inner content of a column to Gutenberg blocks.
	 *
	 * Handles [vc_column_text], [vc_row_inner], and unknown shortcodes.
	 *
	 * @param string $content The inner content of a column.
	 *
	 * @return string Gutenberg block markup.
	 */
	private function convert_inner_content( string $content ): string {
		$blocks = [];

		$remaining = $content;

		while ( $remaining !== '' ) {
			$remaining = \ltrim( $remaining );

			if ( $remaining === '' ) {
				break;
			}

			// Match [vc_column_text]...[/vc_column_text].
			if ( \preg_match( '/^\[vc_column_text(?:\s[^\]]*)?](.*?)\[\/vc_column_text]/s', $remaining, $match ) ) {
				$html = \trim( $match[1] );
				if ( $html !== '' ) {
					$blocks[] = ( $this->inner_converter )( $html );
				}
				$remaining = \substr( $remaining, \strlen( $match[0] ) );
				continue;
			}

			// Match [vc_row_inner]...[/vc_row_inner] for recursive conversion.
			if ( \preg_match( '/^\[vc_row_inner(?:\s[^\]]*)?].*?\[\/vc_row_inner]/s', $remaining, $match ) ) {
				$blocks[]  = $this->convert( $match[0] );
				$remaining = \substr( $remaining, \strlen( $match[0] ) );
				continue;
			}

			// Match any other shortcode and wrap in core/shortcode block.
			if ( \preg_match( '/^\[(\w[\w-]*)(?:\s[^\]]*)?](?:.*?\[\/\1])?/s', $remaining, $match ) ) {
				$blocks[]  = "<!-- wp:shortcode -->\n{$match[0]}\n<!-- /wp:shortcode -->";
				$remaining = \substr( $remaining, \strlen( $match[0] ) );
				continue;
			}

			// Plain text/HTML — pass to inner converter.
			$next_shortcode = \strpos( $remaining, '[' );
			if ( $next_shortcode === false ) {
				$fragment  = \trim( $remaining );
				$remaining = '';
			} else {
				$fragment  = \trim( \substr( $remaining, 0, $next_shortcode ) );
				$remaining = \substr( $remaining, $next_shortcode );
			}

			if ( $fragment !== '' ) {
				$blocks[] = ( $this->inner_converter )( $fragment );
			}
		}

		return \implode( "\n\n", \array_filter( $blocks, static fn( string $b ): bool => $b !== '' ) );
	}
}
