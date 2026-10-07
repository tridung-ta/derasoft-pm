async originalPage => {
    const browser=originalPage.context().browser();
    const context=await browser.newContext({viewport:{width:1440,height:1000}});
    const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
    const base='http://127.0.0.1:18767/';
    try{
        await page.clock.install();
        let payload;await page.route('**/pm_ajax.php?**',route=>route.fulfill({status:200,json:payload}));
        await page.goto(base+'.local/pm-costs-missing.html');
        payload=JSON.parse(await page.locator('#cost-data').textContent());
        await page.waitForFunction(()=>window.Chart&&document.getElementById('trend-chart-empty').textContent.includes('Chưa đủ đơn giá'));
        if(!await page.locator('#actual-cost').textContent().then(t=>t.includes('0.00 VND')&&t.includes('Chưa đủ đơn giá')))throw new Error('Initial missing zero not labeled');
        if(await page.evaluate(()=>!!Chart.getChart('trend-chart')||!!Chart.getChart('budget-chart')))throw new Error('Incomplete total charted as complete');
        await page.clock.fastForward(60001);
        await page.waitForFunction(()=>document.getElementById('refresh-status').textContent.includes('Đã cập nhật'));
        if(await page.locator('.pm-cost-incomplete').count()!==5)throw new Error('Polling dropped group/task/project/summary markers');
        for(const width of [1440,390]){
            await page.setViewportSize({width,height:1000});
            if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Missing-rate note overflow');
        }
        const complete=await (await page.request.get(base+'.local/phase6-preview.json')).json();
        payload=structuredClone(complete);payload.summary.cost='0.00';payload.groups.date['2026-10-01'].cost='0.00';
        await page.clock.fastForward(60001);
        await page.waitForFunction(()=>!!Chart.getChart('trend-chart'));
        if(await page.locator('#actual-cost .pm-cost-incomplete').count()||!await page.locator('#actual-cost').textContent().then(t=>t==='0.00 VND'))throw new Error('Configured zero labeled missing');
        if(!await page.evaluate(()=>Chart.getChart('trend-chart').data.datasets[0].data[0]===0&&!!Chart.getChart('budget-chart')))throw new Error('Legitimate zero chart removed');
        payload=JSON.parse(await (await page.request.get(base+'.local/pm-costs-missing.html')).text().then(html=>html.match(/<script id="cost-data" type="application\/json">([\s\S]*?)<\/script>/)[1]));
        await page.clock.fastForward(60001);
        await page.waitForFunction(()=>!Chart.getChart('trend-chart'));
        if(!await page.locator('#actual-cost .pm-cost-incomplete').isVisible())throw new Error('Complete to incomplete transition failed');
        if(errors.length)throw new Error(errors.join('; '));
        const noJs=await browser.newContext({javaScriptEnabled:false,viewport:{width:390,height:1000}});
        try{
            const fallback=await noJs.newPage();await fallback.goto(base+'.local/pm-costs-missing.html');
            if(!await fallback.locator('#actual-cost').textContent().then(t=>t.includes('Chưa đủ đơn giá'))||await fallback.locator('.pm-cost-incomplete').count()!==5)throw new Error('No-JS warning missing');
        }finally{await noJs.close();}
        return 'PASS: missing-rate saved zero labeled in initial/polling/no-JS, summary/project/task/group markers, incomplete charts hidden, valid zero charts retained, complete/incomplete transitions and desktop/mobile overflow; synthetic data only.';
    }finally{await context.close();}
}
