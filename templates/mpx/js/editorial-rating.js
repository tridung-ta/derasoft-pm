(function () {
  'use strict';
  var root = document.querySelector('[data-editorial-rating]');
  if (!root) return;
  var stars = root.querySelector('.ed-rating__stars');
  var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-rating-value]'));
  var status = root.querySelector('[data-rating-status]');
  var average = root.querySelector('[data-rating-average]');
  var count = root.querySelector('[data-rating-count]');
  var selected = Number(root.getAttribute('data-selected-rating')) || 0;
  var busy = false;

  function paint(value) {
    buttons.forEach(function (button) {
      var active = Number(button.getAttribute('data-rating-value')) <= value;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
  }

  function notify(text, type) {
    if (status) status.textContent = text;
    if (typeof window.editorialToast === 'function') window.editorialToast(text, type || 'success');
  }

  function submit(value) {
    if (busy || value < 1 || value > 5) return;
    busy = true;
    root.classList.add('is-loading');
    buttons.forEach(function (button) { button.disabled = true; });
    var data = new FormData();
    data.append('op', 'editorial_rating');
    data.append('csrf_token', window.csrfToken || '');
    data.append('article_id', root.getAttribute('data-article-id') || '');
    data.append('rating', String(value));
    data.append('lang', root.getAttribute('data-rating-lang') || 'vn');
    fetch('/ajax.php', { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) {
        return response.json().catch(function () { return {}; }).then(function (payload) {
          if (!response.ok || !payload.success) throw new Error(payload.message || 'Không thể lưu đánh giá.');
          return payload;
        });
      })
      .then(function (payload) {
        selected = Number(payload.rating) || value;
        root.setAttribute('data-selected-rating', String(selected));
        paint(selected);
        if (average) average.textContent = Number(payload.average || 0).toFixed(1);
        if (count) count.textContent = String(Number(payload.count) || 0);
        notify(payload.message || 'Đã lưu đánh giá của bạn.', 'success');
      })
      .catch(function (error) { paint(selected); notify(error.message || 'Không thể lưu đánh giá.', 'error'); })
      .then(function () {
        busy = false;
        root.classList.remove('is-loading');
        buttons.forEach(function (button) { button.disabled = false; });
      });
  }

  paint(selected);
  buttons.forEach(function (button) {
    button.addEventListener('mouseenter', function () { if (!busy) paint(Number(button.getAttribute('data-rating-value'))); });
    button.addEventListener('focus', function () { if (!busy) paint(Number(button.getAttribute('data-rating-value'))); });
    button.addEventListener('click', function () { submit(Number(button.getAttribute('data-rating-value'))); });
  });
  stars.addEventListener('mouseleave', function () { if (!busy) paint(selected); });
  stars.addEventListener('focusout', function (event) { if (!stars.contains(event.relatedTarget) && !busy) paint(selected); });
})();
