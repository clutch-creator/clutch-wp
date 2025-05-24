<?php
/**
 * This file contains functionality to enhance JetEngine integration with Clutch.
 * It includes functions to format JetEngine values for REST API responses.
 */

namespace Clutch\WP\JetEngine;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Check if JetEngine is installed and active.
 *
 * @return bool True if JetEngine is installed and active, false otherwise.
 */
function is_jetengine_installed()
{
	return class_exists('Jet_Engine');
}

/**
 * Format JetEngine meta fields for REST API response.
 *
 * @param mixed $value The raw value.
 * @param string $key The meta key.
 * @param int $post_id The post ID.
 * @return mixed The formatted value.
 */
function format_jetengine_value_for_rest($field, $value)
{
	$value = maybe_unserialize($value);

	// Handle specific JetEngine field types
	switch ($field['type']) {
		case 'user':
			$user_values = is_array($value) ? $value : [$value];
			$formatted = array_map(function ($user_id) {
				return [
					'_clutch_type' => 'user',
					'id' => $user_id,
				];
			}, $user_values);
			return is_array($value) ? $formatted : $formatted[0];

		case 'post':
		case 'posts':
			$post_ids = is_array($value) ? $value : [$value];
			$formatted = array_map(function ($post_id) {
				$post_id = is_numeric($post_id) ? (int) $post_id : null; // Ensure numeric post_id
				$post_type = $post_id ? get_post_type($post_id) : null;

				return [
					'_clutch_type' => 'post',
					'id' => $post_id,
					'post_type' => $post_type,
				];
			}, $post_ids);
			return is_array($value) ? $formatted : $formatted[0];

		case 'media':
			$media_values = is_array($value) ? $value : [$value];
			$formatted = array_map(function ($media_id) {
				if (!is_numeric($media_id)) {
					return $media_id; // Return raw value if not numeric
				}

				return [
					'_clutch_type' => 'media',
					'id' => (int) $media_id,
				];
			}, $media_values);
			return is_array($value) ? $formatted : $formatted[0];

		case 'repeater':
			$fields = $field['repeater-fields'] ?? [];
			$repeat_items = is_array($value) ? array_values($value) : [];

			$formatted = array_map(function ($item) use ($fields) {
				$formatted_item = [];

				foreach ($fields as $sub_field) {
					$sub_value = $item[$sub_field['name']] ?? null;
					$formatted_item[
						$sub_field['name']
					] = format_jetengine_value_for_rest($sub_field, $sub_value);
				}

				return $formatted_item;
			}, $repeat_items);

			return $formatted;
		default:
			// Default formatting for unsupported field types
			return $value;
	}
}

/**
 * Apply JetEngine meta fields to the REST API response.
 *
 * @param array $response_data The existing response data.
 * @param int $post_id The post ID.
 * @return array The updated response data with JetEngine meta fields.
 */
function apply_jetengine_fields_on_response(
	$response_data,
	$context = 'post_type',
	$type = 'post'
) {
	if (is_jetengine_installed() === false) {
		return $response_data;
	}

	// https://gist.github.com/MjHead/5dac7f0182549b08cfc257d9ea6f14e3
	$fields = jet_engine()->meta_boxes->get_fields_for_context($context, $type);
	$jetengine_fields = [];

	foreach ($fields as $field) {
		if (isset($field['show_in_rest']) && $field['show_in_rest'] === true) {
			$fieldName = $field['name'];
			$value = $response_data['meta'][$fieldName] ?? null;

			unset($response_data['meta'][$fieldName]);

			$jetengine_fields[$field['name']] = format_jetengine_value_for_rest(
				$field,
				$value
			);
		}
	}

	if (empty($jetengine_fields)) {
		return $response_data;
	}

	$response_data['jetengine'] = $jetengine_fields;

	return $response_data;
}
