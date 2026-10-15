async page=>{
 const context=await page.context().browser().newContext();const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 const keys=['launch','login','overview','profile','password','team','users','users-restricted','projects','project-detail','project-restricted','timesheets','timesheets-employee','timesheets-error','allocations','costs','costs-empty','reports','reports-costs','reports-users','reports-projects','reports-tasks','reports-error','imports','imports-validation','imports-apply','notifications','notifications-empty','audit'];
 const results=[];
 try{
  for(const width of [1440,390]){
   await p.setViewportSize({width,height:960});
   for(const key of keys){
    const response=await p.goto('http://127.0.0.1:18769/.local/review-'+key+'.html');await p.evaluate(()=>document.fonts.ready);
    const stats=await p.evaluate(()=>{
     const visible=e=>!!(e.getClientRects().length&&getComputedStyle(e).visibility!=='hidden');
     const labels=[...document.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=button]),select,textarea')].filter(visible).filter(e=>!e.labels?.length&&!e.getAttribute('aria-label')&&!e.getAttribute('aria-labelledby')).map(e=>e.name);
     const heading=document.querySelector('h1'),hs=heading?getComputedStyle(heading):null;
     return{overflow:document.documentElement.scrollWidth>innerWidth+1,labels,h1:hs?.fontSize,font:hs?.fontFamily,top:heading?.getBoundingClientRect().top};
    });
    results.push({key,width,status:response.status(),...stats});
    await p.screenshot({path:'.local/ui-review-'+key+'-'+width+'.png'});
   }
  }
  const post=await p.request.post('http://127.0.0.1:18769/admin.php',{data:'action=save'});
  const php=await p.request.get('http://127.0.0.1:18769/admin.php');
  if(results.some(r=>r.status!==200||r.overflow||r.labels.length)||errors.length||post.status()!==405||php.status()!==404)throw Error(JSON.stringify({results,errors,post:post.status(),php:php.status()}));
  return {results,errors,writeBlocked:post.status()===405,phpBlocked:php.status()===404};
 }finally{await context.close();}
}
