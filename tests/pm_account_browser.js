async page=>{
 const context=await page.context().browser().newContext({viewport:{width:1440,height:960}});const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));const base='http://127.0.0.1:18767/.local/';
 try{
  await p.goto(base+'phase9-profile.html');if(!await p.locator('#username').evaluate(e=>e.readOnly&&!e.name))throw new Error('Username editable/submitted');
  await p.goto(base+'phase9-password.html');const form=p.locator('[data-pm-password-form]');
  if(await form.locator('.pm-password-toggle').count()!==3)throw new Error('Password toggles missing');
  const toggle=form.locator('.pm-password-toggle').first();await toggle.focus();await p.keyboard.press('Enter');
  if(await p.locator('#current_password').getAttribute('type')!=='text'||await toggle.getAttribute('aria-pressed')!=='true')throw new Error('Reveal keyboard failed');
  await toggle.click();if(await p.locator('#current_password').getAttribute('type')!=='password')throw new Error('Hide failed');
  await p.locator('#new_password').fill('ExampleNew123');await p.locator('#confirm_password').fill('Different123');
  if(await p.locator('#confirm_password').evaluate(e=>e.validity.valid)||!await p.locator('#pm-password-match').isVisible())throw new Error('Mismatch not indicated');
  await p.locator('#confirm_password').fill('ExampleNew123');if(!await p.locator('#confirm_password').evaluate(e=>e.validity.valid)||await p.locator('#pm-password-match').isVisible())throw new Error('Mismatch not cleared');
  await p.goto(base+'phase9-audit-1.html');await p.locator('.pm-audit-detail summary').first().click();if(await p.locator('.pm-audit-diff').first().locator('div').count()!==2)throw new Error('Before/after layout missing');
  if(await p.locator('.pm-audit-detail pre script').count())throw new Error('Audit HTML rendered as executable');
  const exactHtml=await (await p.request.get(base+'phase9-audit-1.html')).text();const literal='{"amount":12345678901234567890.01}';
  await p.route('**/audit-exact.html',route=>route.fulfill({contentType:'text/html',body:exactHtml.replace(/<pre>[\s\S]*?<\/pre>/, '<pre>'+literal+'</pre>')}));await p.goto(base+'audit-exact.html');
  if(await p.locator('.pm-audit-detail pre').first().textContent()!==literal)throw new Error('Historical numeric value rounded');
  for(const [name,file] of [['profile','phase9-profile'],['password','phase9-password'],['audit','phase9-audit-1']]){for(const width of [1440,390]){await p.setViewportSize({width,height:960});await p.goto(base+file+'.html');if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Overflow '+name+' '+width);await p.screenshot({path:'workspace-'+name+'-'+width+'.png'});}}
  if(errors.length)throw new Error(errors.join(';'));return 'PASS: readonly username, 3 accessible password toggles, mismatch/recovery, escaped audit before/after, desktop/mobile. No POST or DB mutation.';
 }finally{await context.close();}
}