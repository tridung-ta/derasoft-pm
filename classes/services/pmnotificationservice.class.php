<?php
include_once(ROOT_PATH.'classes/dao/pmnotifications.class.php');
class PmNotificationService {
    public function __construct(private PmNotifications $dao,private int $actorId,private ?DateTimeImmutable $clock=null) {
        if($actorId<=0)throw new InvalidArgumentException('Tài khoản không hợp lệ.');
    }
    public function listing(int $page=1,int $size=20): array {
        $this->dao->preflight();$date=$this->today();$out=$this->dao->list($this->actorId,$date,$page,$size);
        foreach($out['rows'] as &$row){
            $row['title']=$row['kind']==='timesheet_missing'?'Bạn chưa ghi chấm công hôm nay':($row['current_due_date']<$date?'Công việc đã quá hạn':($row['current_due_date']===$date?'Công việc đến hạn hôm nay':'Công việc đến hạn ngày mai'));
            $row['url']=$row['kind']==='timesheet_missing'?'?op=pmtimesheets':'?op=pmprojects&project_id='.(int)$row['project_id'];
        }unset($row);return $out;
    }
    public function markRead(int $id): bool {$this->dao->preflight();return $id>0&&$this->dao->markRead($this->actorId,$id,$this->today());}
    private function today(): string {return ($this->clock??new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('Y-m-d');}
}
