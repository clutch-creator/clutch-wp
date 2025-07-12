<?php
/**
 * This file contains functionality to enhance Advanced Custom Fields (ACF) integration with Clutch.
 * It includes filters to format ACF values for REST API responses and ensures proper handling of field groups.
 */

namespace Clutch\WP\MetaBox;

require_once __DIR__ . '/functions.php';

if (!defined('ABSPATH')) {
	exit();
}

add_filter(
	'clutch/prepare_post_fields',
	__NAMESPACE__ . '\\apply_metabox_fields_on_response',
	10,
	2
);

add_filter(
	'clutch/prepare_term_fields',
	function ($response_data, $term_id) {
		return apply_metabox_fields_on_response(
			$response_data,
			$term_id,
			'term'
		);
	},
	10,
	2
);

// Add combined validation and processing hook for post creation
add_filter(
	'clutch/process_post_fields_before_save',
	function ($processed, $request, $post_id) {
		if (!is_metabox_installed()) {
			return $processed;
		}

		$metabox_fields = $request->get_param('meta_box');
		if (empty($metabox_fields) || !is_array($metabox_fields)) {
			return $processed;
		}

		$errors = [];

		// Get field configurations for validation context
		$field_configs = \rwmb_get_object_fields($post_id, 'post') ?: [];

		foreach ($metabox_fields as $field_key => $field_value) {
			$field_config = $field_configs[$field_key] ?? null;

			// Basic required field validation since MetaBox doesn't always handle this in update
			if (
				$field_config &&
				!empty($field_config['required']) &&
				empty($field_value)
			) {
				$errors[] = sprintf(
					__('Meta Box field "%s" is required.', 'textdomain'),
					$field_config['name'] ?? $field_key
				);
				continue;
			}

			// Use standard WordPress meta update which MetaBox relies on
			// MetaBox hooks into this to handle field-specific validation and processing
			$result = update_post_meta($post_id, $field_key, $field_value);

			// Check if the update was successful
			if ($result !== false) {
				$processed['meta_box'][$field_key] = $field_value;
			} else {
				$field_name = $field_config['name'] ?? $field_key;
				$errors[] = sprintf(
					__(
						'Meta Box field "%s" could not be saved. Please check the field value.',
						'textdomain'
					),
					$field_name
				);
			}
		}

		// If there were validation errors, add them to the processed array
		if (!empty($errors)) {
			$processed['_errors'] = array_merge(
				$processed['_errors'] ?? [],
				$errors
			);
		}

		return $processed;
	},
	10,
	3
);
