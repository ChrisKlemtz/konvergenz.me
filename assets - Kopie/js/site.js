/* Konvergenz 53 – kleine Interaktionen, ohne Bibliotheken */
(function () {
  'use strict';
  var root = document.documentElement;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Mobile Navigation */
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  function setNav(open) {
    toggle.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('is-open', open);
  }
  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      setNav(toggle.getAttribute('aria-expanded') !== 'true');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        setNav(false);
        toggle.focus();
      }
    });
    window.matchMedia('(min-width: 52.01rem)').addEventListener('change', function () { setNav(false); });
  }

  /* Sprachauswahl schließen bei Klick außerhalb */
  document.addEventListener('click', function (e) {
    document.querySelectorAll('details.lang[open]').forEach(function (d) {
      if (!d.contains(e.target)) d.removeAttribute('open');
    });
  });

  /* Header-Schatten beim Scrollen */
  var header = document.querySelector('[data-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* Sanftes Einblenden beim Scrollen */
  var items = document.querySelectorAll('.reveal');
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach(function (el) { el.classList.add('is-in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          io.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    // leichte Staffelung innerhalb einer Gruppe
    var groups = new Map();
    items.forEach(function (el) {
      var parent = el.parentElement;
      var i = groups.get(parent) || 0;
      el.style.setProperty('--delay', Math.min(i, 5) * 90 + 'ms');
      groups.set(parent, i + 1);
      io.observe(el);
    });
  }

  /* Speisekarte: aktive Kategorie markieren */
  var jumpLinks = document.querySelectorAll('.menu-jump a');
  if (jumpLinks.length && 'IntersectionObserver' in window) {
    var map = {};
    jumpLinks.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
    var catIo = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        jumpLinks.forEach(function (a) { a.classList.remove('is-active'); a.removeAttribute('aria-current'); });
        var link = map[entry.target.id];
        if (link) {
          link.classList.add('is-active');
          link.setAttribute('aria-current', 'true');
          link.scrollIntoView({ block: 'nearest', inline: 'center', behavior: reduce ? 'auto' : 'smooth' });
        }
      });
    }, { rootMargin: '-35% 0px -60% 0px' });
    document.querySelectorAll('.menu-cat').forEach(function (s) { catIo.observe(s); });
  }
})();
