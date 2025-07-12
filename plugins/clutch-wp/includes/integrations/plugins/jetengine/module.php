<?php
/**
 * This file contains functionality to enhance JetEngine integration with Clutch.
 * It includes filters to format JetEngine values for REST API responses.
 */

namespace Clutch\WP\JetEngine;

require_once __DIR__ . '/functions.php';

if (!defined('ABSPATH')) {
	exit();
}

add_filter(
	'clutch/prepare_post_fields',
	function ($response_data, $post_id) {
		return apply_jetengine_fields_on_response(
			$response_data,
			'post_type',
			$response_data['type']
		);
	},
	10,
	2
);

add_filter(
	'clutch/prepare_term_fields',
	function ($response_data, $term_id) {
		return apply_jetengine_fields_on_response(
			$response_data,
			'taxonomy',
			$response_data['taxonomy']
		);
	},
	10,
	2
);

// Add combined validation and processing hook for post creation
add_filter(
	'clutch/process_post_fields_before_save',
	function ($processed, $request, $post_id) {
		if (!is_jetengine_installed()) {
			return $processed;
		}

		$jetengine_fields = $request->get_param('jetengine');
		if (empty($jetengine_fields) || !is_array($jetengine_fields)) {
			return $processed;
		}

		$errors = [];
		$post_type = get_post_type($post_id);

		// Get field configurations for validation context
		$fields = \jet_engine()->meta_boxes->get_fields_for_context(
			'post_type',
			$post_type
		);

		foreach ($jetengine_fields as $field_key => $field_value) {
			$field_config = null;

			// Find the field configuration
			foreach ($fields as $field) {
				if ($field['name'] === $field_key) {
					$field_config = $field;
					break;
				}
			}

			// Basic required field validation
			if (
				$field_config &&
				!empty($field_config['is_required']) &&
				empty($field_value)
			) {
				$errors[] = sprintf(
					__('JetEngine field "%s" is required.', 'textdomain'),
					$field_config['title'] ?? $field_key
				);
				continue;
			}

			// Use standard WordPress meta update which JetEngine relies on
			// JetEngine hooks into this to handle field-specific validation and processing
			$result = update_post_meta($post_id, $field_key, $field_value);

			// Check if the update was successful
			if ($result !== false) {
				$processed['jetengine'][$field_key] = $field_value;
			} else {
				$field_name = $field_config['title'] ?? $field_key;
				$errors[] = sprintf(
					__(
						'JetEngine field "%s" could not be saved. Please check the field value.',
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
