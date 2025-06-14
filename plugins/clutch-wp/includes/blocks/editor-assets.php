<?php

/**
 * Clutch Blocks Editor Assets Handler
 *
 * Handles asset enqueueing and URL corrections for the block editor
 */

namespace Clutch\WP\Blocks;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Enqueue Clutch styles for the editor
 */
function enqueue_clutch_styles(): void
{
	// Only enqueue for users who can edit posts.
	if (!current_user_can('edit_posts')) {
		return;
	}

	$selected_host = get_user_meta(
		get_current_user_id(),
		'selected_clutch_host',
		true
	);

	if (empty($selected_host)) {
		return;
	}

	// Validate URL format.
	if (!filter_var($selected_host, FILTER_VALIDATE_URL)) {
		return;
	}

	$clutch_variables_url = esc_url($selected_host) . '/clutch/variables.css';
	$clutch_classes_url = esc_url($selected_host) . '/clutch/classes.css';

	wp_enqueue_style(
		CLUTCH_BLOCK_VARIABLES_HANDLE,
		$clutch_variables_url,
		[],
		time() // Use current time to avoid caching issues
	);
	wp_enqueue_style(
		CLUTCH_BLOCK_STYLES_HANDLE,
		$clutch_classes_url,
		[CLUTCH_BLOCK_VARIABLES_HANDLE],
		time() // Use current time to avoid caching issues
	);
}

add_action('enqueue_block_assets', __NAMESPACE__ . '\\enqueue_clutch_styles');

/**
 * Correct asset URLs for uploads directory
 *
 * @param string $src The source URL of the enqueued asset.
 * @param string $handle The asset's registered handle.
 * @return string Modified or original source URL.
 */
function correct_asset_src_for_uploads_dir(string $src, string $handle): string
{
	// Early return if not a clutch blocks asset.
	if (strpos($src, 'clutch/blocks') === false) {
		return $src;
	}

	// Extract block directory from URL.
	if (!preg_match('#clutch/blocks/([^/]+)#', $src, $matches)) {
		return $src;
	}

	$uploads_dir = wp_upload_dir();

	// Check for upload directory errors.
	if (!empty($uploads_dir['error'])) {
		return $src;
	}

	$base_url = trailingslashit($uploads_dir['baseurl']);

	return $base_url . 'clutch/blocks/' . $matches[1] . '/' . wp_basename($src);
}

add_filter(
	'style_loader_src',
	__NAMESPACE__ . '\\correct_asset_src_for_uploads_dir',
	10,
	2
);
add_filter(
	'script_loader_src',
	__NAMESPACE__ . '\\correct_asset_src_for_uploads_dir',
	10,
	2
);
