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
			$block['attrs'] = (object) $block['attrs'];
		}

		// Validate block name exists.
		if (empty($block['blockName'])) {
			continue;
		}

		// Mark image blocks as media.
		if (
			$block['blockName'] === 'core/image' &&
			isset($block['attrs']->id)
		) {
			$block['attrs']->_clutch_type = 'media';
		}

		// Extract inner content for clutch/paragraph blocks
		if ($block['blockName'] === 'clutch/paragraph') {
			$block['attrs'] = process_paragraph_block($block);
		}

		if (!empty($block['innerBlocks']) && is_array($block['innerBlocks'])) {
			format_blocks($block['innerBlocks']);
			$block['innerBlocks'] = process_slot_blocks($block);
		}
	}
}

/**
 * Process clutch/paragraph blocks to extract inner content
 *
 * @param array $block The paragraph block array.
 * @return object The modified attributes object.
 */
function process_paragraph_block(array $block): object
{
	$attrs = $block['attrs'];

	// Ensure we have innerHTML to work with
	if (empty($block['innerHTML'])) {
		$attrs->innerContent = '';
		return $attrs;
	}

	$html = trim($block['innerHTML']);

	// Get the tag from attributes, default to 'span'
	$tag = isset($attrs->tag) ? $attrs->tag : 'span';

	// Extract content between the opening and closing tags
	$pattern =
		'/<' .
		preg_quote($tag, '/') .
		'[^>]*>(.*?)<\/' .
		preg_quote($tag, '/') .
		'>/s';

	if (preg_match($pattern, $html, $matches)) {
		$attrs->innerContent = trim($matches[1]);
	} else {
		// Fallback: if no tags found, use the HTML as is
		$attrs->innerContent = $html;
	}

	return $attrs;
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

	foreach ($block['innerBlocks'] as &$inner_block) {
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

		// Ensure attributes are always returned as an object.
		if (
			!isset($inner_block['attrs']) ||
			!is_object($inner_block['attrs'])
		) {
			$inner_block['attrs'] = (object) $inner_block['attrs'];
		}

		$slot_name = $inner_block['attrs']->name ?: 'children';
		$block['attrs']->$slot_name = $inner_block['innerBlocks'];
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
