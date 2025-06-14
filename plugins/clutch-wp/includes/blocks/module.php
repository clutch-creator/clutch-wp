<?php

/**
 * Clutch Blocks Module
 *
 * Handles registration and management of Clutch blocks
 */

namespace Clutch\WP\Blocks;

if (!defined('ABSPATH')) {
	exit();
}

// Constants
const CLUTCH_BLOCK_STYLES_HANDLE = 'clutch-block-styles';
const CLUTCH_BLOCK_VARIABLES_HANDLE = 'clutch-block-variables';

/**
 * Component properties to block attributes mapping.
 */
require_once __DIR__ . '/attributes.php';

/**
 * Block registration and remote component processing.
 */
require_once __DIR__ . '/registration.php';

/**
 * Block formatting and processing for Clutch consumption.
 */
require_once __DIR__ . '/formatting.php';

/**
 * Asset enqueueing and URL corrections.
 */
require_once __DIR__ . '/editor-assets.php';
