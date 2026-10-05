/* Native confirmation behavior moved out of inline attributes for CSP. */
document.addEventListener('submit', event => {
    const message = event.target.dataset.pmConfirm;
    if (message && !window.confirm(message)) event.preventDefault();
});

/* Enhance existing, usable details editors; no network or business mutations. */
if (typeof HTMLDialogElement !== 'undefined' && typeof HTMLDialogElement.prototype.showModal === 'function') {
    document.querySelectorAll('[data-pm-editor]').forEach(editor => {
        const opener = editor.querySelector('summary');
        const content = editor.querySelector('[data-pm-editor-content]');
        const close = content?.querySelector('[data-pm-dialog-close]');
        if (!opener || !content || !close) return;
        const dialog = document.createElement('dialog');
        dialog.className = 'pm-person-dialog';
        dialog.id = editor.id + '-dialog';
        dialog.setAttribute('aria-labelledby', content.querySelector('h2').id);
        dialog.append(content);
        document.body.append(dialog);
        close.hidden = false;
        opener.setAttribute('aria-haspopup', 'dialog');
        opener.setAttribute('aria-controls', dialog.id);
        opener.addEventListener('click', event => {
            event.preventDefault();
            if (document.querySelector('.pm-person-dialog[open]')) return;
            dialog.showModal();
            (dialog.querySelector('input:not([type="hidden"]),select') || close).focus();
        });
        close.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => opener.focus());
        dialog.addEventListener('keydown', event => {
            if (event.key !== 'Tab') return;
            const controls = [...dialog.querySelectorAll('button,input,select,textarea,a[href],[tabindex]')]
                .filter(control => !control.disabled && control.tabIndex >= 0 && control.getClientRects().length);
            const first = controls[0], last = controls[controls.length - 1];
            if (!first) { event.preventDefault(); dialog.focus(); return; }
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault(); last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault(); first.focus();
            }
        });
    });
}
