<?php
declare(strict_types=1);
require_once __DIR__ . '/design-base.php';
$c = konturm_site_contacts();
?>
<div class="site-footer__brand">
  <div class="site-footer__logo" aria-hidden="false">
    <img src="<?= htmlspecialchars($footerWordmark, ENT_QUOTES, 'UTF-8') ?>" width="196" height="42" alt="Контур-М" decoding="async" />
  </div>

  <address class="site-footer__contacts">
    <p class="site-footer__phone">
      <a href="<?= htmlspecialchars($c['phone_main_href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['phone_main_label'], ENT_QUOTES, 'UTF-8') ?></a>
    </p>
    <p class="site-footer__email">
      <a href="mailto:<?= htmlspecialchars($c['email_sales'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['email_sales'], ENT_QUOTES, 'UTF-8') ?></a>
    </p>
  </address>
</div>

