<?php

declare(strict_types=1);

namespace Apermo\ClassicToGutenbergAddons\WPBakery;

/**
 * Orchestrates WPBakery-to-Gutenberg conversion via pre/post_convert hooks.
 *
 * In pre_convert: finds [vc_row]...[/vc_row] blocks, converts them to Gutenberg
 * block markup, and replaces them with placeholder comments.
 *
 * In post_convert: swaps the placeholder comments (which the pipeline wraps in
 * core/html blocks) back with the actual converted block markup.
 */
class Converter {

	/**
	 * Row converter instance.
	 *
	 * @var RowConverter
	 */
	private RowConverter $row_converter;

	/**
	 * Stored converted block markup indexed by placeholder number.
	 *
	 * @var array<int, string>
	 */
	private array $blocks = [];

	/**
	 * Current placeholder counter.
	 *
	 * @var int
	 */
	private int $counter = 0;

	/**
	 * Create a new converter.
	 *
	 * @param RowConverter $row_converter The row converter instance.
	 */
	public function __construct( RowConverter $row_converter ) {
		$this->row_converter = $row_converter;
	}

	/**
	 * Pre-convert filter callback.
	 *
	 * Finds all top-level [vc_row]...[/vc_row] blocks, converts them,
	 * and replaces with placeholder comments.
	 *
	 * @param string $content Raw content before wpautop.
	 *
	 * @return string Content with WPBakery rows replaced by placeholders.
	 */
	public function pre_convert( string $content ): string {
		$this->blocks  = [];
		$this->counter = 0;

		return (string) \preg_replace_callback(
			'/\[vc_row(?:\s[^\]]*)?].*?\[\/vc_row]/s',
			function ( array $matches ): string {
				$converted = $this->row_converter->convert( $matches[0] );
				$index     = $this->counter;

				$this->blocks[ $index ] = $converted;
				$this->counter++;

				return "<!-- vc:placeholder:{$index} -->";
			},
			$content,
		);
	}

	/**
	 * Post-convert filter callback.
	 *
	 * Replaces placeholder comments (which may be wrapped in core/html blocks
	 * by the pipeline) with the actual converted block markup.
	 *
	 * @param string $content Converted content from the pipeline.
	 *
	 * @return string Content with placeholders replaced by block markup.
	 */
	public function post_convert( string $content ): string {
		// Replace placeholders wrapped in html blocks by the pipeline.
		$content = (string) \preg_replace_callback(
			'/<!-- wp:html -->\s*<!-- vc:placeholder:(\d+) -->\s*<!-- \/wp:html -->/',
			fn( array $matches ): string => $this->blocks[ (int) $matches[1] ] ?? '',
			$content,
		);

		// Replace any remaining bare placeholders (edge case).
		$content = (string) \preg_replace_callback(
			'/<!-- vc:placeholder:(\d+) -->/',
			fn( array $matches ): string => $this->blocks[ (int) $matches[1] ] ?? '',
			$content,
		);

		return $content;
	}
}
