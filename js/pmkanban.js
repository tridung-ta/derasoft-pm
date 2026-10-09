/* Progressive enhancement: native POST remains available without JavaScript. */
(() => {
    'use strict';
    if (!window.fetch || !window.FormData) return;
    document.querySelectorAll('[data-pm-kanban]').forEach(board => {
        const pending = new WeakSet();
        const cards = [...board.querySelectorAll('[data-task-id]')];
        const toolbar = document.createElement('div');
        toolbar.className = 'pm-kanban-toolbar';
        const label = document.createElement('label'); label.textContent = 'Người phụ trách';
        const filter = document.createElement('select'); filter.className = 'form-select';
        filter.add(new Option('Tất cả người phụ trách', ''));
        const people = new Map();
        cards.forEach(card => people.set(card.dataset.taskAssigneeId || 'unassigned', card.dataset.taskAssignee || 'Chưa giao'));
        const nameCounts = new Map();people.forEach(name => nameCounts.set(name, (nameCounts.get(name) || 0) + 1));
        [...people].sort((a,b) => a[1].localeCompare(b[1], 'vi')).forEach(([id,name]) => filter.add(new Option(nameCounts.get(name) > 1 ? name + ' (tài khoản #' + id + ')' : name, id)));
        label.append(filter); toolbar.append(label);
        const hint = document.createElement('p'); hint.textContent = 'Kéo công việc sang cột khác hoặc dùng nút cập nhật trạng thái.';
        toolbar.append(hint); board.before(toolbar);
        const summary = document.createElement('p'); summary.className = 'pm-board-result'; summary.setAttribute('role','status'); summary.setAttribute('aria-live','polite'); toolbar.after(summary);
        const refreshBoard = () => {
            const visible = cards.filter(card => !card.hidden).length;
            summary.textContent = filter.value ? visible + ' công việc của người được chọn' : cards.length + ' công việc trong dự án';
            board.querySelectorAll('[data-task-column]').forEach(column => {
                const count = [...column.querySelectorAll('[data-task-id]')].filter(card => !card.hidden).length;
                let badge = column.querySelector('.pm-column-count');
                if (!badge) {badge = document.createElement('span'); badge.className='pm-column-count'; column.querySelector('h3').append(badge);}
                badge.textContent = String(count); badge.setAttribute('aria-label',count + ' công việc');
                let empty = column.querySelector('.pm-column-empty');
                if (!empty) {empty=document.createElement('p');empty.className='pm-column-empty';column.append(empty);}
                empty.textContent = filter.value ? 'Không có công việc của người được chọn.' : 'Chưa có công việc ở trạng thái này.';
                empty.hidden = count > 0;
            });
        };
        filter.addEventListener('change', () => {cards.forEach(card => {card.hidden = !!filter.value && (card.dataset.taskAssigneeId || 'unassigned') !== filter.value;});refreshBoard();});
        refreshBoard();
        const parts = new Intl.DateTimeFormat('en-CA', {timeZone:'Asia/Ho_Chi_Minh',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date());
        const part = type => parts.find(item => item.type === type).value;
        const today = part('year') + '-' + part('month') + '-' + part('day');
        const updateDue = card => {
            const overdue = card.dataset.taskDue && card.dataset.taskDue < today && card.dataset.taskStatus !== 'done';
            let badge = card.querySelector('[data-task-overdue]');
            if (overdue && !badge) {badge = document.createElement('span'); badge.dataset.taskOverdue = ''; badge.className = 'pm-task-overdue'; badge.textContent = 'Quá hạn'; card.querySelector('h4').after(badge);}
            if (!overdue && badge) badge.remove();
        };
        let dragged = null;
        cards.forEach(card => {
            updateDue(card);
            if (!card.querySelector('[data-task-status-form]')) return;
            const handle = document.createElement('span'); handle.className = 'pm-task-drag'; handle.textContent = 'Kéo để chuyển'; handle.draggable = true;
            card.prepend(handle);
            handle.addEventListener('dragstart', event => {
                if (card.hasAttribute('aria-busy')) {event.preventDefault(); return;}
                dragged = card; event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', card.dataset.taskId);
            });
            handle.addEventListener('dragend', () => {dragged = null; board.querySelectorAll('.pm-drop-target').forEach(column => column.classList.remove('pm-drop-target'));});
        });
        board.querySelectorAll('[data-task-column]').forEach(column => {
            column.addEventListener('dragover', event => {if (dragged) {event.preventDefault(); event.dataTransfer.dropEffect = 'move'; column.classList.add('pm-drop-target');}});
            column.addEventListener('dragleave', event => {if (!column.contains(event.relatedTarget)) column.classList.remove('pm-drop-target');});
            column.addEventListener('drop', event => {
                event.preventDefault(); column.classList.remove('pm-drop-target');
                if (!dragged || dragged.hasAttribute('aria-busy') || dragged.dataset.taskStatus === column.dataset.taskColumn) return;
                const form = dragged.querySelector('[data-task-status-form]');
                form.elements.namedItem('status').value = column.dataset.taskColumn;
                if (form.requestSubmit) form.requestSubmit(); else form.dispatchEvent(new Event('submit', {bubbles:true,cancelable:true}));
                dragged = null;
            });
        });
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
                if (!response.ok) {failureMessage = response.status === 401 ? (location.pathname.startsWith('/.local/') ? 'Đây là bản xem giao diện bằng dữ liệu giả, chưa có phiên đăng nhập. Để lưu trạng thái thật, hãy mở trang Dự án sau khi đăng nhập vào ứng dụng.' : 'Bạn chưa đăng nhập hoặc phiên đăng nhập đã hết hạn.') : (typeof result.error === 'string' ? result.error : 'Không thể cập nhật trạng thái.');throw new Error('response');}
                const task = result.task;
                const column = task && columns.find(item => item.dataset.taskColumn === task.status);
                if (!column || String(task.id) !== card.dataset.taskId) {failureMessage = 'Phản hồi không hợp lệ.';throw new Error('invalid');}
                column.append(card);
                card.dataset.taskStatus = task.status;
                updateDue(card);
                refreshBoard();
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
