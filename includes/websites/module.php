<?php
/**
 * Adds functionality/handling for registered clutch websites
 */
namespace Clutch\WP\Websites;

use function Clutch\WP\Settings\get_icon;

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
 * Retrieves the list of registered websites via REST API.
 *
 * @return \WP_REST_Response Response object containing the websites.
 */
function rest_get_websites()
{
	$websites = get_registered_websites();
	return rest_ensure_response($websites);
}

/**
 * Saves the selected host for the current user via REST API.
 *
 * @param \WP_REST_Request $request The REST API request object.
 * @return \WP_REST_Response Response object or WP_Error on failure.
 */
function rest_save_selected_host($request)
{
	$params = $request->get_json_params();
	$selected_host = isset($params['selectedHost'])
		? esc_url_raw($params['selectedHost'])
		: '';

	if (empty($selected_host)) {
		return new \WP_Error(
			'invalid_params',
			'Invalid selectedHost parameter',
			['status' => 400]
		);
	}

	$user_id = get_current_user_id();
	if (!$user_id) {
		return new \WP_Error('not_logged_in', 'User not logged in', [
			'status' => 403,
		]);
	}

	update_user_meta($user_id, 'selected_clutch_host', $selected_host);

	return rest_ensure_response([
		'success' => true,
		'selectedHost' => $selected_host,
	]);
}

/**
 * Retrieves the selected host for the current user via REST API.
 *
 * @return \WP_REST_Response Response object containing the selected host.
 */
function rest_get_selected_host()
{
	$user_id = get_current_user_id();
	if (!$user_id) {
		return new \WP_Error('not_logged_in', 'User not logged in', [
			'status' => 403,
		]);
	}

	$selected_host = get_user_meta($user_id, 'selected_clutch_host', true);

	return rest_ensure_response(['selectedHost' => $selected_host]);
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

	register_rest_route('clutch/v1', '/get-websites', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_websites',
	]);

	register_rest_route('clutch/v1', '/save-selected-host', [
		'methods' => 'POST',
		'callback' => __NAMESPACE__ . '\\rest_save_selected_host',
		'permission_callback' => function () {
			return current_user_can('edit_posts'); // Ensure the user has the appropriate capability
		},
	]);

	register_rest_route('clutch/v1', '/get-selected-host', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_selected_host',
		'permission_callback' => function () {
			return current_user_can('edit_posts'); // Ensure the user has the appropriate capability
		},
	]);
}

add_action('rest_api_init', __NAMESPACE__ . '\register_website_routes');

// Enqueue admin bar script for both admin and preview pages
add_action(
	'admin_enqueue_scripts',
	__NAMESPACE__ . '\\enqueue_admin_bar_script'
);
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_bar_script');

