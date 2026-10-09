async page => {
 const context=await page.context().browser().newContext({viewport:{width:1440,height:1000}});
 const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 try{
  await p.goto('http://127.0.0.1:18767/.local/workspace-dashboard-managed.html');
  if(await p.locator('.pm-dashboard-panel').count()!==3)throw new Error('Management panels missing');
  if(!(await p.locator('.pm-dashboard-budgets').textContent()).includes('25.00% ngân sách'))throw new Error('Budget presentation');
  if(await p.locator('.pm-dashboard-row script').count())throw new Error('Unescaped project name');
  const task=p.locator('.pm-dashboard-task-warnings');await task.locator('summary').focus();await p.keyboard.press('Enter');
  if(!await task.evaluate(e=>e.open)||!(await task.textContent()).includes('(Lịch sử)'))throw new Error('Historical warning/keyboard');
  if(await p.locator('[data-pm-success],.pm-toast').count())throw new Error('Reminder shown as success');
  for(const width of [360,390,768,1440]){
   await p.setViewportSize({width,height:1000});
   if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Dashboard overflow '+width);
  }
  await p.setViewportSize({width:390,height:1000});await p.evaluate(()=>document.documentElement.style.fontSize='200%');
  if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('200% overflow');
  await p.evaluate(()=>document.documentElement.style.fontSize='');
  for(const width of [1440,390]){
   await p.setViewportSize({width,height:1000});await p.screenshot({path:'dashboard-managed-'+width+'.png',fullPage:true});
  }
  // This real unauthenticated endpoint path exits before DB bootstrap. No cookies/DB writes.
  const response=await context.request.post('http://127.0.0.1:18767/pm_ajax.php?op=pmtaskstatus',
   {form:{project_id:'1',task_id:'1',status:'done',csrf_token:'synthetic'}});
  const result=await response.json();
  if(response.status()!==401||!result.error||result.task)throw new Error('Unauthenticated endpoint guard');
  if(errors.length)throw new Error(errors.join(';'));
  return 'PASS: managed dashboard synthetic data, budget/scope/history/escaping, keyboard, 4 viewports/200%, real unauthenticated task AJAX returns 401 before DB. Authenticated saving and SQL execution NOT RUN.';
 }finally{await context.close();}
}
