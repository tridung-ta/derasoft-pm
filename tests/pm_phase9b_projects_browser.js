async page => {
    const context=await page.context().browser().newContext({viewport:{width:1440,height:1000}});
    page=await context.newPage();
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.addInitScript(()=>{window.pmCspViolations=[];document.addEventListener('securitypolicyviolation',e=>window.pmCspViolations.push(e.violatedDirective));});
    const base='http://127.0.0.1:18767/';
    try{
        await page.goto(base+'.local/phase9b-project-list.html');
        if(await page.locator('.pm-project-card').count()!==6)throw new Error('Project cards missing');
        if(await page.locator('.pm-project-create').evaluate(e=>e.open))throw new Error('Create form not collapsed');
        await page.locator('.pm-project-create>summary').focus();await page.keyboard.press('Enter');
        if(!await page.getByRole('button',{name:'Lưu dự án',exact:true}).isVisible())throw new Error('Keyboard cannot expand create form');
        if(await page.locator('.pm-project-create input[name=csrf_token]').inputValue()!=='test')throw new Error('CSRF field lost');
        await page.locator('.pm-project-create>summary').focus();await page.keyboard.press('Space');
        if(await page.locator('.pm-project-create').evaluate(e=>e.open))throw new Error('Keyboard cannot collapse');
        const progress=await page.locator('progress').evaluateAll(nodes=>nodes.map(e=>({value:e.value,max:e.max,labels:e.labels.length})));
        if(JSON.stringify(progress.map(p=>p.value))!==JSON.stringify([50,0,100,33.3])||progress.some(p=>p.max!==100||p.labels!==1))throw new Error('Progress values/labels wrong');
        if(await page.locator('.pm-project-card').first().locator('progress').count())throw new Error('No-task project shows invented progress');
        if(await page.locator('.pm-project-card').last().locator('progress').count())throw new Error('Restricted progress exposed');
        if(await page.locator('.pm-project-card script').count())throw new Error('Name escaping failed');
        for(const [width,columns] of [[360,1],[390,1],[768,2],[1440,3]]){
            await page.setViewportSize({width,height:1000});
            const layout=await page.locator('.pm-project-grid').evaluate(e=>({columns:getComputedStyle(e).gridTemplateColumns.split(' ').length,overflow:document.documentElement.scrollWidth>innerWidth+1}));
            if(layout.columns!==columns||layout.overflow)throw new Error('Responsive grid '+width+': '+JSON.stringify(layout));
        }
        const colors=await page.locator('.pm-project-status').evaluateAll(nodes=>nodes.map(e=>({fg:getComputedStyle(e).color,bg:getComputedStyle(e).backgroundColor})));
        function luminance(rgb){return rgb.match(/[\d.]+/g).slice(0,3).map(Number).map(x=>{x/=255;return x<=.04045?x/12.92:((x+.055)/1.055)**2.4}).reduce((sum,x,i)=>sum+x*[.2126,.7152,.0722][i],0);}
        const contrasts=colors.map(c=>{const a=luminance(c.fg),b=luminance(c.bg);return (Math.max(a,b)+.05)/(Math.min(a,b)+.05);});
        if(contrasts.some(c=>c<4.5))throw new Error('Badge contrast '+contrasts);
        const numeric=await page.locator('.pm-project-card dd.num-cell').first().evaluate(e=>({font:getComputedStyle(e).fontFamily,variant:getComputedStyle(e).fontVariantNumeric,align:getComputedStyle(e).textAlign}));
        if(!numeric.font.startsWith('Inter')||numeric.variant!=='tabular-nums'||numeric.align!=='right')throw new Error('Numeric styles');
        const next=await page.getByRole('link',{name:'Trang sau'}).getAttribute('href');
        if(!next.includes('page=2')||!next.includes('q=%3Cscript%3Efilter%3C%2Fscript%3E'))throw new Error('Search pagination lost');
        for(const [name,width] of [['desktop',1440],['mobile',390]]){
            await page.setViewportSize({width,height:1000});await page.evaluate(()=>scrollTo(0,0));
            await page.screenshot({path:'phase9b-projects-'+name+'.png',fullPage:true});
        }
        await page.evaluate(()=>document.documentElement.style.fontSize='200%');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Text scaling overflow');
        await page.evaluate(()=>document.documentElement.style.fontSize='');
        // Intercept navigation to the synthetic detail page; no production or writes.
        const detail=await (await page.request.get(base+'.local/phase9-projects-1.html')).text();
        await page.route('**/admin.php?op=pmprojects&project_id=2',route=>route.fulfill({contentType:'text/html',body:detail}));
        await page.locator('.pm-project-card h3 a').nth(1).click();
        if(!page.url().includes('project_id=2')||!await page.getByRole('heading',{name:'Bảng công việc'}).isVisible())throw new Error('Project card navigation');
        await page.goto(base+'.local/phase9b-project-list-readonly.html');
        if(await page.locator('.pm-project-create').count()||await page.locator('form[method=post]').count())throw new Error('Read-only mutation UI');
        if(await page.evaluate(()=>window.pmCspViolations.length)||errors.length)throw new Error('CSP/JS: '+errors.join(';'));
        const fallback=await context.browser().newContext({javaScriptEnabled:false,viewport:{width:390,height:1000}});
        try{
            const p=await fallback.newPage();await p.goto(base+'.local/phase9b-project-list.html');
            await p.locator('.pm-project-create>summary').click();
            if(!await p.getByRole('button',{name:'Lưu dự án',exact:true}).isVisible()||await p.locator('progress').count()!==4)throw new Error('No-JS fallback');
        }finally{await fallback.close();}
        return {result:'PASS: grid, native progress, missing/restricted data, keyboard collapse, CSRF, escaped names, pagination/navigation, responsive/text scaling and no-JS.',contrasts};
    }finally{await context.close();}
}
