(function () {
  "use strict";

  var K = window.KonturM;
  if (!K) return;

  var PLACEHOLDER_IMG = "/design/assets/figma/e7a2477a-3b7f-4aec-ab4f-a7dbf2597787.png";

  function category2Url(slug) {
    return "/category2?slug=" + encodeURIComponent(slug);
  }

  function cardVariantClass(i) {
    return "catalog-card--c" + ((i % 9) + 1);
  }

  function renderCard(slug, name, photo, index) {
    var img = K.mediaUrl(photo) || PLACEHOLDER_IMG;
    var href = category2Url(slug);
    var cls = "catalog-card " + cardVariantClass(index);
    return (
      '<li>' +
      '<a class="' +
      K.escapeHtml(cls) +
      '" href="' +
      K.escapeHtml(href) +
      '">' +
      '<span class="catalog-card__media">' +
      '<img class="catalog-card__hero" src="' +
      K.escapeHtml(img) +
      '" alt="" decoding="async" loading="lazy" />' +
      "</span>" +
      '<span class="catalog-card__title">' +
      K.escapeHtml(name) +
      "</span>" +
      '<svg class="catalog-card__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true" width="24" height="24">' +
      '<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
      "</svg>" +
      "</a>" +
      "</li>"
    );
  }

  function renderSidebarLink(slug, name) {
    return (
      '<li class="catalog-sidebar__item">' +
      '<a class="catalog-sidebar__link" style="padding-left:16px" href="' +
      K.escapeHtml(category2Url(slug)) +
      '">' +
      K.escapeHtml(name) +
      "</a></li>"
    );
  }

  function init() {
    var grid = document.getElementById("catalog-root-grid");
    var nav = document.getElementById("catalog-nav-tree");
    var errEl = document.getElementById("catalog-home-error");
    if (!grid || !nav) return;

    K.fetchJson("/categories/favorites")
      .then(function (data) {
        var mainList = K.normalizeTree(data && data.main) || [];
        var sideList = K.normalizeTree(data && data.sidebar) || [];

        if (!mainList.length) {
          grid.innerHTML =
            '<li class="catalog-sidebar__item catalog-home-loading">Нет категорий для главной — отметьте их в админке («Избранные» → «На главной»)</li>';
        } else {
          var rootsHtml = "";
          mainList.forEach(function (node, i) {
            rootsHtml += renderCard(node.slug, node.name, node.photo, i);
          });
          grid.innerHTML = rootsHtml;
        }

        if (!sideList.length) {
          nav.innerHTML =
            '<li class="catalog-sidebar__item catalog-home-loading">В меню пусто — отметьте категории в админке («В сайдбаре»)</li>';
        } else {
          nav.innerHTML = sideList.map(function (row) {
            return renderSidebarLink(row.slug, row.name);
          }).join("");
        }

        if (errEl) errEl.hidden = true;
      })
      .catch(function (e) {
        if (errEl) {
          errEl.hidden = false;
          errEl.textContent =
            "Не удалось загрузить каталог. Проверьте, что API доступен. " + (e.message || "");
        }
        grid.innerHTML =
          '<li class="catalog-sidebar__item catalog-home-loading">Ошибка загрузки</li>';
        nav.innerHTML =
          '<li class="catalog-sidebar__item catalog-home-loading">Ошибка загрузки</li>';
      });
  }

  document.addEventListener("DOMContentLoaded", init);
})();
