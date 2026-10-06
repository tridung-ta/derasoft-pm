async page => {
    const context=await page.context().browser().newContext({viewport:{width:1440,height:1000}});
    page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
    const base='http://127.0.0.1:18767/.local/';
    function luminance(rgb){return rgb.match(/[\d.]+/g).slice(0,3).map(Number).map(x=>{x/=255;return x<=.04045?x/12.92:((x+.055)/1.055)**2.4}).reduce((s,x,i)=>s+x*[.2126,.7152,.0722][i],0);}
    try {
        await page.goto(base+'phase9-dashboard.html');
        if(await page.locator('.pm-overview-kpis .metric').count()!==3||await page.locator('.pm-overview-shortcuts a').count()!==4)throw new Error('Overview metrics/shortcuts');
        await page.goto(base+'phase9b-dashboard-restricted.html');
        if(await page.locator('.pm-overview-shortcuts a').count()!==1||await page.locator('.pm-overview-kpis').textContent().then(t=>t.includes('987654321')))throw new Error('Restricted overview');
        await page.goto(base+'phase9-audit-1.html');
        const detail=page.locator('.pm-audit-detail').first();await detail.locator('summary').focus();await page.keyboard.press('Enter');
        if(!await detail.locator('pre').last().isVisible()||await page.locator('main script').count())throw new Error('Inline audit escaping/keyboard');
        const timestamp=await page.locator('.pm-audit-timestamp').first().evaluate(e=>({font:getComputedStyle(e).fontFamily,variant:getComputedStyle(e).fontVariantNumeric}));
        if(!timestamp.font.startsWith('Inter')||timestamp.variant!=='tabular-nums')throw new Error('Timestamp typography');
        const colors=await page.locator('.pm-audit-action').evaluateAll(nodes=>nodes.map(e=>({fg:getComputedStyle(e).color,bg:getComputedStyle(e).backgroundColor})));
        const contrasts=colors.map(c=>{const a=luminance(c.fg),b=luminance(c.bg);return(Math.max(a,b)+.05)/(Math.min(a,b)+.05);});if(contrasts.some(c=>c<4.5))throw new Error('Action badge contrast');
        await page.goto(base+'phase9b-reports-hours.html');
        if(await page.locator('.pm-report-header button').textContent()!=='Xuất XLSX'||await page.locator('.pm-report-header input[name=csrf_token]').inputValue()!=='test'||await page.locator('.pm-report-header input[name=mode]').inputValue()!=='hours')throw new Error('Export header/filters/CSRF');
        const workbook=await (await page.request.get(base+'phase8-preview.xlsx')).body();
        await page.route('**/.local/phase9b-reports-hours.html',async route=>{
            if(route.request().method()!=='POST'){await route.continue();return;}
            const params=new URLSearchParams(route.request().postData());
            if(params.get('op')!=='pmreports'||params.get('action')!=='export'||params.get('csrf_token')!=='test'||params.get('mode')!=='hours'||params.get('from')!=='2026-10-01'||params.get('to')!=='2026-10-02')throw new Error('Header export submitted wrong filters');
            await route.fulfill({contentType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',headers:{'Content-Disposition':'attachment; filename="synthetic-report.xlsx"'},body:workbook});
        });
        const downloadPromise=page.waitForEvent('download');await page.locator('.pm-report-header button').click();const download=await downloadPromise;
        if(download.suggestedFilename()!=='synthetic-report.xlsx'||await download.failure())throw new Error('Header export download failed');
        const numeric=await page.locator('.pm-report-summary dd').first().evaluate(e=>({font:getComputedStyle(e).fontFamily,variant:getComputedStyle(e).fontVariantNumeric,align:getComputedStyle(e).textAlign}));
        if(!numeric.font.startsWith('Inter')||numeric.variant!=='tabular-nums'||numeric.align!=='right')throw new Error('Summary numerals');
        await page.goto(base+'phase9b-reports-restricted.html');if(await page.locator('button').filter({hasText:'Xuất XLSX'}).count())throw new Error('Export restriction');
        await page.goto(base+'phase9b-reports-error.html');if(!await page.getByRole('alert').isVisible()||await page.locator('.pm-report-header form').count())throw new Error('Report error state');
        for(const file of ['phase9-dashboard.html','phase9-audit-1.html','phase9b-reports-hours.html','phase9b-reports-costs.html']){
            await page.goto(base+file);
            for(const width of [360,390,768,1440]){await page.setViewportSize({width,height:1000});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error(file+' overflow '+width);}
            if(file.includes('reports-costs')){const budget=await page.locator('.pm-money-table td').nth(1).textContent();if(budget!=='1234567890123.45')throw new Error('Decimal changed');}
            for(const [name,width] of [['desktop',1440],['mobile',390]]){await page.setViewportSize({width,height:1000});await page.evaluate(()=>scrollTo(0,0));await page.screenshot({path:'phase9b-final-'+file.replace('.html','')+'-'+name+'.png',fullPage:true});}
            await page.setViewportSize({width:720,height:1000});await page.evaluate(()=>document.documentElement.style.fontSize='200%');if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error(file+' scaling overflow');await page.evaluate(()=>document.documentElement.style.fontSize='');
        }
        const fallback=await context.browser().newContext({javaScriptEnabled:false});
        try{const p=await fallback.newPage();await p.goto(base+'phase9-audit-1.html');await p.locator('.pm-audit-detail summary').first().click();if(!await p.locator('.pm-audit-detail pre').first().isVisible())throw new Error('No-JS audit details');}finally{await fallback.close();}
        if(errors.length)throw new Error(errors.join(';'));
        return {result:'PASS: overview/restricted shortcuts, inline audit keyboard/no-JS, action contrast, export/filter/CSRF gates, exact cost strings, Inter/tabular summary, responsive and 200% text scaling. No business POST.',contrasts};
    } finally {await context.close();}
}
