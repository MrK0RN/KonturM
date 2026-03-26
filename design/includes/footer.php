<?php
declare(strict_types=1);

// Figma MCP assets (mirror to /design/assets for production; URLs expire ~7 days).
$footerWordmark = 'https://www.figma.com/api/mcp/asset/6e84a2e2-8ce7-47ec-ab85-82b5038d25ec';
?>

<footer class="site-footer" aria-label="Подвал сайта">
  <div class="site-footer__stage">
    <?php require __DIR__ . '/footer-brand.php'; ?>
    <?php require __DIR__ . '/footer-nav.php'; ?>
    <?php require __DIR__ . '/footer-cta.php'; ?>
    <?php require __DIR__ . '/footer-bottom.php'; ?>
  </div>
</footer>

