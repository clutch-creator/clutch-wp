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
	$secret_key = AUTH_KEY; // Use the same key for verification
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
