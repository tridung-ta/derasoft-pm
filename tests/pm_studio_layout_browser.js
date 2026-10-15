async original=>{
 const context=await original.context().browser().newContext();const page=await context.newPage();
 const cases=[['overview','.pm-work-stream','.pm-command-rail'],['users','.pm-people-filter','.pm-people-list'],['costs','.filters','.metrics'],['reports','main>section:first-of-type','main>section:nth-of-type(2)'],['allocations','.pm-allocation-controls','.pm-allocation-canvas'],['timesheets','.pm-time-entry','.pm-time-history'],['project-detail','.pm-project-team','.pm-project-board-area']];const checked=[];
 try{
  for(const width of [1440,390]){
   await page.setViewportSize({width,height:1000});
   for(const [key,left,right] of cases){
    await page.goto('http://127.0.0.1:18769/.local/review-'+key+'.html');
    const a=await page.locator(left).boundingBox(),b=await page.locator(right).boundingBox();
    if(width===1440&&a.x+a.width>b.x+2)throw Error('Not side by side '+key);
    if(width===390&&b.y<a.y+a.height-2)throw Error('Not stacked '+key);
    if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Overflow '+key);
    checked.push({key,width,layout:width===1440?'side-by-side':'stacked'});
   }
  }
  return {result:'PASS: new structural layouts across seven workspaces, desktop rails/mobile stack, no overflow.',checked};
 }finally{await context.close();}
}
