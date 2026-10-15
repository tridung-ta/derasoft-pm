async page=>{
 const faults=[];page.on('pageerror',e=>faults.push(e.message));
 for(const width of [1440,390]){
  await page.setViewportSize({width,height:900});
  await page.goto('http://127.0.0.1:18767/.local/import-password-feedback.html');
  await page.route('**/.local/import-password-feedback.html',async route=>{if(route.request().method()==='POST')await route.fulfill({status:200,contentType:'text/plain',body:'Fixture POST accepted'});else await route.continue();});
  for(const row of ['2','3']){
   await page.goto('http://127.0.0.1:18767/.local/import-password-feedback.html');
   const form=page.locator('form[data-pm-provision-password]').filter({has:page.locator('[name=source_row][value="'+row+'"]')});
   await form.locator('[name=password]').fill('FixtureOnly12345');
   const request=page.waitForRequest(r=>r.url().endsWith('/.local/import-password-feedback.html')&&r.method()==='POST');
   await form.locator('button').click();
   const body=new URLSearchParams((await request).postData());
   if(body.get('source_row')!==row||body.get('password')!=='FixtureOnly12345'||body.get('action')!=='provision_password'||body.get('csrf_token')!=='test-token')throw Error('Row/submitter forwarding');
  }
  await page.goto('http://127.0.0.1:18767/.local/import-password-feedback.html');
  const form=page.locator('[data-pm-provision-password]').first(),input=form.locator('[name=password]');
  await input.fill('1234567');await form.locator('button').click();
  if(!await form.locator('[data-pm-password-error]').isVisible()||await form.locator('button').isDisabled())throw Error('Invalid feedback or form locked');
  await input.fill('12345678');if(!await input.evaluate(e=>e.checkValidity()))throw Error('8 byte password rejected');
  await input.fill('ấ'.repeat(3));if(!await input.evaluate(e=>e.checkValidity()))throw Error('UTF8 valid8-72 byte password rejected by character minimum');
  await input.fill('ấ'.repeat(25));if(await input.evaluate(e=>e.checkValidity()))throw Error('UTF8 >72 bytes accepted');
  await page.goto('http://127.0.0.1:18767/.local/import-password-success.html');
  if(await page.locator('.pm-toast').count()!==1||await page.locator('form[ data-pm-provision-password] p.notice').count()!==1)throw Error('Success feedback');
  await page.screenshot({path:'.local/import-password-success-'+width+'.png'});
  await page.goto('http://127.0.0.1:18767/.local/import-password-error.html');
  if(!await page.locator('[data-pm-import-error]').evaluate(e=>document.activeElement===e))throw Error('Server error not focused');
  await page.unroute('**/.local/import-password-feedback.html');
 }
 if(faults.length)throw Error(faults.join(';'));
 return 'PASS: two distinct row POSTs with same synthetic password, CSRF/submitter preserved, byte bounds/native feedback/form unlock, success toast+row status, focused server error,1440/390. Requests intercepted; no DB writes.';
}
