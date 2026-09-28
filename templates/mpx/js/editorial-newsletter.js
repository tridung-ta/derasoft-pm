(function () {
  'use strict';
  var forms = document.querySelectorAll('[data-newsletter-form]');
  if (!forms.length) return;

  Array.prototype.forEach.call(forms, function (form) {
    var button = form.querySelector('button[type="submit"]');
    var feedback = form.querySelector('[data-newsletter-feedback]');
    var email = form.querySelector('input[name="email"]');
    var consent = form.querySelector('input[name="consent"]');
    var busy = false;

    function show(message, isError) {
      feedback.textContent = message || '';
      feedback.classList.toggle('is-error', Boolean(isError));
      if (message && typeof window.editorialToast === 'function') {
        window.editorialToast(message, isError ? 'error' : 'success');
      }
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      if (busy) return;
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }
      busy = true;
      button.disabled = true;
      button.textContent = button.getAttribute('data-loading-label') || button.textContent;
      show('', false);

      fetch('/ajax.php?op=editorial_newsletter', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Request failed');
            return payload;
          });
        })
        .then(function (payload) {
          show(payload.message, false);
          email.value = '';
          consent.checked = false;
        })
        .catch(function (error) { show(error.message, true); })
        .then(function () {
          busy = false;
          button.disabled = false;
          button.textContent = button.getAttribute('data-default-label') || button.textContent;
        });
    });
  });
})();
