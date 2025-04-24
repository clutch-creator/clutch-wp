<?php
/**
 * This file contains functionality to enhance Advanced Custom Fields (ACF) integration with Clutch.
 * It includes filters to format ACF values for REST API responses and ensures proper handling of field groups.
 */

namespace Clutch\WP\MetaBox;

require_once __DIR__ . '/functions.php';

if (!defined('ABSPATH')) {
	exit();
}
