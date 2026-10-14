<?php
include_once(ROOT_PATH.'classes/dao/pmnotifications.class.php');
class PmReminderService {
    public function __construct(private PmNotifications $dao) {}
    public static function eligibleTime(string $job,DateTimeImmutable $clock): bool {
        $clock=$clock->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        if($job==='timesheet')return (int)$clock->format('N')<=5&&$clock->format('H:i')>='17:30';
        if($job==='task')return $clock->format('H:i')>='08:00';
        throw new InvalidArgumentException('Loại nhắc việc không hợp lệ.');
    }
    public function run(string $job,bool $commit,DateTimeImmutable $clock): array {
        $clock=$clock->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
        $stats=['job'=>$job,'dry_run'=>!$commit,'candidates'=>0,'created'=>0,'duplicate'=>0,'stale'=>0,'failed'=>0,'skipped_window'=>false,'skipped_busy'=>false];
        if(!self::eligibleTime($job,$clock)){$stats['skipped_window']=true;return $stats;}
        if($commit)$this->dao->preflight();
        $lock=null;
        try {
            if($commit){$name=$this->dao->lockName($job);if(!$this->dao->acquire($name)){$stats['skipped_busy']=true;return $stats;}$lock=$name;}
            $date=$clock->format('Y-m-d');$afterUser=0;$afterTask=0;
            do {
                $rows=$this->dao->candidates($job,$date,$afterUser,$afterTask);
                foreach($rows as $row){
                    $afterUser=(int)$row['recipient_id'];$afterTask=(int)$row['entity_id'];$stats['candidates']++;
                    if(!$commit)continue;
                    $kind=$job==='timesheet'?'timesheet_missing':($row['due_date']<$date?'task_overdue':($row['due_date']===$date?'task_today':'task_tomorrow'));
                    try{$inserted=$this->dao->insert($row,$job,$kind,$date);$stats[$inserted?'created':'stale']++;}
                    catch(mysqli_sql_exception $e){if((int)$e->getCode()===1062)$stats['duplicate']++;else{$stats['failed']++;error_log('PM reminder insert failed.');}}
                    catch(Throwable $e){$stats['failed']++;error_log('PM reminder insert failed.');}
                }
            }while(count($rows)===100);
        }finally{if($lock!==null){try{$this->dao->release($lock);}catch(Throwable $e){$stats['failed']++;error_log('PM reminder lock release failed.');}}}
        return $stats;
    }
}
