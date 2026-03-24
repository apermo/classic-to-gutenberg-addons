<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery;

use Apermo\ClassicToGutenberg\Converter\BlockMarkup;
use Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler\VcElementHandlerInterface;
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
	 * @var Closure
	 */
	private Closure $inner_converter;

	/**
	 * Registered element handlers indexed by shortcode tag.
	 *
	 * @var array<string, VcElementHandlerInterface>
	 */
	private array $handlers = [];

	/**
	 * Pre-compiled regex patterns for handler matching, indexed by tag.
	 *
	 * @var array<string, string>
	 */
	private array $handler_patterns = [];

	/**
	 * Create a new row converter.
	 *
	 * @param Closure                     $inner_converter Converts HTML to Gutenberg blocks.
	 * @param VcElementHandlerInterface[] $handlers        Element handlers.
	 */
	public function __construct( Closure $inner_converter, array $handlers = [] ) {
		$this->inner_converter = $inner_converter;

		foreach ( $handlers as $handler ) {
			$this->register_handler( $handler );
		}
	}

	/**
	 * Register an additional element handler.
	 *
	 * @param VcElementHandlerInterface $handler The handler to register.
	 *
	 * @return void
	 */
	public function add_handler( VcElementHandlerInterface $handler ): void {
		$this->register_handler( $handler );
	}

	/**
	 * Register a handler and pre-compile its regex pattern.
	 *
	 * @param VcElementHandlerInterface $handler The handler to register.
	 *
	 * @return void
	 */
	private function register_handler( VcElementHandlerInterface $handler ): void {
		$tag = $handler->get_tag();

		$this->handlers[ $tag ]         = $handler;
		$this->handler_patterns[ $tag ] = '/^\[' . \preg_quote( $tag, '/' ) . '(?:\s[^\]]*)?](?:.*?\[\/' . \preg_quote( $tag, '/' ) . '])?/s';
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

		return BlockMarkup::wrap( 'columns', "<div class=\"wp-block-columns\">{$inner}</div>" );
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
		$attrs        = [];
		$style_attr   = '';

		if ( $percent !== '' && $percent !== '100%' ) {
			$attrs      = [ 'width' => $percent ];
			$style_attr = " style=\"flex-basis:{$percent}\"";
		}

		return BlockMarkup::wrap( 'column', "<div class=\"wp-block-column\"{$style_attr}>{$inner_blocks}</div>", $attrs );
	}

	/**
	 * Convert the inner content of a column to Gutenberg blocks.
	 *
	 * Iterates registered element handlers, falls back to core/shortcode for unknown tags.
	 *
	 * @param string $content The inner content of a column.
	 *
	 * @return string Gutenberg block markup.
	 */
	private function convert_inner_content( string $content ): string {
		$blocks    = [];
		$remaining = $content;

		while ( $remaining !== '' ) {
			$remaining = \ltrim( $remaining );

			if ( $remaining === '' ) {
				break;
			}

			$match_result = $this->try_match_handler( $remaining );

			if ( $match_result !== null ) {
				[ $remaining, $block ] = $match_result;
				if ( $block !== '' ) {
					$blocks[] = $block;
				}
				continue;
			}

			$match_result = $this->try_match_unknown_shortcode( $remaining );

			if ( $match_result !== null ) {
				[ $remaining, $block ] = $match_result;
				$blocks[] = $block;
				continue;
			}

			[ $remaining, $block ] = $this->consume_plain_text( $remaining );
			if ( $block !== '' ) {
				$blocks[] = $block;
			}
		}

		return \implode( "\n\n", $blocks );
	}

	/**
	 * Try to match a registered element handler at the start of remaining content.
	 *
	 * @param string $remaining Current remaining content.
	 *
	 * @return array{string, string}|null [remaining, block] or null if no match.
	 */
	private function try_match_handler( string $remaining ): ?array {
		foreach ( $this->handlers as $tag => $handler ) {
			if ( \preg_match( $this->handler_patterns[ $tag ], $remaining, $match ) ) {
				$block = $handler->convert( $match[0], $this->inner_converter );
				return [ \substr( $remaining, \strlen( $match[0] ) ), $block ];
			}
		}

		return null;
	}

	/**
	 * Match any unknown shortcode and wrap in core/shortcode block.
	 *
	 * @param string $remaining Current remaining content.
	 *
	 * @return array{string, string}|null [remaining, block] or null if no match.
	 */
	private function try_match_unknown_shortcode( string $remaining ): ?array {
		if ( \preg_match( '/^\[(\w[\w-]*)(?:\s[^\]]*)?](?:.*?\[\/\1])?/s', $remaining, $match ) ) {
			$block = BlockMarkup::wrap( 'shortcode', $match[0] );
			return [ \substr( $remaining, \strlen( $match[0] ) ), $block ];
		}

		return null;
	}

	/**
	 * Consume plain text/HTML up to the next shortcode.
	 *
	 * @param string $remaining Current remaining content.
	 *
	 * @return array{string, string} [remaining, block].
	 */
	private function consume_plain_text( string $remaining ): array {
		$next_shortcode = \strpos( $remaining, '[' );

		if ( $next_shortcode === false ) {
			$fragment  = \trim( $remaining );
			$remaining = '';
		} else {
			$fragment  = \trim( \substr( $remaining, 0, $next_shortcode ) );
			$remaining = \substr( $remaining, $next_shortcode );
		}

		$block = $fragment !== '' ? ( $this->inner_converter )( $fragment ) : '';

		return [ $remaining, $block ];
	}
}
