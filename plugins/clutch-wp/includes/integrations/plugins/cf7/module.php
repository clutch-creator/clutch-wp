<?php
/**
 * This file contains functionality to expose Contact Form 7 forms via the Clutch API.
 */

namespace Clutch\WP\CF7;

if (!defined('ABSPATH')) {
	exit();
}

/**
 * Get all published CF7 forms
 *
 * @return \WP_REST_Response
 */
function rest_get_cf7_forms()
{
	$args = [
		'post_type' => 'wpcf7_contact_form',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	];

	$forms = get_posts($args);
	$response = [];

	foreach ($forms as $form) {
		$form_data = \WPCF7_ContactForm::get_instance($form->ID);
		$response[] = [
			'id' => $form->ID,
			'title' => $form->post_title,
			'slug' => $form->post_name,
			'content' => $form->post_content,
			'form_fields' => $form_data->scan_form_tags(),
			'additional_settings' => $form_data->additional_setting('', false), // Fix: Add required parameters
			'mail_settings' => $form_data->prop('mail'),
		];
	}

	return new \WP_REST_Response($response);
}

/**
 * Get a specific CF7 form by ID
 *
 * @param \WP_REST_Request $request
 * @return \WP_REST_Response
 */
function rest_get_cf7_form(\WP_REST_Request $request)
{
	$form_id = $request->get_param('id');
	$form = get_post($form_id);

	if (!$form || $form->post_type !== 'wpcf7_contact_form') {
		return new \WP_REST_Response(['message' => 'Form not found'], 404);
	}

	$form_data = \WPCF7_ContactForm::get_instance($form_id);
	$response = [
		'id' => $form->ID,
		'title' => $form->post_title,
		'slug' => $form->post_name,
		'content' => $form->post_content,
		'form_fields' => $form_data->scan_form_tags(),
		'additional_settings' => $form_data->additional_setting('', false), // Fix: Add required parameters
		'mail_settings' => $form_data->prop('mail'),
	];

	return new \WP_REST_Response($response);
}

// Register the Clutch API endpoints for CF7
add_action('rest_api_init', function () {
	register_rest_route('clutch/v1', '/cf7', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_cf7_forms',
		'permission_callback' => function () {
			return true; // Allow public access
		},
	]);

	register_rest_route('clutch/v1', '/cf7/(?P<id>\d+)', [
		'methods' => 'GET',
		'callback' => __NAMESPACE__ . '\\rest_get_cf7_form',
		'permission_callback' => function () {
			return true; // Allow public access
		},
	]);
});
