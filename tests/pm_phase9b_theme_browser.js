async page => {
    const fixtures=['phase9-dashboard.html','phase9-users-1.html','phase9-timesheets-1.html','phase6-preview.html','phase7-preview.html','phase8-import-preview.html','phase9-theme-nav.html'];
    const results=[];
    for(const file of fixtures){
        await page.goto('http://127.0.0.1:18767/.local/'+file);
        await page.evaluate(()=>document.fonts.ready);
        const styles=await page.evaluate(()=>{
            const s=getComputedStyle(document.body);
            return {background:s.backgroundColor,foreground:s.color,font:s.fontFamily,loaded:[...document.fonts].some(f=>f.family==='Inter'&&f.status==='loaded'),external:[...performance.getEntriesByType('resource')].filter(r=>!r.name.startsWith(location.origin)).map(r=>r.name)};
        });
        if(styles.background!=='rgb(255, 255, 255)'||styles.foreground!=='rgb(9, 9, 11)'||!styles.loaded||!styles.font.startsWith('Inter')||styles.external.length)throw new Error(file+': '+JSON.stringify(styles));
        if(file==='phase9-dashboard.html'){
            const muted=await page.locator('.metric-label').first().evaluate(e=>getComputedStyle(e).color);
            if(muted!=='rgb(113, 113, 122)')throw new Error('Dashboard muted text regressed: '+muted);
        }
        for(const width of [360,390,768,1440]){
            await page.setViewportSize({width,height:1000});
            if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error(file+' overflow '+width);
        }
        if(file==='phase9-theme-nav.html'){
            if(await page.locator('.pm-module-nav a').count()!==9||await page.locator('.pm-module-nav a .pm-nav-icon').count()!==9)throw new Error('Full navigation icons missing');
            const nav=await page.locator('.pm-module-nav').evaluate(e=>getComputedStyle(e).backgroundColor);
            if(nav!=='rgb(244, 244, 245)')throw new Error('Wrong sidebar theme');
        }
        if(file==='phase6-preview.html'){
            const align=await page.locator('.pm-money-table td').nth(1).evaluate(e=>getComputedStyle(e).textAlign);
            if(align!=='right')throw new Error('Numeric alignment');
        }
        if(['phase9-dashboard.html','phase9-timesheets-1.html','phase6-preview.html'].includes(file)){
            for(const [name,width] of [['desktop',1440],['mobile',390]]){
                await page.setViewportSize({width,height:1000});await page.evaluate(()=>scrollTo(0,0));
                await page.screenshot({path:'phase9b-theme-'+file+'-'+name+'.png',fullPage:true});
            }
        }
        results.push({file,...styles});
    }
    // Chromium reports the font that actually renders every Vietnamese precomposed glyph.
    await page.evaluate(()=>{const span=document.createElement('span');span.id='pm-font-coverage';span.textContent=Array.from({length:90},(_,i)=>String.fromCharCode(0x1ea0+i)).join('');document.getElementById('pm-main').append(span)});
    // Font inspection needs a rendered node, not just an inserted DOM node.
    await page.locator('#pm-font-coverage').scrollIntoViewIfNeeded();
    await page.evaluate(async()=>{await document.fonts.ready;await new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)))});
    const session=await page.context().newCDPSession(page);
    try{
        await session.send('DOM.enable');await session.send('CSS.enable');
        const {root}=await session.send('DOM.getDocument');
        const {nodeId}=await session.send('DOM.querySelector',{nodeId:root.nodeId,selector:'#pm-font-coverage'});
        const {fonts}=await session.send('CSS.getPlatformFontsForNode',{nodeId});
        if(!fonts.length||fonts.some(f=>!f.isCustomFont||!f.familyName.startsWith('Inter')))throw new Error('Vietnamese fallback glyphs: '+JSON.stringify(fonts));
        results.push({vietnameseFonts:fonts});
    }finally{await session.detach()}
    return results;
}
