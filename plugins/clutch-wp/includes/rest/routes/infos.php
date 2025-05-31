<?php
/**
 * This file defines custom REST API endpoints for Clutch.
 * It includes endpoints for retrieving plugin info, post types, taxonomies, and clearing cache.
 */
namespace Clutch\WP\Rest;

add_action('rest_api_init', function () {
	register_rest_route('clutch/v1', '/info', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_info',
	]);

	register_rest_route('clutch/v1', '/permalink-info', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_permalink_info',
	]);

	register_rest_route('clutch/v1', '/front-page', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_front_page',
	]);
});

/**
 * Retrieves plugin information such as name, version, and URI.
 *
 * @return \WP_REST_Response A REST response containing plugin information.
 */
function rest_get_info()
{
	if (!function_exists('get_plugin_data')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	// get clutch plugin info
	$plugin_data = get_plugin_data(CLUTCHWP_FILE);

	$response = [
		'name' => $plugin_data['Name'],
		'version' => $plugin_data['Version'],
		'uri' => $plugin_data['PluginURI'],
	];

	return new \WP_REST_Response($response);
}

/**
 * Analyzes a URL and determines its type (post, taxonomy, external, etc.).
 *
 * @param string $url The URL to analyze.
 * @return array An array containing the object type and details.
 */
function get_permalink_info($url)
{
	$response = ['object_type' => 'unknown', 'details' => []];

	// Check if the URL is external
	$site_url = wp_parse_url(home_url(), PHP_URL_HOST);
	$url_host = wp_parse_url($url, PHP_URL_HOST);

	if ($site_url !== $url_host) {
		$response['object_type'] = 'external';
		return $response;
	}

	$post_id = url_to_postid($url);

	if ($post_id) {
		$post = get_post($post_id);
		$post_type = get_post_type_object($post->post_type);

		if ($post) {
			$response['object_type'] = 'post';
			$response['details'] = [
				'id' => $post->ID,
				'name' => $post->post_name,
				'post_type' => $post->post_type,
				'rest_base' => $post_type->rest_base ?: $post_type->name,
				'rest_namespace' => $post_type->rest_namespace ?: 'wp/v2',
			];
		}
	} else {
		$path = wp_parse_url($url, PHP_URL_PATH);
		$slug = end(explode('/', trim($path, '/')));
		$taxonomies = get_taxonomies(['public' => true], 'objects');

		$foundTerm = false;
		foreach ($taxonomies as $taxonomy) {
			$term = get_term_by('slug', $slug, $taxonomy->name);

			if ($term) {
				$foundTerm = true;
				$response['object_type'] = 'taxonomy_term';
				$response['details'] = [
					'id' => $term->term_id,
					'name' => $term->name,
					'taxonomy_name' => $taxonomy->name,
					'rest_base' => $taxonomy->rest_base ?: $taxonomy->name,
					'rest_namespace' => $taxonomy->rest_namespace ?: 'wp/v2',
				];
				break;
			}
		}

		if (!$foundTerm) {
			foreach ($taxonomies as $taxonomy) {
				$rewrite_slug = isset($taxonomy->rewrite['slug'])
					? $taxonomy->rewrite['slug']
					: '';

				if ($slug === $rewrite_slug) {
					$response['object_type'] = 'taxonomy';
					$response['details'] = [
						'name' => $taxonomy->name,
						'rest_base' => $taxonomy->rest_base ?: $taxonomy->name,
						'rest_namespace' =>
							$taxonomy->rest_namespace ?: 'wp/v2',
					];
					break;
				}
			}
		}
	}

	// lastly, check if it's a date archive
	if ($response['object_type'] === 'unknown') {
		$parts = explode('/', trim($path, '/'));
		$year = isset($parts[0]) ? (int) $parts[0] : null;
		$month = isset($parts[1]) ? (int) $parts[1] : null;
		$day = isset($parts[2]) ? (int) $parts[2] : null;

		if ($year >= 1000 && $year <= 9999) {
			$response['object_type'] = 'date_archive';
			$response['details'] = [
				'year' => $year,
				'month' => $month,
				'day' => $day,
			];
		}
	}

	return $response;
}

/**
 * Retrieves permalink information for a given URL.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response A REST response containing permalink information.
 */
function rest_get_permalink_info(\WP_REST_Request $request)
{
	$url = $request->get_param('url');

	$response = get_permalink_info($url);

	return new \WP_REST_Response($response);
}

/**
 * Retrieves the front page details.
 *
 * @return \WP_REST_Response A REST response containing front page details or an error if not set.
 */
function rest_get_front_page()
{
	$front_page_id = get_option('page_on_front');
	if (!$front_page_id) {
		return new \WP_REST_Response(['message' => 'No front page set'], 404);
	}

	$post = get_post($front_page_id);
	if (!$post) {
		return new \WP_REST_Response(
			['message' => 'Front page not found'],
			404
		);
	}

	return new \WP_REST_Response([
		'id' => $post->ID,
		'title' => $post->post_title,
		'slug' => $post->post_name,
	]);
}
