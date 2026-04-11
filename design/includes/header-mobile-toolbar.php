<?php
declare(strict_types=1);

/**
 * Mobile header — toolbar (Figma 1:591): wordmark, search, menu.
 */
$mhAsset = static function (string $file): string {
    return htmlspecialchars(konturm_design_url('assets/figma/' . $file), ENT_QUOTES, 'UTF-8');
};
?>
<div class="mh-toolbar">
  <a class="mh-toolbar__logo" href="/">
    <img
      src="<?= $mhAsset('header-mobile-logo-wordmark.svg') ?>"
      width="94"
      height="20"
      alt="Контур-М"
      decoding="async"
    />
  </a>

  <form class="mh-toolbar__search" role="search" action="/search" method="get">
    <label class="visually-hidden" for="header-mobile-search-q">Поиск по каталогу</label>
    <input
      id="header-mobile-search-q"
      class="mh-toolbar__search-input"
      type="search"
      name="q"
      placeholder="Поиск"
      autocomplete="off"
      enterkeyhint="search"
    />
    <button class="mh-toolbar__search-submit" type="submit" aria-label="Искать">
      <img src="<?= $mhAsset('header-mobile-icon-search.svg') ?>" width="20" height="20" alt="" decoding="async" />
    </button>
  </form>

  <button
    class="site-header__burger mh-toolbar__burger"
    type="button"
    aria-label="Открыть меню"
    aria-expanded="false"
    aria-controls="site-mobile-drawer"
  >
    <img
      class="mh-toolbar__burger-icon"
      src="<?= $mhAsset('header-mobile-icon-menu.svg') ?>"
      width="20"
      height="20"
      alt=""
      decoding="async"
    />
  </button>
</div>
