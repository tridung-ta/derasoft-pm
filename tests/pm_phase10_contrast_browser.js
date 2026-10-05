async page => {
    const failures=[];
    for(const file of ['phase9-login.html','phase9-users-1.html','phase9-dashboard.html','phase9-projects-1.html']){
        await page.goto('http://127.0.0.1:18767/.local/'+file);
        const issues=await page.evaluate(()=>{
            const rgb=s=>(s.match(/[\d.]+/g)||[]).slice(0,3).map(Number);
            const luminance=c=>c.map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((a,v,i)=>a+v*[.2126,.7152,.0722][i],0);
            const result=[];
            for(const e of document.querySelectorAll('a,button,label,h1,h2,h3,p,small,th,td')){
                if(!e.checkVisibility()||!e.textContent.trim()||e.children.length||e.disabled)continue;
                const style=getComputedStyle(e);let parent=e,bg;
                while(parent){const c=getComputedStyle(parent).backgroundColor;if(c!=='rgba(0, 0, 0, 0)'&&c!=='transparent'){bg=rgb(c);break;}parent=parent.parentElement;}
                bg=bg||[255,255,255];const l1=luminance(rgb(style.color)),l2=luminance(bg);const ratio=(Math.max(l1,l2)+.05)/(Math.min(l1,l2)+.05);
                const size=parseFloat(style.fontSize),large=size>=24||(size>=18.66&&Number(style.fontWeight)>=700);
                if(ratio<(large?3:4.5))result.push({tag:e.tagName,text:e.textContent.trim().slice(0,45),ratio:Number(ratio.toFixed(2))});
            }return result;
        });
        if(issues.length)failures.push({file,issues});
    }
    if(failures.length)throw new Error(JSON.stringify(failures));
    return 'PASS: computed solid-color contrast sample of four synthetic screens; not full contrast/screen-reader or browser zoom certification.';
}
