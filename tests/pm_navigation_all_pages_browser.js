async page=>{
 const context=await page.context().browser().newContext();const p=await context.newPage();const errors=[];p.on('pageerror',e=>errors.push(e.message));
 const fixtures=['phase9-dashboard.html','phase9-profile.html','phase9-password.html','phase9-team.html','phase9-users-1.html','phase9-projects-1.html','phase9-timesheets-1.html','phase9-audit-1.html','phase6-preview.html','phase7-preview.html','phase9b-import-initial.html','phase9b-reports-hours.html'];
 try{
  for(const width of [1440,390]){
   await p.setViewportSize({width,height:900});
   for(const fixture of fixtures){
    await p.goto('http://127.0.0.1:18767/.local/'+fixture);
    if(await p.locator('.pm-work-sidebar').count()!==0||await p.locator('.pm-header-controls').count()!==1||await p.locator('.pm-work-topbar').count()!==1)throw new Error('Missing/duplicate shared shell: '+fixture);
    if(!await p.locator('.pm-user-menu summary').isVisible())throw new Error('Account hidden: '+fixture);
    if(await p.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Overflow: '+fixture);if(await p.locator('h1').first().evaluate(e=>e.getBoundingClientRect().top)<80)throw new Error('Title overlaps controls');
    const button=p.locator('.pm-work-menu summary');if(!await button.isVisible())throw new Error('Navigation toggle hidden: '+fixture);
    const accountRect=await p.locator('.pm-user-menu summary').boundingBox();const navRect=await button.boundingBox();
    if(navRect.y<0||navRect.y+navRect.height>64||Math.abs(navRect.x-(width>640?24:16))>2)throw new Error('Navigation not inside topbar');
    if(await p.locator('.pm-module-nav').isVisible())throw new Error('Menu not initially hidden');
    await button.focus();await p.keyboard.press('Enter');if(!await p.locator('.pm-module-nav').isVisible())throw new Error('Header menu did not open');
    await p.keyboard.press('Escape');if(await p.locator('.pm-module-nav').isVisible()||!await button.evaluate(e=>e===document.activeElement))throw new Error('Escape/focus');
    await button.click();if(!await p.locator('.pm-module-nav').isVisible())throw new Error('Pointer open');const panelRect=await p.locator('.pm-module-nav').boundingBox();if(panelRect.x<0||panelRect.x+panelRect.width>width)throw new Error('Open menu clipped');await p.mouse.click(width-12,80);if(await p.locator('.pm-module-nav').isVisible())throw new Error('Outside close');
    await p.locator('.pm-user-menu summary').click();
    if(!await p.locator('button.pm-logout').isVisible())throw new Error('Logout hidden: '+fixture);
    const logout=p.locator('button.pm-logout').locator('..');
    if(await logout.getAttribute('method')!=='post'||await logout.locator('input[name=csrf_token]').count()!==1)throw new Error('Logout POST/CSRF changed');
   }
  }
  if(errors.length)throw new Error(errors.join(';'));
  return 'PASS: 12 PM screens x desktop/mobile no sidebar; navigation inside topbar/menu/logout POST+CSRF, no horizontal overflow. Synthetic render fixtures; production and authenticated DB NOT RUN.';
 }finally{await context.close();}
}