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

/**
 * Integrations module for Clutch
 */

// Load core SEO functionality
require_once __DIR__ . '/seo.php';

// Load plugin integrations
function load_plugin_integrations()
{
	// Yoast SEO integration
	if (defined('WPSEO_VERSION')) {
		require_once __DIR__ . '/plugins/yoast.php';
	}

	// SlimSEO integration
	if (class_exists('SlimSEO\\Plugin')) {
		require_once __DIR__ . '/plugins/slimseo.php';
	}
}

add_action('plugins_loaded', __NAMESPACE__ . '\\load_plugin_integrations');
