<?php
/**
 * Plugin Name: Clutch WP
 * Description: A WordPress plugin for integrating Clutch features.
 * Version: 1.0.0
 * Author: Clutch
 * License: GPL2
 */

if (!defined('ABSPATH')) {
	exit();
}

// Define constants
define('CLUTCHWP_PATH', plugin_dir_path(__FILE__));
define('CLUTCHWP_URL', plugin_dir_url(__FILE__));

// Load modules
require_once CLUTCHWP_PATH . 'includes/rest/module.php';
require_once CLUTCHWP_PATH . 'includes/cache/module.php';
require_once CLUTCHWP_PATH . 'includes/settings/module.php';
require_once CLUTCHWP_PATH . 'includes/preview/module.php';
require_once CLUTCHWP_PATH . 'includes/websites/module.php';
require_once CLUTCHWP_PATH . 'includes/integrations/module.php'; // Add this line
