<?php
declare(strict_types=1);

require_once __DIR__ . '/design-base.php';

// Локальный wordmark (Figma MCP URL для подвала больше не доступен).
$footerWordmark = konturm_design_url('assets/logo-wordmark.svg');
?>

<footer class="site-footer" aria-label="Подвал сайта">
  <div class="site-footer__stage">
    <?php require __DIR__ . '/footer-brand.php'; ?>
    <?php require __DIR__ . '/footer-nav.php'; ?>
    <?php require __DIR__ . '/footer-cta.php'; ?>
    <?php require __DIR__ . '/footer-bottom.php'; ?>
  </div>
</footer>

