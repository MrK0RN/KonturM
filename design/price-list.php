<?php
declare(strict_types=1);

require __DIR__ . '/includes/design-base.php';

function price(int|float|null $v): string {
    if ($v === null || $v == 0) return '—';
    return number_format((float)$v, 0, ',', '&thinsp;') . ' ₽';
}
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Прайс-лист — Контур-М</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/header.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/catalog-shared.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/footer.css'), ENT_QUOTES, 'UTF-8') ?>" />
    <link rel="stylesheet" href="<?= htmlspecialchars(konturm_design_url('css/price-list.css'), ENT_QUOTES, 'UTF-8') ?>" />
  </head>
  <body class="catalog-page">
    <?php require __DIR__ . '/includes/header.php'; ?>

    <div class="pl-page">

      <!-- Hero -->
      <div class="pl-hero">
        <div class="pl-hero__left">
          <h1 class="pl-hero__title">Прайс-лист</h1>
          <p class="pl-hero__sub">Цены действительны с января 2026 г. Для оформления заявки воспользуйтесь корзиной или свяжитесь с менеджером. НДС не включён.</p>
        </div>
        <div class="pl-hero__actions">
          <a class="pl-btn pl-btn--primary" href="/price-list.pdf" download>
            <svg width="16" height="16" fill="none" viewBox="0 0 16 16"><path d="M8 1v9m0 0-3-3m3 3 3-3M3 14h10" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Скачать прайс
          </a>
          <a class="pl-btn pl-btn--outline" href="/cart">Корзина заявок</a>
        </div>
      </div>

      <!-- Tab navigation -->
      <div class="pl-tabs" role="tablist">
        <button class="pl-tab is-active" role="tab" data-tab="ruletki">Рулетки</button>
        <button class="pl-tab" role="tab" data-tab="metroshoki">Метроштоки</button>
        <button class="pl-tab" role="tab" data-tab="merniki">Мерники 2Р</button>
        <button class="pl-tab" role="tab" data-tab="merniki1r">Мерники 1Р</button>
        <button class="pl-tab" role="tab" data-tab="mernikitech">Мерники техн.</button>
        <button class="pl-tab" role="tab" data-tab="probo">Пробоотборники</button>
        <button class="pl-tab" role="tab" data-tab="raznoe">Разное</button>
      </div>

      <!-- ═══════════════════════════════════════════════════════
           PANEL 1 — РУЛЕТКИ
      ══════════════════════════════════════════════════════════ -->
      <div class="pl-panel is-active" id="panel-ruletki" role="tabpanel">

        <!-- С грузом -->
        <div class="pl-section">
          <h2 class="pl-section__title">Рулетки с грузом</h2>
          <p class="pl-note">ТУ 4433-011-50618805-2012 по ГОСТ 7502-98. Свид. № 48078, продл. до 02.05.2027, рег. № 51171-12. Рулетки поверены, имеют паспорт, индивидуальный заводской номер и упаковку.</p>

          <p class="pl-section__sub" style="margin-top:20px">3 класс точности</p>
          <div class="pl-dual">
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--carbon">Углеродистая сталь</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr>
                    <th>Артикул</th>
                    <th>Груз</th>
                    <th class="th-price">Цена</th>
                  </tr></thead>
                  <tbody>
                    <tr><td class="td-code">Р10У3Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(8090) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(8690) ?></td></tr>
                    <tr><td class="td-code">Р20У3Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(9390) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(9990) ?></td></tr>
                    <tr><td class="td-code">Р30У3Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(10690) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(11300) ?></td></tr>
                    <tr><td class="td-code">Р50У3Г</td><td>2&thinsp;кг</td><td class="td-price"><?= price(13080) ?></td></tr>
                    <tr><td class="td-code">Р100У3Г</td><td>2&thinsp;кг</td><td class="td-price"><?= price(20800) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--stainless">Нержавеющая сталь</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr>
                    <th>Артикул</th>
                    <th>Груз</th>
                    <th class="th-price">Цена</th>
                  </tr></thead>
                  <tbody>
                    <tr><td class="td-code">Р10Н3Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(8690) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(9390) ?></td></tr>
                    <tr><td class="td-code">Р20Н3Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(10830) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(11530) ?></td></tr>
                    <tr><td class="td-code">Р30Н3Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(12600) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(13080) ?></td></tr>
                    <tr><td colspan="3" class="td-price-empty" style="text-align:center;padding:10px">—</td></tr>
                    <tr><td colspan="3" class="td-price-empty" style="text-align:center;padding:10px">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <p class="pl-section__sub">2 класс точности</p>
          <div class="pl-dual">
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--carbon">Углеродистая сталь</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr>
                    <th>Артикул</th>
                    <th>Груз</th>
                    <th class="th-price">Цена</th>
                  </tr></thead>
                  <tbody>
                    <tr><td class="td-code">Р10У2Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(9280) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(9880) ?></td></tr>
                    <tr><td class="td-code">Р20У2Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(10580) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(11180) ?></td></tr>
                    <tr><td class="td-code">Р30У2Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(11890) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(12490) ?></td></tr>
                    <tr><td class="td-code">Р50У2Г</td><td>2&thinsp;кг</td><td class="td-price"><?= price(14260) ?></td></tr>
                    <tr><td class="td-code">Р100У2Г</td><td>2&thinsp;кг</td><td class="td-price"><?= price(21990) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--stainless">Нержавеющая сталь</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr>
                    <th>Артикул</th>
                    <th>Груз</th>
                    <th class="th-price">Цена</th>
                  </tr></thead>
                  <tbody>
                    <tr><td class="td-code">Р10Н2Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(9880) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(10580) ?></td></tr>
                    <tr><td class="td-code">Р20Н2Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(12020) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(12720) ?></td></tr>
                    <tr><td class="td-code">Р30Н2Г</td><td>1&thinsp;кг</td><td class="td-price"><?= price(13790) ?></td></tr>
                    <tr><td class="td-code"></td><td>2&thinsp;кг</td><td class="td-price"><?= price(14510) ?></td></tr>
                    <tr><td colspan="3" class="td-price-empty" style="text-align:center;padding:10px">—</td></tr>
                    <tr><td colspan="3" class="td-price-empty" style="text-align:center;padding:10px">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- С кольцом -->
        <div class="pl-section">
          <h2 class="pl-section__title">Рулетки с кольцом</h2>

          <p class="pl-section__sub">3 класс точности</p>
          <div class="pl-dual">
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--carbon">Углеродистая сталь</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr>
                    <th>Артикул</th>
                    <th class="th-price">Цена</th>
                  </tr></thead>
                  <tbody>
                    <tr><td class="td-code">Р10У3К</td><td class="td-price"><?= price(7260) ?></td></tr>
                    <tr><td class="td-code">Р20У3К</td><td class="td-price"><?= price(8560) ?></td></tr>
                    <tr><td class="td-code">Р30У3К</td><td class="td-price"><?= price(9880) ?></td></tr>
                    <tr><td class="td-code">Р50У3К</td><td class="td-price"><?= price(11650) ?></td></tr>
                    <tr><td class="td-code">Р100У3К</td><td class="td-price"><?= price(18900) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--stainless">Нержавеющая сталь</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr>
                    <th>Артикул</th>
                    <th class="th-price">Цена</th>
                  </tr></thead>
                  <tbody>
                    <tr><td class="td-code">Р10Н3К</td><td class="td-price"><?= price(7740) ?></td></tr>
                    <tr><td class="td-code">Р20Н3К</td><td class="td-price"><?= price(9880) ?></td></tr>
                    <tr><td class="td-code">Р30Н3К</td><td class="td-price"><?= price(11650) ?></td></tr>
                    <tr><td colspan="2" class="td-price-empty" style="padding:10px;text-align:center">—</td></tr>
                    <tr><td colspan="2" class="td-price-empty" style="padding:10px;text-align:center">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <p class="pl-section__sub">2 класс точности</p>
          <div class="pl-dual">
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--carbon">Углеродистая сталь</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr>
                    <th>Артикул</th>
                    <th class="th-price">Цена</th>
                  </tr></thead>
                  <tbody>
                    <tr><td class="td-code">Р10У2К</td><td class="td-price"><?= price(8210) ?></td></tr>
                    <tr><td class="td-code">Р20У2К</td><td class="td-price"><?= price(9510) ?></td></tr>
                    <tr><td class="td-code">Р30У2К</td><td class="td-price"><?= price(10830) ?></td></tr>
                    <tr><td class="td-code">Р50У2К</td><td class="td-price"><?= price(12960) ?></td></tr>
                    <tr><td class="td-code">Р100У2К</td><td class="td-price"><?= price(20800) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--stainless">Нержавеющая сталь</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr>
                    <th>Артикул</th>
                    <th class="th-price">Цена</th>
                  </tr></thead>
                  <tbody>
                    <tr><td class="td-code">Р10Н2К</td><td class="td-price"><?= price(8920) ?></td></tr>
                    <tr><td class="td-code">Р20Н2К</td><td class="td-price"><?= price(11060) ?></td></tr>
                    <tr><td class="td-code">Р30Н2К</td><td class="td-price"><?= price(12830) ?></td></tr>
                    <tr><td colspan="2" class="td-price-empty" style="padding:10px;text-align:center">—</td></tr>
                    <tr><td colspan="2" class="td-price-empty" style="padding:10px;text-align:center">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /panel-ruletki -->


      <!-- ═══════════════════════════════════════════════════════
           PANEL 2 — МЕТРОШТОКИ
      ══════════════════════════════════════════════════════════ -->
      <div class="pl-panel" id="panel-metroshoki" role="tabpanel">
        <div class="pl-section">
          <h2 class="pl-section__title">Метроштоки</h2>
          <p class="pl-note">Возможно изготовление метроштоков со шкалой на полную длину.</p>

          <div class="pl-triple" style="margin-top:20px">
            <!-- Круглые -->
            <div>
              <p class="pl-dual__label" style="margin:0 0 8px"><span class="pl-badge pl-badge--carbon">Круглые</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>МШС-1,5 (1 звено)</td><td class="td-price"><?= price(4480) ?></td></tr>
                    <tr><td>МШС-2,0 (1 звено)</td><td class="td-price"><?= price(6900) ?></td></tr>
                    <tr><td>МШС-2,5 (1 звено)</td><td class="td-price"><?= price(8540) ?></td></tr>
                    <tr><td>МШС-2,5 (2 звена)</td><td class="td-price"><?= price(8950) ?></td></tr>
                    <tr><td>МШС-3,0 (1 звено)</td><td class="td-price"><?= price(8540) ?></td></tr>
                    <tr><td>МШС-3,0 (2 звена)</td><td class="td-price"><?= price(8950) ?></td></tr>
                    <tr><td>МШС-3,5 (1 звено)</td><td class="td-price"><?= price(8950) ?></td></tr>
                    <tr><td>МШС-3,5 (2 звена)</td><td class="td-price"><?= price(8950) ?></td></tr>
                    <tr><td>МШС-3,5 (3 звена)</td><td class="td-price"><?= price(9900) ?></td></tr>
                    <tr><td>МШС-4,0 (1 звено)</td><td class="td-price"><?= price(9730) ?></td></tr>
                    <tr><td>МШС-4,0 (2 звена)</td><td class="td-price"><?= price(9730) ?></td></tr>
                    <tr><td>МШС-4,0 (3 звена)</td><td class="td-price"><?= price(10250) ?></td></tr>
                    <tr><td>МШС-4,5 (2 звена)</td><td class="td-price"><?= price(10480) ?></td></tr>
                    <tr><td>МШС-4,5 (3 звена)</td><td class="td-price"><?= price(11310) ?></td></tr>
                    <tr><td>МШС-5,0 (3 звена)</td><td class="td-price"><?= price(12080) ?></td></tr>
                    <tr><td>МШС-5,5 (3 звена)</td><td class="td-price"><?= price(13610) ?></td></tr>
                    <tr><td>МШС-6,0 (3 звена)</td><td class="td-price"><?= price(14900) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Круглые анодированные -->
            <div>
              <p class="pl-dual__label" style="margin:0 0 8px"><span class="pl-badge pl-badge--stainless">Круглые анодированные</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>МШС-1,5 (1 звено)</td><td class="td-price"><?= price(5770) ?></td></tr>
                    <tr><td>МШС-2,0 (1 звено)</td><td class="td-price"><?= price(7660) ?></td></tr>
                    <tr><td>МШС-2,5 (1 звено)</td><td class="td-price"><?= price(9730) ?></td></tr>
                    <tr><td>МШС-2,5 (2 звена)</td><td class="td-price"><?= price(10250) ?></td></tr>
                    <tr><td>МШС-3,0 (1 звено)</td><td class="td-price"><?= price(10250) ?></td></tr>
                    <tr><td>МШС-3,0 (2 звена)</td><td class="td-price"><?= price(10790) ?></td></tr>
                    <tr><td>МШС-3,5 (1 звено)</td><td class="td-price"><?= price(10900) ?></td></tr>
                    <tr><td>МШС-3,5 (2 звена)</td><td class="td-price"><?= price(10900) ?></td></tr>
                    <tr><td>МШС-3,5 (3 звена)</td><td class="td-price"><?= price(11540) ?></td></tr>
                    <tr><td>МШС-4,0 (1 звено)</td><td class="td-price"><?= price(11020) ?></td></tr>
                    <tr><td>МШС-4,0 (2 звена)</td><td class="td-price"><?= price(11540) ?></td></tr>
                    <tr><td>МШС-4,0 (3 звена)</td><td class="td-price"><?= price(12190) ?></td></tr>
                    <tr><td>МШС-4,5 (2 звена)</td><td class="td-price"><?= price(12720) ?></td></tr>
                    <tr><td>МШС-4,5 (3 звена)</td><td class="td-price"><?= price(13370) ?></td></tr>
                    <tr><td colspan="2" class="td-price-empty" style="text-align:center;padding:10px">—</td></tr>
                    <tr><td colspan="2" class="td-price-empty" style="text-align:center;padding:10px">—</td></tr>
                    <tr><td colspan="2" class="td-price-empty" style="text-align:center;padding:10px">—</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Т-образные -->
            <div>
              <p class="pl-dual__label" style="margin:0 0 8px"><span class="pl-badge pl-badge--carbon">Т-образные</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>МШС-1,5 (1 звено)</td><td class="td-price"><?= price(5840) ?></td></tr>
                    <tr><td>МШС-2,0 (1 звено)</td><td class="td-price"><?= price(7140) ?></td></tr>
                    <tr><td>МШС-3,5 (2 звена)</td><td class="td-price"><?= price(9490) ?></td></tr>
                    <tr><td>МШС-4,0 (2 звена)</td><td class="td-price"><?= price(10250) ?></td></tr>
                    <tr><td>МШС-4,5 (2 звена)</td><td class="td-price"><?= price(10900) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /panel-metroshoki -->


      <!-- ═══════════════════════════════════════════════════════
           PANEL 3 — МЕРНИКИ 2 РАЗРЯДА
      ══════════════════════════════════════════════════════════ -->
      <div class="pl-panel" id="panel-merniki" role="tabpanel">
        <div class="pl-section">
          <h2 class="pl-section__title">Мерники 2-го разряда</h2>
          <p class="pl-note">ГОСТ 8.400-2013. Свид. об утв. типа № 65932 до 02 мая 2027, рег. № 67392-17.</p>
          <div class="pl-table-wrap" style="margin-top:20px">
            <table class="pl-table">
              <thead>
                <tr>
                  <th>Артикул</th>
                  <th>Описание</th>
                  <th class="th-price">Углерод. сталь δ 0,001</th>
                  <th class="th-price">Нерж. сталь δ 0,001</th>
                  <th class="th-price">Нерж. сталь δ 0,0005</th>
                </tr>
              </thead>
              <tbody>
                <tr><td class="td-code">М2Р-2-01</td><td>без пеногасителя, верхний слив</td><td class="td-price"><?= price(12000) ?></td><td class="td-price"><?= price(28800) ?></td><td class="td-price-empty">—</td></tr>
                <tr><td class="td-code">М2Р-5-01</td><td>без пеногасителя, верхний слив</td><td class="td-price"><?= price(14600) ?></td><td class="td-price"><?= price(38400) ?></td><td class="td-price-empty">—</td></tr>
                <tr><td class="td-code">М2Р-5-01</td><td>без пеногасителя, нижний слив</td><td class="td-price"><?= price(18600) ?></td><td class="td-price"><?= price(42000) ?></td><td class="td-price-empty">—</td></tr>
                <tr><td class="td-code">М2Р-10-01</td><td>без пеногасителя, верхний слив</td><td class="td-price"><?= price(18600) ?></td><td class="td-price"><?= price(50300) ?></td><td class="td-price"><?= price(59500) ?></td></tr>
                <tr><td class="td-code">М2Р-10-01</td><td>без пеногасителя, нижний слив</td><td class="td-price"><?= price(23300) ?></td><td class="td-price"><?= price(56800) ?></td><td class="td-price"><?= price(68700) ?></td></tr>
                <tr><td class="td-code">М2Р-10-01П</td><td>пеногаситель, верхний слив</td><td class="td-price"><?= price(23300) ?></td><td class="td-price"><?= price(55500) ?></td><td class="td-price"><?= price(64800) ?></td></tr>
                <tr><td class="td-code">М2Р-10-01П</td><td>пеногаситель, нижний слив</td><td class="td-price"><?= price(27800) ?></td><td class="td-price"><?= price(62100) ?></td><td class="td-price"><?= price(76600) ?></td></tr>
                <tr><td class="td-code">М2Р-10-СШ</td><td>пеногаситель, спецшкала, верхний слив</td><td class="td-price"><?= price(36800) ?></td><td class="td-price"><?= price(78800) ?></td><td class="td-price-empty">—</td></tr>
                <tr><td class="td-code">М2Р-10-СШ</td><td>пеногаситель, спецшкала, нижний слив</td><td class="td-price"><?= price(43700) ?></td><td class="td-price"><?= price(87000) ?></td><td class="td-price-empty">—</td></tr>
                <tr><td class="td-code">М2Р-20-01</td><td>без пеногасителя</td><td class="td-price"><?= price(28600) ?></td><td class="td-price"><?= price(63400) ?></td><td class="td-price"><?= price(71400) ?></td></tr>
                <tr><td class="td-code">М2Р-20-01П</td><td>пеногаситель</td><td class="td-price"><?= price(33800) ?></td><td class="td-price"><?= price(70000) ?></td><td class="td-price"><?= price(83200) ?></td></tr>
                <tr><td class="td-code">М2Р-20-СШ</td><td>пеногаситель, спецшкала</td><td class="td-price"><?= price(61300) ?></td><td class="td-price"><?= price(90600) ?></td><td class="td-price-empty">—</td></tr>
                <tr><td class="td-code">М2Р-50-01</td><td>без пеногасителя</td><td class="td-price"><?= price(48900) ?></td><td class="td-price"><?= price(91200) ?></td><td class="td-price"><?= price(103000) ?></td></tr>
                <tr><td class="td-code">М2Р-50-01П</td><td>пеногаситель</td><td class="td-price"><?= price(55500) ?></td><td class="td-price"><?= price(99000) ?></td><td class="td-price"><?= price(114800) ?></td></tr>
                <tr><td class="td-code">М2Р-50-СШ</td><td>пеногаситель, спецшкала</td><td class="td-price"><?= price(92600) ?></td><td class="td-price"><?= price(133200) ?></td><td class="td-price-empty">—</td></tr>
                <tr><td class="td-code">М2Р-100-01</td><td>без пеногасителя</td><td class="td-price-empty">—</td><td class="td-price"><?= price(125400) ?></td><td class="td-price"><?= price(134600) ?></td></tr>
                <tr><td class="td-code">М2Р-100-01П</td><td>пеногаситель</td><td class="td-price-empty">—</td><td class="td-price"><?= price(138500) ?></td><td class="td-price"><?= price(145200) ?></td></tr>
                <tr><td class="td-code">М2Р-200-01</td><td>—</td><td class="td-price-empty">—</td><td class="td-price"><?= price(191300) ?></td><td class="td-price"><?= price(203100) ?></td></tr>
                <tr><td class="td-code">М2Р-500-01</td><td>тумба с домкратами или 3 опоры с домкратами</td><td class="td-price-empty">—</td><td class="td-price"><?= price(692200) ?></td><td class="td-price"><?= price(731700) ?></td></tr>
                <tr><td class="td-code">М2Р-1000-01</td><td>тумба с домкратами или 3 опоры с домкратами</td><td class="td-price-empty">—</td><td class="td-price"><?= price(857000) ?></td><td class="td-price"><?= price(909800) ?></td></tr>
                <tr><td class="td-code">М2Р-1500-01</td><td>тумба с домкратами или 3 опоры с домкратами</td><td class="td-price-empty">—</td><td class="td-price"><?= price(1015200) ?></td><td class="td-price"><?= price(1094300) ?></td></tr>
                <tr><td class="td-code">М2Р-2000-01</td><td>стационарный (тумба, домкраты)</td><td class="td-price-empty">—</td><td class="td-price"><?= price(1426200) ?></td><td class="td-price"><?= price(1522100) ?></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /panel-merniki -->


      <!-- ═══════════════════════════════════════════════════════
           PANEL 4 — МЕРНИКИ 1 РАЗРЯДА
      ══════════════════════════════════════════════════════════ -->
      <div class="pl-panel" id="panel-merniki1r" role="tabpanel">
        <div class="pl-section">
          <h2 class="pl-section__title">Мерники 1-го разряда</h2>
          <p class="pl-note">Нержавеющая сталь. ГОСТ 8.400-2013. Свид. об утв. типа № 65932 до 02 мая 2027, рег. № 67392-17.</p>
          <div class="pl-triple" style="margin-top:20px">

            <!-- Группа 01 -->
            <div>
              <p class="pl-dual__label" style="margin:0 0 8px"><span class="pl-badge pl-badge--carbon">С отметкой на горловине (01)</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Артикул</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td class="td-code">М1Р-2-01</td><td class="td-price"><?= price(147500) ?></td></tr>
                    <tr><td class="td-code">М1Р-5-01</td><td class="td-price"><?= price(187100) ?></td></tr>
                    <tr><td class="td-code">М1Р-10-01</td><td class="td-price"><?= price(223100) ?></td></tr>
                    <tr><td class="td-code">М1Р-20-01</td><td class="td-price"><?= price(253100) ?></td></tr>
                    <tr><td class="td-code">М1Р-50-01</td><td class="td-price"><?= price(356100) ?></td></tr>
                    <tr><td class="td-code">М1Р-100-01</td><td class="td-price"><?= price(450800) ?></td></tr>
                    <tr><td class="td-code">М1Р-200-01</td><td class="td-price"><?= price(573000) ?></td></tr>
                    <tr><td class="td-code">М1Р-500-01</td><td class="td-price"><?= price(1570000) ?></td></tr>
                    <tr><td class="td-code">М1Р-1000-01</td><td class="td-price"><?= price(2025400) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Группа 02 -->
            <div>
              <p class="pl-dual__label" style="margin:0 0 8px"><span class="pl-badge pl-badge--stainless">С переливной горловиной (02)</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Артикул</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td class="td-code">М1Р-10-02</td><td class="td-price"><?= price(242300) ?></td></tr>
                    <tr><td class="td-code">М1Р-20-02</td><td class="td-price"><?= price(280600) ?></td></tr>
                    <tr><td class="td-code">М1Р-50-02</td><td class="td-price"><?= price(404000) ?></td></tr>
                    <tr><td class="td-code">М1Р-100-02</td><td class="td-price"><?= price(497400) ?></td></tr>
                    <tr><td class="td-code">М1Р-200-02</td><td class="td-price"><?= price(630500) ?></td></tr>
                    <tr><td class="td-code">М1Р-500-02</td><td class="td-price"><?= price(1761800) ?></td></tr>
                    <tr><td class="td-code">М1Р-1000-02</td><td class="td-price"><?= price(2193100) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Группа 03 -->
            <div>
              <p class="pl-dual__label" style="margin:0 0 8px"><span class="pl-badge pl-badge--carbon">Со шкалой +1% от вместимости (03)</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Артикул</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td class="td-code">М1Р-50-03</td><td class="td-price"><?= price(444800) ?></td></tr>
                    <tr><td class="td-code">М1Р-100-03</td><td class="td-price"><?= price(558600) ?></td></tr>
                    <tr><td class="td-code">М1Р-200-03</td><td class="td-price"><?= price(690500) ?></td></tr>
                    <tr><td class="td-code">М1Р-500-03</td><td class="td-price"><?= price(1773700) ?></td></tr>
                    <tr><td class="td-code">М1Р-1000-03</td><td class="td-price"><?= price(2265100) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div><!-- /panel-merniki1r -->


      <!-- ═══════════════════════════════════════════════════════
           PANEL 5 — МЕРНИКИ ТЕХНИЧЕСКИЕ
      ══════════════════════════════════════════════════════════ -->
      <div class="pl-panel" id="panel-mernikitech" role="tabpanel">
        <div class="pl-section">
          <h2 class="pl-section__title">Мерники металлические технические 1 класс</h2>
          <p class="pl-note">Нержавеющая сталь. ГОСТ 8.633-2013. Свид. об утв. типа № 85597-22 от 18.05.2022 до 18.05.2027.</p>

          <p class="pl-section__sub" style="margin-top:20px">Стандартные (полной вместимости)</p>
          <div class="pl-dual">
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--carbon">Исполнение 1.1</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Артикул</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td class="td-code">М1кл&nbsp;5-1.1</td><td class="td-price"><?= price(55700) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;10-1.1</td><td class="td-price"><?= price(82800) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;20-1.1</td><td class="td-price"><?= price(115200) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;50-1.1</td><td class="td-price"><?= price(194300) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;100-1.1</td><td class="td-price"><?= price(266200) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;200-1.1</td><td class="td-price"><?= price(368000) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;500-1.1</td><td class="td-price"><?= price(1044000) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;750-1.1</td><td class="td-price"><?= price(1221300) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;1000-1.1</td><td class="td-price"><?= price(1363900) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;1500-1.1</td><td class="td-price"><?= price(1802500) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;2000-1.1</td><td class="td-price"><?= price(1911600) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;2500-1.1</td><td class="td-price"><?= price(2175200) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--stainless">Исполнение 1.2</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Артикул</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td class="td-code">М1кл&nbsp;5-1.2</td><td class="td-price"><?= price(50400) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;10-1.2</td><td class="td-price"><?= price(75700) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;20-1.2</td><td class="td-price"><?= price(108000) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;50-1.2</td><td class="td-price"><?= price(182400) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;100-1.2</td><td class="td-price"><?= price(253100) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;200-1.2</td><td class="td-price"><?= price(351300) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;500-1.2</td><td class="td-price"><?= price(990100) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;750-1.2</td><td class="td-price"><?= price(1162600) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;1000-1.2</td><td class="td-price"><?= price(1296800) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;1500-1.2</td><td class="td-price"><?= price(1712700) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;2000-1.2</td><td class="td-price"><?= price(1815700) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;2500-1.2</td><td class="td-price"><?= price(2067300) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <p class="pl-section__sub">Шкальные</p>
          <div class="pl-dual">
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--carbon">С окнами (2.0)</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Артикул</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td class="td-code">М1кл&nbsp;100-2.0</td><td class="td-price"><?= price(1062000) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;200-2.0</td><td class="td-price"><?= price(1268100) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;500-2.0</td><td class="td-price"><?= price(1524500) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;750-2.0</td><td class="td-price"><?= price(1651500) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;1000-2.0</td><td class="td-price"><?= price(1953500) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;1500-2.0</td><td class="td-price"><?= price(2102100) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;2000-2.0</td><td class="td-price"><?= price(2345400) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;2500-2.0</td><td class="td-price"><?= price(2488100) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div>
              <p class="pl-dual__label"><span class="pl-badge pl-badge--stainless">С уровнемерными трубками (3.0)</span></p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Артикул</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td class="td-code">М1кл&nbsp;50-3.0</td><td class="td-price"><?= price(889400) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;100-3.0</td><td class="td-price"><?= price(955300) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;200-3.0</td><td class="td-price"><?= price(1142200) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;500-3.0</td><td class="td-price"><?= price(1372300) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;750-3.0</td><td class="td-price"><?= price(1486100) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;1000-3.0</td><td class="td-price"><?= price(1759400) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;1500-3.0</td><td class="td-price"><?= price(1891200) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;2000-3.0</td><td class="td-price"><?= price(2110600) ?></td></tr>
                    <tr><td class="td-code">М1кл&nbsp;2500-3.0</td><td class="td-price"><?= price(2262700) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /panel-mernikitech -->


      <!-- ═══════════════════════════════════════════════════════
           PANEL 6 — ПРОБООТБОРНИКИ
      ══════════════════════════════════════════════════════════ -->
      <div class="pl-panel" id="panel-probo" role="tabpanel">
        <div class="pl-section">
          <h2 class="pl-section__title">Пробоотборники</h2>

          <div class="pl-dual" style="margin-top:8px">
            <!-- ПО-серия -->
            <div>
              <p class="pl-section__sub" style="margin-top:16px">Серия ПО</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>ПО-80, диам. 80 мм, 1 л, без троса</td><td class="td-price"><?= price(6330) ?></td></tr>
                    <tr><td>ПО-45-500, диам. 45 мм, 0,5 л, без троса</td><td class="td-price"><?= price(6010) ?></td></tr>
                    <tr><td>ПО-45-330, диам. 45 мм, 0,33 л, без троса</td><td class="td-price"><?= price(5350) ?></td></tr>
                    <tr><td>ПО-М45-650, диам. 45 мм, 0,65 л, провод заземл. 7м, зажим</td><td class="td-price"><?= price(8630) ?></td></tr>
                    <tr><td>ПО-М45-330, диам. 45 мм, 0,33 л, провод заземл. 7м, зажим</td><td class="td-price"><?= price(7100) ?></td></tr>
                  </tbody>
                </table>
              </div>

              <p class="pl-section__sub" style="margin-top:20px">Серия ПА</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>ПА-50-700-1,0 без троса</td><td class="td-price"><?= price(4260) ?></td></tr>
                    <tr><td>ПА-50-250-0,33 без троса</td><td class="td-price"><?= price(3340) ?></td></tr>
                    <tr><td>ПА-75-250-0,7 без троса</td><td class="td-price"><?= price(4750) ?></td></tr>
                    <tr><td>ПА-75-350-1,0 без троса</td><td class="td-price"><?= price(5080) ?></td></tr>
                  </tbody>
                </table>
              </div>

              <p class="pl-section__sub" style="margin-top:20px">Серия ППА</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>ППА-38-400-0,3Д без троса</td><td class="td-price"><?= price(6930) ?></td></tr>
                    <tr><td>ППА-40-400-0,3 без троса</td><td class="td-price"><?= price(4430) ?></td></tr>
                    <tr><td>ППА-50-250-0,33 без троса</td><td class="td-price"><?= price(4430) ?></td></tr>
                    <tr><td>ППА-50-400-0,5 без троса</td><td class="td-price"><?= price(4800) ?></td></tr>
                    <tr><td>ППА-50-400-0,5Д без троса</td><td class="td-price"><?= price(7370) ?></td></tr>
                    <tr><td>ППА-75-250-0,7 без троса</td><td class="td-price"><?= price(5080) ?></td></tr>
                    <tr><td>ППА-75-350-1,0 без троса</td><td class="td-price"><?= price(5290) ?></td></tr>
                    <tr><td>ПШ-75-300-0,8 с тросом 5 м</td><td class="td-price"><?= price(13970) ?></td></tr>
                    <tr><td>ППА-75-250-0,5Д без троса</td><td class="td-price"><?= price(7700) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Комплекты и аксессуары -->
            <div>
              <p class="pl-section__sub" style="margin-top:16px">Трос нержавеющий (диам. 1 мм)</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Длина</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>5 метров</td><td class="td-price"><?= price(150) ?></td></tr>
                    <tr><td>10 метров</td><td class="td-price"><?= price(300) ?></td></tr>
                    <tr><td>15 метров</td><td class="td-price"><?= price(450) ?></td></tr>
                    <tr><td>20 метров</td><td class="td-price"><?= price(600) ?></td></tr>
                    <tr><td>25 метров</td><td class="td-price"><?= price(750) ?></td></tr>
                  </tbody>
                </table>
              </div>

              <p class="pl-section__sub" style="margin-top:20px">Провод заземляющий</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Длина</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>5 метров</td><td class="td-price"><?= price(200) ?></td></tr>
                    <tr><td>10 метров</td><td class="td-price"><?= price(400) ?></td></tr>
                    <tr><td>15 метров</td><td class="td-price"><?= price(600) ?></td></tr>
                    <tr><td>20 метров</td><td class="td-price"><?= price(800) ?></td></tr>
                    <tr><td>25 метров</td><td class="td-price"><?= price(1000) ?></td></tr>
                  </tbody>
                </table>
              </div>

              <p class="pl-section__sub" style="margin-top:20px">Подъёмные комплекты</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>Подъёмный комплект №1 (5 м)</td><td class="td-price"><?= price(1210) ?></td></tr>
                    <tr><td>Подъёмный комплект №1 (10 м)</td><td class="td-price"><?= price(1430) ?></td></tr>
                    <tr><td>Подъёмный комплект №1 (15 м)</td><td class="td-price"><?= price(1650) ?></td></tr>
                    <tr><td>Подъёмный комплект №1 (20 м)</td><td class="td-price"><?= price(1810) ?></td></tr>
                    <tr><td>Подъёмный комплект №2 (5 м)</td><td class="td-price"><?= price(1210) ?></td></tr>
                    <tr><td>Подъёмный комплект №2 (10 м)</td><td class="td-price"><?= price(1650) ?></td></tr>
                    <tr><td>Подъёмный комплект №2 (15 м)</td><td class="td-price"><?= price(2080) ?></td></tr>
                    <tr><td>Подъёмный комплект №2 (20 м)</td><td class="td-price"><?= price(2520) ?></td></tr>
                  </tbody>
                </table>
              </div>

              <p class="pl-section__sub" style="margin-top:20px">Заборные вёдра</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>Заборное ведро 1 л, диам. 80 мм</td><td class="td-price"><?= price(4800) ?></td></tr>
                    <tr><td>Заборное ведро 0,5 л, диам. 45 мм</td><td class="td-price"><?= price(4040) ?></td></tr>
                    <tr><td>Заборное ведро В-10, для смешивания проб, 10 л</td><td class="td-price"><?= price(9720) ?></td></tr>
                    <tr><td>Заборное ведро В-5, для смешивания проб, 5 л</td><td class="td-price"><?= price(7540) ?></td></tr>
                    <tr><td>Заборное ведро В-2, для смешивания проб, 2 л</td><td class="td-price"><?= price(5240) ?></td></tr>
                    <tr><td>Ведро дюралевое 10 л</td><td class="td-price"><?= price(6010) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /panel-probo -->


      <!-- ═══════════════════════════════════════════════════════
           PANEL 7 — РАЗНОЕ
      ══════════════════════════════════════════════════════════ -->
      <div class="pl-panel" id="panel-raznoe" role="tabpanel">
        <div class="pl-section">
          <h2 class="pl-section__title">Разное</h2>

          <div class="pl-dual" style="margin-top:8px">
            <div>
              <p class="pl-section__sub" style="margin-top:16px">Ареометры с поверкой РФ</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>Ареометр АНТ-1&emsp;650–710</td><td class="td-price"><?= price(2210) ?></td></tr>
                    <tr><td>Ареометр АНТ-1&emsp;710–770</td><td class="td-price"><?= price(2210) ?></td></tr>
                    <tr><td>Ареометр АНТ-1&emsp;770–830</td><td class="td-price"><?= price(2210) ?></td></tr>
                    <tr><td>Ареометр АНТ-1&emsp;830–890</td><td class="td-price"><?= price(2210) ?></td></tr>
                    <tr><td>Ареометр АНТ-2&emsp;670–750</td><td class="td-price"><?= price(2210) ?></td></tr>
                    <tr><td>Ареометр АНТ-2&emsp;750–830</td><td class="td-price"><?= price(2210) ?></td></tr>
                    <tr><td>Ареометр АНТ-2&emsp;830–910</td><td class="td-price"><?= price(2210) ?></td></tr>
                  </tbody>
                </table>
              </div>

              <p class="pl-section__sub" style="margin-top:20px">Паста водочувствительная «Акватест»</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>120 г, 1 коробка 10 шт.</td><td class="td-price"><?= price(3400) ?></td></tr>
                    <tr><td>75 г, 1 коробка 10 шт.</td><td class="td-price"><?= price(2600) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div>
              <p class="pl-section__sub" style="margin-top:16px">Инвентарь дюралевый</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>Скребок 1000 мм, 180×1000×4 мм</td><td class="td-price"><?= price(4100) ?></td></tr>
                    <tr><td>Скребок 500 мм, 180×500×4 мм</td><td class="td-price"><?= price(3360) ?></td></tr>
                    <tr><td>Совок, 190×400×60 мм</td><td class="td-price"><?= price(3360) ?></td></tr>
                  </tbody>
                </table>
              </div>

              <p class="pl-section__sub" style="margin-top:20px">Пеналы для метроштоков</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>Пенал ПМ-5200-А-4</td><td class="td-price"><?= price(30980) ?></td></tr>
                    <tr><td>Пенал ПМ-3700-А-3</td><td class="td-price"><?= price(25730) ?></td></tr>
                    <tr><td>Пенал ПМ-4700-А-3</td><td class="td-price"><?= price(27200) ?></td></tr>
                  </tbody>
                </table>
              </div>

              <p class="pl-section__sub" style="margin-top:20px">Тележки для мерников</p>
              <div class="pl-table-wrap">
                <table class="pl-table">
                  <thead><tr><th>Наименование</th><th class="th-price">Цена</th></tr></thead>
                  <tbody>
                    <tr><td>Тележка для мерника 10 л, верхний слив</td><td class="td-price"><?= price(15230) ?></td></tr>
                    <tr><td>Тележка для мерника 10 л, нижний слив</td><td class="td-price"><?= price(16700) ?></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /panel-raznoe -->

    </div><!-- /pl-page -->

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <?php require __DIR__ . '/includes/scripts-bridge.php'; ?>

    <script>
    (function () {
      var tabs = document.querySelectorAll('.pl-tab');
      var panels = document.querySelectorAll('.pl-panel');

      tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
          var key = tab.getAttribute('data-tab');
          tabs.forEach(function (t) { t.classList.remove('is-active'); });
          panels.forEach(function (p) { p.classList.remove('is-active'); });
          tab.classList.add('is-active');
          var panel = document.getElementById('panel-' + key);
          if (panel) panel.classList.add('is-active');
        });
      });
    })();
    </script>
  </body>
</html>
