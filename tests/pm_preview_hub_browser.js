async page=>{
 await page.setViewportSize({width:1440,height:960});await page.goto('http://127.0.0.1:18767/.local/workspace-review.html');const links=await page.locator('main section a').evaluateAll(items=>items.map(a=>a.href));if(links.length!==13)throw new Error('Preview hub missing screens');
 const results=[];for(const url of links){if(!url.startsWith('http://127.0.0.1:18767/.local/'))throw new Error('Preview link leaves local fixtures');const response=await page.request.get(url);if(response.status()!==200)throw new Error('Missing fixture '+url);results.push({page:url.split('/').pop(),status:response.status()});}
 for(const width of [1440,390]){await page.setViewportSize({width,height:960});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Hub overflow '+width);}
 return {result:'PASS: 13 local preview links HTTP 200, desktop/mobile without overflow',pages:results};
}