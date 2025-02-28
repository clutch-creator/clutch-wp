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

// register custom block styles as set in the custom post type
function register_clutch_block_styles()
{
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
			['core/paragraph', 'core/heading'],
			[
				'name' => $block_style_classname,
				'label' => $block_style->post_title,
				'is_default' => false,
				'inline_style' =>
					'.wp-block:is(.is-style-' .
					$block_style_classname .
					') { ' .
					$block_style->post_content .
					' }',
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
		'core/paragraph',
	];
}

add_filter(
	'allowed_block_types_all',
	__NAMESPACE__ . '\\whitelist_editor_blocks',
	10,
	0
);

function disabled_block_settings($metadata)
{
	// if (isset($metadata['supports'])) {
	// 	$metadata['supports'] = ['customClassName' => false];
	// }

	// if (isset($metadata['attributes'])) {
	// 	$metadata['attributes'] = [];
	// }

	return $metadata;
}
add_filter('block_type_metadata', __NAMESPACE__ . '\\disabled_block_settings');

function disable_editor_settings($editor_settings)
{
	// $editor_settings['alignWide'] = false;
	// $editor_settings['disableCustomColors'] = true;
	// $editor_settings['disableCustomFontSizes'] = true;
	// $editor_settings['disableCustomGradients'] = true;
	// $editor_settings['enableCustomLineHeight'] = false;
	// $editor_settings['enableCustomSpacing'] = false;
	// $editor_settings['__experimentalFeatures']['typography']['dropCap'] = false;
	// $editor_settings['__experimentalFeatures']['typography']['textAlign'] = false;

	return $editor_settings;
}
add_filter(
	'block_editor_settings',
	__NAMESPACE__ . '\\disable_editor_settings'
);
