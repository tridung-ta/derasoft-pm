async page => {
    const context=await page.context().browser().newContext({viewport:{width:1440,height:1000}});
    const errors=[];page=await context.newPage();page.on('pageerror',e=>errors.push(e.message));
    const base='http://127.0.0.1:18767/.local/';
    try {
        await page.goto(base+'phase9-timesheets-1.html');
        if(await page.locator('.pm-time-week li').count()!==7)throw new Error('Seven days missing');
        if(await page.locator('.pm-time-ot').count()!==1||!await page.locator('.pm-time-today').textContent().then(t=>t.includes('Hôm nay')))throw new Error('Today/OT labels missing');
        if(await page.locator('.pm-time-settings details').evaluate(e=>e.open))throw new Error('Settings not collapsed');
        await page.locator('.pm-time-settings summary').focus();await page.keyboard.press('Enter');
        if(!await page.getByRole('button',{name:'Lưu quy tắc',exact:true}).isVisible())throw new Error('Keyboard expand failed');
        if(await page.locator('form input[name=csrf_token]').first().inputValue()!=='test')throw new Error('CSRF lost');
        await page.locator('.pm-time-settings summary').focus();await page.keyboard.press('Space');
        for(const width of [360,390,768,1440]){
            await page.setViewportSize({width,height:1000});
            if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Overflow '+width);
            if(await page.locator('.pm-time-week time').count()!==7)throw new Error('Hidden days '+width);
        }
        const styles=await page.locator('.pm-time-week strong').first().evaluate(e=>({font:getComputedStyle(e).fontFamily,variant:getComputedStyle(e).fontVariantNumeric,align:getComputedStyle(e).textAlign}));
        if(!styles.font.startsWith('Inter')||styles.variant!=='tabular-nums'||styles.align!=='right')throw new Error('Numeric typography');
        for(const [name,width] of [['desktop',1440],['mobile',390]]){await page.setViewportSize({width,height:1000});await page.evaluate(()=>scrollTo(0,0));await page.screenshot({path:'phase9b-timesheets-'+name+'.png',fullPage:true});}
        await page.evaluate(()=>document.documentElement.style.fontSize='200%');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Text scaling overflow');
        await page.goto(base+'phase9-timesheets-2.html');
        if(await page.locator('.pm-time-settings,form[method=post]').count())throw new Error('Employee read-only writes exposed');
        await page.goto(base+'phase9b-timesheets-unavailable.html');
        if(await page.locator('.pm-time-week').count()||!await page.getByRole('status').textContent().then(t=>t.includes('unavailable')))throw new Error('Unavailable shows fabricated totals');
        const noJs=await context.browser().newContext({javaScriptEnabled:false});
        try{const p=await noJs.newPage();await p.goto(base+'phase9-timesheets-1.html');await p.locator('.pm-time-settings summary').click();if(!await p.getByRole('button',{name:'Lưu quy tắc',exact:true}).isVisible())throw new Error('No-JS collapse failed');}finally{await noJs.close();}
        if(errors.length)throw new Error(errors.join(';'));
        return 'PASS: weekly ledger, keyboard/no-JS settings, Admin/employee visibility, unavailable state, CSRF, Inter tabular numerals, responsive and 200% text scaling.';
    } finally {await context.close();}
}
