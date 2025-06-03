<?php
/**
 * Adds preview template that will load Clutch through an iframe
 */
namespace Clutch\WP\Preview;

if (!defined('ABSPATH')) {
	exit();
}

add_filter('template_include', __NAMESPACE__ . '\\headless_preview_template');

function headless_preview_template($template)
{
	if (
		is_preview() ||
		(is_admin() && isset($_GET['preview']) && $_GET['preview'] === 'true')
	) {
		$custom_template = plugin_dir_path(__FILE__) . 'templates/preview.php';

		if (file_exists($custom_template)) {
			return $custom_template;
		}
	}
	return $template;
}

// Enqueue basic styles for iframe container (optional)
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\headless_preview_styles');

function headless_preview_styles()
{
	if (is_preview()) {
		wp_enqueue_style(
			'headless-preview-style',
			plugins_url('templates/headless-preview.css', __FILE__)
		);
	}
}

// Modify the "View Page" link in the editor's top bar
add_filter(
	'get_sample_permalink',
	__NAMESPACE__ . '\\modify_editor_view_link',
	10,
	2
);

function modify_editor_view_link($permalink, $post_id)
{
	if (is_admin()) {
		// Append the preview=true parameter to the beautiful permalink
		$permalink[0] = add_query_arg('preview', 'true', $permalink[0]);
	}

	return $permalink;
}

// Enqueue admin script to send refresh-preview message
add_action(
	'admin_enqueue_scripts',
	__NAMESPACE__ . '\\enqueue_admin_preview_script'
);

function enqueue_admin_preview_script($hook)
{
	if ($hook === 'post.php' || $hook === 'post-new.php') {
		wp_enqueue_script(
			'clutch-admin-preview-script',
			plugins_url('assets/admin-preview.js', __FILE__),
			[],
			'1.0',
			true
		);
	}
}
