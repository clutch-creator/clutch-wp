<?php
/**
 * Adds functionality/handling for caching and websites registration
 */
namespace Clutch\WP\Cache;

/**
 * Retrieves the list of registered clutch websites from the WordPress options.
 *
 * @return array An array of registered websites.
 */
function get_registered_websites()
{
	$websites = get_option('clutch_websites', []);
	return $websites;
}

/**
 * Constructs the invalidation URL for a given website and tags.
 *
 * @param array $website An associative array containing website details, including 'invalidationEndpoint' and 'token'.
 * @param array $tags An array of tags to include in the invalidation request. Defaults to an empty array.
 * @return string The constructed invalidation URL.
 */
function get_website_invalidation_url($website, $tags = [])
{
	$endpoint = $website['invalidationEndpoint'];
	$token = $website['token'];
	$tags_param = implode(',', array_map('urlencode', $tags));

	$endpoint = add_query_arg(
		[
			'tags' => $tags_param,
			'token' => $token,
		],
		$endpoint
	);

	return $endpoint;
}
