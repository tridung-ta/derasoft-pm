(function () {
  'use strict';
  var root = document.querySelector('[data-editorial-comments]');
  if (!root) return;
  var form = root.querySelector('[data-comments-form]');
  var textarea = form.querySelector('textarea[name="content"]');
  var submit = form.querySelector('button[type="submit"]');
  var submitLabel = root.querySelector('[data-comments-submit-label]');
  var feedback = root.querySelector('[data-comments-feedback]');
  var list = root.querySelector('[data-comments-list]');
  var total = root.querySelector('[data-comments-total]');
  var length = root.querySelector('[data-comments-length]');
  var more = root.querySelector('[data-comments-more]');
  var articleId = root.getAttribute('data-article-id') || '';
  var lang = root.getAttribute('data-comments-lang') || 'vn';
  var page = 1;
  var totalPages = 0;
  var loading = false;

  function showMessage(text, error) {
    feedback.textContent = text || '';
    feedback.classList.toggle('is-error', Boolean(error));
    if (text && typeof window.editorialToast === 'function') window.editorialToast(text, error ? 'error' : 'success');
  }

  function formatDate(value) {
    var parsed = new Date(String(value || '').replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) return value || '';
    var locale = lang === 'en' ? 'en-US' : (lang === 'zh' ? 'zh-CN' : 'vi-VN');
    return new Intl.DateTimeFormat(locale, { day: '2-digit', month: '2-digit', year: 'numeric' }).format(parsed);
  }

  function makeComment(item) {
    var article = document.createElement('article');
    article.className = 'ed-comment';
    var meta = document.createElement('div');
    meta.className = 'ed-comment__meta';
    var author = document.createElement('strong');
    author.textContent = item.author || '';
    var time = document.createElement('time');
    time.dateTime = item.created_at || '';
    time.textContent = formatDate(item.created_at);
    var content = document.createElement('p');
    content.textContent = item.content || '';
    meta.appendChild(author);
    meta.appendChild(time);
    article.appendChild(meta);
    article.appendChild(content);
    return article;
  }

  function load(nextPage, append) {
    if (loading) return;
    loading = true;
    more.disabled = true;
    var url = '/ajax.php?op=editorial_comments&action=list&article_id=' + encodeURIComponent(articleId) + '&lang=' + encodeURIComponent(lang) + '&page=' + encodeURIComponent(nextPage);
    fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.json().then(function (payload) { if (!response.ok || !payload.success) throw new Error(payload.message || root.getAttribute('data-load-error')); return payload; }); })
      .then(function (payload) {
        if (!append) list.textContent = '';
        (payload.items || []).forEach(function (item) { list.appendChild(makeComment(item)); });
        page = Number(payload.page) || nextPage;
        totalPages = Number(payload.total_pages) || 0;
        total.textContent = String(Number(payload.total) || 0);
        if (!append && !(payload.items || []).length) {
          var empty = document.createElement('p');
          empty.className = 'ed-comments__empty';
          empty.textContent = root.getAttribute('data-empty-label') || '';
          list.appendChild(empty);
        }
        more.hidden = page >= totalPages;
      })
      .catch(function (error) { if (!append) list.textContent = error.message || root.getAttribute('data-load-error'); })
      .then(function () { loading = false; more.disabled = false; });
  }

  textarea.addEventListener('input', function () { length.textContent = String(textarea.value.length); });
  more.addEventListener('click', function () { load(page + 1, true); });
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!form.checkValidity()) { form.reportValidity(); return; }
    submit.disabled = true;
    submitLabel.textContent = lang === 'en' ? 'Submitting…' : (lang === 'zh' ? '正在提交…' : 'Đang gửi…');
    var data = new FormData();
    data.append('op', 'editorial_comments');
    data.append('action', 'create');
    data.append('article_id', articleId);
    data.append('lang', lang);
    data.append('csrf_token', window.csrfToken || '');
    data.append('content', textarea.value);
    fetch('/ajax.php', { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.json().then(function (payload) { if (!response.ok || !payload.success) throw new Error(payload.message || root.getAttribute('data-submit-error')); return payload; }); })
      .then(function (payload) { textarea.value = ''; length.textContent = '0'; showMessage(payload.message, false); })
      .catch(function (error) { showMessage(error.message || root.getAttribute('data-submit-error'), true); })
      .then(function () { submit.disabled = false; submitLabel.textContent = lang === 'en' ? 'Submit for review' : (lang === 'zh' ? '提交审核' : 'Gửi để duyệt'); });
  });

  load(1, false);
})();
