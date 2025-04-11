<?php
/**
 * Custom Preview Template that displays iframe
 */

global $post;

// Retrieve the selected host from user meta
$selected_host = get_user_meta(
	get_current_user_id(),
	'selected_clutch_host',
	true
);

if (is_user_logged_in()) {
	$user = wp_get_current_user();
	$token_payload = [
		'user_id' => $user->ID,
		'iat' => time(),
		'exp' => time() + 60 * 60 * 24, // Token expires in 24 hours
	];
	$secret_key = AUTH_KEY; // Use WordPress AUTH_KEY for signing
	$token = base64_encode(json_encode($token_payload));
	$signature = hash_hmac('sha256', $token, $secret_key); // Generate HMAC signature
	$secure_token = $token . '.' . $signature; // Append signature to the token
}
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php if (is_user_logged_in()): ?>
		<?php wp_body_open(); ?>
		<?php do_action('wp_admin_bar_render'); ?>
	<?php endif; ?>

	<div class="iframe-preview-container" style="position:fixed;top:32px;left:0;bottom:0;right:0;">
	  <iframe id="clutch-preview-iframe" src="" style="border:none;width:100%;height:100%;"></iframe>
	</div>

	<div id="clutch-preview-error" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%, -50%);background:#f8d7da;color:#721c24;padding:20px;border:1px solid #f5c6cb;border-radius:5px;">
	  <p>No route supports the preview for this post.</p>
	</div>

	<script>
	  (function () {
	    const iframe = document.getElementById('clutch-preview-iframe');
	    const errorMessage = document.getElementById('clutch-preview-error');
	    const postId = <?php echo json_encode($post->ID); ?>;
	    const selectedHost = <?php echo json_encode(esc_url($selected_host)); ?>;
	    const token = <?php echo json_encode(
     	$secure_token
     ); ?>; // Pass the secure token to the iframe

	    // Get the preview_id from the URL if it exists
	    const urlParams = new URLSearchParams(window.location.search);
	    const previewId = urlParams.get('preview_id');

	    // Check if selectedHost is defined
	    if (selectedHost) {
	      const apiUrl = `${selectedHost}/api/url?permalink=${encodeURIComponent('<?php echo esc_url(
       	get_permalink($post->ID)
       ); ?>')}`;

	      // Fetch the resolved URL dynamically
	      fetch(apiUrl)
	        .then((response) => response.json())
	        .then((data) => {
	          if (data.resolvedUrl && data.resolvedUrl !== '/') {
	            let resolvedUrl = data.resolvedUrl;
	            if (previewId) {
	              const resolvedUrlParams = new URLSearchParams(resolvedUrl.split('?')[1] || '');
	              resolvedUrlParams.set('preview_id', previewId);
	              resolvedUrl = `${resolvedUrl.split('?')[0]}?${resolvedUrlParams.toString()}`;
	            }
	            const previewUrl = `${selectedHost}/api/draft-mode/enable?redirect=${encodeURIComponent(resolvedUrl)}&token=${token}`;
	            iframe.src = previewUrl;
	          } else {
	            console.error('Error resolving URL:', data);
	            errorMessage.style.display = 'block';
	            iframe.style.display = 'none';
	          }
	        })
	        .catch((error) => {
	          console.error('Error fetching resolved URL:', error);
	          errorMessage.style.display = 'block';
	          iframe.style.display = 'none';
	        });
	    } else {
	      console.error('No selected host found.');
	      errorMessage.style.display = 'block';
	      iframe.style.display = 'none';
	    }

	    // Listen for changes in localStorage
	    window.addEventListener('storage', function (event) {
	      if (event.key === 'refresh-preview' && iframe) {
	        iframe.contentWindow.postMessage('refresh-client-router', '*');
	      }
	    });
	  })();
	</script>
	<?php wp_footer(); ?>
</body>
</html>
