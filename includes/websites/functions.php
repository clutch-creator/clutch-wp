<?php
/**
 * Adds functionality/handling for registered clutch websites
 */
namespace Clutch\WP\Websites;

if (!defined('ABSPATH')) {
	exit();
}

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
 * Registers or updates a clutch website in the WordPress options.
 *
 * @param string $deployment_id The deployment ID of the website.
 * @param string $project_id The project ID of the website.
 * @param string $name The name of the website.
 * @param string $invalidationEndpoint The invalidation endpoint of the website.
 * @param string $url The URL of the website.
 * @param string $token The token for the website.
 * @return array The updated list of registered websites.
 */
function register_website(
	$deployment_id,
	$project_id,
	$name,
	$invalidationEndpoint,
	$url,
	$token
) {
	$websites = get_option('clutch_websites', []);
	$existing_key = -1;
	foreach ($websites as $index => $site) {
		if (
			$site['deploymentId'] === $deployment_id &&
			$site['projectId'] === $project_id
		) {
			$existing_key = $index;
			break;
		}
	}

	if ($existing_key !== -1) {
		// Update fields
		$websites[$existing_key]['name'] = $name;
		$websites[$existing_key][
			'invalidationEndpoint'
		] = $invalidationEndpoint;
		$websites[$existing_key]['url'] = $url;
		$websites[$existing_key]['token'] = $token;
		$websites[$existing_key]['lastPublishDate'] = current_time('mysql');
	} else {
		// Add new website
		$created_date = current_time('mysql');
		$websites[] = [
			'name' => $name,
			'deploymentId' => $deployment_id,
			'projectId' => $project_id,
			'invalidationEndpoint' => $invalidationEndpoint,
			'url' => $url,
			'token' => $token,
			'createdDate' => $created_date,
			'lastPublishDate' => $created_date,
		];
	}

	update_option('clutch_websites', $websites);

	return $websites;
}

/**
 * Removes a clutch website from the WordPress options.
 *
 * @param string $deployment_id The deployment ID of the website to remove.
 * @return array The updated list of registered websites.
 */
function remove_website($deployment_id)
{
	$websites = get_option('clutch_websites', []);
	$websites = array_filter($websites, function ($website) use (
		$deployment_id
	) {
		return $website['deploymentId'] !== $deployment_id;
	});

	update_option('clutch_websites', $websites);

	return $websites;
}
