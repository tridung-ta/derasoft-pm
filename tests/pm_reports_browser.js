async (page) => {
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    const bytes=await (await page.request.get('http://127.0.0.1:18767/.local/phase8-preview.xlsx')).body();
    await page.route('**/.local/phase8-preview.html',async route=>{
        if(route.request().method()!=='POST'){await route.continue();return;}
        const p=new URLSearchParams(route.request().postData());
        if(p.get('op')!=='pmreports'||p.get('csrf_token')!=='preview-token'||p.get('action')!=='export')throw new Error('Missing export route/CSRF fields.');
        await route.fulfill({contentType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',headers:{'Content-Disposition':'attachment; filename="preview.xlsx"'},body:bytes});
    });
    await page.goto('http://127.0.0.1:18767/.local/phase8-preview.html');
    if(await page.locator('script').count()||await page.locator('img[src=x]').count())throw new Error('Unsafe report content.');
    for(const [name,width,height] of [['desktop',1440,1000],['mobile',390,844]]){
        await page.setViewportSize({width,height});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw new Error('Report '+name+' overflow.');
        await page.screenshot({path:'phase8-'+name+'.png',fullPage:true});
    }
    const get=page.locator('form[method="get"]');if(await get.locator('input[name="from"]').inputValue()!=='2026-10-01')throw new Error('Filter not retained.');
    const downloadPromise=page.waitForEvent('download');await page.getByRole('button',{name:'Xuất XLSX'}).click();const download=await downloadPromise;
    if(download.suggestedFilename()!=='preview.xlsx'||await download.failure())throw new Error('Browser download failed.');
    if(errors.length)throw new Error(errors.join('; '));return 'PASS: reports desktop/mobile, escaped content, retained filters and XLSX download route/CSRF.';
}
