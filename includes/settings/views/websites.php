<?php
/**
 * The view for clutch settings page
 */

namespace Clutch\WP\Settings;

require_once __DIR__ . '/../functions.php';

use function Clutch\WP\Cache\get_website_invalidation_url;

$created_date = current_time('mysql');
$websites = [
	[
		'name' => 'Clutch Website 6.0',
		'url' => 'https://clutch.io',
		'invalidationEndpoint' =>
			'https://clutch-demo.com/wp-json/clutch/v1/invalidate-cache',
		'token' => 'demo-token',
		'projectId' => '67e315e12e38f2368404a919',
		'createdDate' => $created_date,
		'lastPublishDate' => $created_date,
		'deploymentId' => '5f7e315e12e38f2368404a919',
	],
];

$icons = [
	'external-link' => get_icon('external-link'),
	'trash' => get_icon('trash'),
	'clear-cache' => get_icon('clear-cache'),
];

// $websites = \Clutch\WP\Cache\get_registered_websites';

/**
 * Formats a date to "day month year" if not the current year.
 *
 * @param string $date The date string.
 * @return string The formatted date.
 */
function format_created_date($date)
{
	$timestamp = strtotime($date);
	if (date('Y', $timestamp) !== date('Y')) {
		return date('d F Y', $timestamp);
	}
	return date('d F', $timestamp);
}

/**
 * Converts a date to a readable time difference label.
 *
 * @param string $date The date string.
 * @return string The readable time difference.
 */
function time_difference_label($date)
{
	$timestamp = strtotime($date);
	$diff = time() - $timestamp;

	if ($diff < 60) {
		return $diff . ' seconds ago';
	} elseif ($diff < 3600) {
		return floor($diff / 60) . ' minutes ago';
	} elseif ($diff < 86400) {
		return floor($diff / 3600) . ' hours ago';
	} elseif ($diff < 604800) {
		return floor($diff / 86400) . ' days ago';
	} else {
		return date('d F Y', $timestamp);
	}
}

/**
 * Renders a website card.call a
 *
 * @param array $website The website data.
 * @param array $icons The icons array.
 */
function render_card($website, $icons)
{
	$invalidation_url = get_website_invalidation_url($website, [
		'wordpress',
	]); ?>
	<div class="clt-card clt-website-card">
		<div class="clt-website-frame-wrapper">
			<iframe src="<?= esc_url($website['url']) ?>"></iframe>
		</div>
		<div class="clt-website-content">
			<div class="clt-website-header">
				<h3><?= esc_html($website['name']) ?></h3>
				<div class="clt-website-buttons">
					<a class="clt-button" href="<?php echo esc_url(
     	'https://app.clutch.io#/project/' . $website['projectId']
     ); ?>" target="_blank"><span>Open in Clutch</span>
					<?php echo $icons['external-link']; ?>  
					</a>
					<a class="clt-button" href="<?php echo esc_url(
     	$website['url']
     ); ?>" target="_blank"><span>Visit</span><?php echo $icons['external-link']; ?>  </a>
				</div>
			</div>
			<div class="clt-infos">
				<p class="clt-info"><span class="clt-label">Created</span><span><?= esc_html(
    	format_created_date($website['createdDate'])
    ) ?></span></p>
				<p class="clt-info"><span class="clt-label">Last Published</span><span><?= esc_html(
    	time_difference_label($website['lastPublishDate'])
    ) ?></span></p>
			</div>
			<div class="clt-website-buttons">
				<button class="clt-button clt-remove-website" data-deployment-id="<?= esc_attr(
    	$website['deploymentId']
    ) ?>">
					<?php echo $icons['trash']; ?><span>Remove</span>
				</button>
				<a class="clt-button" href="<?php echo esc_url(
    	$invalidation_url
    ); ?>" target="_blank"><?php echo $icons['clear-cache']; ?><span>Clear Cache</span></a>
			</div>
		</div>
	</div>
	<?php
}
?>
<div class="clt-websites-header">
  <h2>Websites</h2>
  <p id="success-message" class="clt-text-success" style="display: none;">Website removed successfully.</p>
  <p id="error-message" class="clt-text-error" style="display: none;">Error removing website.</p>
</div>
<?php if (empty($websites)): ?>
	<div class="clt-card">
		<h3>No websites yet.</h3>
		<p>No websites deployed from Clutch are currently connected to this WordPress installation.</p>
	</div>
<?php else: ?>
	<div class="clt-cards">
		<?php foreach ($websites as $website): ?>
			<?php render_card($website, $icons); ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
	const removeButtons = document.querySelectorAll('.clt-remove-website');
	const successMessage = document.getElementById('success-message');
	const errorMessage = document.getElementById('error-message');
	removeButtons.forEach(button => {
		button.addEventListener('click', function () {
			const deploymentId = this.getAttribute('data-deployment-id');
			if (confirm('Are you sure you want to remove this website?')) {
				fetch('<?php echo esc_url(rest_url('clutch/v1/remove-website')); ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
					},
					body: JSON.stringify({ deploymentId })
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						successMessage.style.display = 'block';
						errorMessage.style.display = 'none';
						location.reload();
					} else {
						errorMessage.style.display = 'block';
						successMessage.style.display = 'none';
					}
				})
				.catch(error => {
					console.error('Error:', error);
					errorMessage.style.display = 'block';
					successMessage.style.display = 'none';
				});
			}
		});
	});
});
</script>
