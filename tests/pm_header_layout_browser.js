async page=>{
 for(const width of [1440,390,320]){
  await page.setViewportSize({width,height:900});await page.goto('http://127.0.0.1:18767/.local/pm-notifications-preview.html');
  const brand=page.locator('.pm-work-brand'),nav=page.locator('.pm-work-menu>summary'),account=page.locator('.pm-user-menu>summary');
  const b=await brand.boundingBox(),n=await nav.boundingBox(),a=await account.boundingBox();
  if(Math.abs(b.x+b.width/2-width/2)>2||n.y>64||b.x<n.x+n.width||b.x+b.width>(await page.locator('.pm-header-controls').boundingBox()).x)throw Error('Header layout '+width);
  if(await page.locator('.brand-mark').count())throw Error('D mark remains');
  await nav.click();if(!await page.locator('.pm-work-menu nav').isVisible())throw Error('Navigation');await nav.click();
  await account.click();if(!await page.locator('.pm-user-menu form').isVisible())throw Error('Account');await page.keyboard.press('Escape');
  if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('Overflow');
  await page.screenshot({path:'.local/header-'+width+'.png'});
 }
 return 'PASS: centered brand, navigation in topbar, no collision at1440/390/320, menu/account functional.';
}
