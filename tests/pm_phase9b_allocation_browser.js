async page => {
    const url='http://127.0.0.1:18767/.local/phase7-preview.html';
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.clock.install();
    const data=await (await page.request.get('http://127.0.0.1:18767/.local/phase7-preview.json')).json();
    let payload=structuredClone(data),polls=0;
    await page.route('**/pm_ajax.php?**',route=>{polls++;return route.fulfill({json:payload});});
    await page.goto(url);
    const missing=await page.evaluate(()=>[...document.querySelectorAll('input:not([type="hidden"]),select')].filter(e=>!e.labels?.length).map(e=>e.name));
    if(missing.length)throw new Error('Missing labels: '+missing.join(','));
    const table=page.locator('.pm-week-table');
    if(await table.locator('thead th').count()!==8)throw new Error('Seven-day header missing');
    if(!await table.textContent().then(t=>t.includes('03/01')&&t.includes('9.00 giờ')&&t.includes('41.00 / 40.00')))throw new Error('Year rollover or totals');
    if(await table.locator('.pm-week-over').count()!==2)throw new Error('Day/week overbooking missing');
    if(await table.locator('tbody td').first().textContent().then(t=>!t.includes('Chưa có chi tiết')))throw new Error('Hidden detail misrepresented as zero');
    for(const width of [360,390,768,1440]){
        await page.setViewportSize({width,height:1000});
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Page overflow '+width);
    }
    await page.locator('.pm-allocation-detail').evaluate(e=>e.open=false);
    await table.locator('a').first().click();
    if(!await page.locator('.pm-allocation-detail').evaluate(e=>e.open)||!await page.evaluate(()=>document.activeElement.id==='allocation-row-1'))throw new Error('Detail navigation/focus');
    if(await page.locator('form form').count())throw new Error('Nested forms');
    if(await page.locator('.pm-allocation-tools>details').count()!==3)throw new Error('Management panel missing');
    await page.locator('.pm-allocation-tools>details').nth(1).locator('summary').focus();await page.keyboard.press('Enter');
    if(!await page.locator('#allocation-suggestions').isVisible())throw new Error('Keyboard panel expansion');
    // Genuine polling rendering path with repeated allocations and another employee.
    const r=payload.rows[0];payload.rows.push({...r,id:2,hours:'1.00'},{...r,id:3,user_id:2,user_name:'<script>Nhân viên 2</script>',day_total:'2.00',week_total:'2.00'});
    await page.clock.fastForward(60001);
    await page.waitForFunction(()=>document.querySelectorAll('.pm-week-table tbody tr').length===2);
    if(polls!==1||!await table.textContent().then(t=>t.includes('Xem 2 phân bổ')&&t.includes('9.00 giờ')&&t.includes('<script>Nhân viên 2</script>')))throw new Error('Polling projection or filtered totals');
    if(await table.locator('script').count())throw new Error('Unsafe HTML');
    if(await page.locator('#allocation-rows button.danger').count()!==3)throw new Error('Polling destructive color regressed');
    for(const [name,width] of [['desktop',1440],['mobile',390]]){
        await page.setViewportSize({width,height:1000});await page.evaluate(()=>scrollTo(0,0));
        await page.screenshot({path:'phase9b-allocation-'+name+'.png',fullPage:true});
    }
    await page.evaluate(()=>document.documentElement.style.fontSize='200%');
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Text scaling overflow');
    payload.rows=[];await page.clock.fastForward(60001);
    await page.waitForFunction(()=>!document.querySelector('.pm-week-table'));
    if(!await page.locator('#allocation-week-view').textContent().then(t=>t.includes('Không có phân bổ')))throw new Error('Empty state');
    const context=await page.context().browser().newContext({javaScriptEnabled:false,viewport:{width:390,height:1000}});
    try{
        const fallback=await context.newPage();await fallback.goto(url);
        if(!await fallback.locator('#allocation-rows').isVisible()||!await fallback.locator('.pm-allocation-detail').evaluate(e=>e.open))throw new Error('No-JS table missing');
    }finally{await context.close();}
    if(errors.length)throw new Error(errors.join('; '));
    await page.unroute('**/pm_ajax.php?**');await page.clock.resume();
    return 'PASS: week rollover, server totals, overbooking, polling, escaping, keyboard, forms, responsive, text scaling, empty and no-JS fallback.';
}
