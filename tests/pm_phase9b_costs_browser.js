async page => {
    const context=await page.context().browser().newContext({viewport:{width:1440,height:1000}});
    page=await context.newPage();
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.addInitScript(()=>{window.pmCspViolations=[];document.addEventListener('securitypolicyviolation',e=>window.pmCspViolations.push(e.violatedDirective));});
    const base='http://127.0.0.1:18767/';
    try {
        await page.clock.install();
        const data=await (await page.request.get(base+'.local/phase6-preview.json')).json();
        let payload=structuredClone(data),status=200,polls=0;
        await page.route('**/pm_ajax.php?**',route=>{polls++;return route.fulfill({status,json:payload});});
        await page.goto(base+'.local/phase6-preview.html');
        await page.evaluate(()=>document.querySelector('.pm-skip').href=location.pathname+'#pm-main');
        await page.keyboard.press('Tab');
        if(!await page.locator('.pm-skip').evaluate(e=>e===document.activeElement))throw new Error('Skip order');
        await page.keyboard.press('Enter');
        if(!await page.locator('#pm-main').evaluate(e=>e===document.activeElement))throw new Error('Skip focus');
        const missingLabels=await page.evaluate(()=>[...document.querySelectorAll('input:not([type="hidden"]),select')].filter(e=>!e.labels?.length).map(e=>e.name));
        if(missingLabels.length)throw new Error('Missing labels: '+missingLabels.join(','));
        await page.waitForFunction(()=>!!window.Chart?.getChart('trend-chart'));
        const assert=async(value,message)=>{if(!await value)throw new Error(message);};
        await assert(page.locator('#cost-project-tab').getAttribute('aria-selected').then(v=>v==='true'),'Project tab selection');
        await assert(page.locator('#cost-group-panel').isHidden(),'Inactive group panel');
        await page.locator('#cost-project-tab').focus();await page.keyboard.press('ArrowRight');
        await assert(page.locator('#cost-group-tab').evaluate(e=>e===document.activeElement&&e.getAttribute('aria-selected')==='true'),'Arrow tab focus/selection');
        await assert(page.locator('#cost-group-panel').isVisible(),'Group table missing');
        await page.keyboard.press('Home');await assert(page.locator('#cost-project-tab').evaluate(e=>e===document.activeElement),'Home key');
        await page.keyboard.press('End');await assert(page.locator('#cost-group-tab').evaluate(e=>e===document.activeElement),'End key');
        for(const width of [360,390,768,1440]){
            await page.setViewportSize({width,height:1000});
            await assert(page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'Overflow '+width);
        }
        const empty=structuredClone(data);empty.projects=[];empty.tasks=[];empty.groups={user:{},department:{},role:{},date:{}};
        empty.summary={cost:'0.00',currency:'VND',hours:'0.00',regular_hours:'0.00',ot_hours:'0.00'};empty.estimate_total='0.00';
        payload=empty;await page.clock.fastForward(60001);
        await page.waitForFunction(()=>document.getElementById('trend-chart-empty').hidden===false);
        await assert(page.evaluate(()=>!Chart.getChart('trend-chart')&&!Chart.getChart('budget-chart')),'Obsolete charts not destroyed');
        await assert(page.locator('#trend-chart').isHidden(),'Empty axes visible');
        await assert(page.locator('#breakdowns').textContent().then(t=>t.includes('Chưa có chấm công')),'Empty groups message');
        await assert(page.locator('#cost-group-tab').getAttribute('aria-selected').then(v=>v==='true'),'Polling reset selected tab');
        for(const [name,width] of [['desktop',1440],['mobile',390]]){
            await page.setViewportSize({width,height:1000});await page.evaluate(()=>scrollTo(0,0));await page.screenshot({path:'phase9b-costs-empty-'+name+'.png',fullPage:true});
        }
        payload=structuredClone(data);payload.summary.cost=null;payload.summary.currency=null;payload.projects.forEach(p=>p.comparable=false);
        await page.clock.fastForward(60001);
        await page.waitForFunction(()=>document.getElementById('trend-chart-empty').textContent.includes('tiền tệ'));
        await assert(page.locator('#budget-chart-empty').textContent().then(t=>t.includes('đồng nhất')),'Incompatible explanation');
        // Legitimate zero samples still have charts, not an empty-state substitute.
        payload=structuredClone(data);payload.summary.cost='0.00';payload.groups.date['2026-10-01'].cost='0.00';
        payload.projects[0].budget='0.00';payload.projects[0].lifetime.cost='0.00';payload.projects[0].estimate.cost='0.00';
        await page.clock.fastForward(60001);
        await page.waitForFunction(()=>!!Chart.getChart('trend-chart'));
        await assert(page.evaluate(()=>Chart.getChart('trend-chart').data.datasets[0].data[0]===0&&!!Chart.getChart('budget-chart')),'Zero samples lost');
        await page.locator('#cost-project-tab').click();
        payload=structuredClone(data);await page.clock.fastForward(60001);
        await page.waitForFunction(()=>document.getElementById('actual-cost').textContent.includes('1102.75'));
        await assert(page.locator('#actual-cost').textContent().then(t=>t.includes('1102.75')),'Decimal display changed');
        for(const [name,width] of [['desktop',1440],['mobile',390]]){
            await page.setViewportSize({width,height:1000});await page.evaluate(()=>scrollTo(0,0));await page.screenshot({path:'phase9b-costs-data-'+name+'.png',fullPage:true});
        }
        await page.evaluate(()=>document.documentElement.style.fontSize='200%');
        await assert(page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'200% text scaling overflow');
        await page.evaluate(()=>document.documentElement.style.fontSize='');
        status=500;payload={error:'Thử lại'};await page.clock.fastForward(60001);
        await page.waitForFunction(()=>document.getElementById('refresh-status').textContent.includes('Thử lại'));
        await assert(page.locator('#actual-cost').textContent().then(t=>t.includes('1102.75')),'Error discarded prior data');
        status=403;payload={error:'Quyền thay đổi'};await page.clock.fastForward(60001);
        await page.waitForFunction(()=>document.getElementById('refresh-status').textContent.includes('Phiên hoặc quyền'));
        const count=polls;await page.clock.fastForward(120001);if(count!==polls)throw new Error('Polling continued after authorization loss');
        await assert(page.evaluate(()=>!window.pmCspViolations.length),'CSP violation');
        if(errors.length)throw new Error(errors.join('; '));
        const fallback=await context.browser().newContext({javaScriptEnabled:false,viewport:{width:390,height:1000}});
        try{
            const p=await fallback.newPage();await p.goto(base+'.local/phase9b-costs-empty.html');
            await assert(p.locator('#cost-project-panel').isVisible(),'No-JS projects');await assert(p.locator('#cost-group-panel').isVisible(),'No-JS groups');
            await assert(p.locator('#cost-tabs').isHidden(),'No-JS inert tabs');await assert(p.locator('#trend-chart').isHidden(),'No-JS axes');
        }finally{await fallback.close();}
        const missing=await context.newPage();await missing.route('**/chart.umd.js',route=>route.abort());await missing.goto(base+'.local/phase6-preview.html');
        await missing.waitForFunction(()=>document.getElementById('trend-chart-empty').textContent.includes('chưa tải được'));
        await assert(missing.locator('#cost-tabs').isVisible(),'Missing chart disables tabs');
        return 'PASS: real Chart.js empty/mixed/zero/recovery, decimal strings, tab keyboard/state, polling errors/auth, responsive/text scaling/CSP, no-JS and missing-chart fallbacks.';
    }finally{await context.close();}
}
