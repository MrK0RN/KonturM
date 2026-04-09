    <a class="cat2-skip" href="#cat2-main">Перейти к каталогу</a>
    <div class="cat2-shell">
      <div class="cat2-shell__sidebar" id="cat2-filter-panel">
        <aside class="cat2-filter" aria-label="Фильтр каталога">
          <div class="cat2-filter__head">
            <img
              class="cat2-filter__icon"
              src="/design/assets/icon-filter.svg"
              width="16"
              height="16"
              alt=""
              decoding="async"
            />
            <p class="cat2-filter__title">Фильтр</p>
          </div>
          <form class="cat2-filter__form" action="#" method="get" id="cat2-filter-form">
            <div id="cat2-filter-sections"></div>
            <div class="cat2-filter__footer">
              <button type="submit" class="cat2-filter__submit">Выберите фильтры</button>
              <button type="reset" class="cat2-filter__reset" form="cat2-filter-form">Сбросить</button>
            </div>
          </form>
        </aside>
      </div>
      <main class="cat2-shell__main" id="cat2-main" lang="ru">
        <h1 class="cat2-page-title">Каталог</h1>
        <section class="cat2-subcats" id="cat2-subcats-section" hidden aria-label="Подкатегории">
          <ul class="cat2-subcats__grid" id="cat2-subcats-grid"></ul>
        </section>
        <button
          class="cat2-filter-toggle"
          type="button"
          aria-expanded="false"
          aria-controls="cat2-filter-panel"
        >
          <svg viewBox="0 0 20 14" fill="none" aria-hidden="true" width="18" height="14">
            <rect x="0" y="0" width="20" height="2" rx="1" fill="currentColor"/>
            <rect x="3" y="6" width="14" height="2" rx="1" fill="currentColor"/>
            <rect x="7" y="12" width="6" height="2" rx="1" fill="currentColor"/>
          </svg>
          <span class="cat2-filter-toggle__label">Фильтры</span>
          <span class="cat2-filter-toggle__count" hidden></span>
        </button>
        <nav class="cat2-active-filters" id="cat2-active-filters-mount" aria-label="Выбранные фильтры каталога" hidden></nav>
        <div class="cat2-toolbar" role="toolbar" aria-label="Вид списка товаров и сортировка">
          <div class="cat2-toolbar__views" role="group" aria-label="Режим отображения">
            <button type="button" class="cat2-toolbar__view" aria-pressed="false" aria-label="Плитка">
              <svg viewBox="0 0 14 14" fill="none" aria-hidden="true">
                <rect x="1" y="1" width="5" height="5" rx="1" fill="currentColor" />
                <rect x="8" y="1" width="5" height="5" rx="1" fill="currentColor" />
                <rect x="1" y="8" width="5" height="5" rx="1" fill="currentColor" />
                <rect x="8" y="8" width="5" height="5" rx="1" fill="currentColor" />
              </svg>
            </button>
            <button type="button" class="cat2-toolbar__view cat2-toolbar__view--active" aria-pressed="true" aria-label="Список">
              <svg viewBox="0 0 14 12" fill="none" aria-hidden="true">
                <rect x="1" y="1" width="12" height="2" rx="0.5" fill="currentColor" />
                <rect x="1" y="5" width="12" height="2" rx="0.5" fill="currentColor" />
                <rect x="1" y="9" width="12" height="2" rx="0.5" fill="currentColor" />
              </svg>
            </button>
            <button type="button" class="cat2-toolbar__view" aria-pressed="false" aria-label="Компактный список">
              <svg viewBox="0 0 12 10" fill="none" aria-hidden="true">
                <rect x="0" y="0" width="12" height="1.5" rx="0.5" fill="currentColor" />
                <rect x="0" y="4" width="12" height="1.5" rx="0.5" fill="currentColor" />
                <rect x="0" y="8" width="12" height="1.5" rx="0.5" fill="currentColor" />
              </svg>
            </button>
          </div>
          <div class="cat2-toolbar__controls">
            <div class="cat2-toolbar__field">
              <label class="cat2-toolbar__field-label" for="cat2-per-page">
                <svg viewBox="0 0 16 14" fill="none" aria-hidden="true">
                  <path
                    d="M1 1.5C1 1.22 1.22 1 1.5 1h4l1.5 2.5h7c.28 0 .5.22.5.5v7.5c0 .28-.22.5-.5.5h-13c-.28 0-.5-.22-.5-.5V1.5z"
                    stroke="currentColor"
                    stroke-width="1.2"
                  />
                  <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.2" />
                </svg>
                Показывать:
              </label>
              <div class="cat2-toolbar__select-wrap">
                <select id="cat2-per-page" class="cat2-toolbar__select" name="limit" aria-label="Количество товаров на странице">
                  <option value="20">20</option>
                  <option value="50" selected>50</option>
                  <option value="100">100</option>
                </select>
                <svg class="cat2-toolbar__chevron" viewBox="0 0 10 10" aria-hidden="true">
                  <path d="M2 3l3 3 3-3" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linecap="round" />
                </svg>
              </div>
            </div>
            <div class="cat2-toolbar__field">
              <label class="cat2-toolbar__field-label" for="cat2-sort">
                <svg viewBox="0 0 10 14" fill="none" aria-hidden="true">
                  <path d="M5 1v10M2 8l3 3 3-3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Сортировать:
              </label>
              <div class="cat2-toolbar__select-wrap">
                <select id="cat2-sort" class="cat2-toolbar__select cat2-toolbar__select--wide" name="sort" aria-label="Сортировка товаров">
                  <option value="default" selected>По умолчанию</option>
                  <option value="price_asc">Цена: по возрастанию</option>
                  <option value="price_desc">Цена: по убыванию</option>
                  <option value="name">По названию</option>
                </select>
                <svg class="cat2-toolbar__chevron" viewBox="0 0 10 10" aria-hidden="true">
                  <path d="M2 3l3 3 3-3" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linecap="round" />
                </svg>
              </div>
            </div>
          </div>
        </div>
        <ul class="cat2-grid" id="cat2-product-grid">
          <li class="catalog-home-loading" style="grid-column: 1 / -1">Загрузка…</li>
        </ul>
        <div id="cat2-pagination" hidden></div>
      </main>
    </div>
