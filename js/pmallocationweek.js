/* Presentation only: project the permission-filtered detail rows into a week. */
(() => {
    'use strict';
    const source=document.getElementById('allocation-rows');
    const host=document.getElementById('allocation-week-view');
    const start=document.querySelector('#allocation-filter input[name="week"]');
    if(!source || !host || !start) return;
    const text=(tag,value)=>{const node=document.createElement(tag);node.textContent=value;return node;};
    const first=new Date(start.value+'T00:00:00Z');
    if(Number.isNaN(first.getTime())) return;
    const days=Array.from({length:7},(_,i)=>{const date=new Date(first);date.setUTCDate(date.getUTCDate()+i);return date.toISOString().slice(0,10);});
    const labels=['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7','CN'];
    function render(){
        const people=new Map();
        source.querySelectorAll('tr[data-allocation-id]').forEach(row=>{
            const d=row.dataset;
            if(!people.has(d.userId)) people.set(d.userId,{name:d.userName,weekTotal:d.weekTotal,weekLimit:d.weekLimit,days:new Map()});
            const person=people.get(d.userId);
            if(!person.days.has(d.date)) person.days.set(d.date,[]);
            person.days.get(d.date).push(row);
        });
        const fragment=document.createDocumentFragment();
        fragment.append(text('h2','Lịch phân bổ trong tuần'));
        fragment.append(text('p','Ô hiển thị tổng tải trong đơn vị. Ngày không có chi tiết trong phạm vi xem được ghi “Chưa có chi tiết”, không mặc định là 0 giờ.'));
        if(!people.size){fragment.append(text('p','Không có phân bổ trong tuần/phạm vi đã chọn.'));}
        else {
            const hint=text('p','Cuộn ngang hoặc dùng phím mũi tên trong bảng để xem đủ 7 ngày.');hint.className='pm-table-hint';fragment.append(hint);
            const region=document.createElement('div');region.className='pm-table-region';region.tabIndex=0;region.setAttribute('role','region');region.setAttribute('aria-label','Lưới phân bổ tuần; cuộn ngang để xem đủ ngày');
            const table=document.createElement('table');table.className='pm-week-table';
            const caption=text('caption','Tuần '+days[0]+' — '+days[6]);table.append(caption);
            const head=document.createElement('thead'),headers=document.createElement('tr');
            const name=text('th','Nhân viên / Tổng tuần');name.scope='col';headers.append(name);
            days.forEach((day,i)=>{const th=text('th',labels[i]);th.scope='col';th.append(text('small',day.slice(8,10)+'/'+day.slice(5,7)));headers.append(th);});head.append(headers);table.append(head);
            const body=document.createElement('tbody');
            people.forEach(person=>{
                const tr=document.createElement('tr'),title=text('th',person.name);title.scope='row';
                title.append(text('small',person.weekTotal+' / '+person.weekLimit+' giờ/tuần'));
                if(Number(person.weekTotal)>Number(person.weekLimit)){title.className='pm-week-over';title.append(text('small','Vượt ngưỡng tuần'));}
                tr.append(title);
                days.forEach(day=>{
                    const cell=document.createElement('td');const rows=person.days.get(day);
                    if(!rows){cell.append(text('small','Chưa có chi tiết'));}
                    else {
                        const d=rows[0].dataset;
                        const hours=text('strong',d.dayTotal+' giờ');hours.className='num-cell';cell.append(hours,text('small','Ngưỡng '+d.dayLimit+' giờ/ngày'));
                        if(Number(d.dayTotal)>Number(d.dayLimit)){cell.className='pm-week-over';cell.append(text('small','Vượt ngưỡng ngày'));}
                        const link=text('a','Xem '+rows.length+' phân bổ');link.href=location.pathname+location.search+'#'+rows[0].id;
                        link.addEventListener('click',()=>{const detail=source.closest('details');if(detail)detail.open=true;rows[0].tabIndex=-1;rows[0].focus();});cell.append(link);
                    }
                    tr.append(cell);
                });body.append(tr);
            });table.append(body);region.append(table);fragment.append(region);
        }
        host.replaceChildren(fragment);host.hidden=false;
    }
    render();
    const detail=source.closest('details');if(detail)detail.open=false;
    new MutationObserver(render).observe(source,{childList:true});
})();
