<?php

/**
 * Clutch Blocks Attributes Handler
 *
 * Handles mapping of component properties to block attributes
 */

namespace Clutch\WP\Blocks;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Maps component properties to block attributes
 *
 * @param array $component The component array
 * @return array Formatted attributes array
 */
function map_properties_to_attributes(array $component): array
{
	$attributes = [];

	// Process variants
	if (!empty($component['variants'])) {
		$attributes = array_merge(
			$attributes,
			process_variants($component['variants'])
		);
	}

	// Process slots
	if (!empty($component['slots'])) {
		$attributes = array_merge(
			$attributes,
			process_slots($component['slots'])
		);
	}

	// Process properties
	if (!empty($component['properties'])) {
		$attributes = array_merge(
			$attributes,
			process_properties($component['properties'])
		);
	}

	return $attributes;
}

/**
 * Process component variants into attributes
 *
 * @param array $variants Array of variant configurations.
 * @return array Processed variant attributes.
 */
function process_variants(array $variants): array
{
	$attributes = [];

	foreach ($variants as $variant) {
		// Validate variant structure.
		if (
			empty($variant['name']) ||
			empty($variant['options']) ||
			!is_array($variant['options'])
		) {
			continue;
		}

		$attributes[$variant['name']] = [
			'clutch' => 'VARIANT',
			'default' => $variant['options'][0],
			'enum' => $variant['options'],
		];
	}

	return $attributes;
}

/**
 * Process component slots into attributes
 *
 * @param array $slots Array of slot configurations.
 * @return array Processed slot attributes.
 */
function process_slots(array $slots): array
{
	$attributes = [];

	foreach ($slots as $slot) {
		if (empty($slot['name'])) {
			continue;
		}

		$attributes[$slot['name']] = [
			'clutch' => 'SLOT',
			'type' => 'null',
		];
	}

	return $attributes;
}

/**
 * Process component properties into attributes
 *
 * @param array $properties Array of property configurations.
 * @return array Processed property attributes.
 */
function process_properties(array $properties): array
{
	$attributes = [];

	foreach ($properties as $property) {
		if (empty($property['control']) || empty($property['name'])) {
			continue;
		}

		$attribute = ['clutch' => 'PROPERTY'];

		// Set default value if available.
		if (!empty($property['controlProps']['defaultValue'])) {
			$attribute['default'] = $property['controlProps']['defaultValue'];
		}

		// Process control type.
		$attribute = array_merge(
			$attribute,
			get_control_type_config($property)
		);

		$attributes[$property['name']] = $attribute;

		// Add media ID attributes for media controls.
		if ($property['control'] === 'Core/Media') {
			$attributes = array_merge(
				$attributes,
				create_media_id_attributes($property['name'])
			);
		}
	}

	return $attributes;
}

/**
 * Get configuration for different control types
 *
 * @param array $property Property configuration array.
 * @return array Control type configuration.
 */
function get_control_type_config(array $property): array
{
	$config = [];

	// Validate control type exists.
	if (empty($property['control'])) {
		return $config;
	}

	switch ($property['control']) {
		case 'Core/Input':
		case 'Core/Units':
		case 'Core/RichText':
		case 'Core/MarkupText':
		case 'Core/Textarea':
		case 'Core/Media':
			$config['type'] = 'string';
			if ($property['control'] === 'Core/Media') {
				$config['media'] = true;
			}
			break;

		case 'Core/Number':
			$config['type'] = 'number';
			break;

		case 'Core/Combobox':
		case 'Core/Select':
			$config['enum'] = extract_select_options($property);
			break;

		case 'Core/Checkbox':
			$config['type'] = 'boolean';
			break;

		case 'Core/Array':
			$config['type'] = 'array';
			break;
	}

	return $config;
}

/**
 * Extract options from select/combobox controls
 *
 * @param array $property Property configuration array.
 * @return array Array of select options.
 */
function extract_select_options(array $property): array
{
	$options = [];

	if (!is_array($property['controlProps']['options']['values'])) {
		return $options;
	}

	foreach ($property['controlProps']['options']['values'] as $option) {
		if (isset($option['control'], $option['value'])) {
			$options[] = $option['value'];
		}
	}

	return $options;
}

/**
 * Create media ID attributes for media controls
 *
 * @param string $property_name The property name to create media attributes for.
 * @return array Array of media ID attributes.
 */
function create_media_id_attributes(string $property_name): array
{
	$media_id_key = $property_name . '_media_id';

	return [
		$media_id_key => [
			'clutch' => 'MEDIA_ID',
			'type' => 'number',
			'default' => 0,
			'media' => true,
			'mediaType' => 'image',
			'mediaSize' => 'full',
			'mediaMimeTypes' => [
				'image/jpeg',
				'image/png',
				'image/gif',
				'image/webp',
			],
		],
	];
}
