<?php
/**
 * Adds functionality/handling for registered clutch websites
 */
namespace Clutch\WP\Websites;

if (!defined('ABSPATH')) {
	exit();
}

require_once __DIR__ . '/functions.php';

/**
 * Registers a website via REST API.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_Error|\WP_REST_Response Response object or WP_Error on failure.
 */
function rest_register_website($request)
{
	$params = json_decode($request->get_body(), true);
	$name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
	$deployment_id = isset($params['deploymentId'])
		? sanitize_text_field($params['deploymentId'])
		: '';
	$project_id = isset($params['projectId'])
		? sanitize_text_field($params['projectId'])
		: '';
	$new_endpoint = isset($params['invalidationEndpoint'])
		? esc_url_raw($params['invalidationEndpoint'])
		: '';
	$url = isset($params['url']) ? esc_url_raw($params['url']) : '';
	$token = isset($params['token'])
		? sanitize_text_field($params['token'])
		: '';

	if (
		empty($name) ||
		empty($deployment_id) ||
		empty($project_id) ||
		empty($new_endpoint) ||
		empty($url) ||
		empty($token)
	) {
		return new \WP_Error('invalid_params', 'Invalid parameters', [
			'status' => 400,
		]);
	}

	$websites = register_website(
		$deployment_id,
		$project_id,
		$name,
		$new_endpoint,
		$url,
		$token
	);

	return rest_ensure_response($websites);
}

/**
 * Removes a registered website via REST API. (PROTECTED)
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_Error|\WP_REST_Response Response object or WP_Error on failure.
 */
function rest_remove_website($request)
{
	$params = json_decode($request->get_body(), true);
	$deployment_id = isset($params['deploymentId'])
		? sanitize_text_field($params['deploymentId'])
		: '';

	if (empty($deployment_id)) {
		return new \WP_Error('invalid_params', 'Invalid deploymentId', [
			'status' => 400,
		]);
	}

	$websites = get_registered_websites();
	$updated_websites = remove_website($deployment_id);

	if (count($websites) === count($updated_websites)) {
		return new \WP_Error('not_found', 'Website not found', [
			'status' => 404,
		]);
	}

	return rest_ensure_response(['success' => true]);
}

/**
 * Registers REST API routes for website management.
 *
 * @return void
 */
function register_website_routes()
{
	register_rest_route('clutch/v1', '/register-website', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\\rest_register_website',
	]);

	register_rest_route('clutch/v1', '/remove-website', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\\rest_remove_website',
		'permission_callback' => function () {
			return current_user_can('manage_options');
		},
	]);
}

add_action('rest_api_init', __NAMESPACE__ . '\register_website_routes');