function enqueue_admin_bar_script()
{
	// Only enqueue the script if the admin bar is showing
	if (is_admin_bar_showing()) {
		wp_enqueue_script(
			'clutch-admin-bar-script',
			plugins_url('assets/admin-bar.js', __FILE__),
			['jquery'], // Ensure jQuery is available
			'1.0',
			true
		);

		$svg_icon =
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwIiBoZWlnaHQ9IjEyMCIgdmlld0JveD0iMCAwIDEyMCAxMjAiIGZpbGw9IiNhN2FhYWQiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CiAgPHBhdGgKICAgIGQ9Ik0yNC41NjQ2IDEwLjYyMjlDMzMuMTc3IDEwLjE3MSA0MS44NTk5IDEwLjA0MzYgNTAuNTEzNiAxMC4wMDc4QzUzLjA5NTcgOS45OTcwNyA1NC4zODY4IDkuOTkxNzMgNTUuMTkzNyAxMC43OTVDNTYuMDAwNSAxMS41OTgyIDU2LjAwMDUgMTIuODkyMSA1Ni4wMDA1IDE1LjQ4VjEwNC41MkM1Ni4wMDA1IDEwNy4xMDggNTYuMDAwNSAxMDguNDAyIDU1LjE5MzcgMTA5LjIwNUM1NC4zODY4IDExMC4wMDggNTMuMDk1NyAxMTAuMDAzIDUwLjUxMzYgMTA5Ljk5MkM0MS44NTk5IDEwOS45NTYgMzMuMTc3IDEwOS44MjkgMjQuNTY0NiAxMDkuMzc3QzE0LjgyMDUgMTA4Ljg2NiAxMS4xNDA0IDEwNS4xNzUgMTAuNjI5NCA5NS40NDVDMTAuMDExMyA4My42NzY4IDEwIDcxLjc3NjkgMTAgNTkuOTk5OEMxMCA0OC4yMjI4IDEwLjAxMTQgMzYuMzIzMSAxMC42MjkzIDI0LjU1NUMxMS4xMzMyIDE0Ljk1OTQgMTQuOTY1IDExLjEyNjYgMjQuNTY0NiAxMC42MjI5WiIKICAgIGZpbGw9IiNhN2FhYWQiIC8+CiAgPHBhdGgKICAgIGQ9Ik05NS40ODIxIDEwOS4zNzdDODYuMjM0MSAxMDkuODYyIDc2LjkwNDkgMTA5Ljk3MyA2Ny42MTc4IDEwOS45OTlDNjUuMDM1OSAxMTAuMDA2IDYzLjc0NDkgMTEwLjAwOSA2Mi45Mzk0IDEwOS4yMDZDNjIuMTMzOSAxMDguNDAzIDYyLjEzMzkgMTA3LjExIDYyLjEzMzkgMTA0LjUyNFY3OS41MDYxQzYyLjEzMzkgNzEuNzU2IDYyLjEzMzkgNjcuODgxIDY0LjU0MjUgNjUuNDczNEM2Ni45NTExIDYzLjA2NTggNzAuODI3NiA2My4wNjU4IDc4LjU4MDcgNjMuMDY1OEg5My42MDA4QzEwMS40MDQgNjMuMDY1OCAxMDUuMzA2IDYzLjA2NTggMTA3LjcyMyA2NS41MjA3QzExMC4xMzkgNjcuOTc1NiAxMTAuMDc3IDcxLjgyMDEgMTA5Ljk1MiA3OS41MDkxQzEwOS44NjggODQuNzIwOCAxMDkuNzExIDg5LjkzNDggMTA5LjQyMiA5NS40NDVDMTA4LjkwNiAxMDUuMjY1IDEwNS4yODcgMTA4Ljg2MyA5NS40ODIxIDEwOS4zNzdaIgogICAgZmlsbD0iI2E3YWFhZCIgLz4KICA8cGF0aAogICAgZD0iTTEwOS45MyA0MC40NzgyQzExMC4wNTYgNDguMTc1NiAxMTAuMTE5IDUyLjAyNDMgMTA3LjcwMiA1NC40Nzk1QzEwNS4yODYgNTYuOTM0NyAxMDEuMzg2IDU2LjkzNDcgOTMuNTg1OSA1Ni45MzQ3TDc4LjU4MDcgNTYuOTM0N0M3MC44Mjc2IDU2LjkzNDcgNjYuOTUxMSA1Ni45MzQ3IDY0LjU0MjUgNTQuNTI3MUM2Mi4xMzM5IDUyLjExOTUgNjIuMTMzOSA0OC4yNDQ0IDYyLjEzMzkgNDAuNDk0NFYxNS40NzU4QzYyLjEzMzkgMTIuODg5NyA2Mi4xMzM5IDExLjU5NjcgNjIuOTM5NCAxMC43OTM3QzYzLjc0NDkgOS45OTA2NyA2NS4wMzU5IDkuOTk0MTkgNjcuNjE3OCAxMC4wMDEyQzc2LjkwNDkgMTAuMDI2NiA4Ni4yMzQxIDEwLjEzNzYgOTUuNDgyMSAxMC42MjI5QzEwNS4yMTYgMTEuMTMzNyAxMDguOTEgMTQuODE0NSAxMDkuNDIyIDI0LjU1NUMxMDkuNjg4IDI5LjYyNjcgMTA5Ljg0MiAzNS4wNDc5IDEwOS45MyA0MC40NzgyWiIKICAgIGZpbGw9IiNhN2FhYWQiIC8+Cjwvc3ZnPg==';

		// Pass the REST API URL and nonce to the script
		wp_localize_script('clutch-admin-bar-script', 'ClutchAdminBar', [
			'restUrl' => esc_url_raw(rest_url('clutch/v1/get-websites')),
			'nonce' => wp_create_nonce('wp_rest'), // Generate a nonce for REST API authentication
			'svgIcon' => $svg_icon,
		]);
	}
}
