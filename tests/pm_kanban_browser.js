async page => {
    const browser=page.context().browser();
    const context=await browser.newContext({viewport:{width:1440,height:1000}});
    page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
    const base='http://127.0.0.1:18767/';const url=base+'.local/phase9-projects-1.html';
    const form=()=>page.locator('[data-task-status-form]');
    const card=()=>page.locator('[data-task-id="1"]');
    const select=()=>form().locator('select[name=status]');
    const message=()=>form().locator('[data-task-status-message]');
    let calls=0;let mode='success';let release=null;const payloads=[];
    await page.route('**/pm_ajax.php?op=pmtaskstatus',async route=>{
        calls++;const request=route.request();payloads.push({method:request.method(),body:request.postData()});
        if(mode==='hold')await new Promise(resolve=>{release=resolve;});
        if(mode==='network'){await route.abort('failed');return;}
        if(mode==='auth'){await route.fulfill({status:401,contentType:'application/json',body:JSON.stringify({error:'Phiên hết hạn'})});return;}
        if(mode==='server'){await route.fulfill({status:500,contentType:'application/json',body:JSON.stringify({error:'Không thể cập nhật <script>unsafe</script>'})});return;}
        if(mode==='invalid'){await route.fulfill({contentType:'application/json',body:JSON.stringify({task:{id:999,status:'done'}})});return;}
        await route.fulfill({contentType:'application/json',body:JSON.stringify({task:{id:1,status:'done',completed_at:'2026-10-07 09:00:00'}})});
    });
    async function ready(){await page.goto(url);await page.locator('script[src="js/pmkanban.js"]').waitFor({state:'attached'});}
    async function settle(){await card().locator('[aria-busy]').count();await page.waitForFunction(()=>!document.querySelector('[data-task-id="1"]').hasAttribute('aria-busy'));}
    try{
        await ready();if(await page.locator('[data-task-column=todo] .pm-column-count').textContent()!=='1'||!await page.locator('[data-task-column=done] .pm-column-empty').isVisible())throw new Error('Initial column count/empty state');mode='hold';await select().selectOption('done');
        await form().getByRole('button',{name:'Cập nhật trạng thái'}).focus();await page.keyboard.press('Enter');
        await page.waitForFunction(()=>document.querySelector('[data-task-id="1"]').getAttribute('aria-busy')==='true');
        if(!await form().getByRole('button').isDisabled()||!await select().isDisabled())throw new Error('Pending controls not disabled');
        if(await card().evaluate(e=>e.parentElement.dataset.taskColumn)!=='todo')throw new Error('Optimistic move before success');
        await form().evaluate(e=>{e.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));});
        while(!release)await new Promise(resolve=>setTimeout(resolve,20));release();await settle();
        if(await page.locator('[data-task-column=todo] .pm-column-count').textContent()!=='0'||await page.locator('[data-task-column=done] .pm-column-count').textContent()!=='1'||!await page.locator('[data-task-column=todo] .pm-column-empty').isVisible())throw new Error('Confirmed move did not refresh counts');
        if(calls!==1||await card().evaluate(e=>e.parentElement.dataset.taskColumn)!=='done')throw new Error('Duplicate submission or missing move');
        if(await select().inputValue()!=='done'||await card().locator('details select[name=status]').inputValue()!=='done')throw new Error('Existing edit form status stale');
        if(!await select().evaluate(e=>document.activeElement===e)||!await message().textContent().then(t=>t.includes('Đã chuyển')))throw new Error('Success focus/announcement');
        if(payloads[0].method!=='POST'||!payloads[0].body.includes('name="csrf_token"')||!payloads[0].body.includes('test'))throw new Error('CSRF/form payload lost');
        await ready(); mode='hold'; const beforeDrag=calls;
        await card().locator('.pm-task-drag').dragTo(page.locator('[data-task-column="done"]'));
        await page.waitForFunction(()=>document.querySelector('[data-task-id="1"]').hasAttribute('aria-busy'));
        if(await card().evaluate(e=>e.parentElement.dataset.taskColumn)!=='todo')throw new Error('Drag moved before server confirmation');
        while(!release)await new Promise(resolve=>setTimeout(resolve,20));release();await settle();
        if(calls!==beforeDrag+1||await card().evaluate(e=>e.parentElement.dataset.taskColumn)!=='done')throw new Error('Drag did not use existing AJAX');
        const filter=page.locator('.pm-kanban-toolbar select');
        const names=await filter.locator('option').evaluateAll(options=>options.map(o=>o.value).filter(Boolean));
        if(names.length){await filter.selectOption(names[0]); if(await page.locator('[data-task-id]').evaluateAll(cards=>cards.some(c=>!c.hidden&&(c.dataset.taskAssigneeId||'unassigned')!==document.querySelector('.pm-kanban-toolbar select').value)))throw new Error('Assignee filter leak');await filter.selectOption('');}
        const originalHtml=await (await page.request.get(url)).text();
        const duplicateHtml=await page.evaluate(html=>{const doc=new DOMParser().parseFromString(html,'text/html');const first=doc.querySelector('[data-task-id="1"]');const copy=first.cloneNode(true);copy.dataset.taskId='2';copy.dataset.taskAssigneeId='2';copy.querySelectorAll('input[name=task_id]').forEach(input=>input.value='2');copy.querySelectorAll('[id]').forEach(node=>node.id=node.id.replace('status-1','status-2'));copy.querySelectorAll('label[for]').forEach(node=>node.htmlFor=node.htmlFor.replace('status-1','status-2'));first.after(copy);return '<!doctype html>'+doc.documentElement.outerHTML;},originalHtml);
        await page.route('**/duplicate-kanban.html',route=>route.fulfill({contentType:'text/html',body:duplicateHtml}));await page.goto(base+'.local/duplicate-kanban.html');
        const duplicateFilter=page.locator('.pm-kanban-toolbar select');
        if(await duplicateFilter.locator('option').count()!==3)throw new Error('Same-name identities merged');
        await duplicateFilter.selectOption('1');if(await page.locator('[data-task-column=todo] .pm-column-count').textContent()!=='1'||!(await page.locator('.pm-board-result').textContent()).includes('1 công việc'))throw new Error('Filtered count stale');if(!await page.locator('[data-task-id="1"]').isVisible()||await page.locator('[data-task-id="2"]').isVisible())throw new Error('Same-name assignee filter incorrect');
        const dueHtml=await page.evaluate(html=>{const doc=new DOMParser().parseFromString(html,'text/html');const card=doc.querySelector('[data-task-id="1"]');card.dataset.taskDue='2000-01-01';const done=card.cloneNode(true);done.dataset.taskId='2';done.dataset.taskStatus='done';doc.querySelector('[data-task-column="done"]').append(done);const future=card.cloneNode(true);future.dataset.taskId='3';future.dataset.taskDue='2999-01-01';card.after(future);return '<!doctype html>'+doc.documentElement.outerHTML;},originalHtml);
        await page.route('**/due-kanban.html',route=>route.fulfill({contentType:'text/html',body:dueHtml}));await page.goto(base+'.local/due-kanban.html');
        if(await page.locator('[data-task-id="1"] [data-task-overdue]').count()!==1||await page.locator('[data-task-id="2"] [data-task-overdue],[data-task-id="3"] [data-task-overdue]').count())throw new Error('Overdue badge absent or shown for completed/future task');
        await page.goto(base+'.local/phase9-projects-2.html'); if(await page.locator('.pm-task-drag').count())throw new Error('Read-only drag handle');
        for(const failure of ['network','auth','server','invalid']){
            await ready();mode=failure;await select().selectOption('done');await form().getByRole('button').click();await settle();
            if(await card().evaluate(e=>e.parentElement.dataset.taskColumn)!=='todo'||await select().inputValue()!=='todo'||await form().getByRole('button').isDisabled())throw new Error('Failure rollback/enable '+failure);
            if(!await message().textContent().then(t=>t.includes('tải lại')))throw new Error('Missing uncertain-result guidance');
            if(failure==='auth' && !(await message().textContent()).includes('dữ liệu giả'))throw new Error('Preview authentication guidance missing');
            if(await form().locator('[data-task-status-message] script').count())throw new Error('Error inserted HTML');
        }
        await ready();await page.clock.install();
        await page.evaluate(()=>{window.fetch=(url,options)=>new Promise((resolve,reject)=>options.signal.addEventListener('abort',()=>reject(new Error('Timeout'))));});
        await select().selectOption('done');await form().getByRole('button').click();await page.clock.fastForward(21000);await settle();
        if(await select().isDisabled()||await select().inputValue()!=='todo')throw new Error('Timeout leaves form locked');
        await page.clock.resume();
        for(const width of [360,390,768,1440]){
            await page.setViewportSize({width,height:1000});
            if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Page overflow '+width);
            const metrics=await form().getByRole('button').evaluate(e=>({height:e.getBoundingClientRect().height,width:e.getBoundingClientRect().width}));
            if(metrics.height<44||metrics.width<44)throw new Error('Small target');
        }
        await page.setViewportSize({width:390,height:1000});await page.evaluate(()=>document.documentElement.style.fontSize='200%');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('200% text overflow');
        await page.evaluate(()=>document.documentElement.style.fontSize='');
        for(const [name,width] of [['desktop',1440],['mobile',390]]){
            await page.setViewportSize({width,height:1000});await card().scrollIntoViewIfNeeded();await page.screenshot({path:'kanban-'+name+'.png',fullPage:true});
        }
        await page.goto(base+'.local/phase9-projects-2.html');if(await form().count())throw new Error('Read-only status mutation UI');
        const fallback=await browser.newContext({javaScriptEnabled:false,viewport:{width:390,height:1000}});
        try{
            const p=await fallback.newPage();const html=await (await p.request.get(url)).text();let nativePost=null;
            await p.route(url,async route=>{if(route.request().method()==='POST'){nativePost=route.request().postData();await route.fulfill({contentType:'text/html',body:html});}else await route.continue();});
            await p.goto(url);const f=p.locator('[data-task-status-form]');await f.locator('select').selectOption('review');await f.getByRole('button').click();
            await p.waitForLoadState('load');
            if(!nativePost||!nativePost.includes('action=task_status')||!nativePost.includes('status=review')||!nativePost.includes('csrf_token=test'))throw new Error('Native fallback payload lost');
        }finally{await fallback.close();}
        if(errors.length)throw new Error('JS errors: '+errors.join(';'));
        return {result:'PASS: drag uses AJAX without optimistic move, assignee identity filter including duplicate names; overdue excludes done/future; read-only drag guard; AJAX success/pending/duplicate, existing edit sync, keyboard focus/live status, network/auth/server/invalid/timeout recovery, 4 viewports/200% text, escaped error, read-only and no-JS native POST payload. HTTP backend tested separately.'};
    }finally{await context.close();}
}
