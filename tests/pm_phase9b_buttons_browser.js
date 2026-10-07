async page => {
    await page.goto('http://127.0.0.1:18767/.local/phase9-users-1.html');
    await page.locator('.pm-add-person summary').click();
    const results=[];
    function contrast(a,b){
        const luminance=s=>s.match(/[\d.]+/g).slice(0,3).map(Number).map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4}).reduce((n,v,i)=>n+v*[.2126,.7152,.0722][i],0);
        const x=luminance(a),y=luminance(b);return (Math.max(x,y)+.05)/(Math.min(x,y)+.05);
    }
    for(const label of ['Thêm nhân sự','Khóa','Khôi phục OLD','Ngừng áp dụng']){
        const button=page.getByRole('button',{name:label,exact:true}).first();
        for(const state of ['default','hover','active','focus','disabled']){
            await page.mouse.move(0,0);
            if(state==='hover')await button.hover();
            if(state==='active'){await button.hover();await page.mouse.down();}
            if(state==='focus')await button.focus();
            if(state==='disabled')await button.evaluate(e=>e.disabled=true);
            const colors=await button.evaluate(e=>{const s=getComputedStyle(e);return {color:s.color,background:s.backgroundColor,height:e.getBoundingClientRect().height,opacity:s.opacity}});
            const ratio=contrast(colors.color,colors.background);
            if(ratio<4.5||colors.height<44||colors.opacity!=='1')throw new Error(label+' '+state+': '+JSON.stringify({ratio,...colors}));
            if(state==='default'){
                const destructive=['Khóa','Ngừng áp dụng'].includes(label);
                if(colors.background!==(destructive?'rgb(220, 38, 38)':'rgb(24, 24, 27)')||colors.color!==(destructive?'rgb(255, 255, 255)':'rgb(250, 250, 250)'))throw new Error('Wrong replacement token '+label);
            }
            results.push({label,state,contrast:ratio.toFixed(2),...colors});
            if(state==='active')await page.mouse.move(0,0).then(()=>page.mouse.up());
            await button.evaluate(e=>{e.disabled=false;e.blur()});
        }
    }
    if(!await page.getByRole('button',{name:'Khóa',exact:true}).first().evaluate(e=>e.classList.contains('danger')))throw new Error('Lock not destructive');
    if(await page.getByRole('button',{name:'Khôi phục OLD',exact:true}).evaluate(e=>e.classList.contains('danger')))throw new Error('Restore destructive');
    await page.goto('http://127.0.0.1:18767/.local/phase9-users-unlock.html');
    for(const button of await page.getByRole('button',{name:'Mở khóa',exact:true}).all()){
        if(!await button.evaluate(e=>e.classList.contains('secondary')&&!e.classList.contains('danger')))throw new Error('Unlock destructive');
    }
    for(const file of ['phase7-preview.html','phase6-preview.html','phase8-import-preview.html']){
        await page.goto('http://127.0.0.1:18767/.local/'+file);
        for(const width of [360,390,768,1440]){
            await page.setViewportSize({width,height:1000});
            if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Overflow '+file+' '+width);
        }
    }
    await page.emulateMedia({reducedMotion:'reduce'});
    if(await page.evaluate(()=>getComputedStyle(document.body).animationName)!=='none')throw new Error('Reduced motion');
    return results;
}
