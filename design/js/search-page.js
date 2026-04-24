(function () {
  "use strict";

  var K = window.KonturM;
  if (!K) return;

  var PLACEHOLDER_IMG =
    "/design/assets/figma/e7a2477a-3b7f-4aec-ab4f-a7dbf2597787.png";

  var FILTER_TITLES = {
    volume: "Объём",
    material: "Материал",
    diameter: "Диаметр",
    type: "Тип",
    drain: "Слив",
    equipment: "Оснащение",
    error: "Погрешность, %",
    has_verification: "Поверка",
    gost_number: "ГОСТ",
  };

  var SPEC_ORDER = [
    "type",
    "volume",
    "material",
    "drain",
    "equipment",
    "error",
    "diameter",
    "gost_number",
  ];

  function titleForKey(key) {
    return FILTER_TITLES[key] || key.replace(/_/g, " ");
  }

  function fmtSpecValue(v) {
    if (Array.isArray(v)) return v.map(fmtSpecValue).join(", ");
    if (typeof v === "boolean") return v ? "да" : "нет";
    return String(v);
  }

  function specRowsHtml(specs) {
    if (!specs || typeof specs !== "object") return "";
    var keys = Object.keys(specs).filter(function (k) {
      return k !== "has_verification";
    });
    keys.sort(function (a, b) {
      var ia = SPEC_ORDER.indexOf(a);
      var ib = SPEC_ORDER.indexOf(b);
      if (ia === -1 && ib === -1) return a.localeCompare(b, "ru");
      if (ia === -1) return 1;
      if (ib === -1) return -1;
      return ia - ib;
    });
    var rows = "";
    var n = 0;
    keys.forEach(function (k) {
      if (n >= 4) return;
      var val = fmtSpecValue(specs[k]);
      if (!val) return;
      rows +=
        '<div class="cat2-card__spec-row"><dt>' +
        K.escapeHtml(titleForKey(k)) +
        "</dt><dd>" +
        K.escapeHtml(val) +
        "</dd></div>";
      n++;
    });
    return rows;
  }

  function cartSvg() {
    return (
      '<svg viewBox="0 0 16 14" aria-hidden="true" fill="currentColor" width="15" height="14">' +
      '<path d="M1 1h2l.4 2M4 1h9l-1 7H5.5M4 1L3.6 3M4 1l-1.2 6.4A1 1 0 004.8 11H14M6.5 14a.5.5 0 100-1 .5.5 0 000 1zm6 0a.5.5 0 100-1 .5.5 0 000 1z" stroke="currentColor" stroke-width="1" fill="none"/>' +
      "</svg>"
    );
  }

  function productCardHtml(p) {
    var img = K.mediaUrl(p.photo) || PLACEHOLDER_IMG;
    var href = "/product?slug=" + encodeURIComponent(p.slug || "");
    var title = p.name || "Товар";
    var price = K.fmtPriceRu(p.price);
    var specs = specRowsHtml(p.technical_specs);

    return (
      "<li>" +
      '<article class="cat2-card">' +
      '<a class="cat2-card__media" href="' +
      K.escapeHtml(href) +
      '">' +
      '<img src="' +
      K.escapeHtml(img) +
      '" width="247" height="247" alt="" decoding="async" loading="lazy"/>' +
      "</a>" +
      '<div class="cat2-card__body">' +
      '<h3 class="cat2-card__title"><a href="' +
      K.escapeHtml(href) +
      '">' +
      K.escapeHtml(title) +
      "</a></h3>" +
      '<dl class="cat2-card__specs">' +
      specs +
      "</dl>" +
      '<div class="cat2-card__row">' +
      '<p class="cat2-card__price">' +
      K.escapeHtml(price) +
      "</p>" +
      '<button type="button" class="cat2-card__btn" data-add-cart="' +
      K.escapeHtml(String(p.id)) +
      '" aria-label="Добавить ' +
      K.escapeHtml(title) +
      ' в корзину">' +
      cartSvg() +
      "<span>В корзину</span></button>" +
      "</div>" +
      "</div>" +
      "</article></li>"
    );
  }

  function bindAddToCart(root) {
    if (!root || !K.addProductToCart || !K.replaceAddButtonWithCartStepper) return;

    function bindOne(btn) {
      btn.addEventListener("click", function () {
        var id = btn.getAttribute("data-add-cart");
        btn.disabled = true;
        K.addProductToCart(id, 1)
          .then(function (cart) {
            if (!btn.parentNode) return;
            btn.disabled = false;
            K.replaceAddButtonWithCartStepper(btn, id, cart, bindOne);
          })
          .catch(function () {
            btn.disabled = false;
          });
      });
    }

    root.querySelectorAll("[data-add-cart]").forEach(bindOne);
    if (K.syncCartSteppersForContainer) {
      K.syncCartSteppersForContainer(root, bindOne);
    }
  }

  function qp(name) {
    return new URLSearchParams(window.location.search).get(name) || "";
  }

  function sortParams(val) {
    if (val === "price_asc") return { sort: "price", order: "asc" };
    if (val === "price_desc") return { sort: "price", order: "desc" };
    if (val === "name") return { sort: "name", order: "asc" };
    return { sort: "relevance", order: "desc" };
  }

  function selectValueFromSortUrl() {
    var sort = (qp("sort") || "relevance").toLowerCase();
    var order = (qp("order") || "desc").toLowerCase();
    if (sort === "price" && order === "asc") return "price_asc";
    if (sort === "price" && order === "desc") return "price_desc";
    if (sort === "name") return "name";
    return "default";
  }

  function buildSearchUrl(q, sortSelectValue) {
    var sp = sortParams(sortSelectValue);
    return (
      "/search?q=" +
      encodeURIComponent(q) +
      "&type=all&limit=40&sort=" +
      encodeURIComponent(sp.sort) +
      "&order=" +
      encodeURIComponent(sp.order)
    );
  }

  function syncUrlWithSort(q, sortSelectValue) {
    var sp = sortParams(sortSelectValue);
    var u = new URL(window.location.href);
    u.searchParams.set("q", q);
    u.searchParams.set("sort", sp.sort);
    u.searchParams.set("order", sp.order);
    history.replaceState(null, "", u.pathname + u.search);
  }

  function unwrapSearchProducts(data) {
    if (!data || typeof data !== "object") return [];
    if (Array.isArray(data.products)) return data.products;
    if (data.products && Array.isArray(data.products["hydra:member"]))
      return data.products["hydra:member"];
    return [];
  }

  function unwrapSearchCategories(data) {
    if (!data || typeof data !== "object") return [];
    if (Array.isArray(data.categories)) return data.categories;
    if (data.categories && Array.isArray(data.categories["hydra:member"]))
      return data.categories["hydra:member"];
    return [];
  }

  function ensureSortToolbar(mount) {
    if (!mount || mount.getAttribute("data-ready") === "1") return;
    mount.innerHTML =
      '<div class="cat2-toolbar" role="toolbar" aria-label="Сортировка результатов поиска">' +
      '<div class="cat2-toolbar__controls">' +
      '<div class="cat2-toolbar__field">' +
      '<label class="cat2-toolbar__field-label" for="search-sort">' +
      '<svg viewBox="0 0 10 14" fill="none" aria-hidden="true">' +
      '<path d="M5 1v10M2 8l3 3 3-3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />' +
      "</svg>Сортировать:</label>" +
      '<div class="cat2-toolbar__select-wrap">' +
      '<select id="search-sort" class="cat2-toolbar__select cat2-toolbar__select--wide" aria-label="Сортировка товаров">' +
      '<option value="default">По релевантности</option>' +
      '<option value="price_asc">Цена: по возрастанию</option>' +
      '<option value="price_desc">Цена: по убыванию</option>' +
      '<option value="name">По названию</option>' +
      "</select>" +
      '<svg class="cat2-toolbar__chevron" viewBox="0 0 10 10" aria-hidden="true">' +
      '<path d="M2 3l3 3 3-3" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linecap="round" />' +
      "</svg></div></div></div></div>";
    mount.setAttribute("data-ready", "1");
    var sel = document.getElementById("search-sort");
    if (sel) {
      sel.value = selectValueFromSortUrl();
      sel.addEventListener("change", function () {
        var q = qp("q").trim();
        syncUrlWithSort(q, sel.value);
        fetchAndRender();
      });
    }
  }

  function currentSortSelectValue() {
    var sel = document.getElementById("search-sort");
    if (sel) return sel.value;
    return selectValueFromSortUrl();
  }

  function fetchAndRender() {
    var q = qp("q").trim();
    var title = document.getElementById("search-page-title");
    var sub = document.getElementById("search-page-query");
    var grid = document.getElementById("search-results");
    var cats = document.getElementById("search-categories");
    var tb = document.getElementById("search-toolbar");
    if (!grid) return;

    if (title) title.textContent = q.length >= 2 ? "Результаты поиска" : "Поиск";
    if (sub)
      sub.textContent =
        q.length >= 2
          ? "Запрос: «" + q + "»"
          : "Введите не менее 2 символов в поле поиска в шапке.";

    if (q.length < 2) {
      grid.innerHTML = "";
      if (cats) cats.innerHTML = "";
      if (tb) {
        tb.innerHTML = "";
        tb.hidden = true;
        tb.removeAttribute("data-ready");
      }
      return;
    }

    var sortVal = currentSortSelectValue();
    grid.innerHTML = '<p class="catalog-home-loading">Поиск…</p>';
    K.fetchJson(buildSearchUrl(q, sortVal))
      .then(function (data) {
        var products = unwrapSearchProducts(data);
        var categories = unwrapSearchCategories(data);

        if (cats) {
          if (!categories.length) {
            cats.innerHTML = "";
          } else {
            cats.innerHTML =
              '<h2 class="catalog-card__title" style="margin:24px 0 12px">Категории</h2><ul class="catalog-sidebar__list">' +
              categories
                .map(function (c) {
                  return (
                    '<li class="catalog-sidebar__item"><a class="catalog-sidebar__link" href="/category2?slug=' +
                    encodeURIComponent(c.slug) +
                    '">' +
                    K.escapeHtml(c.name) +
                    "</a></li>"
                  );
                })
                .join("") +
              "</ul>";
          }
        }

        if (!products.length) {
          if (tb) {
            tb.innerHTML = "";
            tb.hidden = true;
            tb.removeAttribute("data-ready");
          }
          grid.innerHTML =
            '<p class="catalog-home-loading">Товары не найдены</p>';
          return;
        }

        if (tb) {
          ensureSortToolbar(tb);
          tb.hidden = false;
        }

        grid.innerHTML =
          '<ul class="cat2-grid cat2-grid--4" id="search-product-grid">' +
          products.map(productCardHtml).join("") +
          "</ul>";
        bindAddToCart(grid);
      })
      .catch(function () {
        grid.innerHTML = '<p class="catalog-home-loading">Ошибка поиска</p>';
      });
  }

  function init() {
    fetchAndRender();
  }

  document.addEventListener("DOMContentLoaded", init);
})();
