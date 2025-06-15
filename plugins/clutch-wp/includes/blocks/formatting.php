<?php

/**
 * Clutch Blocks Formatting Handler
 *
 * Handles formatting and processing of blocks for Clutch consumption
 */

namespace Clutch\WP\Blocks;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Format blocks for Clutch consumption
 *
 * @param array $blocks Array of blocks to format (passed by reference).
 */
function format_blocks(array &$blocks): void
{
	foreach ($blocks as &$block) {
		// Ensure block has required structure.
		if (!is_array($block)) {
			continue;
		}

		// Mark as a Clutch block.
		$block['_clutch_type'] = 'block';

		// Ensure attributes are always returned as an object.
		if (!isset($block['attrs']) || !is_object($block['attrs'])) {
			$block['attrs'] = new \stdClass();
		}

		// Validate block name exists.
		if (empty($block['blockName'])) {
			continue;
		}

		if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
			format_blocks($block['innerBlocks']);
			$block['innerBlocks'] = process_slot_blocks($block);
		}

		if (
			$block['blockName'] === 'core/image' &&
			isset($block['attrs']['id'])
		) {
			// Ensure attrs is an array before setting media type.
			if (!is_array($block['attrs'])) {
				$block['attrs'] = [];
			}
			$block['attrs']['_clutch_type'] = 'media';
		}
	}
}

/**
 * Process slot blocks and extract them to attributes
 *
 * @param array $block The block array (passed by reference).
 * @return array Array of parsed inner blocks.
 */
function process_slot_blocks(array &$block): array
{
	$parsed_inner_blocks = [];

	// Ensure innerBlocks exists and is an array.
	if (empty($block['innerBlocks']) || !is_array($block['innerBlocks'])) {
		return $parsed_inner_blocks;
	}

	foreach ($block['innerBlocks'] as $inner_block) {
		// Validate inner block structure.
		if (!is_array($inner_block) || empty($inner_block['blockName'])) {
			continue;
		}

		if (
			$inner_block['blockName'] !== 'clutch/slot' ||
			empty($inner_block['innerBlocks'])
		) {
			$parsed_inner_blocks[] = $inner_block;
			continue;
		}

		if (!is_array($inner_block['attrs'])) {
			$inner_block['attrs'] = [];
		}

		$slot_name = $inner_block['attrs']['name'] ?? 'children';

		// Ensure attrs is an array before setting slot.
		if (!is_array($block['attrs'])) {
			$block['attrs'] = [];
		}

		$block['attrs'][$slot_name] = $inner_block['innerBlocks'];
	}

	return $parsed_inner_blocks;
}

/**
 * Include formatted blocks in REST API response
 *
 * @param array $response The REST API response.
 * @param int   $post_id The post ID.
 * @return array Modified response with blocks.
 */
function include_raw_post_content(array $response, int $post_id): array
{
	// Validate post ID.
	if (!is_numeric($post_id) || $post_id <= 0) {
		return $response;
	}

	$raw_content = '';

	if (isset($response['content']['raw'])) {
		$raw_content = $response['content']['raw'];
	} else {
		$raw_content = get_post_field('post_content', $post_id);
	}

	// Ensure we have content to parse.
	if (empty($raw_content)) {
		$response['blocks'] = [];
		return $response;
	}

	// Parse blocks and handle potential errors.
	$parsed_blocks = parse_blocks($raw_content);

	if (!is_array($parsed_blocks)) {
		$response['blocks'] = [];
		return $response;
	}

	$response['blocks'] = $parsed_blocks;
	format_blocks($response['blocks']);

	return $response;
}

add_filter(
	'clutch/prepare_post_fields',
	__NAMESPACE__ . '\\include_raw_post_content',
	10,
	2
);
