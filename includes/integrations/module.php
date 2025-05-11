<?php
/**
 * This file initializes the Clutch plugin and sets up the necessary integrations with other plugins
 */
namespace Clutch\WP\Integrations;

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit();
}

require_once __DIR__ . '/plugins/acf/module.php';
require_once __DIR__ . '/plugins/cf7/module.php';
require_once __DIR__ . '/plugins/jetengine/module.php';
require_once __DIR__ . '/plugins/metabox/module.php';
require_once __DIR__ . '/plugins/yoast/module.php';
require_once __DIR__ . '/plugins/slimseo/module.php';
