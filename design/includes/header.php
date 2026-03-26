<?php
declare(strict_types=1);
require_once __DIR__ . '/design-base.php';
?>
<header class="site-header">
  <div class="site-header__shell">
    <div class="site-header__top" aria-label="Шапка сайта">
      <div class="site-header__top-row">
        <a class="site-header__brand" href="/">
          <img
            class="site-header__logo-mark"
            src="<?= htmlspecialchars(konturm_design_url('assets/logo-mark.svg'), ENT_QUOTES, 'UTF-8') ?>"
            width="41"
            height="41"
            alt=""
            decoding="async"
          />
          <span class="site-header__brand-text">
            <span class="site-header__brand-name">Контур-М</span>
            <span class="site-header__brand-tagline">Оборудование для АЗС</span>
          </span>
        </a>

        <form class="site-header__search" role="search" action="/search" method="get">
          <label class="visually-hidden" for="header-search-q">Поиск по каталогу</label>
          <input
            id="header-search-q"
            class="site-header__search-input"
            type="search"
            name="q"
            placeholder="Мерники"
            autocomplete="off"
          />
          <button class="site-header__search-submit" type="submit" aria-label="Искать">
            <img src="<?= htmlspecialchars(konturm_design_url('assets/icon-search.svg'), ENT_QUOTES, 'UTF-8') ?>" width="20" height="20" alt="" decoding="async" />
          </button>
        </form>

        <address class="site-header__contacts">
          <a class="site-header__contact-link" href="mailto:kontur_m16@mail.ru">
            <img
              class="site-header__contact-icon"
              src="<?= htmlspecialchars(konturm_design_url('assets/icon-mail.png'), ENT_QUOTES, 'UTF-8') ?>"
              width="17"
              height="17"
              alt=""
              decoding="async"
            />
            <span>kontur_m16@mail.ru</span>
          </a>
          <a class="site-header__contact-link" href="tel:+79785654997">
            <img
              class="site-header__contact-icon"
              src="<?= htmlspecialchars(konturm_design_url('assets/icon-phone.png'), ENT_QUOTES, 'UTF-8') ?>"
              width="20"
              height="20"
              alt=""
              decoding="async"
            />
            <span>+7 978 565-49-97</span>
          </a>
        </address>

        <a class="site-header__cta" href="/price-list.pdf" download>
          Скачать прайс-лист
        </a>

        <a class="site-header__cart" href="/cart" aria-label="Корзина">
          <img
            src="<?= htmlspecialchars(konturm_design_url('assets/icon-cart.png'), ENT_QUOTES, 'UTF-8') ?>"
            width="52"
            height="52"
            alt=""
            decoding="async"
          />
        </a>
      </div>
    </div>

    <nav class="site-header__nav" aria-label="Основная навигация">
      <ul class="site-header__nav-list">
        <li class="site-header__nav-item">
          <a class="site-header__nav-link site-header__nav-link--current" href="/catalog" aria-current="page"
            >Каталог</a
          >
        </li>
        <li class="site-header__nav-item">
          <a class="site-header__nav-link" href="/price-list">Прайс-лист</a>
        </li>
        <li class="site-header__nav-item">
          <a class="site-header__nav-link" href="/promo">Акции</a>
        </li>
        <li class="site-header__nav-item">
          <a class="site-header__nav-link" href="/about">О компании</a>
        </li>
        <li class="site-header__nav-item">
          <a class="site-header__nav-link" href="/contacts">Контакты</a>
        </li>
      </ul>
    </nav>
  </div>
</header>
