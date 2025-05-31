<?php
/**
 * This file defines custom REST API endpoints for Clutch.
 * It includes endpoints for retrieving plugin info, post types, taxonomies, and clearing cache.
 */
namespace Clutch\WP\Rest;

add_action('rest_api_init', function () {
	register_rest_route('clutch/v1', '/clear-cache', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\\rest_clear_cache',
	]);
});

function rest_clear_cache()
{
	// WPEngine cache clear
	if (class_exists('\wpecommon')) {
		/** @disregard */
		\wpecommon::purge_memcached();
		/** @disregard */
		\wpecommon::purge_varnish_cache();
	}

	// Example WP Rocket cache clear
	if (function_exists('rocket_clean_domain')) {
		/** @disregard */
		rocket_clean_domain();
	}

	// Example Varnish cache clear
	if (function_exists('purge_varnish_cache')) {
		/** @disregard */
		purge_varnish_cache();
	}

	return new \WP_REST_Response(['message' => 'Cache cleared']);
}
