async page => {
    await page.goto('http://127.0.0.1:18767/.local/phase9-users-1.html');
    const form=page.locator('form[data-pm-confirm]').first();
    if(!await form.count())throw new Error('Missing confirmation form.');
    // Intercept the fixture POST; no application mutation is sent.
    let posts=0;
    await page.route('**/.local/phase9-users-1.html',async route=>{
        if(route.request().method()!=='POST')throw new Error('Unexpected fixture request.');
        posts++;await route.fulfill({contentType:'text/html',body:'<p>Fixture POST received</p>'});
    });
    await page.evaluate(()=>{window.confirm=()=>false;});
    await form.locator('button[type="submit"],button:not([type])').first().click();
    if(posts!==0)throw new Error('Cancelled confirmation submitted.');
    await page.evaluate(()=>{window.confirm=()=>true;});
    await form.locator('button[type="submit"],button:not([type])').first().click();
    await page.waitForLoadState();
    if(posts!==1)throw new Error('Accepted confirmation did not submit exactly once.');
    await page.unroute('**/.local/phase9-users-1.html');
    return 'PASS: CSP-compatible confirmation handler cancel/accept with mocked confirm result; intercepted synthetic POST only.';
}
