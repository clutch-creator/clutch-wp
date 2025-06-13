<?php

/**
 * Clutch Blocks Registration Handler
 *
 * Handles registration of blocks and remote component processing
 */

namespace Clutch\WP\Blocks;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Registers all blocks in a directory
 *
 * @param string $path The path to the directory containing block directories.
 */
function register_blocks_in_directory(string $path): void
{
	$directory = trailingslashit($path);

	if (!is_dir($directory)) {
		return;
	}

	$block_dirs = array_filter(glob($directory . '*'), 'is_dir');

	if (empty($block_dirs)) {
		return;
	}

	foreach ($block_dirs as $dir) {
		register_block_type($dir);
	}
}

/**
 * Registers Clutch primitive blocks
 */
function register_clutch_primitive_blocks(): void
{
	$primitives_dir = __DIR__ . '/assets/primitives';
	register_blocks_in_directory($primitives_dir);
}

add_action('init', __NAMESPACE__ . '\\register_clutch_primitive_blocks');

/**
 * Registers Clutch component blocks from remote source
 */
function register_clutch_component_blocks(): void
{
	// Check user permissions.
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

	$components = fetch_remote_components($selected_host);

	if (empty($components)) {
		return;
	}

	$base_assets_dir = trailingslashit(__DIR__ . '/assets/clutch-gen');
	$upload_dir = wp_upload_dir();

	// Check for upload directory errors.
	if (!empty($upload_dir['error'])) {
		return;
	}

	$blocks_destination_dir =
		trailingslashit($upload_dir['basedir']) . 'clutch/blocks';

	process_components($components, $base_assets_dir, $blocks_destination_dir);
	register_blocks_in_directory($blocks_destination_dir);
}

add_action('init', __NAMESPACE__ . '\\register_clutch_component_blocks');

/**
 * Fetch components from remote JSON source
 *
 * @param string $host The host URL to fetch components from.
 * @return array|null Array of components or null on failure.
 */
function fetch_remote_components(string $host): ?array
{
	// Validate URL format.
	if (!filter_var($host, FILTER_VALIDATE_URL)) {
		return null;
	}

	$json_url = esc_url($host) . '/clutch/components.json';

	$response = wp_remote_get($json_url, [
		'timeout' => 2,
	]);

	if (is_wp_error($response)) {
		return null;
	}

	$response_code = wp_remote_retrieve_response_code($response);
	if ($response_code !== 200) {
		return null;
	}

	$json_content = wp_remote_retrieve_body($response);

	if (empty($json_content)) {
		return null;
	}

	$components = json_decode($json_content, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		return null;
	}

	return $components;
}

/**
 * Process components and create block files
 *
 * @param array  $components Array of component data.
 * @param string $base_assets_dir Base assets directory path.
 * @param string $blocks_destination_dir Destination directory for blocks.
 */
function process_components(
	array $components,
	string $base_assets_dir,
	string $blocks_destination_dir
): void {
	foreach ($components as $component_id => $component) {
		// Validate component structure.
		if (!is_array($component) || empty($component['name'])) {
			continue;
		}

		// Sanitize component ID.
		$sanitized_id = sanitize_key($component_id);
		if (empty($sanitized_id)) {
			continue;
		}

		$unique_id = 'composition-' . str_replace('_', '-', $sanitized_id);
		$block_dir = trailingslashit($blocks_destination_dir) . $unique_id;

		create_block_directory($block_dir, $base_assets_dir);
		update_block_files($block_dir, $unique_id, $component);
	}
}

/**
 * Create block directory and copy base assets
 *
 * @param string $block_dir The block directory path.
 * @param string $base_assets_dir The base assets directory path.
 */
function create_block_directory(
	string $block_dir,
	string $base_assets_dir
): void {
	if (!wp_mkdir_p($block_dir)) {
		return;
	}

	$base_assets = glob($base_assets_dir . '*');

	if (empty($base_assets)) {
		return;
	}

	foreach ($base_assets as $asset) {
		$dest = trailingslashit($block_dir) . basename($asset);

		copy($asset, $dest);
	}
}

/**
 * Update block JSON and JS files
 */
function update_block_files(
	string $block_dir,
	string $unique_id,
	array $component
): void {
	update_block_json($block_dir, $unique_id, $component);
	update_block_js($block_dir, $unique_id, $component);
}

/**
 * Update block.json file with component data
 *
 * @param string $block_dir The block directory path.
 * @param string $unique_id The unique block ID.
 * @param array  $component The component array.
 */
function update_block_json(
	string $block_dir,
	string $unique_id,
	array $component
): void {
	$block_json_path = trailingslashit($block_dir) . 'block.json';

	// Bail early if block.json doesn't exist.
	if (!file_exists($block_json_path)) {
		return;
	}

	// Get the block.json content.
	$block_json_content = file_get_contents($block_json_path);
	if ($block_json_content === false) {
		return;
	}

	// Read and decode the existing block.json file.
	$block_json = json_decode($block_json_content, true);

	// Bail early if JSON is invalid or empty.
	if (!$block_json || json_last_error() !== JSON_ERROR_NONE) {
		return;
	}

	// Validate required component data.
	if (empty($component['name'])) {
		return;
	}

	// Update block metadata with component information.
	$block_json['name'] = 'clutch/' . $unique_id;
	$block_json['title'] = sanitize_text_field($component['name']);
	$block_json['attributes'] = map_properties_to_attributes($component);

	// Write the updated block.json back to file with pretty formatting.
	$json_output = wp_json_encode(
		$block_json,
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	);

	if ($json_output === false) {
		return;
	}

	file_put_contents($block_json_path, $json_output);
}

/**
 * Update block JS file
 *
 * @param string $block_dir The block directory path.
 * @param string $unique_id The unique block ID.
 * @param array $component The component array.
 */
function update_block_js(
	string $block_dir,
	string $unique_id,
	array $component
): void {
	$index_js_path = trailingslashit($block_dir) . 'index.js';

	// Bail early if index.js doesn't exist.
	if (!file_exists($index_js_path)) {
		return;
	}

	// Validate component name.
	if (empty($component['name'])) {
		return;
	}

	// Read the JS file content.
	$index_js_content = file_get_contents($index_js_path);

	if ($index_js_content === false) {
		return;
	}

	// Replace placeholders with actual block data.
	$index_js_content = str_replace(
		['CLUTCH_BLOCK_NAME', 'CLUTCH_BLOCK_TITLE'],
		['clutch/' . $unique_id, sanitize_text_field($component['name'])],
		$index_js_content
	);

	// Write the updated JS file back.
	file_put_contents($index_js_path, $index_js_content);
}

/**
 * Whitelist allowed blocks for the editor
 *
 * @return array List of allowed block types.
 */
function whitelist_editor_blocks(): array
{
	static $allowed_blocks = null;

	if ($allowed_blocks !== null) {
		return $allowed_blocks;
	}

	$registered_block_types = \WP_Block_Type_Registry::get_instance()->get_all_registered();

	$allowed_blocks = [
		'core/heading',
		'core/image',
		'core/list',
		'core/list-item',
	];

	foreach ($registered_block_types as $block_name => $block_type) {
		if (strpos($block_name, 'clutch/') === 0) {
			$allowed_blocks[] = $block_name;
		}
	}

	return $allowed_blocks;
}

add_filter(
	'allowed_block_types_all',
	__NAMESPACE__ . '\\whitelist_editor_blocks',
	10,
	0
);
