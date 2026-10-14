async page => {
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 for(const width of [1440,390]){
  await page.setViewportSize({width,height:1000});
  for(const state of ['mixed','empty','error']){
   await page.goto('http://127.0.0.1:18767/.local/pm-notifications-'+state+'.html');
   await page.evaluate(()=>document.fonts.ready);
   if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Overflow '+width+' '+state);
   const shell=await page.locator('.pm-inbox-shell').boundingBox();if(shell.width>1041||shell.x<0||shell.x+shell.width>width)throw Error('Shell bounds');
   if(state==='mixed'){
    if(await page.locator('.pm-inbox-is-unread').count()!==1||await page.locator('.pm-inbox-is-read').count()!==1)throw Error('Read states');
    if(await page.locator('.pm-inbox-is-read form').count()!==0||await page.locator('.pm-inbox-read').count()!==1)throw Error('Read button scope');
    const form=page.locator('.pm-inbox-actions form');if(await form.getAttribute('method')!=='post'||await form.locator('[name=csrf_token]').count()!==1)throw Error('CSRF POST');
    if(await page.locator('.pm-inbox-pagination a').count()!==2)throw Error('Pagination links');
    if(await page.locator('.pm-inbox-is-unread time').textContent()!=='14/10/2026')throw Error('Date format');
    await page.locator('.pm-inbox-read').focus();if(!await page.locator('.pm-inbox-read').evaluate(e=>document.activeElement===e&&getComputedStyle(e).outlineStyle==='solid'))throw Error('Focus');
    for(const button of await page.locator('.pm-inbox-actions a,.pm-inbox-actions button').all()){const b=await button.boundingBox();if(b.height<44||b.x<0||b.x+b.width>width)throw Error('Action hit area');}
   }
   if(state==='empty'&&!await page.locator('.pm-inbox-empty').isVisible())throw Error('Empty state');
   if(state==='error'&&!await page.locator('[role=alert]').isVisible())throw Error('Error state');
   await page.screenshot({path:'.local/inbox-'+state+'-'+width+'.png',fullPage:true});
  }
 }
 if(errors.length)throw Error(errors.join(';'));
 return 'PASS: inbox 1440/390, read/unread/long names/pagination/error/empty, keyboard focus, 44px actions and preserved CSRF POST; fixture only, no DB writes.';
}
