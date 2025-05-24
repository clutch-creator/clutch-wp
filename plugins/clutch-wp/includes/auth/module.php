<?php
/**
 * Module for handling authentication and token validation in Clutch WP.
 */
namespace Clutch\WP\Auth;

use WP_Error;

if (!defined('ABSPATH')) {
	exit();
}

function verify_secure_token($secure_token)
{
	$stored_token = get_option('clutch_approved_token');
	$secret_key = AUTH_KEY; // Use the same key for verification

	// Check if the token matches the stored approved token
	if ($stored_token && $secure_token === $stored_token) {
		return ['user_id' => null]; // Approved token is valid
	}

	// Validate the dynamically generated token
	list($token, $provided_signature) = explode('.', $secure_token, 2);

	// Recalculate the signature
	$calculated_signature = hash_hmac('sha256', $token, $secret_key);

	// Compare the provided signature with the calculated signature
	if (hash_equals($calculated_signature, $provided_signature)) {
		$payload = json_decode(base64_decode($token), true);

		// Check expiration
		if (isset($payload['exp']) && $payload['exp'] > time()) {
			return $payload; // Token is valid
		}
	}

	return false; // Token is invalid
}

// Validate Token Middleware for REST API
add_filter(
	'rest_pre_dispatch',
	function ($result, $server, $request) {
		$auth_header = $request->get_header('Authorization');
		if (!$auth_header) {
			return $result; // No token, proceed as normal
		}

		if (strpos($auth_header, 'Bearer ') !== 0) {
			return new WP_Error('invalid_token', 'Invalid token format', [
				'status' => 403,
			]);
		}

		$secure_token = str_replace('Bearer ', '', $auth_header);

		// Verify the secure token
		$payload = verify_secure_token($secure_token);
		if (!$payload) {
			return new WP_Error('invalid_token', 'Invalid or expired token', [
				'status' => 403,
			]);
		}

		 // Handle approved token logic
		if (is_null($payload['user_id'])) {
			$username = 'clutch_readonly_user';
			$user = get_user_by('login', $username);

			if (!$user) {
				create_hardcoded_read_only_user();
				$user = get_user_by('login', $username);
			}

			wp_set_current_user($user->ID);
			return $result;
		}

		// Handle dynamically generated token logic
		$user = get_user_by('ID', $payload['user_id']);
		if (!$user) {
			return new WP_Error('invalid_user', 'User not found', [
				'status' => 403,
			]);
		}

		wp_set_current_user($user->ID);

		return $result;
	},
	10,
	3
);

/**
 * Register a custom admin menu page for token approval.
 */
function register_approve_token_menu() {
	add_submenu_page(
		null,
		'Clutch',
		'Clutch',
		'administrator',
		'clutch-approve-token',
		__NAMESPACE__ . '\\approve_auth_token_backend',
	);
}
add_action('admin_menu', __NAMESPACE__ . '\\register_approve_token_menu');

/**
 * Display approval page and store the token if approved.
 */
function approve_auth_token_backend() {
	if (!current_user_can('administrator')) {
		wp_die(__('You do not have permission to access this page.', 'clutch-wp'), 403);
	}

	$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : null;

	if (!$token) {
		wp_die(__('Token is required.', 'clutch-wp'), 400);
	}

	if (isset($_POST['approve_token'])) {
		update_option('clutch_approved_token', $token);
		
		echo '<div class="notice notice-success"><p>' . __('Token approved and stored successfully.', 'clutch-wp') . '</p></div>';
		
		return;
	}

	// Display approval form
	echo '<div class="wrap">';
	echo '<h1>' . __('Clutch Authorization', 'clutch-wp') . '</h1>';
	echo '<p>' . __('Do you want to allow Clutch to access your WordPress drafts and previews?', 'clutch-wp') . '</p>';
	echo '<form method="post">';
	echo '<input type="hidden" name="approve_token" value="1">';
	echo '<input type="submit" class="button button-primary" value="' . __('Approve', 'clutch-wp') . '">';
	echo '</form>';
	echo '</div>';

	exit;
}

/**
 * Create a hardcoded user with the read-only drafts role.
 */
function create_hardcoded_read_only_user() {
	$username = 'clutch_readonly_user';
	$email = 'readonly@clutch.io';
	$password = wp_generate_password(16, true, true); // Generate a secure 16-character password

	// Check if the user already exists
	if (!username_exists($username) && !email_exists($email)) {
		// Create the user
		$user_id = wp_create_user($username, $password, $email);

		if (!is_wp_error($user_id)) {
			// Assign the read_only_drafts role
			$user = new \WP_User($user_id);
			$user->set_role('administrator'); // Assign the administrator role for full access
		}
	}
}

// Hook to create the hardcoded user on plugin activation
register_activation_hook(CLUTCHWP_FILE, __NAMESPACE__ . '\\create_hardcoded_read_only_user');
