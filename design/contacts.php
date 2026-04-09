<?php
declare(strict_types=1);

require __DIR__ . '/includes/design-base.php';
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Контакты — Контур-М</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-shared.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-layout.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/contacts.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?>" />
  </head>
  <body class="catalog-page">
    <?php require __DIR__ . '/includes/header.php'; ?>

    <div class="catalog-layout">
      <main class="catalog-main-panel" style="flex: 1 1 100%; max-width: 100%; box-sizing: border-box">
        <div class="catalog-main-panel__inner">
          <h1 style="font-size: 24px; font-weight: 700; margin: 0 0 32px; color: var(--color-text)">Контакты</h1>

          <!-- ── Мессенджеры ─────────────────────────────────────── -->
          <p class="contacts-section-title">Мессенджеры</p>
          <div class="contacts-messengers">

            <!-- ВКонтакте -->
            <a class="contacts-messenger-link" href="https://vk.com/konturm" target="_blank" rel="noopener">
              <span class="contacts-messenger-icon" style="background: transparent; border: none; overflow: hidden; border-radius: 12px;">
                <img src="<?= htmlspecialchars(konturm_design_url('assets/icon-vk.png'), ENT_QUOTES, 'UTF-8') ?>" width="48" height="48" alt="" style="display: block; clip-path: inset(6.5% round 21%);" />
              </span>
              ВКонтакте
            </a>

            <!-- Telegram -->
            <a class="contacts-messenger-link" href="https://t.me/konturm" target="_blank" rel="noopener">
              <span class="contacts-messenger-icon" style="background: transparent; border: none;">
                <svg viewBox="0 0 32 32" width="48" height="48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <defs>
                    <linearGradient id="tg-grad" x1="16" y1="2" x2="16" y2="30" gradientUnits="userSpaceOnUse">
                      <stop stop-color="#37BBFE"/>
                      <stop offset="1" stop-color="#007DBB"/>
                    </linearGradient>
                  </defs>
                  <circle cx="16" cy="16" r="14" fill="url(#tg-grad)"/>
                  <path d="M22.9866 10.2088C23.1112 9.40332 22.3454 8.76755 21.6292 9.082L7.36482 15.3448C6.85123 15.5703 6.8888 16.3483 7.42147 16.5179L10.3631 17.4547C10.9246 17.6335 11.5325 17.541 12.0228 17.2023L18.655 12.6203C18.855 12.4821 19.073 12.7665 18.9021 12.9426L14.1281 17.8646C13.665 18.3421 13.7569 19.1512 14.314 19.5005L19.659 22.8523C20.2585 23.2282 21.0297 22.8506 21.1418 22.1261L22.9866 10.2088Z" fill="white"/>
                </svg>
              </span>
              Telegram
            </a>

            <!-- WhatsApp -->
            <a class="contacts-messenger-link" href="https://wa.me/79785654997" target="_blank" rel="noopener">
              <span class="contacts-messenger-icon" style="background: transparent; border: none;">
                <svg viewBox="0 0 64 64" width="48" height="48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <defs>
                    <linearGradient id="wa-grad" gradientUnits="userSpaceOnUse" x1="32" x2="32" y1="4" y2="64.81">
                      <stop offset="0" stop-color="#1df47c"/>
                      <stop offset="0.31" stop-color="#12df63"/>
                      <stop offset="0.75" stop-color="#05c443"/>
                      <stop offset="1" stop-color="#00ba37"/>
                    </linearGradient>
                  </defs>
                  <rect fill="url(#wa-grad)" height="64" rx="11.2" ry="11.2" width="64"/>
                  <path fill="#fff" d="M27.42,21.38l2,5.43a.76.76,0,0,1-.1.74,10.32,10.32,0,0,1-1.48,1.71.8.8,0,0,0-.16,1.09C28.91,32,32.1,36,36.25,37.43a.79.79,0,0,0,.89-.29l1.66-2.21a.8.8,0,0,1,1-.23L45,37.3a.79.79,0,0,1,.4,1c-.57,1.62-2.36,5.57-6.19,4.93A20.79,20.79,0,0,1,26.4,36c-3.14-3.92-9.34-14,.19-15.14A.8.8,0,0,1,27.42,21.38Z"/>
                  <path fill="#fff" d="M33.6,54.8a24.21,24.21,0,0,1-11.94-3.13l-12,3.08,4.41-9.91A22,22,0,0,1,10,32C10,19.43,20.59,9.2,33.6,9.2S57.2,19.43,57.2,32,46.61,54.8,33.6,54.8ZM22.29,47.37l.73.45a20.13,20.13,0,0,0,10.58,3c10.81,0,19.6-8.43,19.6-18.8S44.41,13.2,33.6,13.2,14,21.63,14,32a18.13,18.13,0,0,0,4,11.34l.75.95-3.61,6.12Z"/>
                </svg>
              </span>
              WhatsApp
            </a>

            <!-- Max (российский мессенджер) -->
            <a class="contacts-messenger-link" href="https://max.ru/konturm" target="_blank" rel="noopener">
              <span class="contacts-messenger-icon" style="background: transparent; border: none;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" width="48" height="48" aria-hidden="true">
                  <defs>
                    <linearGradient id="max-grad-b"><stop offset="0" stop-color="#00f"/><stop offset="1" stop-opacity="0"/></linearGradient>
                    <linearGradient id="max-grad-a"><stop offset="0" stop-color="#4cf"/><stop offset=".662" stop-color="#53e"/><stop offset="1" stop-color="#93d"/></linearGradient>
                    <linearGradient id="max-grad-c" x1="117.847" x2="1000" y1="760.536" y2="500" gradientUnits="userSpaceOnUse" href="#max-grad-a"/>
                    <radialGradient id="max-grad-d" cx="-87.392" cy="1166.116" r="500" fx="-87.392" fy="1166.116" gradientTransform="rotate(51.356 1551.478 559.3)scale(2.42703433 1)" gradientUnits="userSpaceOnUse" href="#max-grad-b"/>
                  </defs>
                  <rect width="1000" height="1000" fill="url(#max-grad-c)" ry="249.681"/>
                  <rect width="1000" height="1000" fill="url(#max-grad-d)" ry="249.681"/>
                  <path fill="#fff" fill-rule="evenodd" d="M508.211 878.328c-75.007 0-109.864-10.95-170.453-54.75-38.325 49.275-159.686 87.783-164.979 21.9 0-49.456-10.95-91.248-23.36-136.873-14.782-56.21-31.572-118.807-31.572-209.508 0-216.626 177.754-379.597 388.357-379.597 210.785 0 375.947 171.001 375.947 381.604.707 207.346-166.595 376.118-373.94 377.224m3.103-571.585c-102.564-5.292-182.499 65.7-200.201 177.024-14.6 92.162 11.315 204.398 33.397 210.238 10.585 2.555 37.23-18.98 53.837-35.587a189.8 189.8 0 0 0 92.71 33.032c106.273 5.112 197.08-75.794 204.215-181.95 4.154-106.382-77.67-196.486-183.958-202.574Z" clip-rule="evenodd"/>
                </svg>
              </span>
              Max
            </a>

          </div>

          <!-- ── Отделы ───────────────────────────────────────────── -->
          <p class="contacts-section-title">Связаться с нами</p>
          <div class="contacts-groups">

            <!-- Отдел продаж -->
            <div class="contacts-group">
              <h2 class="contacts-group__title">Отдел продаж</h2>
              <address class="contacts-group__list">
                <li class="contacts-group__item">
                  <span class="contacts-group__icon">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path d="M3.62 2h2.98l1.5 3.75-1.73 1.04C7.1 8.37 9.63 10.9 11.2 12.65l1.04-1.73L16 12.42V15.4A1.6 1.6 0 0114.4 17C7.53 17 2 11.47 2 4.6A1.6 1.6 0 013.62 2z" fill="#de6814"/>
                    </svg>
                  </span>
                  <a class="contacts-group__link" href="tel:+78432023170">+7 (843) 202-31-70</a>
                </li>
                <li class="contacts-group__item">
                  <span class="contacts-group__icon">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path d="M3.62 2h2.98l1.5 3.75-1.73 1.04C7.1 8.37 9.63 10.9 11.2 12.65l1.04-1.73L16 12.42V15.4A1.6 1.6 0 0114.4 17C7.53 17 2 11.47 2 4.6A1.6 1.6 0 013.62 2z" fill="#de6814"/>
                    </svg>
                  </span>
                  <a class="contacts-group__link" href="tel:+79272495218">+7 927-249-52-18</a>
                </li>
                <li class="contacts-group__item">
                  <span class="contacts-group__icon">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path d="M2 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm2 0l5 3.5L14 5H4zm0 2.5V13h10V7.5L9 11 4 7.5z" fill="#de6814"/>
                    </svg>
                  </span>
                  <a class="contacts-group__link" href="mailto:kontur_m16@mail.ru">kontur_m16@mail.ru</a>
                </li>
              </address>
            </div>

            <!-- Отдел метрологии -->
            <div class="contacts-group">
              <h2 class="contacts-group__title">Отдел метрологии</h2>
              <address class="contacts-group__list">
                <li class="contacts-group__item">
                  <span class="contacts-group__icon">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path d="M2 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm2 0l5 3.5L14 5H4zm0 2.5V13h10V7.5L9 11 4 7.5z" fill="#de6814"/>
                    </svg>
                  </span>
                  <a class="contacts-group__link" href="mailto:kontur_metrolog@mail.ru">kontur_metrolog@mail.ru</a>
                </li>
              </address>
            </div>

          </div>

          <!-- ── Карта ───────────────────────────────────────────── -->
          <p class="contacts-section-title contacts-section-title--map">Как нас найти</p>
          <div class="contacts-map">
            <div class="contacts-map__frame-wrap">
              <iframe
                class="contacts-map__iframe"
                src="https://yandex.ru/map-widget/v1/?oid=92963604301&amp;ll=49.275773%2C55.911633&amp;z=17"
                title="Контур-М на Яндекс Картах"
                loading="lazy"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
              ></iframe>
            </div>
            <a
              class="contacts-map__open-link"
              href="https://yandex.ru/maps/org/kontur_m/92963604301/?ll=49.275773%2C55.911633&amp;z=17"
              target="_blank"
              rel="noopener noreferrer"
            >
              Открыть в Яндекс Картах
            </a>
          </div>

        </div>
      </main>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts-bridge.php'; ?>
  </body>
</html>
