/* Run from ignored .local with playwright-cli run-code --filename=../tests/pm_allocations_browser.js. */
async (page) => {
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.clock.install();
    const data=await (await page.request.get('http://127.0.0.1:18767/.local/phase7-preview.json')).json();
    let calls=0,fail=false,suggestionCalls=0;
    await page.route('**/pm_ajax.php?**',async route=>{
        const url=new URL(route.request().url());
        if(url.searchParams.get('action')==='suggestions'){suggestionCalls++;await route.fulfill({contentType:'application/json',body:JSON.stringify({suggestions:[{name:'<img src=x onerror=alert(1)>',available_hours:'3.00',schedule_unknown:true}]})});return;}
        calls++;if(url.searchParams.get('week')!=='2026-12-28'||url.searchParams.get('op')!=='pmallocations')throw new Error('Lost week filter.');
        await route.fulfill({status:fail?403:200,contentType:'application/json',body:JSON.stringify(fail?{error:'Không có quyền.'}:data)});
    });
    await page.goto('http://127.0.0.1:18767/.local/phase7-preview.html');
    await page.setViewportSize({width:1440,height:1000});
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw new Error('Desktop overflow.');
    await page.screenshot({path:'phase7-desktop.png',fullPage:true});
    await page.setViewportSize({width:390,height:844});
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw new Error('Mobile overflow.');
    await page.screenshot({path:'phase7-mobile.png',fullPage:true});
    await page.locator('#allocation-task').selectOption('1');
    if(await page.locator('#allocation-user').inputValue()!=='1')throw new Error('Task assignee form broken.');
    await page.locator('#allocation-suggestions button').click();
    await page.waitForFunction(()=>document.getElementById('suggestion-results').textContent.includes('3.00'));
    if(suggestionCalls!==1||await page.locator('img[src=x]').count())throw new Error('Suggestions XSS or request failed.');
    data.rows[0].project_name='<img src=x onerror=alert(1)>';
    await page.clock.fastForward(60000);await page.waitForFunction(()=>document.getElementById('allocation-poll-status').textContent.includes('Đã cập nhật'));
    if(calls!==1||await page.locator('img[src=x]').count())throw new Error('Polling/XSS failed.');
    const post=page.locator('#allocation-rows form');
    if(await post.locator('input[name="op"]').inputValue()!=='pmallocations'||await post.locator('input[name="csrf_token"]').inputValue()!=='test-token')throw new Error('Refreshed mutation route/CSRF fields missing.');
    fail=true;await page.clock.fastForward(60000);await page.waitForFunction(()=>document.getElementById('allocation-poll-status').textContent.includes('giữ dữ liệu cũ'));
    if(calls!==2||await page.locator('#allocation-rows tr').count()!==1)throw new Error('Failed poll removed data.');
    await page.evaluate(()=>{Object.defineProperty(document,'hidden',{configurable:true,get:()=>true});document.dispatchEvent(new Event('visibilitychange'));});
    await page.clock.fastForward(120000);if(calls!==2)throw new Error('Hidden tab polled.');
    await page.evaluate(()=>{Object.defineProperty(document,'hidden',{configurable:true,get:()=>false});document.dispatchEvent(new Event('visibilitychange'));});
    await page.clock.fastForward(60000);if(calls!==2||!await page.locator('#allocation-poll-status').textContent().then(t=>t.includes('đăng nhập lại')))throw new Error('Expired authorization kept polling.');
    fail=false;data.rows=[];await page.reload();await page.clock.fastForward(60000);await page.waitForFunction(()=>document.getElementById('allocation-rows').textContent.includes('Không có phân bổ'));
    if(errors.length)throw new Error(errors.join('; '));
    return 'PASS group 5 browser: desktop/mobile, task assignee, safe suggestions, 60s polling, filters, hidden stop/resume, CSRF/route after refresh, XSS, retained errors and empty state.';
}
