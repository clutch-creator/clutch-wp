<?php
/**
 * The view for clutch settings page
 */
namespace Clutch\WP\Settings;

$icons = [
	'clutch' => get_icon('clutch'),
	'clutch-text' => get_icon('clutch-text'),
	'external-link' => get_icon('external-link'),
];

require_once __DIR__ . '/../functions.php';
?>

<div class="clt-root">
    <div class="clt-nav clt-dark">
      <div class="clt-icon-large">
        <?php echo $icons['clutch']; ?>
      </div>
      <div class="clt-logo-text">
        <?php echo $icons['clutch-text']; ?>
        <span class="clt-text-small-bold clt-text-muted">WP Plugin - v1.2.0</span>
      </div>
    </div>
    <div class="clt-content">
      <div class="clt-page">
        <form method="post" action="options.php">
          <?php
          settings_fields('clutch_settings_group');
          do_settings_sections('clutch-settings');
          submit_button();
          ?>
        </form>
      </div>
      <div class="clt-sidebar">
        <div class="clt-card clt-dark">
            <div class="clt-card-header">
              <h3>About Clutch</h3>
              <a href="https://clutch.io/" target="_blank">
                <span>Learn more</span>
                <?php echo $icons['external-link']; ?>
              </a>
            </div>
            <p>Clutch gives creative professionals complete design and functional freedom while delivering superior performance with fewer plugins.</p>
            <img class="clt-app-img" src="<?php echo CLUTCHWP_URL .
            	'includes/settings/assets/images/clutch-website.webp'; ?>" alt="Clutch App" />
        </div>
        <div class="clt-card">
            <h3>Community & Docs</h3>
            <p>Join a thriving community of developers and designers building the future of WordPress.</p>
            <a href="https://docs.clutch.io/getting-started/connecting-wordpress" target="_blank">
              <span>Getting Started</span>
              <?php echo $icons['external-link']; ?>
            </a>
            <a href="https://docs.clutch.io/" target="_blank">
              <span>Docs</span>
              <?php echo $icons['external-link']; ?>
            </a>
            <a href="https://discord.com/invite/j4bnupeese" target="_blank">
              <span>Join the Community</span>
              <?php echo $icons['external-link']; ?>
            </a>
        </div>
      </div>
    </div>
</div>