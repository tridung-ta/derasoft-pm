async (page) => {
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.goto('http://127.0.0.1:18767/.local/phase8-import-preview.html');
    for(const [name,width,height] of [['desktop',1440,1000],['mobile',390,844]]){
        await page.setViewportSize({width,height});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw new Error('Import '+name+' overflow.');
        await page.screenshot({path:'phase8-import-'+name+'.png',fullPage:true});
    }
    if(await page.locator('script').count()||await page.locator('img[src=x]').count())throw new Error('Import preview XSS.');
    const form=page.locator('form[enctype="multipart/form-data"]');
    if(await form.locator('input[name="op"]').inputValue()!=='pmimports'||await form.locator('input[name="csrf_token"]').inputValue()!=='test-token'||await form.locator('input[type="file"]').getAttribute('accept')!=='.xlsx')throw new Error('Upload route/CSRF/type fields.');
    if(!await page.getByRole('button',{name:'Kiểm tra và lưu staging'}).count()||!await page.getByRole('button',{name:'Tải file mẫu'}).count())throw new Error('Import controls missing.');
    if(!await page.getByText('Preview/staging chưa áp dụng', {exact:false}).count()||errors.length)throw new Error('Missing un-applied state or script error.');
    for(const action of ['apply','resume','detail','provision_password']){
        const control=page.locator('button[value="'+action+'"]').first();
        const target=control.locator('..');
        if(await target.locator('input[name="op"]').inputValue()!=='pmimports'||await target.locator('input[name="csrf_token"]').inputValue()!=='test-token')throw new Error('Apply POST fields.');
        if(action==='provision_password')await target.locator('input[name="password"]').fill('Synthetic password 2026');
        let submitted=false;
        await page.route('**/.local/phase8-import-preview.html',async route=>{
            if(route.request().method()!=='POST'){await route.continue();return;}
            const data=new URLSearchParams(route.request().postData());
            if(data.get('action')!==action||data.get('csrf_token')!=='test-token'||!data.get('import_id'))throw new Error('Wrong submitted Apply fields.');
            if(action==='provision_password'&&(data.get('source_row')!=='2'||!data.get('password')))throw new Error('Credential form missing fields.');
            submitted=true;await route.fulfill({contentType:'text/html',body:'<p>Fixture POST received</p>'});
        });
        await control.click();await page.waitForLoadState();if(!submitted)throw new Error('Form did not submit '+action);
        await page.unroute('**/.local/phase8-import-preview.html');await page.goto('http://127.0.0.1:18767/.local/phase8-import-preview.html');
    }
    return 'PASS: import desktop/mobile, escaping, staging state, Apply/resume/detail/credential POST and CSRF fields (synthetic browser fixture).';
}
