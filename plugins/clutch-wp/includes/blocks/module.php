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

function map_properties_to_attributes(&$component)
{
	$attributes = new \stdClass();
	$properties = $component->properties ?? [];
	$variants = $component->variants ?? [];
	$slots = $component->slots ?? [];

	if (isset($variants) && is_array($variants)) {
		foreach ($variants as $variant) {
			if (
				isset($variant->name) &&
				is_array($variant->options) &&
				!empty($variant->options)
			) {
				// Create a new attribute for the variant.
				$attribute = new \stdClass();
				$attribute->clutch = 'VARIANT';
				$attribute->default = $variant->options[0];

				// Add options to the enum.
				$attribute->enum = $variant->options;

				// Add the variant attribute to the attributes object.
				$attributes->{$variant->name} = $attribute;
			}
		}
	}

	if (isset($slots) && is_array($slots)) {
		foreach ($slots as $slot) {
			if (isset($slot->name)) {
				// Create a new attribute for the slot.
				$attribute = new \stdClass();
				$attribute->clutch = 'SLOT';
				$attribute->type = 'null';

				// Add the slot attribute to the attributes object.
				$attributes->{$slot->name} = $attribute;
			}
		}
	}

	if (isset($properties) && is_array($properties)) {
		foreach ($properties as $property) {
			if (isset($property->control)) {
				$attribute = new \stdClass();
				$attribute->clutch = 'PROPERTY';

				if (
					isset(
						$property->controlProps,
						$property->controlProps->defaultValue
					)
				) {
					$attribute->default = $property->controlProps->defaultValue;
				}

				switch ($property->control) {
					case 'Core/Input':
					case 'Core/Units':
					case 'Core/RichText':
					case 'Core/MarkupText':
					case 'Core/Textarea':
						$attribute->type = 'string';
						break;
					case 'Core/Number':
						$attribute->type = 'number';
						break;
					case 'Core/Combobox':
					case 'Core/Select':
						$attribute->enum = [];
						if (
							isset(
								$property->controlProps,
								$property->controlProps->options,
								$property->controlProps->options->values
							)
						) {
							foreach (
								$property->controlProps->options->values
								as $option
							) {
								if (isset($option->control, $option->value)) {
									$attribute->enum[] = $option->value;
								}
							}
						}
						break;
					case 'Core/Checkbox':
						$attribute->type = 'boolean';
						break;
					case 'Core/Media':
						$attribute->type = 'string';
						$attribute->media = true;
						// Add media ID fields for internal tracking.
						$attributes->{$property->name .
							'_media_id'} = new \stdClass();
						$attributes->{$property->name . '_media_id'}->clutch =
							'MEDIA_ID';
						$attributes->{$property->name . '_media_id'}->type =
							'number';
						$attributes->{$property->name .
							'_media_id'}->default = 0;
						$attributes->{$property->name .
							'_media_id'}->media = true;
						$attributes->{$property->name .
							'_media_id'}->mediaType = 'image';
						$attributes->{$property->name .
							'_media_id'}->mediaSize = 'full';
						$attributes->{$property->name .
							'_media_id'}->mediaMimeTypes = [
							'image/jpeg',
							'image/png',
							'image/gif',
							'image/webp',
						];
						break;
					case 'Core/Array':
						// Assume an array of strings for simplicity.
						$attribute->type = 'array';
						break;
					default:
						break;
				}
			}

			// Ensure the property has a name and control.
			if (isset($property->name)) {
				$attributes->{$property->name} = $attribute;
			}
		}
	}

	return $attributes;
}

/**
 * Registers all blocks in a given directory.
 *
 * @param string $path The path to the directory containing block directories.
 */

function register_blocks_in_directory($path)
{
	$directory = trailingslashit($path);

	// Check if the directory exists.
	if (!is_dir($directory)) {
		return;
	}

	// Scan the directory for subdirectories (each representing a block).
	$block_dirs = array_filter(glob($directory . '*'), 'is_dir');

	foreach ($block_dirs as $dir) {
		// Register the block using the block.json file.
		register_block_type($dir);
	}
}

