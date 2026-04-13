<?php
declare(strict_types=1);

require __DIR__ . '/includes/design-base.php';

$figma = static function (string $file): string {
    return htmlspecialchars(konturm_design_url('assets/figma/' . $file), ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php require __DIR__ . '/includes/head-favicon.php'; ?>
    <title>О компании — Контур-М | Производство средств измерений для АЗС</title>
    <meta
      name="description"
      content="ООО «Контур-М» — производитель мерников, метроштоков и пробоотборников для АЗС и нефтебаз. Работаем с 1999 года. Поверка СИ, соответствие ГОСТ, собственное производство."
    />
    <?php require __DIR__ . '/includes/head-fonts.php'; ?>
    <style>
      :root {
        --ab-link-bg: url("<?= htmlspecialchars(konturm_design_url('assets/figma/80e08696-8d13-4c05-8b70-8f6ebf0aa209.png'), ENT_QUOTES, 'UTF-8') ?>");
      }
    </style>
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-shared.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/about-hero.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/about-features.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/about-products.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/about-enterprise.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/about-workflow.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/about-why-us.css'), ENT_QUOTES, 'UTF-8') ?>" />
  </head>
  <body>
    <?php require __DIR__ . '/includes/header.php'; ?>

    <main>

      <!-- ═══════════════════════════════════════════════════════════════
           BLOCK 1 · Hero (Figma 1:17)
           Left: headline + description + CTA button
           Center: decorative product image (мерник)
           Right: 4×3 grid of product category links
      ══════════════════════════════════════════════════════════════════ -->
      <section class="ab-hero" aria-labelledby="ab-hero-title">
        <div class="ab-hero__shell">

          <!-- Left column -->
          <div class="ab-hero__left">
            <h1 class="ab-hero__title" id="ab-hero-title">
              Производство средств измерений для АЗС и нефтебаз
            </h1>
            <p class="ab-hero__desc">
              Мерники, метроштоки и пробоотборники с поверкой и соответствием ГОСТ
            </p>
            <a href="/contacts" class="ab-hero__cta">Заказать звонок</a>
          </div>

          <!-- Decorative product image -->
          <div class="ab-hero__image-wrap" aria-hidden="true">
            <img
              class="ab-hero__image"
              src="<?= $figma('80e08696-8d13-4c05-8b70-8f6ebf0aa209.png') ?>"
              alt=""
              loading="eager"
              decoding="async"
            />
          </div>

          <!-- Right column: product category links -->
          <nav class="ab-hero__links" aria-label="Категории продукции">
            <div class="ab-hero__links-grid">

              <!-- Row 1 -->
              <a href="/catalog/merniki-uglerodnaya" class="ab-link-cell">
                <span class="ab-link-cell__name">Мерники 2-го разряда из углеродистой стали</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="/catalog/merniki-nerzhaveyushchaya" class="ab-link-cell">
                <span class="ab-link-cell__name">Мерники 2-го разряда из нержавеющей стали</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="/catalog/merniki-tehnicheskie" class="ab-link-cell">
                <span class="ab-link-cell__name">Мерники технические 1-го класса</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>

              <!-- Row 2 — featured cell (orange) -->
              <a href="/catalog/merniki-1-razryad" class="ab-link-cell ab-link-cell--featured">
                <span class="ab-link-cell__name">Мерники 1-го разряда</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="/catalog/metroshток-anodirovannyj" class="ab-link-cell">
                <span class="ab-link-cell__name">Метрошток анодированный</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="/catalog/metroshток-t-obraznyj" class="ab-link-cell">
                <span class="ab-link-cell__name">Метрошток Т-образный</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>

              <!-- Row 3 -->
              <a href="/catalog/metroshток" class="ab-link-cell">
                <span class="ab-link-cell__name">Метрошток анодированный</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="/catalog/ruletki-s-lotom" class="ab-link-cell">
                <span class="ab-link-cell__name">Рулетки с лотом</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="/catalog/ruletki-s-kolcom" class="ab-link-cell">
                <span class="ab-link-cell__name">Рулетки с кольцом</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>

              <!-- Row 4 -->
              <a href="/catalog/zabornye-vedra" class="ab-link-cell">
                <span class="ab-link-cell__name">Заборные ведра</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="/catalog/pasta-indikatornaya" class="ab-link-cell">
                <span class="ab-link-cell__name">Паста индикаторная</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <a href="/catalog/sovki-skrebki" class="ab-link-cell">
                <span class="ab-link-cell__name">Совки и скребки</span>
                <svg class="ab-link-cell__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M7 17L17 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M7 7H17V17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>

            </div>
          </nav>

        </div>
      </section>

      <!-- ═══════════════════════════════════════════════════════════════
           BLOCK 2 · Features / Advantages (Figma 1:102)
           Three trust signals: GOST compliance, verification, delivery
      ══════════════════════════════════════════════════════════════════ -->
      <section class="ab-features" aria-label="Наши преимущества">
        <div class="ab-features__shell">
          <ul class="ab-features__grid" role="list">

            <li class="ab-feature">
              <div class="ab-feature__icon" aria-hidden="true">
                <img
                  src="<?= $figma('154f7bdb-5a4e-4176-af58-096123c7dc7c.svg') ?>"
                  alt=""
                  width="20"
                  height="20"
                />
              </div>
              <div class="ab-feature__body">
                <p class="ab-feature__title">Соответствие ГОСТ</p>
                <p class="ab-feature__sub">— подтверждённые параметры</p>
              </div>
            </li>

            <li class="ab-feature">
              <div class="ab-feature__icon" aria-hidden="true">
                <img
                  src="<?= $figma('4957a821-8d11-4878-b188-864da9314d96.svg') ?>"
                  alt=""
                  width="20"
                  height="20"
                />
              </div>
              <div class="ab-feature__body">
                <p class="ab-feature__title">Поверка и аккредитация</p>
                <p class="ab-feature__sub">— гарантия точности</p>
              </div>
            </li>

            <li class="ab-feature">
              <div class="ab-feature__icon" aria-hidden="true">
                <img
                  src="<?= $figma('189922b4-099f-4aa6-a018-7c64b3f595d6.svg') ?>"
                  alt=""
                  width="20"
                  height="20"
                />
              </div>
              <div class="ab-feature__body">
                <p class="ab-feature__title">Срок изготовления и цена</p>
                <p class="ab-feature__sub">— честные условия и прозрачные сроки</p>
              </div>
            </li>

          </ul>
        </div>
      </section>

      <!-- ═══════════════════════════════════════════════════════════════
           BLOCK 3 · Product cards (Figma 1:123)
           Four product categories with images and CTA
      ══════════════════════════════════════════════════════════════════ -->
      <section class="ab-products" aria-labelledby="ab-products-title">
        <div class="ab-products__shell">

          <h2 class="ab-products__title" id="ab-products-title">Выпускаемая продукция</h2>

          <ul class="ab-products__grid" role="list">

            <!-- Мерники -->
            <li>
              <article class="ab-pcard">
                <div class="ab-pcard__body">
                  <img
                    class="ab-pcard__img"
                    src="<?= $figma('a7203627-b1c9-45c2-a8fe-75cdf4857233.png') ?>"
                    alt="Мерник нефтяной"
                    loading="lazy"
                    decoding="async"
                  />
                  <div class="ab-pcard__meta">
                    <svg class="ab-pcard__time-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                      <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.2"/>
                      <path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="ab-pcard__delivery">от 7 дней</span>
                  </div>
                  <h3 class="ab-pcard__name">Мерники</h3>
                </div>
                <div class="ab-pcard__footer">
                  <a href="/contacts?product=merniki" class="ab-pcard__cta">Запросить цену</a>
                </div>
              </article>
            </li>

            <!-- Метроштоки -->
            <li>
              <article class="ab-pcard">
                <div class="ab-pcard__body">
                  <img
                    class="ab-pcard__img"
                    src="<?= $figma('4160a83a-2972-4a7e-b5c4-747d3141b800.png') ?>"
                    alt="Метрошток"
                    loading="lazy"
                    decoding="async"
                  />
                  <div class="ab-pcard__meta">
                    <svg class="ab-pcard__time-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                      <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.2"/>
                      <path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="ab-pcard__delivery">от 12 дней</span>
                  </div>
                  <h3 class="ab-pcard__name">Метроштоки</h3>
                </div>
                <div class="ab-pcard__footer">
                  <a href="/contacts?product=metroshтоki" class="ab-pcard__cta">Запросить цену</a>
                </div>
              </article>
            </li>

            <!-- Рулетки -->
            <li>
              <article class="ab-pcard">
                <div class="ab-pcard__body">
                  <img
                    class="ab-pcard__img"
                    src="<?= $figma('3c5d05ff-f04e-45ee-8998-d0b169170d6f.png') ?>"
                    alt="Рулетка нефтяная"
                    loading="lazy"
                    decoding="async"
                  />
                  <div class="ab-pcard__meta">
                    <svg class="ab-pcard__time-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                      <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.2"/>
                      <path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="ab-pcard__delivery">от 3 дней</span>
                  </div>
                  <h3 class="ab-pcard__name">Рулетки</h3>
                </div>
                <div class="ab-pcard__footer">
                  <a href="/contacts?product=ruletki" class="ab-pcard__cta">Запросить цену</a>
                </div>
              </article>
            </li>

            <!-- Пробоотборники -->
            <li>
              <article class="ab-pcard">
                <div class="ab-pcard__body">
                  <img
                    class="ab-pcard__img"
                    src="<?= $figma('93e9b191-7fc3-4aa0-b538-edc98db5acb1.png') ?>"
                    alt="Пробоотборник"
                    loading="lazy"
                    decoding="async"
                  />
                  <div class="ab-pcard__meta">
                    <svg class="ab-pcard__time-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                      <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.2"/>
                      <path d="M8 5v3l2 2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="ab-pcard__delivery">от 3 дней</span>
                  </div>
                  <h3 class="ab-pcard__name">Пробоотборники</h3>
                </div>
                <div class="ab-pcard__footer">
                  <a href="/contacts?product=probootsborniki" class="ab-pcard__cta">Запросить цену</a>
                </div>
              </article>
            </li>

          </ul>
        </div>
      </section>

      <?php require __DIR__ . '/includes/about-enterprise-block.php'; ?>
      <?php require __DIR__ . '/includes/about-workflow-block.php'; ?>
      <?php require __DIR__ . '/includes/about-why-us-block.php'; ?>

    </main>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts-bridge.php'; ?>
  </body>
</html>
