<?php
if(PHP_SAPI!=='cli')exit;
define('ROOT_PATH',dirname(__DIR__).'/');
require ROOT_PATH.'tests/pm_cron_db_fixture.php';require ROOT_PATH.'classes/services/pmreminderservice.class.php';
try{
 $f=pmCronLoadFixture();$c=pmCronLocalConnection();pmCronMirrorUsers($c,$f);
 $store=(int)($argv[1]??0);if(!in_array($store,$f['stores'],true))throw new RuntimeException('Fixture store guard.');
 $mode=$argv[2]??'run';$date=$argv[3]??$f['date'];$clock=new DateTimeImmutable($date.' 18:00',new DateTimeZone('Asia/Ho_Chi_Minh'));
 class CronBarrierDao extends PmNotifications {
  public string $mode;public string $barrier;
  private function waitBarrier(): void {file_put_contents($this->barrier.'.ready','ready');$end=microtime(true)+15;while(!is_file($this->barrier.'.go')){if(microtime(true)>$end)throw new RuntimeException('Barrier timeout');usleep(20000);}}
  public function acquire(string $name): bool{$ok=parent::acquire($name);if($ok&&$this->mode==='hold')$this->waitBarrier();return $ok;}
  public function insert(array $row,string $job,string $kind,string $date): int{$n=parent::insert($row,$job,$kind,$date);if($n&&$this->mode==='crash')$this->waitBarrier();return $n;}
 }
 $dao=new CronBarrierDao(new PmDb((object)['connection'=>$c]),$store);$dao->mode=$mode;$dao->barrier=ROOT_PATH.'.local/cron-barrier-'.$f['nonce'];
 $out=(new PmReminderService($dao))->run('task',true,$clock);echo json_encode($out,JSON_THROW_ON_ERROR).PHP_EOL;$c->close();exit($out['failed']?1:0);
}catch(Throwable $e){echo json_encode(['error_code'=>(int)$e->getCode(),'test_failed'=>true]).PHP_EOL;exit(1);}
