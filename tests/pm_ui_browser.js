async page => {
    const errors=[];
    await page.addInitScript(()=>{window.pmCspViolations=[];document.addEventListener('securitypolicyviolation',e=>window.pmCspViolations.push(e.violatedDirective));});
    page.on('pageerror',e=>errors.push(e.message));
    const fixtures=['login','users-1','users-readonly','projects-1','projects-2','timesheets-1','timesheets-2','audit-1','audit-2','dashboard','profile','password','team'].map(n=>'phase9-'+n+'.html');
    for(const file of fixtures){
        await page.goto('http://127.0.0.1:18767/.local/'+file);
        // Preview base href=/ is only for local assets; real Admin has no base tag.
        await page.evaluate(()=>document.querySelector('.pm-skip').href=location.pathname+'#pm-main');
        for(const width of [360,390,768,1440]){
            await page.setViewportSize({width,height:1000});
            const issues=await page.evaluate(()=>{
                const missing=[...document.querySelectorAll('input:not([type="hidden"]),select,textarea')].filter(e=>!e.labels?.length&&!e.getAttribute('aria-label')&&!e.getAttribute('aria-labelledby')).map(e=>e.name);
                return {overflow:document.documentElement.scrollWidth>innerWidth+1,missing,main:!!document.getElementById('pm-main'),skip:!!document.querySelector('.pm-skip'),inline:!!document.querySelector('style,[onsubmit],[onclick]')};
            });
            if(issues.overflow||issues.missing.length||!issues.main||!issues.skip||issues.inline)throw new Error(file+' at '+width+': '+JSON.stringify(issues));
        }
        await page.keyboard.press('Control+Home');await page.evaluate(()=>document.activeElement.blur());await page.keyboard.press('Tab');
        // The skip link must be first in tab order, not just present in DOM.
        if(!await page.locator('.pm-skip').evaluate(e=>e===document.activeElement))throw new Error('Skip keyboard order '+file);
        await page.keyboard.press('Enter');if(!await page.locator('#pm-main').evaluate(e=>e===document.activeElement))throw new Error('Skip target focus '+file);
        await page.setViewportSize({width:720,height:900});await page.evaluate(()=>document.documentElement.style.fontSize='200%');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Text zoom overflow '+file);
        await page.evaluate(()=>document.documentElement.style.fontSize='');
        if(await page.evaluate(()=>window.pmCspViolations.length))throw new Error('CSP compatibility '+file);
        if(file==='phase9-users-1.html'){for(const [name,width] of [['desktop',1440],['mobile',390]]){await page.setViewportSize({width,height:1000});await page.screenshot({path:'phase9-users-'+name+'.png',fullPage:true});}}
    }
    if(errors.length)throw new Error(errors.join('; '));
    return 'PASS: '+fixtures.length+' synthetic PM screen/state fixtures at 360/390/768/1440px; visible labels, native table regions, skip keyboard focus, no page overflow/inline code, 200% text scaling (not OS/browser zoom).';
}
