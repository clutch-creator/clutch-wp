<?php
/**
 * This file defines custom REST API endpoints for Clutch.
 * It includes endpoints for retrieving plugin info, post types, taxonomies, and clearing cache.
 *
 * @package Clutch\WP\Rest
 */

namespace Clutch\WP\Rest;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Include helper functions for REST API.
 */
require_once __DIR__ . '/functions.php';

/**
 * Include REST API routes for plugin information.
 */
require_once __DIR__ . '/routes/infos.php';

/**
 * Include REST API routes for Gutenberg blocks.
 */
require_once __DIR__ . '/routes/blocks.php';

/**
 * Include REST API routes for cache management.
 */
require_once __DIR__ . '/routes/cache.php';

/**
 * Include REST API routes for posts.
 */
require_once __DIR__ . '/routes/posts.php';

/**
 * Include REST API routes for taxonomies.
 */
require_once __DIR__ . '/routes/taxonomies.php';
