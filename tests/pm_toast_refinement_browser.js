async page => {
 const context=await page.context().browser().newContext();const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 try{
  await p.clock.install({time:new Date('2026-10-13T00:00:00Z')});await p.clock.pauseAt(new Date('2026-10-13T00:00:00Z'));
  for(const width of [1440,390]){
   await p.setViewportSize({width,height:850});await p.goto('http://127.0.0.1:18767/.local/pm-toast-review.html');
   const toast=p.locator('.pm-toast');
   const details=await toast.evaluate(e=>{const s=getComputedStyle(e),b=e.querySelector('button'),r=b.getBoundingClientRect();return {font:s.fontSize,line:s.lineHeight,buttonWidth:r.width,buttonHeight:r.height,border:getComputedStyle(b).borderTopWidth,svg:!!b.querySelector('svg'),marker:!!e.querySelector('.pm-toast-icon svg'),overflow:document.documentElement.scrollWidth>innerWidth+1,height:e.getBoundingClientRect().height,centerX:e.getBoundingClientRect().left+e.getBoundingClientRect().width/2,top:e.getBoundingClientRect().top,role:e.getAttribute('role')};});
   if(details.font!=='15px'||details.buttonWidth!==44||details.buttonHeight!==44||details.border!=='0px'||!details.svg||!details.marker||details.overflow||details.height<80||Math.abs(details.centerX-width/2)>1||Math.abs(details.top-80)>1||details.role!=='status')throw new Error(JSON.stringify(details));
   await p.screenshot({path:'toast-review-'+width+'.png',fullPage:true});
   await toast.locator('button').focus();await p.keyboard.press('Enter');if(await toast.count())throw new Error('Close did not work');
   await p.goto('http://127.0.0.1:18767/.local/pm-toast-review.html');
   await p.clock.fastForward(9999);if(await toast.count()!==1)throw new Error('Dismissed before10s');
   await p.clock.fastForward(1);if(await toast.count())throw new Error('Still visible after10s');
   await p.goto('http://127.0.0.1:18767/.local/vi-notifications.html');await p.clock.fastForward(10000);
   if(await p.locator('.pm-feedback-success').count()||!await p.locator('.pm-feedback-error').isVisible())throw new Error('Error expired or success persists');
   await p.goto('http://127.0.0.1:18767/');
   if(!await p.locator('.status h2').textContent().then(t=>t==='Sẵn sàng làm việc'))throw new Error('Launch title missing');
   if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Launch overflow');
  }
  if(errors.length)throw new Error(errors.join(';'));
  return 'PASS: top-center large toast1440/390,expires exactly10s,neutral white banner with success SVG,15px,44px SVG close target,no border/no overflow, keyboard dismissal,status role; real launch title Vietnamese; no DB.';
 }finally{await context.close();}
}