async page => {
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    const url='http://127.0.0.1:18767/.local/phase9-users-two.html';
    await page.goto(url);
    if(await page.locator('.pm-person-dialog[open]').count())throw new Error('Editor open initially');
    if(await page.locator('.pm-add-person').getAttribute('open')!==null)throw new Error('Add form not collapsed');
    for(const id of [1,2]){
        const opener=page.locator('#pm-person-'+id+' > summary');
        await opener.focus();await page.keyboard.press('Enter');
        const dialog=page.locator('#pm-person-'+id+'-dialog');
        if(await page.locator('.pm-person-dialog[open]').count()!==1)throw new Error('Multiple/no dialogs');
        if(await dialog.locator('input[name=id]').first().inputValue()!==String(id))throw new Error('Wrong user ID');
        if(await dialog.locator('form form').count())throw new Error('Nested forms');
        if(await dialog.locator('form input[name=csrf_token]').count()!==await dialog.locator('form').count())throw new Error('Missing CSRF');
        for(let n=0;n<35;n++){
            await page.keyboard.press('Tab');
            if(!await dialog.evaluate(e=>e.contains(document.activeElement)))throw new Error('Focus escaped modal');
        }
        for(let n=0;n<35;n++){
            await page.keyboard.press('Shift+Tab');
            if(!await dialog.evaluate(e=>e.contains(document.activeElement)))throw new Error('Reverse focus escaped modal');
        }
        for(const width of [360,390,768,1440]){
            await page.setViewportSize({width,height:900});
            if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Page overflow');
            if(await dialog.evaluate(e=>e.scrollWidth>e.clientWidth+1))throw new Error('Dialog overflow');
        }
        await page.setViewportSize({width:720,height:900});await page.evaluate(()=>document.documentElement.style.fontSize='200%');
        if(await dialog.evaluate(e=>e.scrollWidth>e.clientWidth+1))throw new Error('Text scaling overflow');
        await page.evaluate(()=>document.documentElement.style.fontSize='');
        await page.keyboard.press('Escape');
        if(!await opener.evaluate(e=>e===document.activeElement))throw new Error('Focus not restored after Escape');
        await opener.click();await dialog.getByRole('button',{name:'Đóng',exact:true}).click();
        if(await dialog.getAttribute('open')!==null)throw new Error('Close submitted/open');
    }
    let posts=0;page.on('request',r=>{if(r.method()==='POST')posts++});
    await page.evaluate(()=>{window.pmConfirmCalls=[];window.confirm=message=>{window.pmConfirmCalls.push(message);return false}});
    await page.getByRole('button',{name:'Xóa mềm',exact:true}).first().click();
    if(posts)throw new Error('Cancelled destructive form submitted');
    if(await page.evaluate(()=>window.pmConfirmCalls[0])!=='Soft delete tài khoản này?')throw new Error('Confirmation message changed');
    for(const [name,width] of [['desktop',1440],['mobile',390]]){
        await page.setViewportSize({width,height:900});
        await page.evaluate(()=>scrollTo(0,0));
        await page.screenshot({path:'phase9b-users-'+name+'.png',fullPage:true});
        await page.locator('#pm-person-1 > summary').click();
        await page.screenshot({path:'phase9b-users-dialog-'+name+'.png'});
        await page.keyboard.press('Escape');
    }
    const fallback=await page.context().browser().newContext({javaScriptEnabled:false});
    try{
        const p=await fallback.newPage();await p.goto(url);
        await p.locator('#pm-person-2 > summary').click();
        if(!await p.locator('#pm-person-2 input[name=fullname]').isVisible())throw new Error('No-JS editor inaccessible');
        if(await p.locator('#pm-person-2 input[name=id]').first().inputValue()!=='2')throw new Error('No-JS wrong ID');
    }finally{await fallback.close()}
    if(errors.length)throw new Error(errors.join('; '));
    return 'PASS: distinct users, modal keyboard/focus/close, independent CSRF forms, collapsed add, cancelled confirmation, no-JS fallback, 4 widths/text scaling and screenshots. Synthetic only.';
}
