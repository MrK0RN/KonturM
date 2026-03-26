<?php
declare(strict_types=1);

require __DIR__ . '/../includes/design-base.php';

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
    <title>Мерники для нефтепродуктов — каталог | Контур-М</title>
    <meta
      name="description"
      content="Каталог мерников для нефтепродуктов: фильтр по типу, объёму, материалу, сливу и оснащению. Производство ООО «Контур-М», поверка, доставка."
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Ubuntu:wght@400&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= $c2css('tokens.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('layout.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('sidebar-filter.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('page-heading.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('active-filters.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('catalog-toolbar.css') ?>" />
    <link rel="stylesheet" href="<?= $c2css('product-cards.css') ?>" />
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "Мерники для нефтепродуктов",
        "description": "Каталог мерников для нефтепродуктов с фильтрами по характеристикам.",
        "isPartOf": {
          "@type": "WebSite",
          "name": "Контур-М"
        }
      }
    </script>
  </head>
  <body class="cat2-page">
    <?php require __DIR__ . '/../includes/header.php'; ?>
    <?php require __DIR__ . '/category2/includes/page-body.php'; ?>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
