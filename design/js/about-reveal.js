(function () {
  'use strict';

  document.documentElement.classList.add('ab-reveal-js');

  function revealSection(section) {
    var items = section.querySelectorAll('.ab-reveal-item');
    if (!items.length) {
      return;
    }

    var reduceMotion =
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduceMotion) {
      items.forEach(function (el) {
        el.classList.add('is-visible');
      });
      return;
    }

    var io = new IntersectionObserver(
      function (entries, observer) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }
          items.forEach(function (el) {
            el.classList.add('is-visible');
          });
          observer.unobserve(entry.target);
        });
      },
      { root: null, rootMargin: '0px 0px -10% 0px', threshold: 0.08 }
    );

    io.observe(section);
  }

  function run() {
    document.querySelectorAll('.ab-workflow, .ab-why').forEach(revealSection);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
