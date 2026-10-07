async page=>{
    await page.goto('http://127.0.0.1:18767/.local/phase9-login.html');
    await page.evaluate(()=>document.fonts.ready);
    const results=await page.locator('.intro .brand,.intro h1,.intro p').evaluateAll(nodes=>{
        function lum(color){return color.match(/[\d.]+/g).slice(0,3).map(Number).map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4}).reduce((a,v,i)=>a+v*[.2126,.7152,.0722][i],0)}
        return nodes.map(e=>{const fg=lum(getComputedStyle(e).color),bg=lum(getComputedStyle(e.closest('.intro')).backgroundColor);return {tag:e.tagName,ratio:(Math.max(fg,bg)+.05)/(Math.min(fg,bg)+.05)}});
    });
    if(results.length!==3||results.some(r=>r.ratio<15))throw Error('Login intro not strongly legible: '+JSON.stringify(results));
    if(!await page.locator('link[href*="css/pmui.css"]').evaluate(e=>e.getAttribute('href').includes('v=20261007-login3')))throw Error('Login CSS cache version missing');
    if(!await page.locator('.intro').evaluate(e=>getComputedStyle(e).backgroundColor==='rgb(244, 244, 245)'))throw Error('Generic card rule overrides login intro');
    if(!await page.locator('button.submit').evaluate(e=>getComputedStyle(e).backgroundColor==='rgb(24, 24, 27)'))throw Error('Legacy orange login button remains');
    for(const width of [1440,390,720]){
        await page.setViewportSize({width,height:1000});
        await page.evaluate(scale=>document.documentElement.style.fontSize=scale,width===720?'200%':'');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Login overflow');
        if(!await page.locator('.intro h1').isVisible()||!await page.getByRole('button',{name:'Đăng nhập',exact:true}).isVisible())throw Error('Login content hidden');
        if(width!==720)await page.screenshot({path:'login-contrast-'+width+'.png',fullPage:true});
    }
    await page.evaluate(()=>document.documentElement.style.fontSize='');
    await page.locator('#username').focus();
    if(!await page.locator('#username').evaluate(e=>e===document.activeElement&&parseFloat(getComputedStyle(e).outlineWidth)>=3))throw Error('Login keyboard focus lost');
    return {result:'PASS: intro brand/title/body contrast, desktop/mobile/text scaling and login keyboard focus',contrasts:results};
}
