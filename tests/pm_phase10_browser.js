async page => {
    const base='http://127.0.0.1:18770';
    const fixture=await (await page.request.get(base+'/.local/phase10/fixture.json')).json();
    if(fixture.database!=='derasoft_pm_phase10_20261004')throw new Error('Fixture isolation required.');
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    async function login(role){
        await page.context().clearCookies();await page.goto(base+'/admin.php');
        const before=(await page.context().cookies()).find(c=>c.name==='PHPSESSID')?.value;
        await page.locator('input[name="username"]').fill('phase10_'+role.toLowerCase());
        await page.locator('input[name="password"]').fill(fixture.password);
        await page.locator('button[type="submit"]').click();await page.waitForLoadState();
        if(await page.locator('input[name="username"]').count())throw new Error('Login failed '+role);
        const after=(await page.context().cookies()).find(c=>c.name==='PHPSESSID')?.value;
        if(!before||!after||before===after)throw new Error('Session not regenerated '+role);
    }
    for(const role of ['ADMIN','PM','HR','EMPLOYEE']){
        await login(role);
        for(const op of ['pm','pmusers','pmprojects','pmtimesheets','pmaudit','pmcosts','pmallocations','pmreports','pmimports']){
            const response=await page.goto(base+'/admin.php?op='+op);
            const denied=(op==='pmusers'&&role==='EMPLOYEE')||(op==='pmprojects'&&role==='HR')||(op==='pmcosts'&&['HR','EMPLOYEE'].includes(role))||(op==='pmaudit'&&role==='EMPLOYEE')||(op==='pmallocations'&&role==='HR')||(op==='pmimports'&&role!=='ADMIN');
            if(response.status()!==(denied?403:200))throw new Error(role+' '+op+' unexpected HTTP '+response.status());
            if(!denied){
                if(await page.locator('a[href*="op=logout"]').count())throw new Error('GET logout remains');
                for(const width of [390,1440]){await page.setViewportSize({width,height:900});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw new Error('Overflow '+role+' '+op);}
            }
        }
        await page.goto(base+'/admin.php?op=pm');
        if(role==='ADMIN'||role==='EMPLOYEE')await page.screenshot({path:'phase10-'+role.toLowerCase()+'.png',fullPage:true});
        await page.locator('.sidebar-footer button.logout').click();await page.waitForLoadState();
        await page.goto(base+'/admin.php?op=pm');if(!await page.locator('input[name="username"]').count())throw new Error('Logout did not clear auth '+role);
    }
    await login('ADMIN');await page.goto(base+'/admin.php?op=pmprojects');
    const project=page.locator('form').filter({has:page.locator('input[name="action"][value="project_save"]')});
    await project.locator('[name="code"]').fill('P10-'+Date.now());await project.locator('[name="name"]').fill('Phase10 browser project');
    await project.locator('[name="manager_id"]').selectOption(String(fixture.ids.PM));await project.locator('button').click();await page.waitForLoadState();
    const projectId=await page.locator('input[name="project_id"]').first().inputValue();if(!(Number(projectId)>0))throw new Error('Project not created');
    const member=page.locator('form').filter({has:page.locator('input[name="member_active"][value="1"]')});
    await member.locator('[name="member_id"]').selectOption(String(fixture.ids.EMPLOYEE));await member.locator('button').click();await page.waitForLoadState();
    await login('PM');await page.goto(base+'/admin.php?op=pmprojects&project_id='+projectId);
    const task=page.locator('form').filter({has:page.locator('input[name="task_id"][value="0"]')});
    await task.locator('[name="name"]').fill('Phase10 browser task');await task.locator('[name="assignee_id"]').selectOption(String(fixture.ids.EMPLOYEE));await task.locator('[name="estimated_hours"]').fill('10.00');await task.locator('button').click();await page.waitForLoadState();
    if(!await page.getByRole('heading',{name:'Phase10 browser task',exact:true}).count())throw new Error('Task not created');
    await login('EMPLOYEE');await page.goto(base+'/admin.php?op=pmtimesheets');
    const timesheet=page.locator('form').filter({has:page.locator('input[name="action"][value="save"]')});
    const taskOption=timesheet.locator('select[name="task_id"] option').filter({hasText:'Phase10 browser task'});await timesheet.locator('[name="task_id"]').selectOption(await taskOption.getAttribute('value'));
    await timesheet.locator('[name="shift_label"]').fill('P10');await timesheet.locator('[name="hours"]').fill('9.00');await timesheet.locator('button').click();await page.waitForLoadState();
    if(!await page.getByText('P10',{exact:false}).count())throw new Error('Timesheet not saved');
    await page.goto(base+'/admin.php?op=pmreports');const download=page.waitForEvent('download');await page.getByRole('button',{name:'Xuất XLSX'}).click();await (await download).saveAs('phase10-authenticated.xlsx');
    if(errors.length)throw new Error(errors.join(';'));
    return 'PASS: authenticated four-role route matrix, real login SID rotation/logout, desktop/mobile, Admin project/membership -> PM task -> Employee timesheet and XLSX download, isolated DB only. Fixture cleanup verification must run afterward.';
}
