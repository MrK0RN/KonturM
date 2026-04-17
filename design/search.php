<?php
declare(strict_types=1);

require __DIR__ . '/includes/design-base.php';

$c2css = static function (string $file): string {
    return htmlspecialchars(
        konturm_design_pages_asset('category2', 'css/' . ltrim($file, '/')),
        ENT_QUOTES,
        'UTF-8'
    );
};
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php require __DIR__ . '/includes/head-favicon.php'; ?>
    <title>Поиск — Контур-М</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Ubuntu:wght@400&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-shared.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-layout.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-sidebar.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-cards.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= $c2css('tokens.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('catalog-toolbar.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('product-cards.css') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?>" />
  </head>
  <body class="catalog-page">
    <?php require __DIR__ . '/includes/header.php'; ?>

    <div class="catalog-layout">
      <main class="catalog-main-panel" style="flex: 1 1 100%; max-width: 100%; box-sizing: border-box">
        <div class="catalog-main-panel__inner">
          <h1 class="catalog-card__title" style="margin: 0 0 8px" id="search-page-title">Поиск</h1>
          <p class="site-header__brand-tagline" style="margin: 0 0 24px" id="search-page-query"></p>
          <div id="search-categories"></div>
          <div id="search-toolbar" class="search-page-toolbar" hidden aria-live="polite"></div>
          <div id="search-results"></div>
        </div>
      </main>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts-bridge.php'; ?>
    <script src="<?= htmlspecialchars(konturm_design_url('js/search-page.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
  </body>
</html>
