async page => {
 const faults=[];page.on('pageerror',e=>faults.push(e.message));
 for(const width of [1440,390]){
  await page.setViewportSize({width,height:900});
  await page.goto('http://127.0.0.1:18767/.local/phase9b-import-initial.html');
  if(await page.locator('[role=tabpanel]:visible').count()!==1)throw Error('Initial panel');
  await page.getByRole('tab').nth(1).click();
  await page.locator('[name=workbook]').setInputFiles({name:'personnel.xlsx',mimeType:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',buffer:Buffer.from('fixture-not-real-workbook')});
  let mode='preview',body='';
  await page.route('**/.local/phase9b-import-initial.html',async r=>{
   if(r.request().method()!=='POST')return r.continue();body=r.request().postData();
   if(mode==='preview')return r.fulfill({status:200,contentType:'text/html',body:'<div id="pm-import-preview-result"><section><h2>Fixture preview</h2></section></div>'});
   if(mode==='error')return r.fulfill({status:403,contentType:'text/html',body:'<p data-pm-import-error>Fixture expired session</p>'});
   if(mode==='login')return r.fulfill({status:200,contentType:'text/html',body:'<form>Sign in</form>'});
   if(mode==='network')return r.abort();
   return r.fulfill({status:200,contentType:'text/plain',body:'Stage request intercepted'});
  });
  for(const next of ['preview','error','login','network']){
   mode=next;await page.locator('[value=preview]').click();
   await page.waitForFunction(()=>!document.querySelector('[data-import-upload]').hasAttribute('aria-busy'));
   if(await page.locator('[name=workbook]').evaluate(e=>e.files[0]?.name)!=='personnel.xlsx')throw Error('File lost');
   if(!body.includes('name="action"')||!body.includes('preview')||!body.includes('test-token'))throw Error('Preview payload');
   if(next==='preview'&&!await page.getByText('Fixture preview',{exact:true}).isVisible())throw Error('Preview not shown');
   if(next!=='preview'&&!(await page.locator('[data-import-preview-feedback]').textContent()))throw Error('Error absent');
  }
  await page.getByRole('tab').nth(2).click();if(await page.locator('[role=tabpanel]:visible').count()!==1)throw Error('Tab visibility');
  await page.getByRole('tab').nth(2).press('Home');if(await page.getByRole('tab').first().getAttribute('aria-selected')!=='true')throw Error('Keyboard');
  await page.getByRole('tab').nth(1).click();
  await page.screenshot({path:'.local/import-tabs-'+width+'.png',fullPage:true});
  mode='stage';const request=page.waitForRequest(r=>r.method()==='POST');await page.locator('[value=stage]').click();await request;
  if(!body.includes('personnel.xlsx')||!body.includes('stage')||!body.includes('test-token'))throw Error('Stage lost file/action/token');
  await page.unroute('**/.local/phase9b-import-initial.html');
 }
 if(faults.length)throw Error(faults.join(';'));return 'PASS: desktop/mobile separate tabs, keyboard, Preview file retention, error/session/network recovery, Stage multipart payload; fixture requests only.';
}
