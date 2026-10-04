<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());$s->assign(['error'=>['message'=>['<script>bad</script>']],'aScript'=>'admin.php']);$html=$s->fetch('admin/pm-login.tpl.html');
if(str_contains($html,'<style>')||str_contains($html,'<script>bad')||!str_contains($html,'role="alert"')||!str_contains($html,'pm-skip'))throw new RuntimeException('Login UI failed.');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-login.html',str_replace('<head>','<head><base href="/">',$html));
echo "PASS: login local CSS, escaped error/live alert and skip link.\n";
