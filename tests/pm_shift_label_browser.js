async page=>{
 const context=await page.context().browser().newContext();const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 try{
  for(const width of [1440,390]){
   await p.setViewportSize({width,height:1000});await p.goto('http://127.0.0.1:18767/.local/pm-shift-choice-preview.html');
   const select=p.locator('select[name=shift_label]');const values=await select.locator('option').evaluateAll(xs=>xs.map(x=>x.value));
   if(JSON.stringify(values)!==JSON.stringify(['','Ca sáng','Ca chiều','Ca tối','Ca linh hoạt']))throw new Error('Choices/order');
   if(await select.evaluate(e=>e.checkValidity()))throw new Error('Empty shift accepted');
   await select.selectOption('Ca chiều');if(!await select.evaluate(e=>e.checkValidity()))throw new Error('Choice invalid');
   if(await p.locator('.pm-shift-date').evaluate(e=>getComputedStyle(e).whiteSpace)!=='nowrap')throw new Error('Date wraps');
   if(!await p.locator('.pm-shift-badge').textContent().then(t=>t==='Ca sáng'))throw new Error('Badge');
   if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Shift overflow');
   await p.screenshot({path:'shift-choice-'+width+'.png',fullPage:true});
   await p.goto('http://127.0.0.1:18767/.local/pm-shift-legacy-preview.html');
   if(await p.locator('select[name=shift_label]').inputValue()!=='<script>x</script>')throw new Error('Legacy value changed');
   const details=p.locator('.pm-shift-legacy');await details.locator('summary').focus();await p.keyboard.press('Enter');
   if(!await details.locator('span').textContent().then(t=>t==='<script>x</script>')||await details.locator('script').count())throw new Error('Legacy not escaped');
  }
  if(errors.length)throw new Error(errors.join(';'));
  return 'PASS: fixed shift choices/order/required/badge/date, legacy edit preservation/escaped details keyboard,1440/390 no overflow; no POST/DB.';
 }finally{await context.close();}
}