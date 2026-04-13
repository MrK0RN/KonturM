<?php
declare(strict_types=1);

require __DIR__ . '/includes/design-base.php';

$base = $GLOBALS['KONTURM_REQUEST_BASE_PATH'] ?? '';

$docUrl = static function (string $filename) use ($base): string {
    return htmlspecialchars($base . '/documents/' . rawurlencode($filename), ENT_QUOTES, 'UTF-8');
};

$groups = [
    [
        'title' => 'Сертификаты об утверждении типа средств измерений',
        'items' => [
            [
                'name'  => 'Мерники нефтяные М1Р',
                'type'  => 'Сертификат ОТ',
                'file'  => 'Сертификат   ОТ Мерники М1Р.pdf',
            ],
            [
                'name'  => 'Мерники нефтяные М2Р',
                'type'  => 'Сертификат ОТ',
                'file'  => 'Сертификат   ОТ Мерники М2Р.pdf',
            ],
            [
                'name'  => 'Метроштоки МШС',
                'type'  => 'Сертификат ОТ',
                'file'  => 'Сертификат   ОТ Метроштоки МШС.pdf',
            ],
            [
                'name'  => 'Технические мерники',
                'type'  => 'Сертификат ОТ',
                'file'  => 'Сертификат   ОТ Технические мерники.pdf',
            ],
            [
                'name'  => 'Рулетки нефтяные Р',
                'type'  => 'Сертификат ОТ',
                'file'  => 'Сертификат   ОТ на Рулетки Р.pdf',
            ],
        ],
    ],
    [
        'title' => 'Пасты-индикаторы',
        'items' => [
            [
                'name'  => 'Паста водочувствительная',
                'type'  => 'Сертификат',
                'file'  => 'Сертификат Водочувствительная паста.pdf',
            ],
            [
                'name'  => 'Паста Акватест',
                'type'  => 'Технические условия',
                'file'  => 'Титульный Лист ТУ на пасту Акватест.pdf',
            ],
        ],
    ],
];
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php require __DIR__ . '/includes/head-favicon.php'; ?>
    <title>Сертификаты — Контур-М</title>
    <meta
      name="description"
      content="Сертификаты об утверждении типа средств измерений и технические условия на продукцию ООО «Контур-М»."
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-shared.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-layout.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/certificates.css'), ENT_QUOTES, 'UTF-8') ?>" />
  </head>
  <body class="catalog-page">
    <?php require __DIR__ . '/includes/header.php'; ?>

    <div class="catalog-layout">
      <main class="catalog-main-panel" style="flex: 1 1 100%; max-width: 100%; box-sizing: border-box">
        <div class="catalog-main-panel__inner">

          <h1 class="certs-page-title">Сертификаты и документы</h1>
          <p class="certs-page-subtitle">
            Официальные сертификаты об утверждении типа средств измерений и технические условия на выпускаемую продукцию
          </p>

          <?php foreach ($groups as $group): ?>
            <section class="certs-group" aria-labelledby="certs-group-<?= htmlspecialchars(mb_strtolower(preg_replace('/\s+/', '-', $group['title'])), ENT_QUOTES, 'UTF-8') ?>">
              <h2 class="certs-group__title"><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?></h2>
              <ul class="certs-grid" role="list">
                <?php foreach ($group['items'] as $doc): ?>
                  <li>
                    <article class="cert-card">
                      <div class="cert-card__header">
                        <div class="cert-card__icon" aria-hidden="true">
                          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="8" y1="13" x2="16" y2="13" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                            <line x1="8" y1="17" x2="12" y2="17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                          </svg>
                        </div>
                        <div class="cert-card__meta">
                          <p class="cert-card__name"><?= htmlspecialchars($doc['name'], ENT_QUOTES, 'UTF-8') ?></p>
                          <span class="cert-card__type"><?= htmlspecialchars($doc['type'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      </div>
                      <a
                        class="cert-card__download"
                        href="<?= $docUrl($doc['file']) ?>"
                        target="_blank"
                        rel="noopener"
                        aria-label="Открыть PDF — <?= htmlspecialchars($doc['name'], ENT_QUOTES, 'UTF-8') ?>"
                      >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                          <path d="M12 15V3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                          <path d="M7 10l5 5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                          <path d="M3 17v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        Открыть PDF
                      </a>
                    </article>
                  </li>
                <?php endforeach; ?>
              </ul>
            </section>
          <?php endforeach; ?>

        </div>
      </main>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts-bridge.php'; ?>
  </body>
</html>
