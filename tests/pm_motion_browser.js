async original=>{
 const browser=original.context().browser();const findings=[];
 for(const width of [1440,390])for(const reduced of ['no-preference','reduce']){
  const context=await browser.newContext({reducedMotion:reduced,viewport:{width,height:960}});const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
  try{
   await page.goto('http://127.0.0.1:18769/.local/review-notifications.html');
   const nav=page.locator('.pm-work-menu>summary');await nav.click();
   const motion=await page.locator('.pm-module-nav').evaluate(e=>e.getAnimations().map(a=>({duration:a.effect.getTiming().duration,frames:a.effect.getKeyframes().map(f=>({transform:f.transform,clipPath:f.clipPath}))})));
   if(reduced==='reduce'&&motion.length)throw Error('Reduced motion moved menu');
   if(reduced==='no-preference'&&(!motion.length||motion[0].duration>300))throw Error('Menu motion absent or slow');
   await page.keyboard.press('Escape');if(await page.locator('.pm-work-menu').evaluate(e=>e.open))throw Error('Escape broken');
   for(let i=0;i<4;i++){await nav.click();await nav.click();}
   if(await page.locator('.pm-work-menu').evaluate(e=>e.open))throw Error('Rapid toggle state');
   await page.emulateMedia({reducedMotion:'reduce'});
   if(await page.locator('.pm-module-nav').evaluate(e=>e.getAnimations().some(a=>a.playState==='running')))throw Error('Preference change did not stop movement');
   await page.goto('http://127.0.0.1:18769/.local/review-users.html');
   await page.locator('[data-pm-editor]>summary').first().click();
   if(!await page.locator('dialog[open]').isVisible())throw Error('Dialog unavailable');await page.keyboard.press('Escape');
   if(await page.locator('dialog[open]').count())throw Error('Dialog did not close');
   await page.goto('http://127.0.0.1:18769/.local/review-imports.html');
   await page.getByRole('tab').nth(1).click();if(await page.locator('[role=tabpanel]:visible').count()!==1)throw Error('Tab state');
   if(errors.length)throw Error(errors.join(';'));
   findings.push({width,reduced,menuAnimations:motion.length,menuDuration:motion[0]?.duration||0});
  }finally{await context.close();}
 }
 return {result:'PASS: menu motion budget, reduced-motion path, preference cancellation, rapid toggles, Escape, dialog, tabs. Local fixture only.',findings};
}
