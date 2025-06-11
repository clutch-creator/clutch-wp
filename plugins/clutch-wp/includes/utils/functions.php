<?php
/**
 * Clutch plugin utility functions.
 */
namespace Clutch\WP\Utils;

if (!defined('ABSPATH')) {
	exit();
}

function first_non_empty_str(...$candidates)
{
	foreach ($candidates as $value) {
		if (!empty($value)) {
			return $value;
		}
	}
	return '';
}

/**
 * Makes parallel HTTP GET requests using curl_multi or wp_remote_get fallback
 *
 * @param string[] $urls Array of URLs to request
 * @param int $batch_size Number of parallel requests to process (default: 10)
 * @param int $timeout Timeout in seconds for each request (default: 2)
 * @return void
 */
function make_parallel_requests($urls, $batch_size = 10, $timeout = 2)
{
	$unique_urls = array_unique($urls);

	if (!function_exists('curl_multi_init') || !function_exists('curl_init')) {
		// Bailout to wp_remote_get if curl is not available
		foreach ($unique_urls as $url) {
			wp_remote_get($url, [
				'timeout' => $timeout,
				'blocking' => false,
			]);
		}

		return;
	}

	$batches = array_chunk($unique_urls, $batch_size);

	foreach ($batches as $batch) {
		$multi_handle = curl_multi_init();
		$curl_handles = [];

		// Initialize curl handles for this batch
		foreach ($batch as $url) {
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl_handle, CURLOPT_MAXREDIRS, 3);

			curl_multi_add_handle($multi_handle, $curl_handle);
			$curl_handles[] = $curl_handle;
		}

		// Execute all requests in parallel
		$running = null;
		do {
			curl_multi_exec($multi_handle, $running);
			curl_multi_select($multi_handle);
		} while ($running > 0);

		// Clean up handles
		foreach ($curl_handles as $curl_handle) {
			curl_multi_remove_handle($multi_handle, $curl_handle);
			curl_close($curl_handle);
		}
		curl_multi_close($multi_handle);
	}
}
