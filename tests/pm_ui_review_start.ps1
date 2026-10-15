$ErrorActionPreference='Stop'
$reviewRoot=Split-Path -Parent $PSScriptRoot
Push-Location $reviewRoot
try {
    $php=Join-Path $reviewRoot '.tools/php83/php.exe'
    if(!(Test-Path -LiteralPath '.local/phase9-dashboard.html')){
        & powershell -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1
        if($LASTEXITCODE -ne 0){throw 'Local fixture generation failed'}
    }
    & $php tests/pm_ui_review_preview.php
    if($LASTEXITCODE -ne 0){throw 'Review preview generation failed'}
    Write-Output 'Open http://127.0.0.1:18769/ — local fixtures only, POST disabled.'
    & $php -S 127.0.0.1:18769 -t . tests/pm_ui_review_router.php
}finally{Pop-Location}
