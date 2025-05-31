<?php
/**
 * Clutch plugin utility functions.
 */
namespace Clutch\WP\Utils;

if (!defined('ABSPATH')) {
	exit();
}

function first_non_empty_str(...$candidates)
{
	foreach ($candidates as $value) {
		if (!empty($value)) {
			return $value;
		}
	}
	return '';
}
