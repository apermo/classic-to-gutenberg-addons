<?php
/**
 * Plugin Name: WPBakery to Gutenberg
 * Description: Convert WPBakery Page Builder content to native Gutenberg blocks.
 * Version:     0.1.0
 * Author:      Christoph Daum
 * Author URI:  https://apermo.de
 * License:     GPL-2.0-or-later
 * Text Domain: wpbakery-to-gutenberg
 * Requires Plugins: classic-to-gutenberg
 * Requires at least: 6.5
 * Requires PHP: 8.2
 */

declare(strict_types=1);

namespace Apermo\WPBakeryToGutenberg;

\defined( 'ABSPATH' ) || exit();

require_once __DIR__ . '/vendor/autoload.php';

Plugin::init( __FILE__ );
