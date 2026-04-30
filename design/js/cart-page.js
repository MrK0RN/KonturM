(function () {
  "use strict";

  var K = window.KonturM;
  if (!K) return;

  /* ── Helpers ─────────────────────────────────────────── */
  var PLACEHOLDER_SVG =
    '<svg class="cart-line__img-placeholder" viewBox="0 0 36 36" fill="none" aria-hidden="true">' +
    '<rect width="36" height="36" rx="8" fill="currentColor"/>' +
    '<path d="M8 28l7-8 5 6 4-5 7 7" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
    '<circle cx="12" cy="13" r="3" stroke="#fff" stroke-width="1.5"/>' +
    "</svg>";

  var ICON_REMOVE =
    '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">' +
    '<path d="M3 8h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>' +
    '<path d="M2 4h12M6 4V2h4v2M6 12l-.3-6M10 12l.3-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>' +
    "</svg>";

  function fmtPrice(val) {
    return (Number(val) || 0).toLocaleString("ru-RU", {
      maximumFractionDigits: 2,
      minimumFractionDigits: 2,
    }) + " ₽";
  }

  /* ── Render cart lines ──────────────────────────────────── */
  function render(cart, mount, emptyEl, layoutEl) {
    var items = cart.items || [];

    if (!items.length) {
      mount.innerHTML = "";
      if (emptyEl) emptyEl.hidden = false;
      if (layoutEl) layoutEl.hidden = true;
      return;
    }

    if (emptyEl) emptyEl.hidden = true;
    if (layoutEl) layoutEl.hidden = false;

    var html = "";
    items.forEach(function (it) {
      var name = K.escapeHtml(it.name || "Позиция");
      var art = it.article ? K.escapeHtml(it.article) : "";
      var price = fmtPrice(it.price);
      var qty = it.quantity | 0;
      var rawId = String(it.id);
      var idAttr = K.escapeHtml(rawId);
      var safeDomId = K.escapeHtml(rawId.replace(/[^a-zA-Z0-9_-]/g, "_"));
      var href = it.slug
        ? K.escapeHtml("/product?slug=" + encodeURIComponent(it.slug))
        : "#";

      var imgHtml = it.image
        ? '<img src="' + K.escapeHtml(it.image) + '" alt="' + name + '" loading="lazy" />'
        : PLACEHOLDER_SVG;

      html +=
        '<li class="cart-line">' +
        '<div class="cart-line__img-wrap">' + imgHtml + "</div>" +
        '<div class="cart-line__info">' +
        '<p class="cart-line__name"><a href="' + href + '">' + name + "</a></p>" +
        (art ? '<p class="cart-line__article">Арт. ' + art + "</p>" : "") +
        "</div>" +
        '<div class="cart-line__controls">' +
        '<div class="cart-qty-wrap">' +
        '<button type="button" class="cart-qty-btn" data-cart-dec="' + idAttr + '" aria-label="Уменьшить количество">−</button>' +
        '<input class="cart-qty-input" type="number" min="1" step="1" id="q_' + safeDomId + '" data-cart-qty="' + idAttr + '" value="' + qty + '" aria-label="Количество" />' +
        '<button type="button" class="cart-qty-btn" data-cart-inc="' + idAttr + '" aria-label="Увеличить количество">+</button>' +
        "</div>" +
        '<p class="cart-line__price">' + K.escapeHtml(price) + "</p>" +
        '<button type="button" class="cart-line__remove" data-cart-remove="' + idAttr + '" aria-label="Удалить">' + ICON_REMOVE + "</button>" +
        "</div>" +
        "</li>";
    });

    mount.innerHTML = html;
    bindEvents(mount);
  }

  function bindEvents(mount) {
    /* Qty input change */
    mount.querySelectorAll("[data-cart-qty]").forEach(function (inp) {
      inp.addEventListener("change", function () {
        var cid = inp.getAttribute("data-cart-qty");
        var q = parseInt(inp.value, 10) || 0;
        K.patchCartItem(cid, q)
          .then(function (c) { refreshAll(c); })
          .catch(function () {});
      });
    });

    /* Decrement button */
    mount.querySelectorAll("[data-cart-dec]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var cid = btn.getAttribute("data-cart-dec");
        var inp = mount.querySelector('[data-cart-qty="' + cid + '"]');
        var current = parseInt((inp && inp.value) || "1", 10) || 1;
        if (current <= 1) {
          refreshAll(optimisticCartWithoutItem(cid));
          K.deleteCartItem(cid)
            .then(function (c) { refreshAll(c); })
            .catch(function () {
              K.refreshCartBadge().then(function (c) { refreshAll(c); });
            });
          return;
        }
        K.patchCartItem(cid, current - 1)
          .then(function (c) { refreshAll(c); })
          .catch(function () {});
      });
    });

    /* Increment button */
    mount.querySelectorAll("[data-cart-inc]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var cid = btn.getAttribute("data-cart-inc");
        var inp = mount.querySelector('[data-cart-qty="' + cid + '"]');
        var q = (parseInt((inp && inp.value) || "1", 10) || 1) + 1;
        K.patchCartItem(cid, q)
          .then(function (c) { refreshAll(c); })
          .catch(function () {});
      });
    });

    /* Remove button */
    mount.querySelectorAll("[data-cart-remove]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var cid = btn.getAttribute("data-cart-remove");
        refreshAll(optimisticCartWithoutItem(cid));
        K.deleteCartItem(cid)
          .then(function (c) { refreshAll(c); })
          .catch(function () {
            K.refreshCartBadge().then(function (c) { refreshAll(c); });
          });
      });
    });
  }

  /* ── Global refs ────────────────────────────────────────── */
  var _mount, _emptyEl, _layoutEl, _sumEl, _qtyEl, _summaryQtyEl, _cartState;

  function refreshAll(cart) {
    _cartState = cart || { items: [], total_quantity: 0, total_amount: 0 };
    render(cart, _mount, _emptyEl, _layoutEl);
    K.updateCartLinkLabel(cart.total_quantity);
    var total = Number(cart.total_amount) || 0;
    var qty = cart.total_quantity | 0;
    if (_sumEl) _sumEl.textContent = fmtPrice(total);
    if (_qtyEl) _qtyEl.textContent = String(qty);
    if (_summaryQtyEl) _summaryQtyEl.textContent = String(qty);
  }

  function optimisticCartWithoutItem(itemId) {
    var base = _cartState || { items: [], total_quantity: 0, total_amount: 0 };
    var items = Array.isArray(base.items) ? base.items : [];
    var nextItems = [];
    var removed = null;
    items.forEach(function (it) {
      if (!removed && String(it.id) === String(itemId)) {
        removed = it;
        return;
      }
      nextItems.push(it);
    });
    if (!removed) return base;
    var removedQty = Number(removed.quantity) || 0;
    var removedSum = (Number(removed.price) || 0) * removedQty;
    return {
      items: nextItems,
      total_quantity: Math.max(0, (Number(base.total_quantity) || 0) - removedQty),
      total_amount: Math.max(0, (Number(base.total_amount) || 0) - removedSum),
    };
  }

  /* ── INN validation helper ─────────────────────────────── */
  function validateInn(inn) {
    if (!inn) return true; // optional field
    var s = String(inn).replace(/\D/g, "");
    return s.length === 10 || s.length === 12;
  }

  /* ── Init ───────────────────────────────────────────────── */
  function init() {
    _mount = document.getElementById("cart-lines");
    _emptyEl = document.getElementById("cart-empty");
    _layoutEl = document.getElementById("cart-layout");
    _sumEl = document.getElementById("cart-sum");
    _qtyEl = document.getElementById("cart-total-qty");
    _summaryQtyEl = document.getElementById("cart-summary-qty");

    var clearBtn = document.getElementById("cart-clear");
    var form = document.getElementById("cart-checkout-form");
    var submitBtn = document.getElementById("cart-submit-btn");
    var statusEl = document.getElementById("cart-checkout-status");
    var innInput = document.getElementById("co-inn");

    if (!_mount) return;

    /* Initial load */
    K.refreshCartBadge().then(function (cart) {
      refreshAll(cart);
    });

    /* Clear cart */
    if (clearBtn) {
      clearBtn.addEventListener("click", function () {
        K.clearCart()
          .then(function (c) { refreshAll(c); })
          .catch(function () {});
      });
    }

    /* INN: allow only digits */
    if (innInput) {
      innInput.addEventListener("input", function () {
        innInput.value = innInput.value.replace(/\D/g, "");
      });
    }

    /* Checkout form */
    if (form) {
      form.addEventListener("submit", function (ev) {
        ev.preventDefault();

        /* INN client-side validation */
        if (innInput && innInput.value && !validateInn(innInput.value)) {
          innInput.focus();
          showStatus("ИНН должен содержать 10 или 12 цифр", "error");
          return;
        }

        var fd = new FormData(form);
        var payload = {
          customer_name: String(fd.get("customer_name") || ""),
          customer_phone: String(fd.get("customer_phone") || ""),
          customer_email: String(fd.get("customer_email") || ""),
          customer_company: String(fd.get("customer_company") || ""),
          customer_inn: String(fd.get("customer_inn") || ""),
          comment: String(fd.get("comment") || ""),
        };

        if (submitBtn) submitBtn.disabled = true;

        K.fetchJson("/cart/checkout", { method: "POST", body: JSON.stringify(payload) })
          .then(function (res) {
            showStatus("Заявка отправлена. Номер заказа: " + (res.order_number || "—"), "success");
            form.reset();
            K.refreshCartBadge().then(function (c) { refreshAll(c); });
          })
          .catch(function (e) {
            showStatus("Ошибка: " + (e.message || "не удалось оформить"), "error");
          })
          .finally(function () {
            if (submitBtn) submitBtn.disabled = false;
          });
      });
    }

    function showStatus(text, type) {
      if (!statusEl) return;
      statusEl.textContent = text;
      statusEl.className = "cart-status cart-status--" + type;
      statusEl.hidden = false;
      statusEl.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  }

  document.addEventListener("DOMContentLoaded", init);
})();
