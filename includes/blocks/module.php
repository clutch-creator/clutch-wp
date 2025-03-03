<?php

/**
 * Adds custom Block Styles
 */

namespace Clutch\WP\Blocks;

if (!defined('ABSPATH')) {
	exit();
}

// register custom post type to store custom block styles
function register_clutch_block_styles_post_type()
{
	register_post_type('clutch_block_styles', [
		'labels' => [
			'name' => __('Clutch Block Styles', 'textdomain'),
			'singular_name' => __('Clutch Block Style', 'textdomain'),
		],
		'public' => false,
		'show_in_rest' => false,
		'supports' => false,
	]);
}

add_action('init', __NAMESPACE__ . '\\register_clutch_block_styles_post_type');

/**
 * Registers Clutch blocks using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function register_clutch_blocks() {
	// Define the base directory path.
	$base_dir = __DIR__ . '/build';

	// Check if the directory exists.
	if ( ! is_dir( $base_dir ) ) {
		return;
	}

	// Scan the directory for subdirectories (each representing a block).
	$block_dirs = array_filter( glob( $base_dir . '/*' ), 'is_dir' );

	foreach ( $block_dirs as $dir ) {
		// Register the block using the block.json file.
		register_block_type($dir);
	}
}

add_action('init', __NAMESPACE__ . '\\register_clutch_blocks');

/**
 * Register custom block styles for clutch/blocks as set in the custom post type
 */
function register_clutch_block_styles()
{
	// @todo: get list of blocks from each block's block.json file
	$clutch_blocks = [
		'clutch/paragraph',
	];
	$block_styles = get_posts([
		'post_type' => 'clutch_block_styles',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	]);

	// @todo optimize by retrieving style_classname and post_content in a single WP_query
	foreach ($block_styles as $block_style) {
		$block_style_classname = get_post_meta(
			$block_style->ID,
			'style_classname',
			true
		);

		register_block_style(
			$clutch_blocks,
			[
				'name' => $block_style_classname,
				'label' => $block_style->post_title,
				'is_default' => false,
				'inline_style' =>
					'.wp-block:is(.'.$block_style_classname . ') { ' . $block_style->post_content . ' }',
			]
		);
	}
}

add_action('init', __NAMESPACE__ . '\\register_clutch_block_styles');

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