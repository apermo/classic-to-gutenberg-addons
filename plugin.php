<?php
/**
 * Plugin Name: Classic to Gutenberg Addons
 * Description: Niche converters for page builders and third-party plugins.
 * Version:     0.1.0
 * Author:      Christoph Daum
 * Author URI:  https://apermo.de
 * License:     GPL-2.0-or-later
 * Text Domain: classic-to-gutenberg-addons
 * Requires at least: 6.2
 * Requires PHP: 8.2
 */

declare(strict_types=1);

namespace Apermo\ClassicToGutenbergAddons;

\defined( 'ABSPATH' ) || exit();

require_once __DIR__ . '/vendor/autoload.php';

Plugin::init( __FILE__ );
