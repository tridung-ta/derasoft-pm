/* Native confirmation behavior moved out of inline attributes for CSP. */
document.addEventListener('submit', event => {
    const message = event.target.dataset.pmConfirm;
    if (message && !window.confirm(message)) event.preventDefault();
});
