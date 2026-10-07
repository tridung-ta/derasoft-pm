async page => {
    await page.goto('http://127.0.0.1:18767/.local/phase9-users-two.html');
    await page.locator('.pm-add-person > summary').click();
    const form=page.locator('.pm-add-person form');
    const phone=form.locator('[name=tel]');
    const password=form.locator('[name=password]');
    if(await password.getAttribute('required')===null||await password.getAttribute('minlength')!=='8')throw new Error('Password constraints missing');
    const passwordError=form.locator('[data-pm-field-error=password]');
    for(const [value,message] of [['','Vui lòng nhập mật khẩu.'],['1234567','Mật khẩu phải có ít nhất 8 ký tự.'],['        ','Mật khẩu không được chỉ gồm khoảng trắng.'],['áááá','Mật khẩu phải có ít nhất 8 ký tự.']]){
        await password.fill(value);await password.blur();
        if(await passwordError.textContent()!==message||!await passwordError.isVisible()||await password.getAttribute('aria-invalid')!=='true')throw new Error('Password field error incorrect');
    }
    await password.fill('password123');
    if(await passwordError.isVisible()||await password.getAttribute('aria-invalid')!=='false')throw new Error('Password error did not clear');
    for(const [value,valid] of [['',true],['0901234567',true],['090abc1234',false],['090 123456',false],['09012345678',false]]){
        await phone.fill(value);
        // Fill obeys maxlength; assign raw values to also check pasted/bypassed HTML constraints.
        await phone.evaluate((el,value)=>{el.value=value},value);
        await phone.dispatchEvent('input');
        if(await phone.evaluate(el=>el.checkValidity())!==valid)throw new Error('Unexpected phone validity: '+value);
        const error=form.locator('[data-pm-field-error=tel]');
        if(await error.isVisible()===valid)throw new Error('Phone field error visibility incorrect');
        if(!valid&&!(await error.textContent()).includes(value.length>10?'10 số':'chữ số'))throw new Error('Phone error did not explain cause');
    }
    await phone.fill('0901234567');
    if(await phone.getAttribute('inputmode')!=='numeric'||await phone.getAttribute('maxlength')!=='10')throw new Error('Phone affordance missing');
    if(!await page.locator('#pm-new-phone-help').isVisible())throw new Error('Optional phone hint missing');
    await form.locator('[name=username]').fill('demo-new');
    await form.locator('[name=fullname]').fill('Demo New');
    await form.locator('[name=email]').fill('demo-new@example.test');
    let posts=0;const observe=request=>{if(request.method()==='POST')posts++};page.on('request',observe);
    await password.fill('short');await phone.fill('abc');
    await form.getByRole('button',{name:'Thêm nhân sự',exact:true}).click();
    if(posts||!await passwordError.isVisible()||!await form.locator('[data-pm-field-error=tel]').isVisible())throw new Error('Invalid submit sent/omitted errors');
    page.off('request',observe);
    for(const width of [1440,390]){
        await page.setViewportSize({width,height:900});
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Personnel page overflow');
    }
    return 'PASS: exact password/phone inline errors, correction clears errors, invalid submit blocked, optional phone, desktop/mobile overflow; static fake-data fixture, no POST.';
}
