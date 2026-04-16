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

  function titleForKey(key, labels) {
    if (labels && labels[key]) return labels[key];
    return FILTER_TITLES[key] || key.replace(/_/g, " ");
  }

  function slugFromBody() {
    var b = document.body;
    var s = b && b.getAttribute("data-category-slug");
    return s && s.trim() ? s.trim() : "merniki";
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

  function buildFiltersObject(selected) {
    var obj = {};
    Object.keys(selected || {}).forEach(function (key) {
      var vals = selected[key];
      if (!vals || !vals.length) return;
      if (key === "has_verification") {
        var last = vals[vals.length - 1];
        obj.has_verification = last === true || last === "true" || last === "1";
        return;
      }
      obj[key] = vals.length === 1 ? vals[0] : vals;
    });
    return obj;
  }

  /** Преобразует объект фильтров API в формат state.selected (массивы значений на ключ). */
  function jsonToSelected(o) {
    if (!o || typeof o !== "object" || Array.isArray(o)) return {};
    var sel = {};
    Object.keys(o).forEach(function (k) {
      var v = o[k];
      if (v === undefined || v === null) return;
      if (k === "has_verification") {
        var b = v === true || v === "true" || v === "1";
        sel[k] = [b];
        return;
      }
      if (Array.isArray(v)) {
        sel[k] = v.map(function (x) {
          if (typeof x === "boolean") return x;
          return String(x);
        });
      } else if (typeof v === "boolean") {
        sel[k] = [v];
      } else {
        sel[k] = [String(v)];
      }
    });
    return sel;
  }

  /**
   * Предвыбор фильтров: data-filters-prefill с сервера (из ?filters=) или query string.
   * Ключи — как в technical_specs / API.
   */
  function parseFiltersPrefill() {
    var raw = "";
    var body = document.body;
    if (body) {
      var fromAttr = body.getAttribute("data-filters-prefill");
      if (fromAttr && String(fromAttr).trim()) raw = String(fromAttr).trim();
    }
    if (!raw) {
      try {
        raw = new URLSearchParams(window.location.search).get("filters") || "";
      } catch (e) {
        raw = "";
      }
    }
    if (!raw) return {};
    try {
      return jsonToSelected(JSON.parse(raw));
    } catch (e1) {
      try {
        return jsonToSelected(JSON.parse(decodeURIComponent(raw)));
      } catch (e2) {
        try {
          var m = /(?:^|[?&])filters=([^&]*)/.exec(window.location.search || "");
          if (!m || !m[1]) return {};
          var dec = decodeURIComponent(m[1].replace(/\+/g, " "));
          return jsonToSelected(JSON.parse(dec));
        } catch (e3) {
          return {};
        }
      }
    }
  }

  function renderActiveFilters(mount, formEl, selected, reload, labels) {
    if (!mount) return;
    var keys = Object.keys(selected).filter(function (k) {
      return selected[k] && selected[k].length;
    });
    if (!keys.length) {
      mount.innerHTML = "";
      mount.hidden = true;
      return;
    }
    mount.hidden = false;
    var html = "";
    keys.forEach(function (key) {
      var label = titleForKey(key, labels);
      html += '<div class="cat2-active-filters__group">';
      html += '<h2 class="cat2-active-filters__label">' + K.escapeHtml(label) + "</h2>";
      html += '<ul class="cat2-active-filters__list">';
      selected[key].forEach(function (val) {
        var display =
          key === "has_verification"
            ? val === true || val === "true" || val === "1"
              ? "С поверкой"
              : "Без поверки"
            : String(val);
        html +=
          '<li class="cat2-active-filters__chip"><a class="cat2-active-filters__link" href="#" rel="nofollow" data-rm-filter="' +
          K.escapeHtml(key) +
          '" data-rm-val="' +
          K.escapeHtml(String(val)) +
          '">' +
          K.escapeHtml(display) +
          "</a></li>";
      });
      html += "</ul></div>";
    });
    mount.innerHTML = html;
    mount.querySelectorAll("a[data-rm-filter]").forEach(function (a) {
      a.addEventListener("click", function (ev) {
        ev.preventDefault();
        var k = a.getAttribute("data-rm-filter");
        var v = a.getAttribute("data-rm-val");
        if (!selected[k]) return;
        if (k === "has_verification") {
          var wantTrue = v === "true";
          selected[k] = selected[k].filter(function (x) {
            var xt = x === true || x === "true" || x === "1";
            return xt !== wantTrue;
          });
        } else {
          selected[k] = selected[k].filter(function (x) {
            return String(x) !== String(v);
          });
        }
        if (!selected[k].length) delete selected[k];
        syncFormFromSelected(formEl, selected);
        renderActiveFilters(mount, formEl, selected, reload, labels);
        reload();
      });
    });
  }

  function syncFormFromSelected(form, selected) {
    if (!form) return;
    form.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
      var key = cb.getAttribute("data-filter-key");
      var raw = cb.getAttribute("data-filter-value");
      if (!key) return;
      var arr = selected[key] || [];
      if (key === "has_verification") {
        var want = raw === "true";
        cb.checked = arr.some(function (x) {
          return (x === true || x === "true" || x === "1") === want;
        });
      } else {
        cb.checked = arr.some(function (x) {
          return String(x) === String(raw);
        });
      }
    });
  }

  function readSelectedFromForm(form) {
    var sel = {};
    form.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
      var key = cb.getAttribute("data-filter-key");
      var raw = cb.getAttribute("data-filter-value");
      if (!key) return;
      var val = key === "has_verification" ? raw === "true" : raw;
      if (!sel[key]) sel[key] = [];
      sel[key].push(val);
    });
    return sel;
  }

  function renderFilterSections(container, apiFilters, selected, meta) {
    if (!container) return;
    var labels = (meta && meta.labels) || {};
    var order = meta && meta.order;
    var keys =
      order && order.length
        ? order.filter(function (k) {
            return Object.prototype.hasOwnProperty.call(apiFilters || {}, k);
          })
        : Object.keys(apiFilters || {}).sort(function (a, b) {
            return titleForKey(a, labels).localeCompare(titleForKey(b, labels), "ru");
          });
    var html = "";
    keys.forEach(function (key) {
      var vals = apiFilters[key];
      if (key === "has_verification") {
        if (!Array.isArray(vals) || !vals.length) return;
        html += '<fieldset class="cat2-filter__section">';
        html +=
          '<legend class="cat2-filter__section-title">' +
          K.escapeHtml(titleForKey(key, labels)) +
          "</legend>";
        html += '<ul class="cat2-filter__options">';
        vals.forEach(function (bv, hi) {
          var id = "hv_" + hi + "_" + (bv ? "1" : "0");
          var hv = selected.has_verification || [];
          var checked = hv.some(function (x) {
            return (x === true || x === "true" || x === "1") === !!bv;
          });
          html +=
            '<li class="cat2-filter__option cat2-filter__option--compact"><label class="cat2-filter__label">' +
            '<input class="cat2-filter__checkbox" type="checkbox" data-filter-key="has_verification" data-filter-value="' +
            (bv ? "true" : "false") +
            '" id="' +
            id +
            '"' +
            (checked ? " checked" : "") +
            "/>" +
            '<span class="cat2-filter__label-text">' +
            (bv ? "С поверкой" : "Без поверки") +
            "</span></label></li>";
        });
        html += "</ul></fieldset>";
        return;
      }
      if (!Array.isArray(vals) || !vals.length) return;
      html += '<fieldset class="cat2-filter__section">';
      html +=
        '<legend class="cat2-filter__section-title">' + K.escapeHtml(titleForKey(key, labels)) + "</legend>";
      html += '<ul class="cat2-filter__options">';
      vals.forEach(function (v) {
        var vs = String(v);
        var compact = vs.length < 24 ? " cat2-filter__option--compact" : "";
        var selArr = selected[key] || [];
        var isOn = selArr.some(function (x) {
          return String(x) === vs;
        });
        html +=
          '<li class="cat2-filter__option' +
          compact +
          '"><label class="cat2-filter__label">' +
          '<input class="cat2-filter__checkbox" type="checkbox" data-filter-key="' +
          K.escapeHtml(key) +
          '" data-filter-value="' +
          K.escapeHtml(vs) +
          '"' +
          (isOn ? " checked" : "") +
          "/>" +
          '<span class="cat2-filter__label-text">' +
          K.escapeHtml(vs) +
          "</span></label></li>";
      });
      html += "</ul></fieldset>";
    });
    container.innerHTML = html;
  }

  function sortParams(val) {
    if (val === "price_asc") return { sort: "price", order: "asc" };
    if (val === "price_desc") return { sort: "price", order: "desc" };
    if (val === "name") return { sort: "name", order: "asc" };
    return { sort: "created_at", order: "desc" };
  }

  function subcatArrowSvg() {
    return (
      '<svg class="cat2-subcat-card__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true" width="24" height="24">' +
      '<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>' +
      "</svg>"
    );
  }

  function renderSubcategories(section, grid, children) {
    if (!section || !grid || !children || !children.length) return;
    section.hidden = false;
    grid.innerHTML = children
      .map(function (child) {
        var href = "/category2?slug=" + encodeURIComponent(child.slug || "");
        var name = child.name || "";
        var img = child.photo ? K.mediaUrl(child.photo) : "";
        var imgHtml = img
          ? '<div class="cat2-subcat-card__img">' +
            '<img src="' +
            K.escapeHtml(img) +
            '" alt="' +
            K.escapeHtml(child.photo_alt || name) +
            '" loading="lazy" decoding="async"/>' +
            "</div>"
          : "";
        var countHtml =
          child.aggregate_products && child.aggregate_products > 0
            ? '<span class="cat2-subcat-card__count">' +
              child.aggregate_products +
              "\u00a0" +
              pluralProducts(child.aggregate_products) +
              "</span>"
            : "";
        return (
          "<li>" +
          '<a class="cat2-subcat-card" href="' +
          K.escapeHtml(href) +
          '">' +
          imgHtml +
          '<div class="cat2-subcat-card__body">' +
          '<span class="cat2-subcat-card__title">' +
          K.escapeHtml(name) +
          "</span>" +
          countHtml +
          "</div>" +
          subcatArrowSvg() +
          "</a>" +
          "</li>"
        );
      })
      .join("");
  }

  function pluralProducts(n) {
    var n10 = n % 10;
    var n100 = n % 100;
    if (n10 === 1 && n100 !== 11) return "\u0442\u043e\u0432\u0430\u0440";
    if (n10 >= 2 && n10 <= 4 && (n100 < 10 || n100 >= 20))
      return "\u0442\u043e\u0432\u0430\u0440\u0430";
    return "\u0442\u043e\u0432\u0430\u0440\u043e\u0432";
  }

  function init() {
    var slug = slugFromBody();
    var grid = document.getElementById("cat2-product-grid");
    var filterMount = document.getElementById("cat2-filter-sections");
    var form = document.getElementById("cat2-filter-form");
    var activeMount = document.getElementById("cat2-active-filters-mount");
    var h1 = document.querySelector(".cat2-page-title");
    var perPage = document.getElementById("cat2-per-page");
    var sortSel = document.getElementById("cat2-sort");
    var pag = document.getElementById("cat2-pagination");
    var subcatsSection = document.getElementById("cat2-subcats-section");
    var subcatsGrid = document.getElementById("cat2-subcats-grid");
    if (!grid || !form || !filterMount) return;

    var state = {
      page: 1,
      aggregate: true,
      selected: parseFiltersPrefill(),
      filterLabels: {},
      filterOrder: null,
    };

    function loadProducts() {
      var limit = Math.min(100, Math.max(1, parseInt(perPage && perPage.value, 10) || 20));
      var sp = sortParams(sortSel && sortSel.value);
      var filtersObj = buildFiltersObject(state.selected);
      var q =
        "/categories/" +
        encodeURIComponent(slug) +
        "/products?page=" +
        state.page +
        "&limit=" +
        limit +
        "&sort=" +
        encodeURIComponent(sp.sort) +
        "&order=" +
        encodeURIComponent(sp.order) +
        "&aggregate=" +
        (state.aggregate ? "true" : "false");
      if (Object.keys(filtersObj).length) {
        q += "&filters=" + encodeURIComponent(JSON.stringify(filtersObj));
      }
      grid.innerHTML =
        '<li class="catalog-home-loading" style="grid-column:1/-1">Загрузка…</li>';
      return K.fetchJson(q)
        .then(K.normalizeCategoryProductsPayload)
        .then(function (pack) {
          var items = pack.items || [];
          if (!items.length) {
            grid.innerHTML =
              '<li class="catalog-home-loading" style="grid-column:1/-1">Нет товаров по выбранным условиям</li>';
          } else {
            grid.innerHTML = items.map(productCardHtml).join("");
          }
          grid.querySelectorAll("[data-add-cart]").forEach(function (btn) {
            btn.addEventListener("click", function () {
              var id = btn.getAttribute("data-add-cart");
              btn.disabled = true;
              K.addProductToCart(id, 1)
                .catch(function () {})
                .then(function () {
                  btn.disabled = false;
                });
            });
          });
          renderPagination(pack.pagination);
        })
        .catch(function () {
          grid.innerHTML =
            '<li class="catalog-home-loading" style="grid-column:1/-1">Ошибка загрузки товаров</li>';
        });
    }

    function renderPagination(p) {
      if (!pag || !p || !p.total_pages) {
        if (pag) pag.hidden = true;
        return;
      }
      var tp = p.total_pages | 0;
      var cur = p.page | 0;
      if (tp <= 1) {
        pag.hidden = true;
        return;
      }
      pag.hidden = false;
      var parts = [];
      if (cur > 1) {
        parts.push(
          '<button type="button" class="cat2-filter__reset" data-cat2-page="' +
            (cur - 1) +
            '">Назад</button>'
        );
      }
      parts.push(
        '<span class="cat2-toolbar__field-label">Стр. ' +
          cur +
          " из " +
          tp +
          " (всего " +
          (p.total | 0) +
          ")</span>"
      );
      if (cur < tp) {
        parts.push(
          '<button type="button" class="cat2-filter__submit" data-cat2-page="' +
            (cur + 1) +
            '">Вперёд</button>'
        );
      }
      pag.innerHTML =
        '<div class="cat2-toolbar__controls" style="margin-top:16px">' + parts.join(" ") + "</div>";
      pag.querySelectorAll("[data-cat2-page]").forEach(function (b) {
        b.addEventListener("click", function () {
          state.page = parseInt(b.getAttribute("data-cat2-page"), 10) || 1;
          loadProducts();
        });
      });
    }

    function onFiltersChanged() {
      state.selected = readSelectedFromForm(form);
      renderActiveFilters(activeMount, form, state.selected, function () {
        state.page = 1;
        loadProducts();
      }, state.filterLabels);
      state.page = 1;
      loadProducts();
    }

    K.fetchJson("/categories/by-slug/" + encodeURIComponent(slug) + "?include_children=true")
      .then(function (cat) {
        if (h1 && cat.name) h1.textContent = cat.name;
        if (cat.meta_title) document.title = cat.meta_title;
        state.aggregate = !!cat.aggregate_products;

        var children = cat.children && cat.children.length ? cat.children : [];
        if (children.length) {
          renderSubcategories(subcatsSection, subcatsGrid, children);
        }
        if (cat.display_mode === "subcategories_only") {
          document.body.classList.add("cat2-mode--subcats-only");
        }

        return K.fetchJson(
          "/categories/" +
            encodeURIComponent(slug) +
            "/filters?aggregate=" +
            (state.aggregate ? "true" : "false")
        );
      })
      .then(function (res) {
        var f = (res && res.filters) || {};
        state.filterLabels = (res && res.filter_labels) || {};
        state.filterOrder = (res && res.filter_order) || null;
        renderFilterSections(filterMount, f, state.selected, {
          labels: state.filterLabels,
          order: state.filterOrder,
        });
        renderActiveFilters(activeMount, form, state.selected, function () {
          state.page = 1;
          loadProducts();
        }, state.filterLabels);
        form.addEventListener("change", function (e) {
          if (e.target && e.target.matches('input[type="checkbox"]')) {
            state.selected = readSelectedFromForm(form);
            renderActiveFilters(activeMount, form, state.selected, function () {
              state.page = 1;
              loadProducts();
            }, state.filterLabels);
            state.page = 1;
            loadProducts();
          }
        });
        form.addEventListener("submit", function (e) {
          e.preventDefault();
          onFiltersChanged();
        });
        var resetBtn = form.querySelector(".cat2-filter__reset");
        if (resetBtn) {
          resetBtn.addEventListener("click", function () {
            setTimeout(function () {
              state.selected = {};
              renderActiveFilters(activeMount, form, state.selected, function () {
                state.page = 1;
                loadProducts();
              }, state.filterLabels);
              state.page = 1;
              loadProducts();
            }, 0);
          });
        }
        return loadProducts();
      })
      .catch(function () {
        filterMount.innerHTML =
          '<p class="catalog-home-loading">Не удалось загрузить фильтры</p>';
        grid.innerHTML =
          '<li class="catalog-home-loading" style="grid-column:1/-1">Категория недоступна</li>';
      });

    if (perPage) {
      perPage.addEventListener("change", function () {
        state.page = 1;
        loadProducts();
      });
    }
    if (sortSel) {
      sortSel.addEventListener("change", function () {
        state.page = 1;
        loadProducts();
      });
    }

    document.querySelectorAll(".cat2-toolbar__view").forEach(function (btn) {
      btn.addEventListener("click", function () {
        document.querySelectorAll(".cat2-toolbar__view").forEach(function (b) {
          b.classList.remove("cat2-toolbar__view--active");
          b.setAttribute("aria-pressed", "false");
        });
        btn.classList.add("cat2-toolbar__view--active");
        btn.setAttribute("aria-pressed", "true");
      });
    });

    setupMobileFilterToggle();
  }

  function setupMobileFilterToggle() {
    var toggleBtn = document.querySelector(".cat2-filter-toggle");
    var panel = document.getElementById("cat2-filter-panel");
    if (!toggleBtn || !panel) return;

    toggleBtn.addEventListener("click", function () {
      var isOpen = panel.classList.contains("is-filter-open");
      if (isOpen) {
        panel.classList.remove("is-filter-open");
        toggleBtn.setAttribute("aria-expanded", "false");
      } else {
        panel.classList.add("is-filter-open");
        toggleBtn.setAttribute("aria-expanded", "true");
        setTimeout(function () {
          panel.scrollIntoView({ behavior: "smooth", block: "start" });
        }, 40);
      }
    });

    function updateFilterCount() {
      var countEl = toggleBtn.querySelector(".cat2-filter-toggle__count");
      if (!countEl) return;
      var checkedCount = 0;
      if (panel) {
        checkedCount = panel.querySelectorAll('input[type="checkbox"]:checked').length;
      }
      if (checkedCount > 0) {
        countEl.textContent = checkedCount;
        countEl.hidden = false;
      } else {
        countEl.hidden = true;
      }
    }

    if (panel) {
      panel.addEventListener("change", updateFilterCount);
    }

    var activeMount = document.getElementById("cat2-active-filters-mount");
    if (activeMount) {
      var obs = new MutationObserver(updateFilterCount);
      obs.observe(activeMount, { childList: true, subtree: true });
    }
  }

  document.addEventListener("DOMContentLoaded", init);
})();
