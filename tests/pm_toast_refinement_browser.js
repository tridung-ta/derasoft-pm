async page => {
 const context=await page.context().browser().newContext();const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 try{
  for(const width of [1440,390]){
   await p.setViewportSize({width,height:850});await p.goto('http://127.0.0.1:18767/.local/pm-toast-review.html');
   const toast=p.locator('.pm-toast');
   const details=await toast.evaluate(e=>{const s=getComputedStyle(e),b=e.querySelector('button'),r=b.getBoundingClientRect();return {font:s.fontSize,line:s.lineHeight,buttonWidth:r.width,buttonHeight:r.height,border:getComputedStyle(b).borderTopWidth,svg:!!b.querySelector('svg'),overflow:document.documentElement.scrollWidth>innerWidth+1,height:e.getBoundingClientRect().height,centerX:e.getBoundingClientRect().left+e.getBoundingClientRect().width/2,centerY:e.getBoundingClientRect().top+e.getBoundingClientRect().height/2,role:e.getAttribute('role')};});
   if(details.font!==(width<640?'16px':'18px')||details.buttonWidth!==44||details.buttonHeight!==44||details.border!=='0px'||!details.svg||details.overflow||details.height<110||Math.abs(details.centerX-width/2)>1||Math.abs(details.centerY-425)>1||details.role!=='status')throw new Error(JSON.stringify(details));
   await p.screenshot({path:'toast-review-'+width+'.png',fullPage:true});
   await toast.locator('button').focus();await p.keyboard.press('Enter');if(await toast.count())throw new Error('Close did not work');
   await p.goto('http://127.0.0.1:18767/');
   if(!await p.locator('.status h2').textContent().then(t=>t==='Sẵn sàng làm việc'))throw new Error('Launch title missing');
   if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Launch overflow');
  }
  if(errors.length)throw new Error(errors.join(';'));
  return 'PASS: centered large toast1440/390,18px desktop/16px mobile,44px SVG close target,no border/no overflow, keyboard dismissal,status role; real launch title Vietnamese; no DB.';
 }finally{await context.close();}
}