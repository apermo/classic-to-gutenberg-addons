<?php

declare(strict_types=1);

namespace Plugin_Name;

\defined( 'ABSPATH' ) || exit();

\define( 'PLUGIN_NAME_VERSION', '0.1.0' );
\define( 'PLUGIN_NAME_FILE', __FILE__ );
\define( 'PLUGIN_NAME_DIR', get_template_directory() . '/' );

require_once __DIR__ . '/vendor/autoload.php';

Theme::init();
