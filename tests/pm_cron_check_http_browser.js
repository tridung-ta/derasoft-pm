async page => {
 const url='http://127.0.0.1:18767/editorial-category-scope-smoke.php';
 for(const item of [
  {method:'get',url,status:401,text:'Vui lòng đăng nhập'},
  {method:'post',url,status:405,text:'Chỉ hỗ trợ'},
  {method:'get',url:url+'?file=pm-reminders.php',status:400,text:'không nhận tham số'},
  {method:'get',url:'http://127.0.0.1:18767/modules/ajax/pmcroncheck.module.php',status:404,text:''},
 ]){
  const response=await page.request[item.method](item.url);
  if(response.status()!==item.status)throw Error('Unexpected status '+response.status());
  const body=await response.text();if(!body.includes(item.text)||body.includes(': PASS')||body.includes('SELECT '))throw Error('Unexpected protected response');
  if(item.status!==404){if(!response.headers()['cache-control'].includes('no-store'))throw Error('Cache policy');if(!response.headers()['content-type'].includes('text/plain'))throw Error('Content type');}
 }
 return 'PASS: real browser endpoint anonymous401/method405/parameter400/direct-module404, no-cache and no result leak. No DB connected. Authenticated Admin/live-role SQL not tested.';
}
