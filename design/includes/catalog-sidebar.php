<?php
declare(strict_types=1);

$catalogSidebarLinks = [
  ['href' => '#merniki-1-go', 'label' => 'Мерники 1го разряда'],
  ['href' => '#merniki-2-go', 'label' => 'Мерники 2го разряда'],
  ['href' => '#merniki-teh', 'label' => 'Технические мерники'],
  ['href' => '#metro-kruglyj', 'label' => 'Метрошток круглый'],
  ['href' => '#metro-t', 'label' => 'Метрошток Т-образный'],
  ['href' => '#metro-anod', 'label' => 'Метрошток анодированный'],
  ['href' => '#ruletki-lot', 'label' => 'Рулетки с лотом'],
  ['href' => '#ruletki-kolco', 'label' => 'Рулетки с кольцом'],
  ['href' => '#probootbory', 'label' => 'Пробоотборники'],
  ['href' => '#zabornye-vedra', 'label' => 'Заборные ведра'],
  ['href' => '#pasta', 'label' => 'Паста индикаторная'],
  ['href' => '#otvertki', 'label' => 'Отвёртки'],
  ['href' => '#urovni', 'label' => 'Строительные уровни'],
  ['href' => '#nozhi', 'label' => 'Ножи и лезвия'],
  ['href' => '#sovki', 'label' => 'Совки и скребки'],
  ['href' => '#prochee', 'label' => 'Прочее'],
];
?>
<aside class="catalog-sidebar">
  <nav class="catalog-sidebar__nav" aria-label="Категории каталога">
    <h2 class="catalog-sidebar__title">Категории</h2>
    <ul class="catalog-sidebar__list">
      <?php foreach ($catalogSidebarLinks as $item) : ?>
        <li class="catalog-sidebar__item">
          <a class="catalog-sidebar__link" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>
</aside>
