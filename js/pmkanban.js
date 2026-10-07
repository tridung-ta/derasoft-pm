/* Progressive enhancement: native POST remains available without JavaScript. */
(() => {
    'use strict';
    if (!window.fetch || !window.FormData) return;
    document.querySelectorAll('[data-pm-kanban]').forEach(board => {
        const pending = new WeakSet();
        board.addEventListener('submit', async event => {
            const form = event.target;
            if (!form.matches('[data-task-status-form]')) return;
            event.preventDefault();
            if (pending.has(form)) return;
            const card = form.closest('[data-task-id]');
            const select = form.elements.namedItem('status');
            const message = form.querySelector('[data-task-status-message]');
            const payload = new FormData(form);
            const columns = [...board.querySelectorAll('[data-task-column]')];
            const oldStatus = card.dataset.taskStatus;
            const controls = [...card.querySelectorAll('input,select,textarea,button,summary')];
            const prior = controls.map(control => ({control, disabled: control.disabled, tabIndex: control.getAttribute('tabindex')}));
            pending.add(form);
            card.setAttribute('aria-busy', 'true');
            controls.forEach(control => { if ('disabled' in control) control.disabled = true; else control.setAttribute('tabindex', '-1'); });
            message.textContent = 'Đang cập nhật trạng thái…';
            let moved = false;
            let failureMessage = 'Chưa xác nhận được kết quả cập nhật.';
            const controller = window.AbortController ? new AbortController() : null;
            const timeout = controller ? setTimeout(() => controller.abort(), 20000) : null;
            try {
                const response = await fetch('pm_ajax.php?op=pmtaskstatus', {method: 'POST', credentials: 'same-origin', body: payload, headers: {'Accept': 'application/json'}, ...(controller ? {signal: controller.signal} : {})});
                if (response.redirected) {failureMessage = 'Phiên làm việc đã thay đổi.';throw new Error('redirect');}
                const result = await response.json();
                if (!response.ok) {failureMessage = typeof result.error === 'string' ? result.error : 'Không thể cập nhật trạng thái.';throw new Error('response');}
                const task = result.task;
                const column = task && columns.find(item => item.dataset.taskColumn === task.status);
                if (!column || String(task.id) !== card.dataset.taskId) {failureMessage = 'Phản hồi không hợp lệ.';throw new Error('invalid');}
                column.append(card);
                card.dataset.taskStatus = task.status;
                select.value = task.status;
                // Keep the existing full edit form consistent after the move.
                card.querySelectorAll('select[name="status"]').forEach(item => { item.value = task.status; });
                message.textContent = 'Đã chuyển sang ' + select.selectedOptions[0].textContent + '.';
                moved = true;
            } catch {
                select.value = oldStatus;
                message.textContent = failureMessage + ' Vui lòng tải lại trang để xác nhận trạng thái trước khi thử lại.';
            } finally {
                if (timeout !== null) clearTimeout(timeout);
                prior.forEach(({control, disabled, tabIndex}) => { if ('disabled' in control) control.disabled = disabled; else if (tabIndex === null) control.removeAttribute('tabindex'); else control.setAttribute('tabindex', tabIndex); });
                card.removeAttribute('aria-busy');pending.delete(form);
                if (moved || !board.contains(document.activeElement) || document.activeElement === document.body) select.focus();
            }
        });
    });
})();
