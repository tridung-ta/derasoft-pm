async (page) => {
 const context=await page.context().browser().newContext();const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 for(const fixture of ['phase11-tasks.html','phase9b-reports-hours.html','phase11-users.html','phase11-projects.html']){
  await p.goto('http://127.0.0.1:18767/.local/'+fixture);await p.evaluate(()=>document.fonts.ready);
  if(fixture.includes('tasks')&&await p.locator('select[name="mode"] option[value="tasks"]').count()!==1)throw Error('Tasks option missing');
  const role=p.locator('form[method="get"] select[name="role_code"]');if(await role.count()!==1)throw Error('Role filter misplaced');
  for(const width of [1440,390,720]){await p.setViewportSize({width,height:1000});if(width===720)await p.evaluate(()=>document.documentElement.style.fontSize='200%');if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('Report overflow');await p.screenshot({path:'phase11-'+fixture+'-'+width+'.png',fullPage:true});await p.evaluate(()=>document.documentElement.style.fontSize='');}
  if(await p.locator('script:not([src])').count())throw Error('Unexpected inline script');
 }
 if(errors.length)throw Error(errors.join('; '));await context.close();return 'PASS: task/hour reports role filter, desktop/mobile/text scaling and no pageerror.';
}
