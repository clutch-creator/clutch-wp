<?php

/**
 * Adds custom Block Styles
 */

namespace Clutch\WP\Blocks;

define('CLUTCH_BLOCK_STYLES_HANDLE', 'clutch-block-styles');
define('CLUTCH_BLOCK_VARIABLES_HANDLE', 'clutch-block-variables');

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Registers Clutch blocks using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function register_clutch_blocks()
{
	// Define the base directory path.
	$base_dir = __DIR__ . '/build';

	// Check if the directory exists.
	if (!is_dir($base_dir)) {
		return;
	}

	// Scan the directory for subdirectories (each representing a block).
	$block_dirs = array_filter(glob($base_dir . '/*'), 'is_dir');

	foreach ($block_dirs as $dir) {
		// Register the block using the block.json file.
		register_block_type($dir);
	}
}

add_action('init', __NAMESPACE__ . '\\register_clutch_blocks');

/*
 * Fetches theme CSS from the frontend and enqueue it in the editor to be used
 * for block styles.
 */
function enqueue_clutch_styles()
{
	// Retrieve the selected host from user meta
	$selected_host = get_user_meta(
		get_current_user_id(),
		'selected_clutch_host',
		true
	);

	$clutch_variables_url = esc_url($selected_host) . '/clutch/variables.css';
	$clutch_classes_url = esc_url($selected_host) . '/clutch/classes.css';

	wp_enqueue_style(
		CLUTCH_BLOCK_VARIABLES_HANDLE,
		$clutch_variables_url,
		[],
		time()
	);
	wp_enqueue_style(
		CLUTCH_BLOCK_STYLES_HANDLE,
		$clutch_classes_url,
		[CLUTCH_BLOCK_VARIABLES_HANDLE],
		time()
	);
}
add_action('enqueue_block_assets', __NAMESPACE__ . '\\enqueue_clutch_styles');

/**
 * Whitelists specific blocks for use in the editor.
 *
 * @return array List of allowed block types.
 */
function whitelist_editor_blocks()
{
	return [
		'core/heading',
		'core/image',
		'core/list',
		'core/list-item',
		'clutch/paragraph',
	];
}

add_filter(
	'allowed_block_types_all',
	__NAMESPACE__ . '\\whitelist_editor_blocks',
	10,
	0
);

/**
 * Recursively formats a list of parsed blocks for easy use within Clutch.
 */
function format_blocks(&$blocks)
{
	foreach ($blocks as $index => &$block) {
		// Format inner blocks recursively
		if ($block['innerBlocks']) {
			format_blocks($block['innerBlocks']);
		}

		// Tag block as Clutch block
		$block['_clutch_type'] = 'block';
		$block['id'] = $index;

		// Initialize block attributes as empty object if not set or empty array
		if (!$block['attrs']) {
			$block['attrs'] = new \stdClass();
		}

		if (
			$block['blockName'] === 'core/image' &&
			isset($block['attrs']['id'])
		) {
			// Tag image as Clutch media
			$block['attrs']['_clutch_type'] = 'media';
		}
	}
}

/**
 * Include RAW post content in REST API response.
 */
function include_raw_post_content($response, $postId)
{
	// Initialize blocks array
	$response['blocks'] = [];

	// Extract blocks from the raw post content
	if (isset($response['content'], $response['content']['raw'])) {
		$response['blocks'] = parse_blocks($response['content']['raw']);
	} else {
		$raw_content = get_post_field('post_content', $postId);
		$response['blocks'] = parse_blocks($raw_content);
	}

	// Format blocks for use within Clutch
	format_blocks($response['blocks']);

	return $response;
}

add_filter(
	'clutch/prepare_post_fields',
	__NAMESPACE__ . '\\include_raw_post_content',
	10,
	2
);
