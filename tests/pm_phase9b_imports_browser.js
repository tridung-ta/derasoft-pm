async page => {
    const context=await page.context().browser().newContext({viewport:{width:1440,height:1000}});
    page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.addInitScript(()=>{window.pmCspViolations=[];document.addEventListener('securitypolicyviolation',e=>window.pmCspViolations.push(e.violatedDirective));});
    const base='http://127.0.0.1:18767/.local/';
    try {
        await page.goto(base+'phase9b-import-initial.html');
        if(await page.locator('.pm-import-steps li').count()!==3)throw new Error('Three steps missing');
        if(await page.locator('.pm-import-history details').evaluate(e=>e.open))throw new Error('History not collapsed');
        const file=page.locator('input[name=workbook]');
        if(await file.getAttribute('accept')!=='.xlsx'||!await file.evaluate(e=>e.required&&e.labels.length===1)||await file.locator('..').textContent().then(t=>!t.includes('Workbook')))throw new Error('Native upload/label changed');
        if(await page.locator('#pm-import-upload form').getAttribute('enctype')!=='multipart/form-data')throw new Error('Upload encoding lost');
        // Synthetic previews use base href=/ for assets; normalize only fixture anchors.
        await page.evaluate(()=>document.querySelectorAll('.pm-import-steps a').forEach(a=>a.href=location.pathname+a.getAttribute('href')));
        await page.locator('.pm-import-steps a').nth(2).click();
        await page.locator('#pm-import-history').focus();await page.keyboard.press('Enter');
        if(!await page.getByText('Chưa có staging.',{exact:true}).isVisible())throw new Error('Keyboard history failed');
        await page.locator('#pm-import-history').focus();await page.keyboard.press('Space');
        for(const width of [360,390,768,1440]){
            await page.setViewportSize({width,height:1000});
            if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Initial overflow '+width);
            if(await page.locator('.pm-import-steps a').count()!==3)throw new Error('Hidden steps');
        }
        await page.goto(base+'phase9b-import-validation.html');
        if(!await page.getByRole('alert').isVisible()||await page.locator('main script,main img').count())throw new Error('Validation errors/escaping');
        await page.goto(base+'phase9b-import-apply.html');
        if(!await page.getByRole('heading',{name:'Kết quả Apply #102',exact:true}).isVisible()||!await page.locator('input[name=password]').isVisible())throw new Error('Apply result hidden in collapse');
        if(await page.locator('input[name=password]').count()!==1)throw new Error('Credential gate changed');
        await page.locator('#pm-import-history').click();
        for(const [action,count] of [['apply',1],['resume',2],['detail',4]])if(await page.locator('.pm-import-history button[value='+action+']').count()!==count)throw new Error('History actions '+action);
        if(await page.locator('input[name=csrf_token]').evaluateAll(nodes=>nodes.some(e=>e.value!=='test-token')))throw new Error('CSRF changed');
        for(const width of [360,390,768,1440]){await page.setViewportSize({width,height:1000});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Apply overflow '+width);}
        for(const [name,width] of [['desktop',1440],['mobile',390]]){await page.setViewportSize({width,height:1000});await page.evaluate(()=>scrollTo(0,0));await page.screenshot({path:'phase9b-import-'+name+'.png',fullPage:true});}
        await page.setViewportSize({width:720,height:1000});await page.evaluate(()=>document.documentElement.style.fontSize='200%');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Text scaling overflow');
        if(await page.evaluate(()=>window.pmCspViolations.length)||errors.length)throw new Error('CSP/JS errors');
        const fallback=await context.browser().newContext({javaScriptEnabled:false});
        try{const p=await fallback.newPage();await p.goto(base+'phase9b-import-apply.html');await p.locator('#pm-import-history').click();if(!await p.locator('button[value=apply]').isVisible()||!await p.locator('input[name=password]').isVisible())throw new Error('No-JS fallback failed');}finally{await fallback.close();}
        return 'PASS: three steps, native upload/CSRF, empty/errors, keyboard/no-JS history, Apply/result/password gates, responsive, text scaling and CSP. No business POST.';
    } finally {await context.close();}
}
