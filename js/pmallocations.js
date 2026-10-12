/* Local-only polling; DOM text nodes keep user data out of HTML. */
(() => {
    'use strict';
    const failureText = error => error.name === 'AbortError' ? 'Hết thời gian chờ. Vui lòng thử lại.' : error.name === 'TypeError' ? 'Không thể kết nối máy chủ. Vui lòng kiểm tra kết nối mạng và thử lại.' : error.name === 'SyntaxError' ? 'Phản hồi từ máy chủ không hợp lệ. Vui lòng tải lại trang.' : error.message;

    const filter = document.getElementById('allocation-filter');
    if (!filter) return;
    const status = document.getElementById('allocation-poll-status');
    const task = document.getElementById('allocation-task');
    if (task) task.addEventListener('change', () => { document.getElementById('allocation-user').value = task.selectedOptions[0]?.dataset.user || ''; });
    let timer, controller, authorizationLost = false;
    const text = (tag, value) => { const el = document.createElement(tag); el.textContent = value; return el; };
    async function request(params, signal) {
        const response = await fetch('pm_ajax.php?' + params, {credentials:'same-origin', cache:'no-store', signal});
        const data = await response.json();
        if(response.status===401||response.status===403){authorizationLost=true;clearTimeout(timer);renderTaskEstimateWarnings([]);throw new Error('Phiên hoặc quyền truy cập đã thay đổi. Vui lòng đăng nhập lại hoặc tải lại trang.');}
        if (!response.ok) throw new Error(data.error || 'Không thể tải dữ liệu.');
        return data;
    }
    function renderTaskEstimateWarnings(warnings) {
        const panel=document.getElementById('task-estimate-warning-panel');
        const list=document.getElementById('task-estimate-warning-list');
        if(!panel||!list)return;
        const items=(Array.isArray(warnings)?warnings:[]).map(w=>{
            const item=document.createElement('li');
            item.append(text('strong',w.task_name),text('span',w.project_name+(w.historical?' · Đã ẩn / Lịch sử':'')),
                text('p','Thực tế '+w.actual_hours+' giờ · Ước tính '+w.estimated_hours+' giờ · Vượt '+w.excess_hours+' giờ'));
            return item;
        });
        list.replaceChildren(...items);panel.hidden=items.length===0;
    }
    function render(data) {
        renderTaskEstimateWarnings(data.task_estimate_warnings);

        const body = document.getElementById('allocation-rows');
        const fragment = document.createDocumentFragment();
        data.rows.forEach(row => {
            const tr = document.createElement('tr');
            tr.id='allocation-row-'+row.id;
            Object.assign(tr.dataset,{allocationId:String(row.id),userId:String(row.user_id),userName:row.user_name,date:row.work_date,dayTotal:row.day_total,dayLimit:row.daily_limit,weekTotal:row.week_total,weekLimit:row.weekly_limit});
            tr.append(text('td', row.work_date + ' / ' + row.user_name), text('td', row.project_name + ' / ' + row.task_name + (row.historical ? ' (lịch sử)' : '')),
                text('td', row.hours + ' / ' + (row.start_time && row.end_time ? row.start_time + ' – ' + row.end_time : 'Chưa có khoảng giờ')),
                text('td', row.day_total + ' / ' + row.daily_limit + ' ngày; ' + row.week_total + ' / ' + row.weekly_limit + ' tuần'));
            const cell = text('td', row.warnings.join(' '));
            if (data.can_manage) {
                const link = text('a', 'Sửa');
                const params = new URLSearchParams(new FormData(filter)); params.set('id', row.id);
                link.href = '?' + params; cell.append(document.createElement('br'), link);
                const form = document.createElement('form'); form.method = 'post';
                const token = document.querySelector('#allocation-editor input[name="csrf_token"]')?.value || '';
                Object.entries({op:'pmallocations', action:'delete', id:row.id, csrf_token:token}).forEach(([name,value]) => { const input=document.createElement('input');input.type='hidden';input.name=name;input.value=value;form.append(input); });
                const button = text('button', 'Ẩn phân bổ'); button.className='danger';form.append(button);cell.append(form);
            }
            tr.append(cell);fragment.append(tr);
        });
        if (!data.rows.length) {const tr=document.createElement('tr');const cell=text('td','Không có phân bổ trong tuần/phạm vi đã chọn.');cell.colSpan=5;tr.append(cell);fragment.append(tr);}
        body.replaceChildren(fragment);
        status.textContent = data.truncated ? 'Đã cập nhật; chỉ hiển thị 1000 dòng đầu. Hãy lọc thêm.' : 'Đã cập nhật lịch phân bổ; tự động làm mới mỗi 60 giây.';
    }
    async function poll() {
        if (document.hidden || authorizationLost) return;
        controller = new AbortController();
        const timeout = setTimeout(() => controller?.abort(), 15000);
        try {const params=new URLSearchParams(new FormData(filter));params.set('op','pmallocations');render(await request(params,controller.signal));}
        catch (e) {if (!document.hidden) status.textContent='Không thể cập nhật; giữ dữ liệu cũ. ' + failureText(e);}
        finally {clearTimeout(timeout);controller=null;if(!document.hidden&&!authorizationLost)timer=setTimeout(poll,60000);}
    }
    document.addEventListener('visibilitychange', () => {clearTimeout(timer);if(document.hidden)controller?.abort();else if(!controller&&!authorizationLost)timer=setTimeout(poll,60000);});
    if (!document.hidden) timer=setTimeout(poll,60000);
    const suggestions=document.getElementById('allocation-suggestions');
    if (suggestions) suggestions.addEventListener('submit', async event => {
        event.preventDefault();const list=document.getElementById('suggestion-results');list.replaceChildren(text('li','Đang kiểm tra khả năng nhận việc…'));
        const ctrl=new AbortController();const timeout=setTimeout(()=>ctrl.abort(),15000);
        try {const params=new URLSearchParams(new FormData(suggestions));params.set('op','pmallocations');params.set('action','suggestions');const data=await request(params,ctrl.signal);list.replaceChildren(...data.suggestions.map(u=>text('li',u.name+' · '+u.available_hours+' giờ trống'+(u.schedule_unknown?' · thiếu lịch chi tiết':''))));if(!data.suggestions.length)list.append(text('li','Không có nhân sự đủ khả năng nhận việc theo bộ lọc.'));}
        catch(e){list.replaceChildren(text('li',failureText(e)));}finally{clearTimeout(timeout);}
    });
})();
