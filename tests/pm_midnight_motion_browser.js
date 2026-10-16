async page=>{
 const results=[];
 for(const reduced of ['no-preference','reduce']){
  await page.emulateMedia({reducedMotion:reduced});
  await page.setViewportSize({width:1440,height:960});
  await page.goto('http://127.0.0.1:18769/.local/review-login.html');
  const button=page.locator('button.submit');
  await button.hover();
  const sweep=await button.evaluate(e=>{const s=getComputedStyle(e,'::after');return {display:s.display,duration:s.transitionDuration,pointer:s.pointerEvents}});
  if(reduced==='reduce'&&sweep.display!=='none')throw Error('Reduced motion must remove sweep');
  if(reduced==='no-preference'&&(sweep.duration!=='0.8s'||sweep.pointer!=='none'))throw Error('Sweep timing or hit testing regression');
  await page.goto('http://127.0.0.1:18769/.local/review-projects.html');
  const card=page.locator('.pm-project-card').first();await card.hover();
  await card.evaluate(async e=>{await Promise.all(e.getAnimations().map(a=>a.finished.catch(()=>{})))});
  const transform=await card.evaluate(e=>getComputedStyle(e).transform);
  if(reduced==='reduce'&&transform!=='none')throw Error('Reduced motion must remove lift');
  if(reduced==='no-preference'&&!transform.includes('-6'))throw Error('Card lift missing');
  results.push({reduced,sweep,transform});
 }
 await page.emulateMedia({reducedMotion:'no-preference'});
 return {result:'PASS: one hover sweep, pointer-safe overlay, 6px card lift and reduced motion',results};
}
