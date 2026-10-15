(() => {
 const root=document.querySelector('.pm-imports-page main');if(!root)return;
 const links=[...root.querySelectorAll('.pm-import-steps a')];
 const panels=[...root.querySelectorAll('section[data-import-panel]')];
 root.querySelector('.pm-import-steps ol').setAttribute('role','tablist');
 function select(link,focus=false){
  links.forEach(a=>{const active=a===link;a.setAttribute('aria-selected',String(active));a.tabIndex=active?0:-1;});
  panels.forEach(p=>{p.hidden='#'+p.id!==link.hash;});
  if(focus)link.focus();
 }
 links.forEach((a,i)=>{a.id='pm-import-tab-'+i;a.setAttribute('role','tab');a.setAttribute('aria-controls',a.hash.slice(1));
  const panel=panels.find(p=>'#'+p.id===a.hash);panel.setAttribute('role','tabpanel');panel.setAttribute('aria-labelledby',a.id);
  a.addEventListener('click',e=>{e.preventDefault();select(a);});
  a.addEventListener('keydown',e=>{let n=i;if(e.key==='ArrowRight')n=(i+1)%links.length;else if(e.key==='ArrowLeft')n=(i+links.length-1)%links.length;else if(e.key==='Home')n=0;else if(e.key==='End')n=links.length-1;else if(e.key===' ')n=i;else return;e.preventDefault();select(links[n],true);});
 });
 const selected=links.find(a=>a.hash==='#pm-import-'+root.dataset.importPanel)||links[0];select(selected);
 const form=root.querySelector('[data-import-upload]'),output=root.querySelector('#pm-import-preview-result'),feedback=root.querySelector('[data-import-preview-feedback]');
 const input=form.querySelector('[name=workbook]');let busy=false;
 input.addEventListener('change',()=>{output.replaceChildren();feedback.textContent='';});
 form.addEventListener('submit',async e=>{
  if(e.submitter?.value!=='preview')return;e.preventDefault();if(busy)return;
  busy=true;const data=new FormData(form);data.set('action','preview');const buttons=[...form.querySelectorAll('button')];buttons.forEach(b=>b.disabled=true);input.disabled=true;form.setAttribute('aria-busy','true');feedback.classList.remove('error');feedback.textContent='Đang kiểm tra tệp…';output.replaceChildren();
  try{
   const response=await fetch(form.getAttribute('action') || window.location.href,{method:'POST',body:data,credentials:'same-origin',redirect:'follow'});
   const doc=new DOMParser().parseFromString(await response.text(),'text/html');
   const error=doc.querySelector('[data-pm-import-error]');
   if(error)throw Error(error.textContent.trim());
   const result=doc.querySelector('#pm-import-preview-result');
   if(!response.ok||!result||!result.querySelector('section'))throw Error('Không nhận được kết quả. Kiểm tra phiên đăng nhập và thử lại.');
   output.append(...[...result.childNodes].map(n=>document.importNode(n,true)));
   feedback.textContent='Đã kiểm tra. Tệp vẫn được giữ để lưu staging.';
  }catch(error){feedback.classList.add('error');feedback.textContent=error instanceof TypeError?'Không thể kết nối. Tệp vẫn được giữ, vui lòng thử lại.':error.message||'Không thể kiểm tra. Vui lòng thử lại.';}
  finally{busy=false;buttons.forEach(b=>b.disabled=false);input.disabled=false;form.removeAttribute('aria-busy');feedback.focus({preventScroll:true});}
 });
})();
