/* Import password feedback only; no requests, password storage or global form changes. */
(() => {
    const serverError = document.querySelector('[data-pm-import-error]');
    if (serverError) {
        serverError.focus({ preventScroll: true });
        serverError.scrollIntoView({ block: 'center' });
    }
    document.querySelectorAll('form[data-pm-provision-password]').forEach(form => {
        const input = form.querySelector('input[name=password]');
        const feedback = form.querySelector('[data-pm-password-error]');
        if (!input || !feedback) return;
        // The service measures UTF-8 bytes, whereas native minlength measures characters.
        input.removeAttribute('minlength');
        let wasValidated = false;
        function validate() {
            wasValidated = true;
            const bytes = new TextEncoder().encode(input.value).length;
            let message = '';
            if (!input.value) message = 'Vui lòng nhập mật khẩu cho tài khoản này.';
            else if (input.value.includes('\0')) message = 'Mật khẩu không được chứa ký tự NUL.';
            else if (bytes < 12 || bytes > 72) message = 'Mật khẩu cần 12–72 byte UTF-8; hiện có ' + bytes + ' byte.';
            input.setCustomValidity(message);
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
            feedback.textContent = message;
            feedback.hidden = !message;
            return !message;
        }
        input.addEventListener('invalid', validate);
        input.addEventListener('blur', validate);
        input.addEventListener('input', () => {
            if (wasValidated) validate();
        });
        form.addEventListener('submit', event => {
            if (!validate()) {
                event.preventDefault();
                input.focus();
                input.reportValidity();
            }
        });
    });
})();
