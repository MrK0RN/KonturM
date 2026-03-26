<?php
declare(strict_types=1);
require __DIR__ . '/includes/design-base.php';
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Контур-М — оборудование для АЗС</title>
    <meta
      name="description"
      content="Контур-М: оборудование для автозаправочных станций. Каталог, прайс-лист, акции. Связь: kontur_m16@mail.ru, +7 978 565-49-97."
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?>" />
  </head>
  <body>
    <?php require __DIR__ . '/includes/header.php'; ?>

    <?php require __DIR__ . '/includes/footer.php'; ?>
  </body>
</html>
