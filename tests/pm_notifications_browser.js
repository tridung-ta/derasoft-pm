async page=>{
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 const cron=await page.request.get('http://127.0.0.1:18767/cron/pm-reminders.php');if(cron.status()!==404||await cron.text()!=='')throw Error('Cron exposed over HTTP');
 for(const width of [1440,390]){
  await page.setViewportSize({width,height:900});
  await page.goto('http://127.0.0.1:18767/.local/pm-notifications-preview.html');
  if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Overflow');
  const bell=page.locator('.pm-notification-menu>summary');
  await bell.focus();await page.keyboard.press('Enter');
  if(!await page.locator('.pm-notification-panel').isVisible())throw Error('Bell menu keyboard');
  const box=await page.locator('.pm-notification-panel').boundingBox();if(box.x<0||box.x+box.width>width)throw Error('Bell menu clipped');
  await page.keyboard.press('Escape');if(await page.locator('.pm-notification-panel').isVisible()||!await bell.evaluate(e=>e===document.activeElement))throw Error('Escape/focus');
  await bell.click();await page.mouse.click(12,70);if(await page.locator('.pm-notification-panel').isVisible())throw Error('Outside close');
  if(await page.locator('.pm-notification-row').count()!==1||!await page.locator('.pm-notification-row').textContent().then(x=>x.includes('<script>unsafe</script>')))throw Error('Escaped task');
  const form=page.locator('.pm-notification-row form');if(await form.getAttribute('method')!=='post'||await form.locator('[name=csrf_token]').count()!==1)throw Error('POST CSRF');
  const nav=await page.locator('.pm-work-menu>summary').boundingBox();if(Math.abs(nav.x-(width>640?24:18))>2)throw Error('Navigation moved');
  await page.screenshot({path:'.local/notification-'+width+'.png'});
  await page.goto('http://127.0.0.1:18767/.local/pm-notifications-empty.html');if(await page.locator('.pm-notification-row').count()||!await page.locator('.pm-empty-state').isVisible())throw Error('Empty');
 }
 if(errors.length)throw Error(errors.join(';'));
 return 'PASS notifications: desktop/mobile, escaped task, bell Enter/Escape/outside, menu bounds, existing left navigation, empty state and POST/CSRF markup. No authenticated DB write.';
}
