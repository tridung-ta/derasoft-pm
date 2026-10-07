<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());$s->assign(['error'=>['message'=>['<script>bad</script>']],'aScript'=>'admin.php']);$html=$s->fetch('admin/pm-login.tpl.html');
if(str_contains($html,'<style>')||str_contains($html,'<script>bad')||!str_contains($html,'role="alert"')||!str_contains($html,'pm-skip'))throw new RuntimeException('Login UI failed.');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-login.html',str_replace('<head>','<head><base href="/">',$html));
if(!str_contains($html,'class="pm-app pm-login-page"'))throw new RuntimeException('PM root scope missing');
if(in_array('--preview',$argv,true)){
    $s->assign(['pmNavigation'=>[
        ['url'=>'?op=pm','label'=>'Tổng quan','current'=>false],
        ['url'=>'?op=pmusers','label'=>'Nhân sự','current'=>true],
        ['url'=>'?op=pmprojects','label'=>'Dự án & công việc','current'=>false],
        ['url'=>'?op=pmtimesheets','label'=>'Chấm công','current'=>false],
        ['url'=>'?op=pmaudit','label'=>'Nhật ký','current'=>false],
        ['url'=>'?op=pmcosts','label'=>'Chi phí','current'=>false],
        ['url'=>'?op=pmallocations','label'=>'Phân bổ','current'=>false],
        ['url'=>'?op=pmreports','label'=>'Báo cáo','current'=>false],
        ['url'=>'?op=pmimports','label'=>'Import nhân sự','current'=>false]
    ],'op'=>'pmusers','csrfToken'=>'synthetic']);
    $nav=$s->fetch('admin/pm-navigation.tpl.html');
    file_put_contents(ROOT_PATH.'.local/phase9-theme-nav.html','<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="/"><link rel="stylesheet" href="css/pmui.css"><title>Theme navigation fixture</title></head><body class="pm-app">'.$nav.'<main id="pm-main" tabindex="-1"><h1>Điều hướng PM</h1><p>Dữ liệu giả để kiểm tra giao diện.</p></main></body></html>');
}
echo "PASS: login local CSS, escaped error/live alert and skip link.\n";
