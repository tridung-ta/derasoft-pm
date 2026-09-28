(function () {
  'use strict';

  var header = document.querySelector('[data-editorial-header]');
  var toggle = document.querySelector('[data-editorial-menu-toggle]');
  var navigation = document.querySelector('[data-editorial-navigation]');
  var backToTop = document.getElementById('btnToTop');
  var themeToggle = document.querySelector('[data-theme-toggle]');

  function normalizeMessage(value) {
    var message = String(value || '');
    if (!/[ÃƒÃ‚Ã„Ã†]/.test(message)) return message;
    try { return decodeURIComponent(escape(message)); } catch (error) { return message; }
  }

  function showToast(message, type) {
    var region = document.querySelector('[data-editorial-toasts]');
    if (!region) {
      region = document.createElement('div');
      region.className = 'ed-toasts';
      region.setAttribute('data-editorial-toasts', '');
      region.setAttribute('aria-live', 'polite');
      region.setAttribute('aria-atomic', 'false');
      document.body.appendChild(region);
    }
    var toast = document.createElement('div');
    toast.className = 'ed-toast ed-toast--' + (type === 'error' ? 'error' : 'success');
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.textContent = normalizeMessage(message);
    region.appendChild(toast);
    window.requestAnimationFrame(function () { toast.classList.add('is-visible'); });
    window.setTimeout(function () {
      toast.classList.remove('is-visible');
      window.setTimeout(function () { toast.remove(); }, 220);
    }, 3200);
  }

  window.editorialToast = showToast;

  function updateThemeToggle() {
    if (!themeToggle) return;
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    themeToggle.classList.toggle('is-dark', dark);
    themeToggle.setAttribute('aria-pressed', String(dark));
    themeToggle.querySelector('span').textContent = dark ? '\u2600' : '\u263d';
  }

  if (themeToggle) {
    updateThemeToggle();
    themeToggle.addEventListener('click', function () {
      var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try { window.localStorage.setItem('editorial-theme', next); } catch (error) {}
      updateThemeToggle();
    });
  }

  function enhanceHomeCardImages() {
    var images = document.querySelectorAll('.ed-home .ed-card__media img[loading="lazy"]');
    Array.prototype.forEach.call(images, function (image) {
      var media = image.closest('.ed-card__media');
      if (!media || (image.complete && image.naturalWidth > 0)) return;
      media.classList.add('is-loading');
      var finish = function () { media.classList.remove('is-loading'); };
      image.addEventListener('load', finish, { once: true });
      image.addEventListener('error', finish, { once: true });
      window.setTimeout(finish, 10000);
    });
  }

  function enhanceArchiveViews() {
    var archive = document.querySelector('.ed-archive');
    var controls = document.querySelector('[data-archive-view-controls]');
    var grid = archive && archive.querySelector('.ed-archive-grid');
    if (!archive || !controls || !grid) return;

    var buttons = controls.querySelectorAll('[data-archive-view]');
    var storageKey = archive.classList.contains('ed-archive--arts') ? 'editorial-arts-view' : 'editorial-archive-view';
    var allowed = Array.prototype.map.call(buttons, function (button) {
      return button.getAttribute('data-archive-view');
    });

    function activate(view) {
      if (allowed.indexOf(view) === -1) view = 'grid';
      grid.setAttribute('data-archive-layout', view);
      Array.prototype.forEach.call(buttons, function (button) {
        var active = button.getAttribute('data-archive-view') === view;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', String(active));
      });
      try { window.localStorage.setItem(storageKey, view); } catch (error) {}
    }

    var initialView = 'grid';
    try { initialView = window.localStorage.getItem(storageKey) || initialView; } catch (error) {}
    activate(initialView);
    controls.addEventListener('click', function (event) {
      var button = event.target.closest('[data-archive-view]');
      if (button) activate(button.getAttribute('data-archive-view'));
    });
  }

  function closeMenu() {
    if (!toggle || !navigation) return;
    toggle.classList.remove('is-active');
    navigation.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', toggle.getAttribute('data-open-label') || 'Open menu');
    document.body.classList.remove('menu-open');
  }

  if (header && toggle && navigation) {
    toggle.addEventListener('click', function () {
      var isOpen = toggle.getAttribute('aria-expanded') !== 'true';
      toggle.classList.toggle('is-active', isOpen);
      navigation.classList.toggle('is-open', isOpen);
      toggle.setAttribute('aria-expanded', String(isOpen));
      toggle.setAttribute('aria-label', toggle.getAttribute(isOpen ? 'data-close-label' : 'data-open-label') || 'Menu');
      document.body.classList.toggle('menu-open', isOpen);
      if (isOpen) {
        var firstLink = navigation.querySelector('a');
        if (firstLink) firstLink.focus();
      }
    });

    navigation.addEventListener('click', function (event) {
      if (event.target.closest('a')) closeMenu();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        closeMenu();
        toggle.focus();
      }
    });
    document.addEventListener('click', function (event) {
      if (!header.contains(event.target)) closeMenu();
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 767) closeMenu();
    });
  }

  function updateScrollState() {
    if (header) header.classList.toggle('is-scrolled', window.scrollY > 24);
    if (backToTop) backToTop.classList.toggle('is-visible', window.scrollY > 600);
  }

  if (backToTop) {
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    });
  }
  updateScrollState();
  window.addEventListener('scroll', updateScrollState, { passive: true });
  enhanceHomeCardImages();
  enhanceArchiveViews();
})();
