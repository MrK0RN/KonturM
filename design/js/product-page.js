(function () {
  "use strict";

  var K = window.KonturM;
  if (!K) return;

  var PLACEHOLDER_IMG =
    "/design/assets/figma/e7a2477a-3b7f-4aec-ab4f-a7dbf2597787.png";

  function qp(name) {
    return new URLSearchParams(window.location.search).get(name) || "";
  }

  function unwrapProduct(data) {
    if (!data || typeof data !== "object") return null;
    if (data.id && data.name) return data;
    if (data.data && typeof data.data === "object") return data.data;
    return null;
  }

  function esc(s) {
    return K.escapeHtml(String(s || ""));
  }

  /** Как в catalog-home.js: витрина категории — /category2, не /catalog/{slug}. */
  function category2Url(slug) {
    return "/category2?slug=" + encodeURIComponent(String(slug || ""));
  }

  /**
   * Текст описания из API: обычная строка или JSON-объект с текстовыми полями.
   * Иногда встречается битый/обрезанный JSON — вытаскиваем строку после первого "…": ".
   */
  function normalizeProductDescription(raw) {
    if (raw === null || raw === undefined) return "";
    var s = String(raw).trim();
    if (!s) return "";
    var first = s.charAt(0);
    if (first === "{" || first === "[") {
      try {
        var parsed = JSON.parse(s);
        if (typeof parsed === "string") return parsed.trim();
        if (parsed && typeof parsed === "object" && !Array.isArray(parsed)) {
          var texts = [];
          Object.keys(parsed).forEach(function (k) {
            var v = parsed[k];
            if (typeof v === "string" && v.trim()) texts.push(v.trim());
          });
          if (texts.length) return texts.join("\n\n");
        }
        if (Array.isArray(parsed)) {
          return parsed
            .map(function (x) {
              return typeof x === "string" ? x.trim() : "";
            })
            .filter(Boolean)
            .join("\n\n");
        }
      } catch (e1) {
        var m = /^\{\s*"[^"]*"\s*:\s*"/.exec(s);
        if (m) {
          var rest = s.slice(m[0].length);
          var out = "";
          for (var i = 0; i < rest.length; i++) {
            var ch = rest.charAt(i);
            if (ch === "\\") {
              if (i + 1 < rest.length) {
                out += rest.charAt(i + 1);
                i++;
              }
              continue;
            }
            if (ch === '"') break;
            out += ch;
          }
          var t = out.replace(/\s+/g, " ").trim();
          if (t) return t;
        }
      }
    }
    return s.replace(/\s+/g, " ");
  }

  function buildSpecsHtml(ts) {
    if (!ts || typeof ts !== "object") return "";
    var keys = Object.keys(ts);
    if (!keys.length) return "";
    var rows = keys
      .map(function (k) {
        var v = ts[k];
        var val = Array.isArray(v) ? v.join(", ") : String(v);
        return (
          '<div class="pd__specs-row">' +
          "<dt>" + esc(k) + "</dt>" +
          "<dd>" + esc(val) + "</dd>" +
          "</div>"
        );
      })
      .join("");
    return (
      '<section class="pd__specs-section">' +
      '<h2 class="pd__specs-title">Характеристики</h2>' +
      '<dl class="pd__specs-table">' + rows + "</dl>" +
      "</section>"
    );
  }

  function buildGalleryHtml(photos, mainImg, mainAlt) {
    var hasMultiple = photos && photos.length > 1;

    var thumbsHtml = "";
    if (hasMultiple) {
      thumbsHtml = '<div class="pd__thumbs" role="tablist" aria-label="Фотографии товара">';
      photos.forEach(function (ph, i) {
        var tu = K.mediaUrl(ph.thumb_url || ph.url);
        var fu = K.mediaUrl(ph.url);
        thumbsHtml +=
          '<button type="button"' +
          ' class="pd__thumb' + (i === 0 ? " is-active" : "") + '"' +
          ' role="tab"' +
          ' aria-selected="' + (i === 0 ? "true" : "false") + '"' +
          ' aria-label="Фото ' + (i + 1) + '"' +
          ' data-full="' + esc(fu) + '">' +
          '<img src="' + esc(tu) + '" width="56" height="56" alt="" decoding="async" loading="lazy"/>' +
          "</button>";
      });
      thumbsHtml += "</div>";
    }

    return (
      '<div class="pd__gallery' + (hasMultiple ? "" : " pd__gallery--no-thumbs") + '">' +
      thumbsHtml +
      '<div class="pd__main-wrap">' +
      '<img id="pd-main-img" class="pd__main-img" src="' + esc(mainImg) + '"' +
      ' width="480" height="480" alt="' + esc(mainAlt) + '" decoding="async"/>' +
      "</div>" +
      "</div>"
    );
  }

  function buildCartIcon() {
    return (
      '<svg viewBox="0 0 20 18" aria-hidden="true" fill="none" width="18" height="18">' +
      '<path d="M1 1h3l.6 3M7 1h10l-1.5 9H6L4.6 4M7 1L5.6 4"' +
      ' stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
      '<circle cx="8" cy="16" r="1.5" fill="currentColor"/>' +
      '<circle cx="15" cy="16" r="1.5" fill="currentColor"/>' +
      "</svg>"
    );
  }

  function buildPhoneIcon() {
    return (
      '<svg viewBox="0 0 20 20" aria-hidden="true" fill="none" width="16" height="16">' +
      '<path d="M3 3.5C3 2.67 3.67 2 4.5 2h2.22a1 1 0 01.95.68l.97 2.9a1 1 0 01-.23 1.02L7 8a10.5 10.5 0 005 5l1.4-1.4a1 1 0 011.02-.23l2.9.97A1 1 0 0118 13.28V15.5A2.5 2.5 0 0115.5 18C8.044 18 2 11.956 2 4.5A1.5 1.5 0 013 3.5z"' +
      ' stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>' +
      "</svg>"
    );
  }

  function parseSpecs(raw) {
    if (!raw) return null;
    if (typeof raw === "object") return raw;
    if (typeof raw === "string") {
      try {
        return JSON.parse(raw);
      } catch (e) {
        return null;
      }
    }
    return null;
  }

  function relatedSpecsHtml(rawSpecs) {
    var specs = parseSpecs(rawSpecs);
    if (!specs || typeof specs !== "object") return "";
    var keys = Object.keys(specs).filter(function (k) {
      return k !== "has_verification";
    });
    keys.sort();
    return keys
      .slice(0, 3)
      .map(function (k) {
        var v = specs[k];
        var val = Array.isArray(v) ? v.join(", ") : String(v);
        return (
          '<div class="cat2-card__spec-row"><dt>' +
          esc(k.replace(/_/g, " ")) +
          "</dt><dd>" +
          esc(val) +
          "</dd></div>"
        );
      })
      .join("");
  }

  function relatedProductCardHtml(product) {
    var img = K.mediaUrl(product.photo) || PLACEHOLDER_IMG;
    var href = "/product?slug=" + encodeURIComponent(product.slug || "");
    var title = product.name || "Товар";
    return (
      "<li>" +
      '<article class="cat2-card pd-related__card">' +
      '<a class="cat2-card__media" href="' + esc(href) + '">' +
      '<img src="' + esc(img) + '" width="247" height="247" alt="' + esc(product.photo_alt || title) + '" decoding="async" loading="lazy"/>' +
      "</a>" +
      '<div class="cat2-card__body">' +
      '<h3 class="cat2-card__title"><a href="' + esc(href) + '">' + esc(title) + "</a></h3>" +
      '<dl class="cat2-card__specs">' + relatedSpecsHtml(product.technical_specs) + "</dl>" +
      '<div class="cat2-card__row">' +
      '<p class="cat2-card__price">' + esc(K.fmtPriceRu(product.price)) + "</p>" +
      '<button type="button" class="cat2-card__btn" data-add-cart="' + esc(String(product.id)) + '" data-related-add-cart="' + esc(String(product.id)) + '" aria-label="Добавить ' + esc(title) + ' в корзину">' +
      buildCartIcon() +
      "<span>В корзину</span></button>" +
      "</div>" +
      "</div>" +
      "</article></li>"
    );
  }

  function buildAlsoBoughtHtml(products) {
    if (!Array.isArray(products) || products.length === 0) return "";
    var hasCarousel = products.length > 4;
    return (
      '<section class="pd-related" aria-labelledby="pd-related-title">' +
      '<div class="pd-related__head">' +
      '<div><p class="pd-related__eyebrow">Рекомендуем</p>' +
      '<h2 class="pd-related__title" id="pd-related-title">С этим товаром покупают</h2></div>' +
      '<p class="pd-related__lead">Подберите совместимые позиции и добавьте их в заказ без перехода в каталог.</p>' +
      (hasCarousel
        ? '<div class="pd-related__nav" aria-label="Прокрутка блока рекомендаций">' +
          '<button type="button" class="pd-related__arrow" data-related-prev aria-label="Предыдущие товары">‹</button>' +
          '<button type="button" class="pd-related__arrow" data-related-next aria-label="Следующие товары">›</button>' +
          "</div>"
        : "") +
      "</div>" +
      '<ul class="pd-related__grid' + (hasCarousel ? " is-carousel" : "") + '">' +
      products.map(relatedProductCardHtml).join("") +
      "</ul>" +
      "</section>"
    );
  }

  function bindRelatedCartButtons(root, cart) {
    if (!root || !K.addProductToCart || !K.replaceAddButtonWithCartStepper) return;

    function bindOne(btn) {
      btn.addEventListener("click", function () {
        var id = btn.getAttribute("data-related-add-cart");
        btn.disabled = true;
        btn.innerHTML = buildCartIcon() + "<span>Добавляем…</span>";
        K.addProductToCart(id, 1)
          .then(function (nextCart) {
            btn.disabled = false;
            K.replaceAddButtonWithCartStepper(btn, id, nextCart, bindOne);
          })
          .catch(function () {
            btn.disabled = false;
            btn.innerHTML = buildCartIcon() + "<span>В корзину</span>";
          });
      });
    }

    root.querySelectorAll("[data-related-add-cart]").forEach(bindOne);
    if (K.syncCartSteppersForContainer) {
      K.syncCartSteppersForContainer(root, bindOne, cart);
    }
  }

  function bindRelatedCarousel(root) {
    if (!root) return;
    var grid = root.querySelector(".pd-related__grid.is-carousel");
    if (!grid) return;
    var prev = root.querySelector("[data-related-prev]");
    var next = root.querySelector("[data-related-next]");
    if (!prev || !next) return;

    function stepWidth() {
      var first = grid.querySelector("li");
      if (!first) return grid.clientWidth;
      var cardWidth = first.getBoundingClientRect().width;
      return Math.max(180, Math.round(cardWidth + 16));
    }

    function updateButtons() {
      var max = Math.max(0, grid.scrollWidth - grid.clientWidth - 2);
      prev.disabled = grid.scrollLeft <= 2;
      next.disabled = grid.scrollLeft >= max;
    }

    prev.addEventListener("click", function () {
      grid.scrollBy({ left: -stepWidth(), behavior: "smooth" });
    });
    next.addEventListener("click", function () {
      grid.scrollBy({ left: stepWidth(), behavior: "smooth" });
    });
    grid.addEventListener("scroll", updateButtons, { passive: true });
    window.addEventListener("resize", updateButtons);
    updateButtons();
  }

  function init() {
    var slug = qp("slug").trim();
    var mount = document.getElementById("product-detail");
    if (!mount) return;

    if (!slug || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
      mount.innerHTML = '<p class="catalog-home-loading">Товар не указан</p>';
      return;
    }

    mount.innerHTML = '<p class="catalog-home-loading">Загрузка…</p>';

    Promise.all([
      K.fetchJson("/products/by-slug/" + encodeURIComponent(slug)).then(unwrapProduct),
      K.fetchJson("/cart")
        .then(K.normalizeCartPayload)
        .catch(function () {
          return { items: [], total_quantity: 0, total_amount: 0 };
        }),
    ])
      .then(function (pair) {
        var p = pair[0];
        var cart = pair[1];
        if (!p) {
          mount.innerHTML = '<p class="catalog-home-loading">Товар не найден</p>';
          return;
        }

        K.updateCartLinkLabel(cart.total_quantity);

        document.title = (p.name || "Товар") + " — Контур-М";

        var kc = window.__KONTURM_CONTACTS__ || {};
        var phoneMainHref = String(kc.phone_main_href || "tel:+78432023170");
        var phoneMainLabel = String(kc.phone_main_label || "+7 (843) 202-31-70");

        var photos = Array.isArray(p.photos) && p.photos.length ? p.photos : null;
        var mainImg = (photos && photos[0] ? K.mediaUrl(photos[0].url) : null)
          || K.mediaUrl(p.photo)
          || PLACEHOLDER_IMG;
        var mainAlt = (photos && photos[0] && photos[0].alt) || p.photo_alt || p.name || "";

        var ts = p.technical_specs;
        if (typeof ts === "string") {
          try { ts = JSON.parse(ts); } catch (e) { ts = null; }
        }

        var price = K.fmtPriceRu(p.price);

        var categoryName = (p.category && p.category.name) ? p.category.name : "";
        var categorySlug = (p.category && p.category.slug) ? p.category.slug : "";

        var breadcrumbHtml =
          '<nav class="pd__breadcrumb" aria-label="Навигация">' +
          '<a href="/catalog">Каталог</a>' +
          '<span class="pd__breadcrumb-sep" aria-hidden="true">›</span>' +
          (categoryName && categorySlug
            ? '<a href="' + esc(category2Url(categorySlug)) + '">' + esc(categoryName) + '</a>' +
              '<span class="pd__breadcrumb-sep" aria-hidden="true">›</span>'
            : "") +
          '<span class="pd__breadcrumb-current" aria-current="page">' + esc(p.name || "Товар") + "</span>" +
          "</nav>";

        var galleryHtml = buildGalleryHtml(photos, mainImg, mainAlt);

        var metaHtml =
          '<div class="pd__meta">' +
          (p.article ? '<span class="pd__article">Арт. ' + esc(p.article) + "</span>" : "") +
          '<span class="pd__badge pd__badge--stock">В наличии</span>' +
          "</div>";

        var priceHtml =
          '<div class="pd__price-block">' +
          '<span class="pd__price">' + esc(price) + "</span>" +
          '<span class="pd__price-note">без НДС</span>' +
          "</div>";

        var actionsHtml =
          '<div class="pd__actions">' +
          '<div class="pd__qty-wrap">' +
          '<button type="button" class="pd__qty-btn" data-delta="-1" aria-label="Уменьшить количество">−</button>' +
          '<input class="pd__qty-input" id="pd-qty" type="number" value="1" min="1" max="9999" aria-label="Количество"/>' +
          '<button type="button" class="pd__qty-btn" data-delta="1" aria-label="Увеличить количество">+</button>' +
          "</div>" +
          '<button type="button" class="pd__cta" id="pd-cart-btn" data-pid="' + esc(String(p.id)) + '">' +
          buildCartIcon() +
          "<span>В корзину</span>" +
          "</button>" +
          "</div>";

        var contactHtml =
          '<div class="pd__contact">' +
          buildPhoneIcon() +
          '<span>Нужна консультация? <a href="' +
          esc(phoneMainHref) +
          '">' +
          esc(phoneMainLabel) +
          "</a></span>" +
          "</div>";

        var specsHtml = buildSpecsHtml(ts);

        var descPlain = normalizeProductDescription(p.description);
        var descriptionHtml = descPlain
          ? '<div class="pd__description" aria-label="Описание товара">' + esc(descPlain) + "</div>"
          : "";
        var alsoBoughtHtml = buildAlsoBoughtHtml(p.also_bought_products);

        var infoHtml =
          '<div class="pd__info">' +
          metaHtml +
          '<h1 class="pd__name">' + esc(p.name || "") + "</h1>" +
          '<hr class="pd__divider"/>' +
          priceHtml +
          actionsHtml +
          contactHtml +
          specsHtml +
          "</div>";

        mount.innerHTML =
          '<div class="pd">' +
          breadcrumbHtml +
          '<div class="pd__body">' +
          galleryHtml +
          infoHtml +
          "</div>" +
          descriptionHtml +
          alsoBoughtHtml +
          "</div>";

        /* Gallery thumb switching */
        var mainEl = document.getElementById("pd-main-img");
        mount.querySelectorAll(".pd__thumb").forEach(function (btn) {
          btn.addEventListener("click", function () {
            var full = btn.getAttribute("data-full");
            if (full && mainEl) {
              mainEl.classList.add("is-loading");
              var tmp = new Image();
              tmp.onload = function () {
                mainEl.setAttribute("src", full);
                mainEl.classList.remove("is-loading");
              };
              tmp.onerror = function () {
                mainEl.setAttribute("src", full);
                mainEl.classList.remove("is-loading");
              };
              tmp.src = full;
            }
            mount.querySelectorAll(".pd__thumb").forEach(function (x) {
              x.classList.remove("is-active");
              x.setAttribute("aria-selected", "false");
            });
            btn.classList.add("is-active");
            btn.setAttribute("aria-selected", "true");
          });
        });

        /* Quantity stepper */
        var qtyInput = document.getElementById("pd-qty");
        mount.querySelectorAll(".pd__qty-btn").forEach(function (btn) {
          btn.addEventListener("click", function () {
            var delta = parseInt(btn.getAttribute("data-delta"), 10) || 0;
            var cur = parseInt(qtyInput.value, 10) || 1;
            var next = Math.max(1, cur + delta);
            qtyInput.value = String(next);
          });
        });

        if (qtyInput) {
          qtyInput.addEventListener("change", function () {
            var v = parseInt(qtyInput.value, 10);
            if (isNaN(v) || v < 1) qtyInput.value = "1";
          });
        }

        var qtyWrapEl = mount.querySelector(".pd__qty-wrap");

        function bindPdCartButton(btn) {
          if (!btn) return;
          btn.addEventListener("click", function () {
            var id = btn.getAttribute("data-pid");
            var qty = qtyInput ? Math.max(1, parseInt(qtyInput.value, 10) || 1) : 1;
            btn.disabled = true;
            btn.innerHTML = buildCartIcon() + "<span>Добавляем…</span>";
            K.addProductToCart(id, qty)
              .then(function (cart) {
                btn.disabled = false;
                var step = K.replaceAddButtonWithCartStepper(btn, id, cart, function (newBtn) {
                  if (qtyWrapEl) qtyWrapEl.style.display = "";
                  bindPdCartButton(newBtn);
                });
                if (step && qtyWrapEl) qtyWrapEl.style.display = "none";
              })
              .catch(function () {
                btn.disabled = false;
                btn.innerHTML = buildCartIcon() + "<span>В корзину</span>";
              });
          });
        }

        var cartBtn = document.getElementById("pd-cart-btn");
        bindPdCartButton(cartBtn);

        var lineInCart = K.findProductCartLine(cart, p.id);
        if (lineInCart && lineInCart.quantity >= 1) {
          var b0 = document.getElementById("pd-cart-btn");
          if (b0) {
            var step0 = K.replaceAddButtonWithCartStepper(b0, p.id, cart, function (newBtn) {
              if (qtyWrapEl) qtyWrapEl.style.display = "";
              bindPdCartButton(newBtn);
            });
            if (step0 && qtyWrapEl) qtyWrapEl.style.display = "none";
          }
        }

        bindRelatedCartButtons(mount, cart);
        bindRelatedCarousel(mount);
      })
      .catch(function () {
        mount.innerHTML = '<p class="catalog-home-loading">Не удалось загрузить товар</p>';
      });
  }

  document.addEventListener("DOMContentLoaded", init);
})();
