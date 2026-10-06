async page => {
    await page.goto('http://127.0.0.1:18767/.local/phase9-users-1.html');
    await page.locator('.pm-add-person summary').click();
    const button=page.getByRole('button',{name:'Thêm nhân sự',exact:true});
    if(!await button.evaluate(e=>e.classList.contains('btn')))throw Error('Bootstrap button missing');
    await button.evaluate(e=>e.disabled=true);
    await page.waitForTimeout(250); // Wait for Bootstrap's color transition to settle.
    const disabled=await button.evaluate(e=>{const s=getComputedStyle(e);return [s.color,s.backgroundColor,s.opacity]});
    if(disabled.join('|')!=='rgb(82, 82, 91)|rgb(244, 244, 245)|1')throw Error('Disabled contrast regressed: '+disabled);
    await button.evaluate(e=>e.disabled=false);
    const input=page.locator('.pm-add-person input[name="fullname"]');
    await input.focus();await page.waitForTimeout(250);
    if(!await input.evaluate(e=>e.classList.contains('form-control')&&getComputedStyle(e).outlineWidth==='3px'&&getComputedStyle(e).boxShadow==='none'))throw Error('Input focus lost');
    const select=page.locator('.pm-add-person select[name="department_id"]');
    if(!await select.evaluate(e=>e.classList.contains('form-select')&&e.getBoundingClientRect().height>=44))throw Error('Select touch target lost');
    if(!await select.evaluate(e=>getComputedStyle(e).backgroundImage!=='none'&&parseFloat(getComputedStyle(e).paddingRight)>=40))throw Error('Select arrow hidden or text overlap');
    const editor=page.locator('.pm-person-editor>summary').first();
    if(!await editor.evaluate(e=>getComputedStyle(e).borderTopStyle==='solid'&&e.getBoundingClientRect().height>=44))throw Error('Edit action not clearly bounded');
    for(const width of [1440,390,720]){
        await page.setViewportSize({width,height:1000});
        await page.evaluate(scale=>document.documentElement.style.fontSize=scale,width===720?'200%':'');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Form page overflow');
    }
    await page.evaluate(()=>document.documentElement.style.fontSize='');
    return 'PASS: real Bootstrap control classes, settled disabled palette, keyboard focus, select touch target and responsive/text scaling.';
}
