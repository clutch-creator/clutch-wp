<?php
/**
 * Plugin Name:       Clutch
 * Description:       Integrate WordPress headlessly with Clutch, the next-gen Visual Builder. Empower creative professionals with total design freedom, advanced functionality, and top-tier performance—all with fewer plugins.
 * Author:            Clutch.io
 * Author URI:        https://clutch.io
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Version:           2.1.2
 * Requires PHP:      7.4
 * Requires at least: 5.7
 *
 */

defined('WPINC') || exit();

define('CLUTCHWP_FILE', __FILE__);
define('CLUTCHWP_DIR', __DIR__);
define('CLUTCHWP_URL', plugin_dir_url(__FILE__));

define('CLUTCHWP_PRIORITY', 1000);

require_once CLUTCHWP_DIR . '/includes/acf/module.php';
require_once CLUTCHWP_DIR . '/includes/metabox/module.php';
require_once CLUTCHWP_DIR . '/includes/admin/module.php';
require_once CLUTCHWP_DIR . '/includes/auth/module.php';
require_once CLUTCHWP_DIR . '/includes/blocks/module.php';
require_once CLUTCHWP_DIR . '/includes/preview/module.php';
require_once CLUTCHWP_DIR . '/includes/rest/module.php';
require_once CLUTCHWP_DIR . '/includes/menus/module.php';
require_once CLUTCHWP_DIR . '/includes/settings/module.php';
require_once CLUTCHWP_DIR . '/includes/cache/module.php';
require_once CLUTCHWP_DIR . '/includes/permalinks/module.php';
require_once CLUTCHWP_DIR . '/includes/websites/module.php';
require_once CLUTCHWP_DIR . '/includes/cf7/module.php';
