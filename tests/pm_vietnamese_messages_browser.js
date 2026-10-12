async page => {
 const context=await page.context().browser().newContext();
 const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 try{
  await p.goto('http://127.0.0.1:18767/.local/vi-notifications.html');
  for(const width of [1440,390]){
   await p.setViewportSize({width,height:850});
   if(!await p.locator('.pm-feedback-error').textContent().then(t=>t.includes('Cần kiểm tra')))throw new Error('Error label missing');
   if(!await p.locator('.pm-feedback-success').textContent().then(t=>t.includes('Đã hoàn tất')))throw new Error('Success label missing');
   const result=await p.locator('.pm-feedback-error').evaluate(e=>{const s=getComputedStyle(e);return {border:s.borderTopWidth,color:s.color,font:s.fontSize,overflow:document.documentElement.scrollWidth>innerWidth+1};});
   if(result.border!=='1px'||result.font!=='15px'||result.overflow)throw new Error('Unreadable feedback');
   const overlap=await p.evaluate(()=>{const a=document.querySelector('.pm-feedback-error').getBoundingClientRect(),b=document.querySelector('.pm-feedback-success').getBoundingClientRect();return a.bottom>b.top;});if(overlap)throw new Error('Success hides error');
   await p.screenshot({path:'vi-feedback-'+width+'.png',fullPage:true});
  }
  await p.locator('.pm-feedback-success button').click();
  if(await p.locator('.pm-feedback-success').count()||!await p.locator('.pm-feedback-error').isVisible())throw new Error('Dismissal affects error');
  const cases=[['required','','Vui lòng điền'],['email','bad','Email không đúng'],['number','9','nhỏ hơn hoặc bằng 8'],['phone','abc','Điện thoại chỉ']];
  for(const [id,value,expected] of cases){
   const result=await p.evaluate(({id,value})=>{const e=document.getElementById(id);e.value=value;e.dispatchEvent(new Event('input'));e.checkValidity();return e.validationMessage;},{id,value});
   if(!result.includes(expected))throw new Error(id+': '+result);
  }
  const preserved=await p.evaluate(()=>{const e=document.getElementById('required');e.dispatchEvent(new Event('input'));e.setCustomValidity('Lỗi riêng của trường');e.checkValidity();return e.validationMessage;});
  if(preserved!=='Lỗi riêng của trường')throw new Error('Existing custom error replaced');
  const corrected=await p.evaluate(()=>{const e=document.getElementById('email');e.value='ten@example.com';e.dispatchEvent(new Event('input'));return e.checkValidity();});
  if(!corrected)throw new Error('Localized validation stuck');
  for(const [url,method,status] of [['pm_ajax.php?op=pmtaskstatus','POST',401],['pm_ajax.php?op=missing','GET',404],['pm_ajax.php?op=pmtaskstatus','GET',405]]){
   const response=await context.request.fetch('http://127.0.0.1:18767/'+url,{method});const body=await response.json();
   if(response.status()!==status||!/[À-ỹ]/.test(body.error)||body.task)throw new Error('Endpoint message/status '+status);
  }
  // Browser-native failures on actual allocation JS; no application DB request.
  await p.clock.install();
  for(const malformed of [false,true]){
   await p.route('**/pm_ajax.php?**',r=>malformed?r.fulfill({body:'not-json',contentType:'application/json'}):r.abort('failed'));
   await p.goto('http://127.0.0.1:18767/.local/phase7-preview.html');
   await p.clock.fastForward(61000);
   await p.waitForTimeout(150);
   const text=await p.locator('#allocation-poll-status').textContent();
   if(!text.includes(malformed?'Phản hồi từ máy chủ không hợp lệ':'Không thể kết nối máy chủ'))throw new Error('Native failure leaked: '+text);
   await p.unrouteAll();
  }
  await p.goto('http://127.0.0.1:18767/maintenance.html');
  for(const width of [1440,390]){await p.setViewportSize({width,height:850});if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Maintenance overflow');await p.screenshot({path:'vi-maintenance-'+width+'.png',fullPage:true});}
  if(!await p.locator('h1').textContent().then(t=>t==='Website đang bảo trì'))throw new Error('Maintenance Vietnamese');
  if(errors.length)throw new Error(errors.join(';'));
  return 'PASS: Vietnamese native validation/recovery/custom-error preservation; real AJAX401/404/405 beforeDB; allocation network/JSON failures; maintenance1440/390. DB/authenticated saves NOT RUN.';
 }finally{await context.close();}
}