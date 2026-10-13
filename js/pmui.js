/* Native confirmation behavior moved out of inline attributes for CSP. */
document.addEventListener('submit', event => {
    const message = event.submitter?.dataset.pmConfirm || event.target.dataset.pmConfirm;
    if (message && !window.confirm(message)) event.preventDefault();
});

/* Enhance existing, usable details editors; no network or business mutations. */
document.querySelectorAll('[data-pm-new-person]').forEach(form => {
    const fields = [form.querySelector('[name="password"]'), form.querySelector('[name="tel"]')];
    function validate(field) {
        let message = '';
        const value = field.value;
        if (field.name === 'password') {
            if (!value) message = 'Vui lòng nhập mật khẩu.';
            else if (!value.trim()) message = 'Mật khẩu không được chỉ gồm khoảng trắng.';
            else if (Array.from(value.trim()).length < 8) message = 'Mật khẩu phải có ít nhất 8 ký tự.';
        } else if (value && /[^0-9]/.test(value)) {
            message = 'Điện thoại chỉ được chứa chữ số từ 0 đến 9, không có chữ, khoảng trắng hoặc ký tự đặc biệt.';
        } else if (value.length > 10) message = 'Điện thoại không được vượt quá 10 số.';
        const error = form.querySelector('[data-pm-field-error="' + field.name + '"]');
        field.setCustomValidity(message);
        field.setAttribute('aria-invalid', message ? 'true' : 'false');
        error.textContent = message;
        error.hidden = !message;
        return !message;
    }
    fields.forEach(field => {
        field.addEventListener('input', () => validate(field));
        field.addEventListener('blur', () => validate(field));
        field.addEventListener('invalid', () => validate(field));
    });
    form.addEventListener('submit', event => {
        const invalid = fields.filter(field => !validate(field));
        if (invalid.length) { event.preventDefault(); invalid[0].focus(); }
    });
});

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

