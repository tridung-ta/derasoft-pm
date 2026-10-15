async page=>{
    await page.goto('http://127.0.0.1:18767/.local/phase9-login.html');
    await page.evaluate(()=>document.fonts.ready);
    const results=await page.locator('.intro .brand,.intro h1,.intro p').evaluateAll(nodes=>{
        function lum(color){return color.match(/[\d.]+/g).slice(0,3).map(Number).map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4}).reduce((a,v,i)=>a+v*[.2126,.7152,.0722][i],0)}
        return nodes.map(e=>{const fg=lum(getComputedStyle(e).color),bg=lum(getComputedStyle(e.closest('.intro')).backgroundColor);return {tag:e.tagName,ratio:(Math.max(fg,bg)+.05)/(Math.min(fg,bg)+.05)}});
    });
    if(results.length!==3||results.some(r=>r.ratio<7))throw Error('Login intro not strongly legible: '+JSON.stringify(results));
    if(!await page.locator('link[href*="css/pmui.css"]').evaluate(e=>e.getAttribute('href').includes('v=20261015-lacquer1')))throw Error('Login CSS cache version missing');
    if(!await page.locator('.intro').evaluate(e=>getComputedStyle(e).backgroundColor==='rgb(246, 239, 228)'))throw Error('Login brand panel color mismatch');
    if(!await page.locator('button.submit').evaluate(e=>getComputedStyle(e).backgroundColor==='rgb(140, 28, 19)'))throw Error('Login button does not match lacquer theme');
    for(const width of [1440,390,720]){
        await page.setViewportSize({width,height:1000});
        await page.evaluate(scale=>document.documentElement.style.fontSize=scale,width===720?'200%':'');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Login overflow');
        if(width===390){const intro=await page.locator('.intro').boundingBox(),panel=await page.locator('.panel').boundingBox();if(panel.y<intro.y+intro.height-2||Math.abs(panel.x-intro.x)>2)throw Error('Mobile login must stack');}
        if(!await page.locator('.intro h1').isVisible()||!await page.getByRole('button',{name:'Đăng nhập',exact:true}).isVisible())throw Error('Login content hidden');
        if(width!==720)await page.screenshot({path:'login-contrast-'+width+'.png',fullPage:true});
    }
    await page.evaluate(()=>document.documentElement.style.fontSize='');
    await page.locator('#username').focus();
    if(!await page.locator('#username').evaluate(e=>e===document.activeElement&&parseFloat(getComputedStyle(e).outlineWidth)>=3))throw Error('Login keyboard focus lost');
    return {result:'PASS: intro brand/title/body contrast, desktop/mobile/text scaling and login keyboard focus',contrasts:results};
}
