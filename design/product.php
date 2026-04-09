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

$ver = static function (string $absPath): string {
    return '?v=' . (@filemtime($absPath) ?: time());
};
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Товар — Контур-М</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Ubuntu:wght@400&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?><?= $ver(__DIR__ . '/css/header.css') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-shared.css'), ENT_QUOTES, 'UTF-8') ?><?= $ver(__DIR__ . '/css/catalog-shared.css') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-layout.css'), ENT_QUOTES, 'UTF-8') ?><?= $ver(__DIR__ . '/css/catalog-layout.css') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?><?= $ver(__DIR__ . '/css/footer.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('tokens.css') ?><?= $ver(__DIR__ . '/pages/category2/css/tokens.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('product-cards.css') ?><?= $ver(__DIR__ . '/pages/category2/css/product-cards.css') ?>" />
  </head>
  <body class="catalog-page">
    <?php require __DIR__ . '/includes/header.php'; ?>

    <div class="catalog-layout">
      <main class="catalog-main-panel" style="flex: 1 1 100%; max-width: 100%; box-sizing: border-box">
        <div class="catalog-main-panel__inner">
          <div id="product-detail"></div>
        </div>
      </main>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts-bridge.php'; ?>
    <script src="<?= htmlspecialchars(konturm_design_url('js/product-page.js'), ENT_QUOTES, 'UTF-8') ?><?= $ver(__DIR__ . '/js/product-page.js') ?>" defer></script>
  </body>
</html>
