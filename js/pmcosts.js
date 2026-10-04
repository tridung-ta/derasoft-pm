/* Chart.js only receives numeric display values; authoritative money remains decimal strings. */
(() => {
    'use strict';
    const source = document.getElementById('cost-data');
    if (!source) return;
    let data = JSON.parse(source.textContent), timer = null, controller = null, authorizationLost = false;
    const charts = {}, status = document.getElementById('refresh-status');
    const text = (id, value) => { document.getElementById(id).textContent = value; };
    const amount = row => row.cost === null ? 'Không cộng: khác currency hoặc thiếu dữ liệu' : `${row.cost} ${row.currency}`;
    const node = (tag, value) => { const element = document.createElement(tag); element.textContent = value; return element; };
    const row = (table, values) => { const tr = document.createElement('tr'); values.forEach(value => { const td = document.createElement('td'); if (typeof value === 'string') td.textContent = value; else td.append(value); tr.append(td); }); table.append(tr); };
    const chart = (id, labels, datasets) => {
        if (!window.Chart) return;
        if (charts[id]) { charts[id].data = { labels, datasets }; charts[id].update('none'); }
        else charts[id] = new window.Chart(document.getElementById(id), { type: 'bar', data: { labels, datasets }, options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { position: 'bottom' } } } });
    };
    function draw() {
        const sameCurrency = data.summary.cost !== null;
        const dates = Object.keys(data.groups.date);
        chart('trend-chart', sameCurrency ? dates : [], [{ label: sameCurrency ? `Actual (${data.summary.currency})` : 'Không vẽ tổng tiền: khác currency', data: sameCurrency ? dates.map(d => Number(data.groups.date[d].cost)) : [], backgroundColor: '#9a3412' }]);
        const comparable = data.projects.filter(p => p.comparable);
        chart('budget-chart', comparable.map(p => p.name), [
            { label: 'Ngân sách VND', data: comparable.map(p => Number(p.budget)), backgroundColor: '#64748b' },
            { label: 'Actual toàn đời VND', data: comparable.map(p => Number(p.lifetime.cost)), backgroundColor: '#9a3412' },
            { label: 'Ước tính VND', data: comparable.map(p => Number(p.estimate.cost)), backgroundColor: '#0f766e' }
        ]);
    }
    function render() {
        text('actual-cost', amount(data.summary));
        text('valuation-date', data.valuation_date);
        text('actual-hours', `${data.summary.hours} / ${data.summary.regular_hours} / ${data.summary.ot_hours}`);
        text('estimate-cost', data.estimate_total === null ? 'Chưa đủ dữ liệu đồng nhất' : `${data.estimate_total} ${data.estimate_currency}`);
        const warnings = document.getElementById('warnings'); warnings.replaceChildren(); data.warnings.forEach(w => warnings.append(node('li', w)));
        const projects = document.getElementById('projects'); projects.replaceChildren();
        data.projects.forEach(p => {
            const link = node('a', p.name), params = new URLSearchParams({ op: 'pmcosts', project_id: p.id, from: data.filters.from, to: data.filters.to });
            link.href = `?${params}`;
            row(projects, [link, p.budget, amount(p.actual), amount(p.lifetime), amount(p.estimate)]);
        });
        if (!data.projects.length) { const tr = node('tr', ''), td = node('td', 'Không có dự án trong phạm vi.'); td.colSpan = 5; tr.append(td); projects.append(tr); }
        const tasks = document.getElementById('tasks');
        if (tasks) { tasks.replaceChildren(); data.tasks.forEach(t => row(tasks, [t.name + (t.deleted_at ? ' (đã ẩn)' : ''), t.actual.hours, amount(t.actual), t.estimate && t.estimate.estimate !== null ? `${t.estimate.estimate} ${t.estimate.currency}` : 'Chưa có ước tính'])); }
        const groups = document.getElementById('breakdowns'); groups.replaceChildren();
        Object.entries({ user: 'Nhân sự', department: 'Phòng ban', role: 'Role chính', date: 'Ngày' }).forEach(([key, label]) => Object.entries(data.groups[key]).forEach(([bucket, r]) => row(groups, [`${label} #${bucket}`, `${r.hours} / ${r.regular_hours} / ${r.ot_hours}`, amount(r)])));
        const pager = document.getElementById('pager'); pager.replaceChildren();
        [[data.page - 1, 'Trang trước'], [data.page + 1, 'Trang sau']].forEach(([page, label]) => {
            if (page < 1 || page > data.total_pages) return;
            const link = node('a', label); link.href = '?' + new URLSearchParams({ op: 'pmcosts', page, ...data.filters }); pager.append(link, node('span', ' '));
        });
        pager.append(node('span', `Trang ${data.page}/${data.total_pages}`));
        draw();
    }
    function schedule() { clearTimeout(timer); if (!document.hidden && !authorizationLost) timer = setTimeout(refresh, 60000); }
    async function refresh() {
        if (document.hidden || controller) return;
        controller = new AbortController();
        const active = controller, timeout = setTimeout(() => active.abort(), 15000);
        try {
            const response = await fetch('pm_ajax.php?' + new URLSearchParams({ op: 'pmcosts', page: data.page, ...data.filters }), { credentials: 'same-origin', cache: 'no-store', signal: active.signal });
            const next = await response.json();
            if(response.status===401||response.status===403){authorizationLost=true;throw new Error('Phiên hoặc quyền truy cập đã thay đổi. Vui lòng đăng nhập lại hoặc tải lại trang.');}
            if (!response.ok) throw new Error(next.error || 'Không thể tải chi phí.');
            data = next; render(); status.textContent = 'Đã cập nhật lúc ' + new Date().toLocaleTimeString('vi-VN') + ' · chu kỳ 60 giây';
        } catch (error) { if (!document.hidden) status.textContent = error.name === 'AbortError' ? 'Cập nhật quá thời gian; giữ số liệu trước đó.' : error.message + ' Giữ số liệu trước đó.'; }
        finally { clearTimeout(timeout); controller = null; schedule(); }
    }
    document.addEventListener('visibilitychange', () => { if (document.hidden) { clearTimeout(timer); if (controller) controller.abort(); } else schedule(); });
    draw();
    if (!window.Chart) status.textContent = 'Biểu đồ chưa tải được; số liệu vẫn có trong bảng.';
    schedule();
})();
