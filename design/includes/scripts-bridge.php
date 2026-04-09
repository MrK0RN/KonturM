<?php
declare(strict_types=1);

require_once __DIR__ . '/design-base.php';

$__konturm_bp = $GLOBALS['KONTURM_REQUEST_BASE_PATH'] ?? '';
?>
<script>
  window.__KONTURM_BASE_PATH__ = <?= json_encode($__konturm_bp, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= htmlspecialchars(konturm_design_url('js/site-api.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
