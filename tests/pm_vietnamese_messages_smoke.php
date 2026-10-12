<?php
define('ROOT_PATH',dirname(__DIR__).'/');
include_once(ROOT_PATH.'classes/services/pmrateservice.class.php');
$rate=(new ReflectionClass(PmRateService::class))->newInstanceWithoutConstructor();
$method=new ReflectionMethod(PmRateService::class,'validateDates');
foreach([['bad-date',null],['2026-10-12','2026-10-11']] as [$from,$to]){
 try{$method->invoke($rate,$from,$to);throw new RuntimeException('Invalid dates accepted');}
 catch(InvalidArgumentException $e){if($e->getMessage()!=='Ngày hiệu lực đơn giá không hợp lệ. Ngày kết thúc phải bằng hoặc sau ngày bắt đầu.')throw $e;}
}
$method->invoke($rate,'2026-10-12','2026-10-12');
$paths=[ROOT_PATH.'pm_ajax.php'];
foreach(['classes/services','classes/dao','classes/security','modules/admin','modules/ajax','templates/admin','js'] as $dir){
 foreach(glob(ROOT_PATH.$dir.'/pm*') as $path)if(is_file($path))$paths[]=$path;
}
$count=0;
foreach($paths as $path){
 $source=file_get_contents($path);if(!preg_match('//u',$source))throw new RuntimeException('Not UTF8: '.$path);
 if(preg_match('/\x{FFFD}|Ã|Â|áº|á»/u',$source))throw new RuntimeException('Mojibake: '.$path);
 if(str_ends_with($path,'.php')&&preg_match("/(?:throw new \\w+\\(|exit\\()\\s*'(Authentication required|Unknown PM endpoint|Forbidden|Invalid [^']+|Hourly rate[^']+|Primary role must[^']+)'/",$source))throw new RuntimeException('English user message: '.$path);
 $count++;
}
$tpl=file_get_contents(ROOT_PATH.'templates/admin/pm-timesheets.tpl.html');
if(!str_contains($tpl,"replace:'Hourly rate is not configured for this user and date.':'Chưa thiết lập đơn giá cho nhân sự tại ngày chấm công này.'|escape"))throw new RuntimeException('Legacy rate warning missing');
echo "PASS: $count PM sources valid UTF8/no mojibake; old English error patterns absent; real rate date validator rejects invalid/reversed and accepts equal dates with Vietnamese message; legacy warning render mapping preserves escaping. No DB connected.\n";