/* Shared progressive workspace behavior; controllers remain authoritative. */
(() => {
 const menu=document.querySelector('.pm-work-menu');const mobile=matchMedia('(max-width:900px)');const adaptMenu=()=>{if(menu)menu.open=!mobile.matches;};adaptMenu();if(mobile.addEventListener)mobile.addEventListener('change',adaptMenu);
 const pending=new WeakSet();
 document.addEventListener('submit',event=>{const form=event.target;const action=event.submitter?.name==='action'?event.submitter.value:form.elements.namedItem('action')?.value;if(event.defaultPrevented||form.method.toLowerCase()!=='post'||['export','template'].includes(action))return;if(pending.has(form)){event.preventDefault();return;}pending.add(form);const submitter=event.submitter;if(submitter&&submitter.name){const copy=document.createElement('input');copy.type='hidden';copy.name=submitter.name;copy.value=submitter.value;copy.dataset.pmSubmitter='1';form.append(copy);}form.setAttribute('aria-busy','true');form.querySelectorAll('button[type=submit],button:not([type]),input[type=submit]').forEach(b=>{b.dataset.pmWasDisabled=b.disabled?'1':'0';b.disabled=true;});});
 addEventListener('pageshow',()=>{document.querySelectorAll('form[aria-busy=true]').forEach(form=>{pending.delete(form);form.removeAttribute('aria-busy');form.querySelectorAll('[data-pm-was-disabled]').forEach(b=>{b.disabled=b.dataset.pmWasDisabled==='1';delete b.dataset.pmWasDisabled;});form.querySelectorAll('[data-pm-submitter]').forEach(e=>e.remove());});});
 document.querySelectorAll('[data-pm-success],.alert-success').forEach(node=>{if(!node.textContent.trim())return;node.classList.add('pm-toast');const close=document.createElement('button');close.type='button';close.setAttribute('aria-label','Đóng thông báo');const icon=document.createElementNS('http://www.w3.org/2000/svg','svg');icon.setAttribute('viewBox','0 0 24 24');icon.setAttribute('aria-hidden','true');const path=document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d','M6 6l12 12M18 6L6 18');icon.append(path);close.append(icon);close.addEventListener('click',()=>node.remove());node.append(close);});
})();

/* Remember only non-text filter choices in this browser tab; restoring never sends a request. */
(() => {
 const prefix='pm-filter:';
 const allowed=new Set(['department_id','role_id','project_id','user_id','mode','role_code','from','to','week','date','entity_type','action']);
 const read=key=>{try{return JSON.parse(sessionStorage.getItem(key)||'null');}catch{return null;}};
 const forget=key=>{try{sessionStorage.removeItem(key);}catch{}};
 document.querySelectorAll('form[data-pm-filter-memory]').forEach(form=>{
  const op=form.elements.namedItem('op')?.value;if(!op)return;
  const key=prefix+op;
  const fields=[...form.elements].filter(field=>allowed.has(field.name)&&!field.disabled);
  if(!fields.length)return;
  const saved=read(key);
  if(saved&&typeof saved==='object'){
   const restore=document.createElement('button');restore.type='button';restore.className='pm-filter-secondary';restore.textContent='Dùng bộ lọc lần trước';
   restore.addEventListener('click',()=>{fields.forEach(field=>{const value=saved[field.name];if(typeof value!=='string')return;if(field.tagName==='SELECT'&&![...field.options].some(option=>option.value===value))return;field.value=value;field.dispatchEvent(new Event('change',{bubbles:true}));});restore.textContent='Đã điền bộ lọc — chưa áp dụng';});
   restore.dataset.pmFilterRestore='1';form.append(restore);
  }
  const clear=document.createElement('button');clear.type='button';clear.className='pm-filter-secondary';clear.textContent='Quên bộ lọc đã nhớ';clear.addEventListener('click',()=>{forget(key);form.querySelector('[data-pm-filter-restore]')?.remove();clear.textContent='Đã quên bộ lọc';clear.disabled=true;});if(saved)form.append(clear);
  form.addEventListener('submit',event=>{if(event.defaultPrevented)return;const values={};fields.forEach(field=>{values[field.name]=field.value;});try{sessionStorage.setItem(key,JSON.stringify(values));}catch{}});
 });
 document.querySelectorAll('[data-pm-clear-filters]').forEach(link=>link.addEventListener('click',()=>{const op=new URL(link.href).searchParams.get('op');if(op)forget(prefix+op);}));
 document.querySelectorAll('[data-pm-open-person]').forEach(button=>button.addEventListener('click',()=>{const editor=document.querySelector('.pm-add-person');if(!editor)return;editor.open=true;editor.scrollIntoView({block:'start'});editor.querySelector('input[name=username]')?.focus();}));
 document.querySelectorAll('.pm-logout').forEach(button=>button.closest('form')?.addEventListener('submit',()=>{try{Object.keys(sessionStorage).filter(key=>key.startsWith(prefix)).forEach(key=>sessionStorage.removeItem(key));}catch{}}));
 const account=document.querySelector('.pm-user-menu');
 document.addEventListener('click',event=>{if(account?.open&&!account.contains(event.target))account.open=false;});
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&account?.open){account.open=false;account.querySelector('summary').focus();}});
})();

