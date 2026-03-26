<?php
declare(strict_types=1);

/* Figma MCP assets (mirror to /design/assets for production; URLs expire ~7 days) */
$img = [
  'c1' => 'https://www.figma.com/api/mcp/asset/7c6d9de7-2931-40c5-86ab-4639d3af2a58',
  'c2a' => 'https://www.figma.com/api/mcp/asset/8a0f145a-ce05-4641-b90a-fb18d74f6f7d',
  'c2b' => 'https://www.figma.com/api/mcp/asset/fc8ad505-87e6-472f-8247-25031ed4aa32',
  'c2c' => 'https://www.figma.com/api/mcp/asset/2752548d-fcf4-4353-b325-fde76b245d44',
  'c3a' => 'https://www.figma.com/api/mcp/asset/6900116e-fadf-4b54-8355-c40c0329769e',
  'c3b' => 'https://www.figma.com/api/mcp/asset/17d83b5e-95cc-4a79-bb1c-a17c047be45c',
  'c3c' => 'https://www.figma.com/api/mcp/asset/8b3e3f8d-11e0-4129-97d3-466e53d33111',
  'disc' => 'https://www.figma.com/api/mcp/asset/f648e46f-5ff6-4e6f-a470-4111659ce061',
  'c4' => 'https://www.figma.com/api/mcp/asset/fd985834-60b2-47d3-9b14-477d0c4914e2',
  'c5' => 'https://www.figma.com/api/mcp/asset/78cf2fef-e37c-4eb0-ba53-2711208d3c2b',
  'c6a' => 'https://www.figma.com/api/mcp/asset/2d6e9829-b3cd-4d29-bde9-bbe4dd1e4b5f',
  'c6b' => 'https://www.figma.com/api/mcp/asset/3e705834-a7c6-49b3-ab82-f1bae6b4a80f',
  'c7' => 'https://www.figma.com/api/mcp/asset/15c8eed8-bb73-4057-a3ac-6faf3b9ea196',
  'c8' => 'https://www.figma.com/api/mcp/asset/2f1011e3-bf4b-46b7-9c4a-c345531f5c94',
  'c9' => 'https://www.figma.com/api/mcp/asset/250f4d2a-e3f2-407e-a35e-08cf920d3afa',
];

$arrowSvg = <<<'SVG'
<span class="catalog-card__arrow" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" focusable="false"><path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
SVG;
?>

<main class="catalog-main-panel">
  <div class="catalog-main-panel__inner">
    <h1 class="visually-hidden">Каталог оборудования Контур-М</h1>
    <h2 class="visually-hidden">Разделы каталога по группам товаров</h2>

    <ul class="catalog-grid">
      <li>
        <a id="merniki-1-go" class="catalog-card catalog-card--featured catalog-card--c1" href="#merniki-1-go">
          <h3 class="catalog-card__title">Мерники 1 разряда</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <img class="catalog-card__hero" src="<?= htmlspecialchars($img['c1'], ENT_QUOTES, 'UTF-8') ?>" width="520" height="600" alt="" decoding="async" />
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
      <li>
        <a class="catalog-card catalog-card--c2" href="#merniki-2-go">
          <h3 class="catalog-card__title">Мерники 2 разряда</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <img class="catalog-card__layer-a" src="<?= htmlspecialchars($img['c2a'], ENT_QUOTES, 'UTF-8') ?>" width="400" height="520" alt="" decoding="async" />
            <img class="catalog-card__layer-b" src="<?= htmlspecialchars($img['c2b'], ENT_QUOTES, 'UTF-8') ?>" width="320" height="420" alt="" decoding="async" />
            <img class="catalog-card__layer-c" src="<?= htmlspecialchars($img['c2c'], ENT_QUOTES, 'UTF-8') ?>" width="280" height="400" alt="" decoding="async" />
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
      <li>
        <a class="catalog-card catalog-card--c3" href="#merniki-teh">
          <h3 class="catalog-card__title">Мерники технические</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <img class="catalog-card__layer-a" src="<?= htmlspecialchars($img['c3a'], ENT_QUOTES, 'UTF-8') ?>" width="360" height="480" alt="" decoding="async" />
            <img class="catalog-card__layer-b" src="<?= htmlspecialchars($img['c3b'], ENT_QUOTES, 'UTF-8') ?>" width="280" height="360" alt="" decoding="async" />
            <img class="catalog-card__layer-c" src="<?= htmlspecialchars($img['c3c'], ENT_QUOTES, 'UTF-8') ?>" width="300" height="380" alt="" decoding="async" />
            <img class="catalog-card__disc catalog-card__disc--1" src="<?= htmlspecialchars($img['disc'], ENT_QUOTES, 'UTF-8') ?>" width="64" height="64" alt="" decoding="async" />
            <img class="catalog-card__disc catalog-card__disc--2" src="<?= htmlspecialchars($img['disc'], ENT_QUOTES, 'UTF-8') ?>" width="64" height="64" alt="" decoding="async" />
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
      <li>
        <a class="catalog-card catalog-card--c4" href="#metro-t">
          <h3 class="catalog-card__title">Метрошток Т-образный</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <div class="catalog-card__rotate-wrap">
              <img class="catalog-card__hero" src="<?= htmlspecialchars($img['c4'], ENT_QUOTES, 'UTF-8') ?>" width="480" height="320" alt="" decoding="async" />
            </div>
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
      <li>
        <a class="catalog-card catalog-card--c5" href="#metro-kruglyj">
          <h3 class="catalog-card__title">Метрошток круглый</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <img class="catalog-card__hero" src="<?= htmlspecialchars($img['c5'], ENT_QUOTES, 'UTF-8') ?>" width="520" height="360" alt="" decoding="async" />
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
      <li>
        <a class="catalog-card catalog-card--c6" href="#metro-anod">
          <h3 class="catalog-card__title">Метрошток анодированный</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <img class="catalog-card__layer-a" src="<?= htmlspecialchars($img['c6a'], ENT_QUOTES, 'UTF-8') ?>" width="320" height="400" alt="" decoding="async" />
            <img class="catalog-card__layer-b" src="<?= htmlspecialchars($img['c6b'], ENT_QUOTES, 'UTF-8') ?>" width="320" height="360" alt="" decoding="async" />
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
      <li>
        <a class="catalog-card catalog-card--c7" href="#ruletki-lot">
          <h3 class="catalog-card__title">Рулетки с лотом</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <div class="catalog-card__tape-wrap">
              <img class="catalog-card__hero" src="<?= htmlspecialchars($img['c7'], ENT_QUOTES, 'UTF-8') ?>" width="480" height="360" alt="" decoding="async" />
            </div>
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
      <li>
        <a class="catalog-card catalog-card--c8" href="#ruletki-kolco">
          <h3 class="catalog-card__title">Рулетки с кольцом</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <div class="catalog-card__rotate-wrap">
              <img class="catalog-card__hero" src="<?= htmlspecialchars($img['c8'], ENT_QUOTES, 'UTF-8') ?>" width="520" height="380" alt="" decoding="async" />
            </div>
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
      <li>
        <a class="catalog-card catalog-card--c9" href="#probootbory">
          <h3 class="catalog-card__title">Пробоотборники и прочее</h3>
          <div class="catalog-card__media" aria-hidden="true">
            <img class="catalog-card__hero" src="<?= htmlspecialchars($img['c9'], ENT_QUOTES, 'UTF-8') ?>" width="400" height="720" alt="" decoding="async" />
          </div>
          <?= $arrowSvg ?>
        </a>
      </li>
    </ul>
  </div>
</main>
