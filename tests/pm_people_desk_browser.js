async page => {
 const context=await page.context().browser().newContext({viewport:{width:1440,height:1000}});
 const p=await context.newPage();const base='http://127.0.0.1:18767/.local/';
 try{
  await p.goto(base+'phase9-projects-1.html');
  const editor=p.locator('.pm-member-add');
  if(await editor.evaluate(e=>e.open))throw new Error('Member editor starts expanded');
  await editor.locator('summary').focus();await p.keyboard.press('Enter');
  if(!await editor.evaluate(e=>e.open)||!await editor.locator('select[name=member_id]').isVisible())throw new Error('Keyboard editor unavailable');
  if(await editor.locator('input[name=csrf_token]').count()!==1)throw new Error('Member CSRF lost');
  await p.goto(base+'phase9-users-1.html');
  if(!await p.locator('.pm-person-identity strong').count()||!await p.locator('.pm-person-state').count())throw new Error('Identity/state missing');
  const trigger=p.locator('.pm-person-editor>summary').first();await trigger.click();
  const close=p.locator('[data-pm-dialog-close]:visible').first();
  await close.waitFor();await p.keyboard.press('Escape');
  if(!await trigger.evaluate(e=>document.activeElement===e))throw new Error('Dialog close focus lost');
  for(const width of [390,1440]){
   await p.setViewportSize({width,height:1000});
   if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('People overflow');
   await p.screenshot({path:'people-desk-'+width+'.png',fullPage:true});
  }
  return 'PASS: membership collapsed/keyboard/CSRF, person identity/state, editor Escape focus, desktop/mobile. No DB writes.';
 }finally{await context.close();}
}