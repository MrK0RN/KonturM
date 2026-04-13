<?php
declare(strict_types=1);
require_once __DIR__ . '/design-base.php';
$c = konturm_site_contacts();
?>
<header class="site-header">
  <div class="site-header__shell">
    <div class="site-header__top" aria-label="Шапка сайта">
      <div class="site-header__top-row site-header__top-row--desktop">
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
            list="site-header-search-datalist"
          />
          <datalist id="site-header-search-datalist"></datalist>
          <button class="site-header__search-submit" type="submit" aria-label="Искать">
            <img src="<?= htmlspecialchars(konturm_design_url('assets/icon-search.svg'), ENT_QUOTES, 'UTF-8') ?>" width="20" height="20" alt="" decoding="async" />
          </button>
        </form>

        <address class="site-header__contacts">
          <a class="site-header__contact-link" href="mailto:<?= htmlspecialchars($c['email_sales'], ENT_QUOTES, 'UTF-8') ?>">
            <img
              class="site-header__contact-icon"
              src="<?= htmlspecialchars(konturm_design_url('assets/icon-mail.png'), ENT_QUOTES, 'UTF-8') ?>"
              width="17"
              height="17"
              alt=""
              decoding="async"
            />
            <span><?= htmlspecialchars($c['email_sales'], ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <a class="site-header__contact-link" href="<?= htmlspecialchars($c['phone_main_href'], ENT_QUOTES, 'UTF-8') ?>">
            <img
              class="site-header__contact-icon"
              src="<?= htmlspecialchars(konturm_design_url('assets/icon-phone.png'), ENT_QUOTES, 'UTF-8') ?>"
              width="20"
              height="20"
              alt=""
              decoding="async"
            />
            <span><?= htmlspecialchars($c['phone_main_label'], ENT_QUOTES, 'UTF-8') ?></span>
          </a>
        </address>

        <a class="site-header__cta" href="/price-list.xlsx" download="прайс Контур-М апрель 2026.xlsx">
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

      <!-- Mobile (≤720px): Figma frame 1:587 — strip + toolbar -->
      <div class="site-header__mobile-figma">
        <?php require __DIR__ . '/header-mobile-strip.php'; ?>
        <?php require __DIR__ . '/header-mobile-toolbar.php'; ?>
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

  <!-- Mobile navigation drawer (hidden on desktop via CSS) -->
  <div
    id="site-mobile-drawer"
    class="site-header__drawer"
    role="dialog"
    aria-label="Навигация"
    aria-modal="true"
  >
    <div class="site-header__drawer-head">
      <a class="site-header__brand" href="/" tabindex="-1" aria-hidden="true">
        <img
          class="site-header__logo-mark"
          src="<?= htmlspecialchars(konturm_design_url('assets/logo-mark.svg'), ENT_QUOTES, 'UTF-8') ?>"
          width="36"
          height="36"
          alt=""
          decoding="async"
        />
        <span class="site-header__brand-text">
          <span class="site-header__brand-name">Контур-М</span>
          <span class="site-header__brand-tagline">Оборудование для АЗС</span>
        </span>
      </a>
      <button class="site-header__drawer-close" type="button" aria-label="Закрыть меню">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" width="24" height="24">
          <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>
    <div class="site-header__drawer-body">
      <form class="site-header__drawer-search" role="search" action="/search" method="get">
        <label class="visually-hidden" for="drawer-search-q">Поиск по каталогу</label>
        <input
          id="drawer-search-q"
          class="site-header__drawer-search-input"
          type="search"
          name="q"
          placeholder="Поиск по каталогу…"
          autocomplete="off"
        />
        <button class="site-header__drawer-search-submit" type="submit" aria-label="Искать">
          <img src="<?= htmlspecialchars(konturm_design_url('assets/icon-search.svg'), ENT_QUOTES, 'UTF-8') ?>" width="20" height="20" alt="" decoding="async" />
        </button>
      </form>

      <ul class="site-header__drawer-nav">
        <li class="site-header__drawer-nav-item">
          <a class="site-header__drawer-nav-link site-header__drawer-nav-link--current" href="/catalog">
            Каталог
            <svg viewBox="0 0 8 14" fill="none" aria-hidden="true" width="8" height="14"><path d="M1 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
        </li>
        <li class="site-header__drawer-nav-item">
          <a class="site-header__drawer-nav-link" href="/price-list">
            Прайс-лист
            <svg viewBox="0 0 8 14" fill="none" aria-hidden="true" width="8" height="14"><path d="M1 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
        </li>
        <li class="site-header__drawer-nav-item">
          <a class="site-header__drawer-nav-link" href="/promo">
            Акции
            <svg viewBox="0 0 8 14" fill="none" aria-hidden="true" width="8" height="14"><path d="M1 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
        </li>
        <li class="site-header__drawer-nav-item">
          <a class="site-header__drawer-nav-link" href="/about">
            О компании
            <svg viewBox="0 0 8 14" fill="none" aria-hidden="true" width="8" height="14"><path d="M1 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
        </li>
        <li class="site-header__drawer-nav-item">
          <a class="site-header__drawer-nav-link" href="/contacts">
            Контакты
            <svg viewBox="0 0 8 14" fill="none" aria-hidden="true" width="8" height="14"><path d="M1 1l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </a>
        </li>
      </ul>

      <address class="site-header__drawer-contacts">
        <a class="site-header__drawer-contact-link" href="<?= htmlspecialchars($c['phone_main_href'], ENT_QUOTES, 'UTF-8') ?>">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" width="20" height="20">
            <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.58.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.29 21 3 13.71 3 4.82c0-.55.45-1 1-1H7.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.58.11.35.03.74-.24 1.02L6.6 10.8z" fill="currentColor"/>
          </svg>
          <?= htmlspecialchars($c['phone_main_label'], ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a class="site-header__drawer-contact-link" href="mailto:<?= htmlspecialchars($c['email_sales'], ENT_QUOTES, 'UTF-8') ?>">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" width="20" height="20">
            <rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/>
            <path d="M2 7l10 7 10-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
          <?= htmlspecialchars($c['email_sales'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      </address>

      <a class="site-header__drawer-cta" href="/price-list.xlsx" download="прайс Контур-М апрель 2026.xlsx">
        Скачать прайс-лист
      </a>
      <a class="site-header__drawer-cart" href="/cart">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" width="22" height="22">
          <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Корзина
      </a>
    </div>
  </div>
</header>
