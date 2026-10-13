async page => {
 const context=await page.context().browser().newContext({viewport:{width:1440,height:960}});
 const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 const base='http://127.0.0.1:18767/.local/';
 try{
  await p.goto(base+'phase9-users-1.html');
  const filter=p.locator('form[data-pm-filter-memory]').first();
  const department=filter.locator('select[name=department_id]');
  const options=await department.locator('option').evaluateAll(items=>items.map(i=>i.value).filter(Boolean));
  if(!options.length)throw new Error('Fixture missing departments');
  await department.selectOption(options[0]);await filter.locator('[name=q]').fill('private search');
  await filter.evaluate(form=>{form.addEventListener('submit',event=>event.preventDefault(),{once:true});form.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));});
  const saved=await p.evaluate(()=>JSON.parse(sessionStorage.getItem('pm-filter:pmusers')));
  if(saved.department_id!==options[0]||'q' in saved||'csrf_token' in saved)throw new Error('Stored unexpected filter data');
  await p.reload();const restore=p.getByRole('button',{name:'Dùng bộ lọc lần trước'});await restore.click();
  if(await department.inputValue()!==options[0])throw new Error('Filter restoration failed');
  await p.getByRole('button',{name:'Quên bộ lọc đã nhớ'}).click();if(await p.evaluate(()=>sessionStorage.getItem('pm-filter:pmusers'))!==null)throw new Error('Memory not cleared');if(await p.getByRole('button',{name:'Dùng bộ lọc lần trước'}).count()||await p.getByRole('button',{name:'Đã điền bộ lọc — chưa áp dụng'}).count())throw new Error('Forgotten filter still restorable');
  const menu=p.locator('.pm-user-menu');await menu.locator('summary').click();await p.keyboard.press('Escape');if(await menu.evaluate(e=>e.open))throw new Error('Account Escape failed');
  await p.evaluate(()=>{const toast=document.createElement('p');toast.className='message';toast.textContent='Đã lưu';document.querySelector('main').append(toast);});
  // Reload a fixture with a server-rendered notice to exercise initialization.
  const html=await (await p.request.get(base+'phase9-users-1.html')).text();
  await p.route('**/phase9-users-1.html',route=>route.fulfill({contentType:'text/html',body:html.replace('<main ', '<div class="msg" role="status" data-pm-success>Đã lưu nhân sự</div><main ')}));
  await p.reload();if(await p.locator('.pm-toast').count()!==1)throw new Error('Notice not promoted to toast');await p.getByRole('button',{name:'Đóng thông báo'}).click();if(await p.locator('.pm-toast').count())throw new Error('Toast not dismissed');
  const submission=await p.evaluate(()=>{
   const save=document.createElement('form');save.method='post';save.innerHTML='<button type="submit" name="action" value="save">Lưu</button>';document.querySelector('main').append(save);
   const button=save.querySelector('button');save.dispatchEvent(new SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:button}));
   const pending=button.disabled&&save.getAttribute('aria-busy')==='true'&&save.querySelector('input[data-pm-submitter]')?.value==='save';
   const duplicate=!save.dispatchEvent(new SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:button}));
   window.dispatchEvent(new Event('pageshow'));const reset=!button.disabled&&!save.hasAttribute('aria-busy');
   const download=document.createElement('form');download.method='post';download.innerHTML='<input name="action" value="export"><button type="submit">Xuất</button>';document.querySelector('main').append(download);download.dispatchEvent(new SubmitEvent('submit',{bubbles:true,cancelable:true,submitter:download.querySelector('button')}));
   const exportReady=!download.querySelector('button').disabled&&!download.hasAttribute('aria-busy');save.remove();download.remove();return {pending,duplicate,reset,exportReady};
  });
  if(Object.values(submission).some(value=>!value))throw new Error('Submit guard/download lifecycle '+JSON.stringify(submission));
  const sticky=await p.locator('thead th').first().evaluate(e=>getComputedStyle(e).position);if(sticky!=='sticky')throw new Error('Table header not sticky');
  for(const width of [390,768,1440]){await p.setViewportSize({width,height:960});if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Overflow '+width);if(await p.locator('.pm-work-menu').evaluate(e=>e.open)!==false)throw new Error('Menu breakpoint '+width);}
  if(errors.length)throw new Error(errors.join(';'));
  return {result:'PASS: filter restore/forget, excludes search text/CSRF, account Escape, success toast/dismiss, submit double guard/submitter/BFCache reset/export reusable, sticky headers, 390/768/1440 without page overflow. No DB access or mutations.'};
 }finally{await context.close();}
}