<?php
declare(strict_types=1);

require_once __DIR__ . '/design-base.php';

$konturmFavicon = konturm_design_url('assets/logo-mark.svg');
?>
<link rel="icon" href="<?= htmlspecialchars($konturmFavicon, ENT_QUOTES, 'UTF-8') ?>" type="image/svg+xml" />
