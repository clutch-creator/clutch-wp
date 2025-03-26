<?php
/**
 * Adds functionality/handling for caching and websites registration
 */
namespace Clutch\WP\Cache;

function get_registered_websites()
{
	$websites = get_option('clutch_websites', []);
	return $websites;
}
