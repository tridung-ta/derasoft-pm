/* Synthetic local fixtures; no production or business writes. */
async (page) => {
 const ctx=await page.context().browser().newContext();const p=await ctx.newPage();const errors=[];
 p.on('pageerror',e=>errors.push(e.message));
 const fixtures=['/.local/phase9-dashboard.html','/.local/phase9-users-1.html'];
 for(const fixture of fixtures){
  await p.goto('http://127.0.0.1:18767'+fixture);await p.evaluate(()=>document.fonts.ready);
  if(await p.locator('.brand-mark').count()!==1)throw Error('Brand missing/duplicated: '+fixture);
  const button=p.locator('button.pm-logout');
  if(await button.count()!==1)throw Error('Logout missing/duplicated');
  const style=await button.evaluate(el=>({bg:getComputedStyle(el).backgroundColor,color:getComputedStyle(el).color,height:el.getBoundingClientRect().height}));
  if(style.bg!=='rgb(24, 24, 27)'||style.color!=='rgb(250, 250, 250)'||style.height<44)throw Error('Logout contrast/target: '+JSON.stringify(style));
  if(await button.locator('svg').count()!==1)throw Error('Logout icon missing');
  if(await button.locator('..').locator('input[name="csrf_token"]').count()!==1)throw Error('Logout CSRF missing');
  for(const width of [1440,390,720]){
   await p.setViewportSize({width,height:1000});
   if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('Overflow '+fixture+' '+width);
   if(width===720)await p.evaluate(()=>document.documentElement.style.fontSize='200%');
   if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('Text-scale overflow');
   await p.evaluate(()=>scrollTo(0,0));await p.screenshot({path:'sidebar-overview-'+(fixture.includes('users')?'users':'dashboard')+'-'+width+'.png',fullPage:true});
   await p.evaluate(()=>document.documentElement.style.fontSize='');
  }
  await button.focus();if(!await button.evaluate(el=>el===document.activeElement))throw Error('Logout keyboard focus');
 }
 await p.goto('http://127.0.0.1:18767/.local/phase9b-dashboard-restricted.html');
 if(await p.locator('.pm-overview-workspace a').count())throw Error('Restricted overview exposed routes');
 if(errors.length)throw Error(errors.join('; '));await ctx.close();
 return 'PASS: shared D brand, prominent logout/CSRF/focus, permission-derived overview, desktop/mobile and 200% text scaling without page overflow. Contrast #18181b/#fafafa = 16.97:1.';
}
