<?php
/**
 * This file contains functionality to enhance Meta Box integration with Clutch.
 * It includes functions to format Meta Box values for REST API responses.
 */

namespace Clutch\WP\MetaBox;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Check if Meta Box is installed and active.
 *
 * @return bool True if Meta Box is installed and active, false otherwise.
 */
function is_metabox_installed()
{
	return class_exists('RWMB_Loader') && function_exists('rwmb_meta');
}

/**
 * Format Meta Box values for REST API response based on field types.
 *
 * @param mixed $value The raw value.
 * @param string $key The meta key.
 * @param int $post_id The post ID.
 * @return mixed The formatted value.
 */
function format_metabox_value_for_rest(
	$field,
	$key,
	$post_id,
	$object_type = 'post'
) {
	if ($object_type === 'user') {
		$value = get_user_meta($post_id, $field['id']);
	} elseif ($object_type === 'term') {
		$value = get_term_meta($post_id, $field['id']);
	} elseif ($object_type === 'comment') {
		$value = get_comment_meta($post_id, $field['id']);
	} else {
		$value = get_post_meta($post_id, $field['id']);
	}

	if (!$field) {
		// Default formatting if the field settings are not available
		return $value;
	}

	// Handle specific Meta Box field types
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

		case 'taxonomy':
			$taxonomy_values = is_array($value) ? $value : [$value];
			$formatted = array_map(function ($term_id) use ($field) {
				return [
					'_clutch_type' => 'taxonomy_term',
					'id' => $term_id,
					'taxonomy' => $field['taxonomy'] ?? null,
				];
			}, $taxonomy_values);
			return is_array($value) ? $formatted : $formatted[0];

		case 'post':
		case 'post_advanced':
			$post_ids = is_array($value) ? $value : [$value];
			$formatted = array_map(function ($post_id) {
				$post_type = get_post_type($post_id);
				$post_type_object = get_post_type_object($post_type);

				return [
					'_clutch_type' => 'post',
					'id' => $post_id,
					'post_type' => $post_type,
				];
			}, $post_ids);
			return is_array($value) ? $formatted : $formatted[0];

		case 'image':
		case 'single_image':
		case 'file':
		case 'media':
		case 'image_advanced':
			$media_values = is_array($value) ? $value : [$value];

			$formatted = array_map(function ($media_id) {
				return [
					'_clutch_type' => 'media',
					'id' => $media_id,
				];
			}, $media_values);

			if (!is_array($value)) {
				// If the original value was not an array, return the first formatted item
				return $formatted[0];
			}

			return $formatted;

		case 'wysiwyg': // Handle WYSIWYG fields
			return rwmb_get_value($key, '', $post_id);

		default:
			// Default formatting for unsupported field types
			return $value;
	}
}

/**
 * Get Meta Box fields for REST API response with formatting.
 *
 * @param int $post_id The post ID.
 * @return array The formatted Meta Box fields.
 */
function get_metabox_fields_for_rest($post_id, $object_type = 'post')
{
	if (is_metabox_installed() === false) {
		return [];
	}

	$raw_meta_box_fields = rwmb_get_object_fields($post_id, $object_type) ?: [];
	$formatted_meta_box_fields = [];

	foreach ($raw_meta_box_fields as $key => $field) {
		$formatted_meta_box_fields[$key] = format_metabox_value_for_rest(
			$field,
			$key,
			$post_id,
			$object_type
		);
	}

	return $formatted_meta_box_fields;
}

/**
 * Apply Meta Box fields to the REST API response.
 *
 * @param array $response_data The existing response data.
 * @param int $post_id The post ID.
 * @return array The updated response data with Meta Box fields.
 */
function apply_metabox_fields_on_response(
	$response_data,
	$post_id,
	$object_type = 'post'
) {
	if (is_metabox_installed() === false) {
		return $response_data;
	}

	$meta_box_fields = get_metabox_fields_for_rest($post_id, $object_type);

	if (empty($meta_box_fields)) {
		return $response_data;
	}

	$response_data['meta_box'] = $meta_box_fields;

	return $response_data;
}
