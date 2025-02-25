<?php
/**
 * Plugin Name:       Clutch
 * Description:       Clutch wordpress plugin
 * Plugin URI:        https://clutch.io
 * Author:            Clutch.io
 * Author URI:        https://clutch.io
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       clutch
 *
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires at least: 5.7
 *
 */

defined('WPINC') || exit();

define('CLUTCHWP_FILE', __FILE__);
define('CLUTCHWP_DIR', __DIR__);

define('CLUTCHWP_PRIORITY', 1000);

require_once CLUTCHWP_DIR . '/includes/admin/module.php';
require_once CLUTCHWP_DIR . '/includes/rest/module.php';
require_once CLUTCHWP_DIR . '/includes/acf/module.php';
