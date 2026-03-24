<?php

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg\WPBakery\ElementHandler;

use Closure;

/**
 * Converts [vc_row_inner] to Gutenberg blocks via recursive row conversion.
 */
class VcRowInnerHandler implements VcElementHandlerInterface {

	/**
	 * Callable that converts a [vc_row_inner] shortcode.
	 *
	 * @var Closure
	 */
	private Closure $row_converter;

	/**
	 * Create handler with a row conversion callable.
	 *
	 * @param Closure $row_converter Converts a row shortcode to block markup.
	 */
	public function __construct( Closure $row_converter ) {
		$this->row_converter = $row_converter;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_tag(): string {
		return 'vc_row_inner';
	}

	/**
	 * Convert [vc_row_inner] via recursive row conversion.
	 *
	 * @param string  $shortcode       The full shortcode string.
	 * @param Closure $inner_converter Converts inner HTML to Gutenberg blocks.
	 *
	 * @return string Gutenberg block markup.
	 */
	public function convert( string $shortcode, Closure $inner_converter ): string {
		return ( $this->row_converter )( $shortcode );
	}
}
