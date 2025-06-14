<?php
/**
 * The view for clutch settings page
 */

namespace Clutch\WP\Settings;

require_once __DIR__ . '/../functions.php';

use function Clutch\WP\Cache\get_website_invalidation_url;
use function Clutch\WP\Websites\get_registered_websites;

$icons = [
	'external-link' => get_icon('external-link'),
	'trash' => get_icon('trash'),
	'clear-cache' => get_icon('clear-cache'),
];

$websites = get_registered_websites();
$selected_host = get_user_meta(
	get_current_user_id(),
	'selected_clutch_host',
	true
);

/**
 * Formats a date to "day month year" if not the current year.
 *
 * @param string $date The date string.
 * @return string The formatted date.
 */
function format_created_date($date)
{
	$timestamp = strtotime($date);
	if (gmdate('Y', $timestamp) !== gmdate('Y')) {
		return gmdate('d F Y', $timestamp);
	}
	return gmdate('d F', $timestamp);
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
		return gmdate('d F Y', $timestamp);
	}
}

/**
 * Renders a website card.call a
 *
 * @param array $website The website data.
 * @param array $icons The icons array.
 */
function render_card($website, $icons, $is_selected)
{
	$invalidation_url = get_website_invalidation_url($website, ['wordpress']);
	$is_website_remote =
		strpos($website['deploymentId'], $website['projectId'] . '-') !== 0;
	?>
	<div class="clt-card clt-website-card">
		<div class="clt-website-frame-wrapper">
			<iframe src="<?php echo esc_url($website['url']); ?>"></iframe>
		</div>
		<div class="clt-website-content">
			<div class="clt-website-header">
				<h3><?php echo esc_html(
    	$website['name'] . ($is_website_remote ?: ' (Local)')
    ); ?></h3>
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
				<p class="clt-info"><span class="clt-label">Created</span><span><?php echo esc_html(
    	format_created_date($website['createdDate'])
    ); ?></span></p>
				<p class="clt-info"><span class="clt-label">Last Published</span><span><?php echo esc_html(
    	time_difference_label($website['lastPublishDate'])
    ); ?></span></p>
		<p class="clt-info"><span class="clt-label">URL</span><span><?php echo esc_html(
  	$website['url']
  ); ?></span></p>
			</div>
			<div class="clt-website-buttons">
				<button class="clt-button clt-remove-website" data-deployment-id="<?php echo esc_attr(
    	$website['deploymentId']
    ); ?>">
					<?php echo $icons['trash']; ?><span>Remove</span>
				</button>
				<?php if ($is_website_remote): ?>
				<a class="clt-button" href="<?php echo esc_url(
    	$invalidation_url
    ); ?>" target="_blank"><?php echo $icons[
	'clear-cache'
]; ?><span>Clear Cache</span></a>
				<?php endif; ?>
				<?php if ($is_selected): ?>
				<button class="clt-button" disabled>
					<span>Currently used as preview</span>
				</button>
				<?php endif; ?>
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
			<?php render_card($website, $icons, $website['url'] === $selected_host); ?>
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
						'X-WP-Nonce': '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>'
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
