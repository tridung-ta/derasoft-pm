async page => {
    await page.goto('http://127.0.0.1:18767/.local/phase9-users-two.html');
    await page.locator('.pm-add-person > summary').click();
    const form=page.locator('.pm-add-person form');
    const phone=form.locator('[name=tel]');
    const password=form.locator('[name=password]');
    if(await password.getAttribute('required')===null||await password.getAttribute('minlength')!=='8')throw new Error('Password constraints missing');
    for(const [value,valid] of [['',true],['0901234567',true],['090abc1234',false],['090 123456',false],['09012345678',false]]){
        await phone.fill(value);
        // Fill obeys maxlength; assign raw values to also check pasted/bypassed HTML constraints.
        await phone.evaluate((el,value)=>{el.value=value},value);
        if(await phone.evaluate(el=>el.checkValidity())!==valid)throw new Error('Unexpected phone validity: '+value);
    }
    await phone.fill('0901234567');
    if(await phone.getAttribute('inputmode')!=='numeric'||await phone.getAttribute('maxlength')!=='10')throw new Error('Phone affordance missing');
    if(!await page.locator('#pm-new-phone-help').isVisible())throw new Error('Optional phone hint missing');
    for(const width of [1440,390]){
        await page.setViewportSize({width,height:900});
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Personnel page overflow');
    }
    return 'PASS: rendered password required/minlength, optional digits-only phone, invalid letters/spaces/11 digits, desktop/mobile overflow; static fake-data fixture, no submit.';
}
