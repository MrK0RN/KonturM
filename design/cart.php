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
    <title>Корзина — Контур-М</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Ubuntu:wght@400&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-shared.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= $c2css('tokens.css') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/cart.css'), ENT_QUOTES, 'UTF-8') ?>" />
  </head>
  <body class="cart-page">
    <?php require __DIR__ . '/includes/header.php'; ?>

    <div class="cart-shell">

      <!-- Breadcrumb -->
      <nav class="cart-breadcrumb" aria-label="Навигация">
        <a href="/">Каталог</a>
        <span class="cart-breadcrumb__sep" aria-hidden="true">›</span>
        <span>Корзина</span>
      </nav>

      <h1 class="cart-heading">Корзина</h1>

      <!-- Empty state (shown by JS when cart is empty) -->
      <div id="cart-empty" class="cart-empty" hidden>
        <svg class="cart-empty__icon" viewBox="0 0 64 64" fill="none" aria-hidden="true">
          <path d="M4 8h8l8 32h28l6-22H18" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="26" cy="52" r="4" stroke="currentColor" stroke-width="2.5"/>
          <circle cx="46" cy="52" r="4" stroke="currentColor" stroke-width="2.5"/>
        </svg>
        <h2 class="cart-empty__title">Корзина пуста</h2>
        <p class="cart-empty__text">Добавьте товары из каталога, чтобы оформить заявку</p>
        <a href="/" class="cart-empty__link">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M10 4L6 8l4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Перейти в каталог
        </a>
      </div>

      <!-- Main layout (hidden when cart is empty) -->
      <div id="cart-layout" class="cart-layout" hidden>

        <!-- Left column: items list -->
        <div class="cart-items-panel">
          <div class="cart-items-panel__header">
            <p class="cart-items-panel__title">
              <span id="cart-total-qty">0</span> позиций
            </p>
            <button type="button" class="cart-clear-btn" id="cart-clear">Очистить корзину</button>
          </div>

          <ul id="cart-lines" style="list-style:none;margin:0;padding:0"></ul>
        </div>

        <!-- Right column: summary + checkout form -->
        <aside class="cart-sidebar">

          <!-- Summary -->
          <div class="cart-summary">
            <h2 class="cart-summary__title">Итого</h2>
            <div class="cart-summary__row">
              <span>Позиций</span>
              <span class="cart-summary__val" id="cart-summary-qty">0</span>
            </div>
            <div class="cart-summary__row cart-summary__row--total">
              <span>Сумма</span>
              <span class="cart-summary__val cart-summary__val--total" id="cart-sum">0 ₽</span>
            </div>
          </div>

          <!-- Checkout form -->
          <div class="cart-form-card">
            <h2 class="cart-form-card__title">Оформление заявки</h2>

            <form id="cart-checkout-form" class="cart-form" action="#" method="post" novalidate>

              <div class="cart-form__group">
                <label class="cart-form__label cart-form__label--required" for="co-name">Имя</label>
                <input
                  class="cart-form__input"
                  id="co-name"
                  name="customer_name"
                  type="text"
                  placeholder="Иванов Иван"
                  autocomplete="name"
                  required
                />
              </div>

              <div class="cart-form__group">
                <label class="cart-form__label cart-form__label--required" for="co-phone">Телефон</label>
                <input
                  class="cart-form__input"
                  id="co-phone"
                  name="customer_phone"
                  type="tel"
                  placeholder="+7 (___) ___-__-__"
                  autocomplete="tel"
                  required
                />
              </div>

              <div class="cart-form__group">
                <label class="cart-form__label cart-form__label--required" for="co-email">E-mail</label>
                <input
                  class="cart-form__input"
                  id="co-email"
                  name="customer_email"
                  type="email"
                  placeholder="mail@example.ru"
                  autocomplete="email"
                  required
                />
              </div>

              <div class="cart-form__group">
                <label class="cart-form__label" for="co-company">Компания</label>
                <input
                  class="cart-form__input"
                  id="co-company"
                  name="customer_company"
                  type="text"
                  placeholder="ООО «Название»"
                  autocomplete="organization"
                />
              </div>

              <div class="cart-form__group">
                <label class="cart-form__label" for="co-inn">ИНН</label>
                <input
                  class="cart-form__input cart-form__input--inn"
                  id="co-inn"
                  name="customer_inn"
                  type="text"
                  inputmode="numeric"
                  maxlength="12"
                  pattern="[0-9]{10}|[0-9]{12}"
                  placeholder="10 или 12 цифр"
                />
                <span class="cart-form__hint">Для выставления счёта и документов</span>
              </div>

              <div class="cart-form__group">
                <label class="cart-form__label" for="co-comment">Комментарий</label>
                <textarea
                  class="cart-form__textarea"
                  id="co-comment"
                  name="comment"
                  placeholder="Уточнения по заказу, пожелания по доставке…"
                ></textarea>
              </div>

              <button type="submit" class="cart-form__submit" id="cart-submit-btn">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
                  <path d="M2 9h14M10 4l5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Отправить заявку
              </button>

            </form>

            <div id="cart-checkout-status" hidden></div>
          </div>

        </aside>
      </div><!-- /.cart-layout -->

    </div><!-- /.cart-shell -->

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts-bridge.php'; ?>
    <script src="<?= htmlspecialchars(konturm_design_url('js/cart-page.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
  </body>
</html>
