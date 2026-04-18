<?php
declare(strict_types=1);

/**
 * Mobile header — top strip (Figma 1:588).
 * Phone and price-list link.
 */
require_once __DIR__ . '/design-base.php';
$c = konturm_site_contacts();
?>
<section class="mh-strip" aria-label="Телефон и прайс-лист">
  <address class="mh-strip__contact">
    <a class="mh-strip__phone" href="<?= htmlspecialchars($c['phone_main_href'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['phone_main_label'], ENT_QUOTES, 'UTF-8') ?></a>
  </address>
  <a class="mh-strip__cta" href="/price-list.xlsx" download="прайс Контур-М апрель 2026.xlsx">Скачать прайс-лист</a>
</section>
