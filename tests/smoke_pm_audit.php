<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$s->assign(['pageTitle'=>'Audit','error'=>'','filters'=>['entity_type'=>'timesheet','action'=>'admin_update_locked'],'page'=>2,'totalPages'=>3,'auditRows'=>[['created_at'=>'2026-10-02','actor_id'=>1,'entity_type'=>'timesheet','entity_id'=>1,'action'=>'admin_update_locked','old_values'=>null,'new_values'=>'{"description":"<script>bad</script>"}']]]);
$html=$s->fetch('admin/pm-audit.tpl.html');
if(str_contains($html,'<script>')||!str_contains($html,'&lt;script&gt;')||!str_contains($html,'page=1')||!str_contains($html,'page=3'))throw new RuntimeException('Audit escaping/pagination failed.');
$s->assign('auditRows',[]);$html=$s->fetch('admin/pm-audit.tpl.html');
if(!str_contains($html,'colspan="4"'))throw new RuntimeException('Empty audit state missing.');
echo "PASS: audit Smarty details, escaping, pagination and empty state.\n";