/* Password controls enhance the existing form without changing its POST contract. */
document.querySelectorAll('[data-pm-password-form],.pm-login-page form').forEach(form=>{
 form.querySelectorAll('input[type=password]').forEach(input=>{
  const label=form.querySelector('label[for="'+input.id+'"]')?.textContent.toLowerCase()||'mật khẩu';
  const toggle=document.createElement('button');toggle.type='button';toggle.className='pm-password-toggle';toggle.setAttribute('aria-controls',input.id);toggle.setAttribute('aria-pressed','false');toggle.textContent='Hiện '+label;
  toggle.addEventListener('click',()=>{const visible=input.type==='password';input.type=visible?'text':'password';toggle.setAttribute('aria-pressed',String(visible));toggle.textContent=(visible?'Ẩn ':'Hiện ')+label;});
  input.after(toggle);
 });
 const password=form.elements.namedItem('new_password'),confirm=form.elements.namedItem('confirm_password');
 const message=form.querySelector('#pm-password-match');
 if(!password||!confirm)return;
 const validate=()=>{const mismatch=!!confirm.value&&password.value!==confirm.value;confirm.setCustomValidity(mismatch?'Xác nhận mật khẩu mới không khớp.':'');if(message){message.hidden=!mismatch;message.textContent=mismatch?'Xác nhận mật khẩu mới không khớp. Nhập lại cùng mật khẩu mới.':'';}if(mismatch)confirm.setAttribute('aria-invalid','true');else confirm.removeAttribute('aria-invalid');};
 password.addEventListener('input',validate);confirm.addEventListener('input',validate);
});
/* Audit values stay verbatim: JSON number parsing could round historical DECIMAL values. */

// Open the existing creation form without introducing another route or write path.
document.querySelectorAll('[data-pm-open-project]').forEach(button => button.addEventListener('click', () => {
    const editor = document.querySelector('.pm-project-create');
    if (!editor) return;
    editor.open = true;
    editor.querySelector('input:not([type=hidden])')?.focus();
}));


/* Localize native validation without replacing field-specific application errors. */
document.querySelectorAll('.pm-app input, .pm-app select, .pm-app textarea, .pm-login-page input').forEach(field => {
    let localized = '';
    field.addEventListener('invalid', () => {
        const v = field.validity;
        if (v.customError) return;
        localized = v.valueMissing ? 'Vui lòng điền thông tin bắt buộc.'
            : v.typeMismatch ? (field.type === 'email' ? 'Email không đúng định dạng. Ví dụ: ten@example.com.' : 'Dữ liệu không đúng định dạng yêu cầu.')
            : v.badInput ? 'Vui lòng nhập một số hợp lệ.'
            : v.rangeUnderflow ? 'Giá trị phải lớn hơn hoặc bằng ' + field.min + '.'
            : v.rangeOverflow ? 'Giá trị phải nhỏ hơn hoặc bằng ' + field.max + '.'
            : v.tooShort ? 'Vui lòng nhập ít nhất ' + field.minLength + ' ký tự.'
            : v.tooLong ? 'Chỉ được nhập tối đa ' + field.maxLength + ' ký tự.'
            : v.stepMismatch ? 'Giá trị không đúng bước tăng cho phép. Vui lòng kiểm tra hướng dẫn của ô nhập.'
            : v.patternMismatch ? (field.name === 'tel' ? 'Điện thoại chỉ được gồm chữ số và tối đa 10 số.' : 'Dữ liệu không đúng định dạng yêu cầu.')
            : 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.';
        field.setCustomValidity(localized);
    });
    const clear = () => {if (localized && field.validationMessage === localized) field.setCustomValidity('');localized = '';};
    field.addEventListener('input', clear);field.addEventListener('change', clear);
    field.form?.addEventListener('reset', clear);
});

/* Persistent, readable feedback; never auto-dismiss errors. */
document.querySelectorAll('p.error,.alert-error,.message.error,[data-pm-success],.alert-success').forEach(node => {
    if (!node.textContent.trim() || node.querySelector('.pm-feedback-title')) return;
    const success = node.matches('[data-pm-success],.alert-success') && !node.matches('.error,.alert-error');
    node.classList.add('pm-feedback', success ? 'pm-feedback-success' : 'pm-feedback-error');
    if (success && [...document.querySelectorAll('p.error,.alert-error,.message.error')].some(error => error.textContent.trim() && !error.hidden)) node.classList.remove('pm-toast');
    node.setAttribute('role', success ? 'status' : 'alert');
    const title = document.createElement('strong');
    title.className = 'pm-feedback-title';title.textContent = success ? 'Đã hoàn tất' : 'Cần kiểm tra';
    node.prepend(title);
});
