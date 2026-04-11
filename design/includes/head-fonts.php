<?php
declare(strict_types=1);

require_once __DIR__ . '/design-base.php';

$konturmFontPreload = konturm_design_url('assets/fonts/InterVariable.woff2');
$konturmFontsCss = konturm_design_url('css/fonts.css');
?>
<link
  rel="preload"
  href="<?= htmlspecialchars($konturmFontPreload, ENT_QUOTES, 'UTF-8') ?>"
  as="font"
  type="font/woff2"
  crossorigin
/>
<link rel="stylesheet" href="<?= htmlspecialchars($konturmFontsCss, ENT_QUOTES, 'UTF-8') ?>" />
