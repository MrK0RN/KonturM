<?php
declare(strict_types=1);

require_once __DIR__ . '/design-base.php';

$__konturm_bp = $GLOBALS['KONTURM_REQUEST_BASE_PATH'] ?? '';
$__konturm_c = konturm_site_contacts();
?>
<script>
  window.__KONTURM_BASE_PATH__ = <?= json_encode($__konturm_bp, JSON_UNESCAPED_UNICODE) ?>;
  window.__KONTURM_CONTACTS__ = <?= json_encode(
      [
          'phone_main_href' => $__konturm_c['phone_main_href'],
          'phone_main_label' => $__konturm_c['phone_main_label'],
      ],
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
  ) ?>;
</script>
<script src="<?= htmlspecialchars(konturm_design_url('js/site-api.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
