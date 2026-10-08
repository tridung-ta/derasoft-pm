/* Also runnable with playwright-cli run-code --filename=tests/pm_task_estimate_browser.js. */
async page => {
    const errors=[];page.on('pageerror',e=>errors.push(e.message));await page.clock.install();
    const url='http://127.0.0.1:18767/.local/phase7-preview.html';
    const data=await (await page.request.get('http://127.0.0.1:18767/.local/phase7-preview.json')).json();
    let payload=structuredClone(data),code=200,calls=0;
    await page.route('**/pm_ajax.php?**',route=>{calls++;return route.fulfill({status:code,json:code===200?payload:{error:'Fixture error'}});});
    await page.goto(url);await page.evaluate(()=>document.fonts.ready);
    const panel=page.locator('#task-estimate-warning-panel');
    if(!await panel.isVisible()||await panel.locator('li').count()!==1)throw new Error('Initial task warning missing.');
    const initial=await panel.textContent();
    for(const label of ['Task đang được phân bổ trong tuần này','Thực tế 5.00 giờ','Ước tính 4.00 giờ','Vượt 1.00 giờ','Đã ẩn / Lịch sử'])if(!initial.includes(label))throw new Error('Missing textual warning '+label);
    if(await panel.locator('script,img').count())throw new Error('Server warning HTML not escaped.');
    const visual=[];
    for(const [name,width] of [['desktop',1440],['mobile',390]]){
        await page.setViewportSize({width,height:1000});
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Overflow '+name);
        visual.push(await panel.evaluate(e=>{const cs=getComputedStyle(e);return {width:innerWidth,border:cs.borderTopWidth,borderColor:cs.borderTopColor,background:cs.backgroundColor,color:cs.color,font:cs.fontFamily};}));
        await page.screenshot({path:'.local/d2-'+name+'.png',fullPage:true});
    }
    await page.evaluate(()=>document.documentElement.style.fontSize='200%');
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Text scaling overflow.');
    await page.evaluate(()=>document.documentElement.style.fontSize='');
    payload.task_estimate_warnings[0].task_name='<img src=x onerror=alert(1)> task';payload.task_estimate_warnings[0].actual_hours='6.00';payload.task_estimate_warnings[0].excess_hours='2.00';
    await page.clock.fastForward(60001);await page.waitForFunction(()=>document.getElementById('task-estimate-warning-list').textContent.includes('6.00'));
    if(await panel.locator('img').count()||!await panel.textContent().then(t=>t.includes('<img src=x onerror=alert(1)> task')))throw new Error('Polling warning unsafe.');
    code=500;await page.clock.fastForward(60001);await page.waitForFunction(()=>document.getElementById('allocation-poll-status').textContent.includes('giữ dữ liệu cũ'));
    if(!await panel.isVisible())throw new Error('Transient error removed valid warning.');
    code=403;await page.clock.fastForward(60001);await page.waitForFunction(()=>document.getElementById('allocation-poll-status').textContent.includes('đăng nhập lại'));
    if(await panel.isVisible()||await panel.locator('li').count())throw new Error('Authorization loss retained task totals.');
    const stopped=calls;await page.clock.fastForward(120001);if(calls!==stopped)throw new Error('Polling continued after authorization loss.');
    code=200;payload.task_estimate_warnings=[];await page.reload();await page.clock.fastForward(60001);await page.waitForFunction(()=>document.getElementById('task-estimate-warning-panel').hidden);
    if(await panel.locator('li').count())throw new Error('Empty warnings retained stale values.');
    delete payload.task_estimate_warnings;await page.clock.fastForward(60001);if(await panel.isVisible()||await panel.locator('li').count())throw new Error('Missing non-manager field retained task totals.');
    const context=await page.context().browser().newContext({javaScriptEnabled:false,viewport:{width:390,height:1000}});
    try{const fallback=await context.newPage();await fallback.goto(url);if(!await fallback.locator('#task-estimate-warning-panel').isVisible()||!await fallback.locator('#task-estimate-warning-panel').textContent().then(t=>t.includes('Vượt 1.00 giờ')))throw new Error('No-JS warning missing.');}finally{await context.close();}
    if(errors.length)throw new Error(errors.join('; '));await page.unroute('**/pm_ajax.php?**');await page.clock.resume();
    return {result:'PASS: task warning text/scope/history, escaped SSR/polling, empty/missing-field/error/auth-loss, no-JS, desktop/mobile and text scaling.',visual};
}
