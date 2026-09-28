(function () {
  'use strict';
  var article = document.querySelector('[data-reader-article]');
  var content = document.getElementById('articleContent');
  if (!article || !content) return;
  var language = document.documentElement.lang || 'vi';
  var plainText = (content.textContent || '').replace(/\s+/g, ' ').trim();
  var units = language.indexOf('zh') === 0 ? plainText.replace(/\s+/g, '').length : (plainText ? plainText.split(' ').length : 0);
  var minutes = Math.max(1, Math.ceil(units / (language.indexOf('zh') === 0 ? 400 : 220)));
  var time = document.getElementById('articleReadingTime');
  if (time) time.textContent = language.indexOf('en') === 0 ? minutes + ' min read' : (language.indexOf('zh') === 0 ? '阅读约 ' + minutes + ' 分钟' : minutes + ' phút đọc');

  var toc = document.getElementById('articleTableOfContents');
  var headings = content.querySelectorAll('h2, h3');
  Array.prototype.forEach.call(headings, function (heading, index) {
    if (!heading.id) heading.id = 'section-' + (index + 1);
    var link = document.createElement('a');
    link.href = '#' + heading.id;
    link.textContent = heading.textContent.trim();
    link.setAttribute('data-level', heading.tagName === 'H3' ? '3' : '2');
    toc.appendChild(link);
  });
  if (!headings.length) {
    var tools = document.getElementById('articleReaderTools');
    if (tools) tools.classList.add('has-no-toc');
  }

  var progress = document.getElementById('articleReadingProgress');
  function updateProgress() {
    var rect = article.getBoundingClientRect();
    var travelled = Math.max(0, -rect.top);
    var available = Math.max(1, article.offsetHeight - window.innerHeight);
    if (progress) progress.style.width = Math.min(100, travelled / available * 100) + '%';
  }
  updateProgress();
  window.addEventListener('scroll', updateProgress, { passive: true });
  window.addEventListener('resize', updateProgress);

  var nativeButton = document.querySelector('[data-share-native]');
  if (nativeButton) nativeButton.addEventListener('click', function () {
    if (navigator.share) navigator.share({ title: document.title, url: window.location.href }).catch(function () {});
    else window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank', 'noopener,noreferrer,width=720,height=560');
  });
  var copyButton = document.querySelector('[data-copy-link]');
  if (copyButton) copyButton.addEventListener('click', function () {
    var original = copyButton.getAttribute('data-copy-label') || copyButton.textContent;
    var done = function () { var message = copyButton.getAttribute('data-copied-label') || original; copyButton.textContent = message; if (window.editorialToast) window.editorialToast(message, 'success'); window.setTimeout(function () { copyButton.textContent = original; }, 1800); };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(window.location.href).then(done).catch(function () {});
  });
})();
