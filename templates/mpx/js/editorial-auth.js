(function () {
  'use strict';

  var language = (document.documentElement.lang || 'vi').toLowerCase();
  var locale = language.indexOf('zh') === 0 ? 'zh' : (language.indexOf('en') === 0 ? 'en' : 'vi');
  var messages = {
    vi: { required: 'Vui lòng nhập thông tin này.', email: 'Email chưa đúng định dạng.', username: 'Tên tài khoản cần có ít nhất 6 ký tự.', password: 'Mật khẩu cần ít nhất 8 ký tự, gồm chữ và số.', confirm: 'Mật khẩu nhập lại chưa khớp.', phone: 'Số điện thoại chưa đúng định dạng.', agree: 'Bạn cần đồng ý với điều khoản sử dụng.', loadingLogin: 'Đang đăng nhập…', loadingRegister: 'Đang tạo tài khoản…' },
    en: { required: 'Please complete this field.', email: 'Enter a valid email address.', username: 'Username must contain at least 6 characters.', password: 'Password must contain at least 8 characters, including letters and numbers.', confirm: 'The passwords do not match.', phone: 'Enter a valid phone number.', agree: 'You need to accept the terms of use.', loadingLogin: 'Signing in…', loadingRegister: 'Creating account…' },
    zh: { required: '请填写此字段。', email: '请输入有效的电子邮箱地址。', username: '用户名至少需要 6 个字符。', password: '密码至少需要 8 个字符，并包含字母和数字。', confirm: '两次输入的密码不一致。', phone: '请输入有效的电话号码。', agree: '您需要同意使用条款。', loadingLogin: '正在登录…', loadingRegister: '正在创建账户…' }
  }[locale];

  function normalizeMessage(value) {
    var text = String(value || '');
    if (!/[ÃÂÄÆ]|á[º»]/.test(text)) return text;
    try { return decodeURIComponent(escape(text)); } catch (error) { return text; }
  }

  function show(form, message, success, fieldName) {
    var box = form.querySelector('[data-auth-message]');
    if (!box) return;
    box.hidden = false;
    box.textContent = normalizeMessage(message) || '';
    box.classList.toggle('is-success', !!success);
    box.classList.toggle('is-error', !success);
    box.dataset.state = success ? 'success' : 'error';
    if (success) {
      Array.prototype.forEach.call(form.querySelectorAll('[aria-invalid="true"]'), function (input) { validateInput(input, form); });
      return;
    }
    if (fieldName && fieldName !== 'form' && fieldName !== 'not_verified') {
      var input = form.elements[fieldName];
      if (input) {
        var error = errorElement(input);
        input.setAttribute('aria-invalid', 'true');
        if (error) { error.textContent = box.textContent; error.hidden = false; input.setAttribute('aria-describedby', error.id); }
        input.focus();
      }
    }
  }

  function errorElement(input) {
    var field = input.closest('.ed-auth-field, .ed-auth-check');
    if (!field) return null;
    var error = field.querySelector('.ed-auth-field__error');
    if (!error) {
      error = document.createElement('small');
      error.className = 'ed-auth-field__error';
      error.id = 'auth-error-' + input.name;
      error.setAttribute('aria-live', 'polite');
      field.appendChild(error);
    }
    return error;
  }

  function validationMessage(input, form) {
    var value = input.type === 'checkbox' ? input.checked : input.value.trim();
    if (input.required && !value) return input.type === 'checkbox' ? messages.agree : messages.required;
    if (!value) return '';
    if (input.name === 'email' || (input.name === 'username' && value.indexOf('@') !== -1)) {
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return messages.email;
    }
    if (form.dataset.editorialAuth === 'register') {
      if (input.name === 'username' && value.length < 6) return messages.username;
      if (input.name === 'password' && (value.length < 8 || !/[A-Za-z]/.test(value) || !/\d/.test(value))) return messages.password;
      if (input.name === 'confirmPassword' && value !== form.elements.password.value) return messages.confirm;
      if (input.name === 'phone' && !/^[+\d][\d\s().-]{7,19}$/.test(value)) return messages.phone;
    }
    return '';
  }

  function validateInput(input, form) {
    var message = validationMessage(input, form);
    var error = errorElement(input);
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
    if (error) {
      error.textContent = message;
      error.hidden = !message;
      input.setAttribute('aria-describedby', error.id);
    }
    return !message;
  }

  function validateForm(form) {
    var valid = true;
    Array.prototype.forEach.call(form.querySelectorAll('input:not([type="hidden"])'), function (input) {
      if (!validateInput(input, form)) valid = false;
    });
    if (!valid) {
      var first = form.querySelector('[aria-invalid="true"]');
      if (first) first.focus();
    }
    return valid;
  }

  function setSubmitting(form, active) {
    var button = form.querySelector('[type="submit"]');
    if (!button) return;
    if (active) {
      if (!button.dataset.defaultHtml) button.dataset.defaultHtml = button.innerHTML;
      var label = button.querySelector('span');
      if (label) label.textContent = form.dataset.editorialAuth === 'register' ? messages.loadingRegister : messages.loadingLogin;
      button.disabled = true;
      button.classList.add('is-loading');
      button.setAttribute('aria-busy', 'true');
      return;
    }
    if (button.dataset.defaultHtml) button.innerHTML = button.dataset.defaultHtml;
    button.disabled = false;
    button.classList.remove('is-loading');
    button.removeAttribute('aria-busy');
  }

  function submit(form) {
    var redirecting = false;
    setSubmitting(form, true);
    fetch('/ajax.php', { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data || !data.success) { show(form, (data && data.message) || 'Không thể thực hiện yêu cầu.', false, data && data.field); return; }
        show(form, data.message || 'Thành công.', true, data.field);
        if (data.redirect) {
          redirecting = true;
          window.setTimeout(function () { window.location.assign(data.redirect); }, 120);
        }
      })
      .catch(function () { show(form, 'Không thể kết nối. Vui lòng thử lại.', false, 'form'); })
      .finally(function () { if (!redirecting) setSubmitting(form, false); });
  }

  document.addEventListener('input', function (event) {
    var input = event.target.closest('[data-editorial-auth] input');
    if (!input || input.type === 'hidden') return;
    validateInput(input, input.form);
    if (input.name === 'password' && input.form.elements.confirmPassword && input.form.elements.confirmPassword.value) validateInput(input.form.elements.confirmPassword, input.form);
  });

  document.addEventListener('focusout', function (event) {
    var input = event.target.closest('[data-editorial-auth] input');
    if (input && input.type !== 'hidden') validateInput(input, input.form);
  });

  document.addEventListener('submit', function (event) {
    var form = event.target.closest('[data-editorial-auth]');
    if (!form) return;
    event.preventDefault();
    if (validateForm(form)) submit(form);
  });

  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-password-toggle]');
    if (!toggle) return;
    var input = toggle.parentNode.querySelector('input');
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    toggle.classList.toggle('is-visible', input.type === 'text');
  });
})();
