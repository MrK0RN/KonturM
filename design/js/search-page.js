(function () {
  "use strict";

  var K = window.KonturM;
  if (!K) return;

  function qp(name) {
    return new URLSearchParams(window.location.search).get(name) || "";
  }

  function unwrapSearchProducts(data) {
    if (!data || typeof data !== "object") return [];
    if (Array.isArray(data.products)) return data.products;
    if (data.products && Array.isArray(data.products["hydra:member"])) return data.products["hydra:member"];
    return [];
  }

  function unwrapSearchCategories(data) {
    if (!data || typeof data !== "object") return [];
    if (Array.isArray(data.categories)) return data.categories;
    if (data.categories && Array.isArray(data.categories["hydra:member"])) return data.categories["hydra:member"];
    return [];
  }

  function init() {
    var q = qp("q").trim();
    var title = document.getElementById("search-page-title");
    var sub = document.getElementById("search-page-query");
    var grid = document.getElementById("search-results");
    var cats = document.getElementById("search-categories");
    if (!grid) return;

    if (title) title.textContent = q.length >= 2 ? "Результаты поиска" : "Поиск";
    if (sub) sub.textContent = q.length >= 2 ? "Запрос: «" + q + "»" : "Введите не менее 2 символов в поле поиска в шапке.";

    if (q.length < 2) {
      grid.innerHTML = "";
      if (cats) cats.innerHTML = "";
      return;
    }

    grid.innerHTML = "<p class=\"catalog-home-loading\">Поиск…</p>";
    K.fetchJson("/search?q=" + encodeURIComponent(q) + "&type=all&limit=40")
      .then(function (data) {
        var products = unwrapSearchProducts(data);
        var categories = unwrapSearchCategories(data);

        if (cats) {
          if (!categories.length) {
            cats.innerHTML = "";
          } else {
            cats.innerHTML =
              "<h2 class=\"catalog-card__title\" style=\"margin:24px 0 12px\">Категории</h2><ul class=\"catalog-sidebar__list\">" +
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
          grid.innerHTML = "<p class=\"catalog-home-loading\">Товары не найдены</p>";
          return;
        }

        var html = '<ul class="catalog-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">';
        products.forEach(function (p, i) {
          var href = "/product?slug=" + encodeURIComponent(p.slug || "");
          var img =
            K.mediaUrl(p.photo) ||
            "/design/assets/figma/e7a2477a-3b7f-4aec-ab4f-a7dbf2597787.png";
          html +=
            "<li>" +
            '<a class="catalog-card catalog-card--c5" href="' +
            K.escapeHtml(href) +
            '">' +
            '<span class="catalog-card__media">' +
            '<img class="catalog-card__hero" src="' +
            K.escapeHtml(img) +
            '" alt="" loading="lazy" decoding="async"/>' +
            "</span>" +
            '<span class="catalog-card__title">' +
            K.escapeHtml(p.name || "") +
            "</span>" +
            '<span class="cat2-card__price" style="position:relative;z-index:2;margin:8px 20px 16px">' +
            K.escapeHtml(K.fmtPriceRu(p.price)) +
            "</span>" +
            '<svg class="catalog-card__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true" width="24" height="24">' +
            '<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
            "</svg>" +
            "</a></li>";
        });
        html += "</ul>";
        grid.innerHTML = html;
      })
      .catch(function () {
        grid.innerHTML = "<p class=\"catalog-home-loading\">Ошибка поиска</p>";
      });
  }

  document.addEventListener("DOMContentLoaded", init);
})();