/**
 * Registers Clutch blocks using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function register_clutch_primitive_blocks()
{
	// Define the base directory path.
	$primitives_dir = __DIR__ . '/assets/primitives';

	register_blocks_in_directory($primitives_dir);
}

add_action('init', __NAMESPACE__ . '\\register_clutch_primitive_blocks');

/**
 * Registers Clutch blocks using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function register_clutch_component_blocks()
{
	// Define the base directory for base assets.
	$base_assets_dir = trailingslashit(__DIR__ . '/assets/clutch-gen');

	// Define the directory where components will be stored.
	$upload_dir = wp_upload_dir();
	$blocks_destination_dir =
		trailingslashit($upload_dir['basedir']) . 'clutch/blocks';

	// Retrieve the selected host from user meta
	$selected_host = get_user_meta(
		get_current_user_id(),
		'selected_clutch_host',
		true
	);

	if (!$selected_host) {
		return;
	}

	// Fetch components from remote json file
	$json_url = esc_url($selected_host) . '/clutch/components.json';
	$response = wp_remote_get($json_url);
	$json_content = null;

	if (!is_wp_error($response)) {
		$json_content = wp_remote_retrieve_body($response);
	}

	if ($json_content) {
		// Decode the JSON content
		$components = json_decode($json_content);

		foreach ($components as $component_id => $component) {
			// Ensure block name follows the WordPress naming convention.
			$unique_id = 'composition-' . str_replace('_', '-', $component_id);

			// Define the path to the block directory.
			$block_dir = trailingslashit($blocks_destination_dir) . $unique_id;

			// Create the directory if it doesn't exist and copy base assets.
			if (!is_dir($block_dir)) {
				mkdir($block_dir, 0755, true);
			}

			// Copy the base assets from the build directory to the block directory.
			$base_assets = glob($base_assets_dir . '*');
			foreach ($base_assets as $asset) {
				$dest = trailingslashit($block_dir) . basename($asset);
				copy($asset, $dest);
			}

			// Modify the block.json file with the component data.
			$block_json_path = trailingslashit($block_dir) . 'block.json';
			if (file_exists($block_json_path)) {
				$block_json = json_decode(
					file_get_contents($block_json_path),
					true
				);

				if ($block_json) {
					// Update the block name and title.
					$block_json['name'] = 'clutch/' . $unique_id;
					$block_json['title'] = $component->name;

					// Ensure attributes is an object even if empty.
					$block_json['attributes'] = new \stdClass();

					// Map properties to attributes object.
					$block_json['attributes'] = map_properties_to_attributes(
						$component
					);

					// Save the updated block.json file.
					file_put_contents(
						$block_json_path,
						json_encode(
							$block_json,
							JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
						)
					);
				}

				// Modify the block's index.js file to include the component data.
				$index_js_path = trailingslashit($block_dir) . 'index.js';
				if (file_exists($index_js_path)) {
					$index_js_content = file_get_contents($index_js_path);

					// Replace the placeholder with the component data.
					$index_js_content = str_replace(
						'CLUTCH_BLOCK_NAME',
						$block_json['name'],
						$index_js_content
					);
					$index_js_content = str_replace(
						'CLUTCH_BLOCK_TITLE',
						$block_json['title'],
						$index_js_content
					);

					// Save the modified index.js file.
					file_put_contents($index_js_path, $index_js_content);
				}
			}
		}
	}

	// Register all new and existing blocks in the components directory.
	register_blocks_in_directory($blocks_destination_dir);
}

add_action('init', __NAMESPACE__ . '\\register_clutch_component_blocks');

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

	if (!$selected_host) {
		return;
	}

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
	$registered_block_types = \WP_Block_Type_Registry::get_instance()->get_all_registered();

	$allowed_blocks = [
		'core/heading',
		'core/image',
		'core/list',
		'core/list-item',
	];

	// Add all Clutch blocks to the allowed blocks list.
	foreach ($registered_block_types as $block_name => $block_type) {
		if (strpos($block_name, 'clutch/') === 0) {
			$allowed_blocks[] = $block_name;
		}
	}

	// Merge allowed core blocks with Clutch blocks.
	return $allowed_blocks;
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
	foreach ($blocks as &$block) {
		// Format inner blocks recursively
		if (!empty($block['innerBlocks'])) {
			format_blocks($block['innerBlocks']);
			$parsed_inner_blocks = [];

			foreach ($block['innerBlocks'] as &$innerBlock) {
				if (
					$innerBlock['blockName'] !== 'clutch/slot' ||
					empty($innerBlock['innerBlocks'])
				) {
					// If the inner block is not a slot, just add it to the block's inner blocks.
					$parsed_inner_blocks[] = $innerBlock;
				} else {
					if (!is_array($innerBlock['attrs'])) {
						$innerBlock['attrs'] = [];
					}

					// If the inner block is a slot, extract its name and assign inner blocks to it.
					$slot_name = $innerBlock['attrs']['name'] ?: 'children';
					$block['attrs'][$slot_name] = $innerBlock['innerBlocks'];
				}
			}

			$block['innerBlocks'] = $parsed_inner_blocks;
		}

		// Tag block as Clutch block
		$block['_clutch_type'] = 'block';

		if (
			$block['blockName'] === 'core/image' &&
			isset($block['attrs']['id'])
		) {
			// Tag image as Clutch media
			$block['attrs']['_clutch_type'] = 'media';
		}

		// Initialize block attributes as empty object if not set or empty array
		if (!$block['attrs']) {
			$block['attrs'] = new \stdClass();
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

/**
 * Modify the source URL for enqueued assets stored in the uploads directory.
 *
 * Filters the source URL of specific enqueued styles and scripts to correct their paths,
 * focusing on assets that include "clutch/blocks" in their URL.
 *
 * @param string $src    The source URL of the enqueued asset.
 * @param string $handle The asset's registered handle.
 *
 * @return string        Modified or original source URL.
 *
 * @see https://github.com/WordPress/wordpress-develop/blob/6.3/src/wp-includes/blocks.php#L149-L165C3
 */
function correct_asset_src_for_uploads_dir($src, $handle)
{
	// Check for the presence of "clutch/blocks" in the src.
	if (strpos($src, 'clutch/blocks') !== false) {
		// Extract the specific block directory.
		preg_match('#clutch/blocks/([^/]+)#', $src, $matches);

		if (isset($matches[1])) {
			$uploads_dir = wp_upload_dir();
			$base_url = trailingslashit($uploads_dir['baseurl']);

			$correct_src =
				$base_url .
				'clutch/blocks/' .
				$matches[1] .
				'/' .
				wp_basename($src);
			return $correct_src;
		}
	}

	return $src;
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
