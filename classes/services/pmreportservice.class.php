<?php
include_once(ROOT_PATH.'classes/dao/pmreports.class.php');
include_once(ROOT_PATH.'classes/services/pmcostservice.class.php');
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');
class PmReportService {
    private PmAccess $access;private PmDb $db;private PmReports $reports;
    public function __construct(private $database,private int $storeId,private int $actorId){$this->access=new PmAccess($database,$storeId,$actorId);$this->db=new PmDb($database);$this->reports=new PmReports($database);}
    private function permission(bool $export): void {
        if(!$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->storeId,$this->actorId])||!$this->access->hasPermission('pm.reports.view')||($export&&!$this->access->hasPermission('pm.reports.export'))||!($this->access->hasRole('ADMIN')||$this->access->hasRole('PM')||$this->access->hasRole('EMPLOYEE')||$this->access->hasRole('HR')))throw new DomainException('Bạn không có quyền báo cáo.');
    }
    private function filter(array $input): array {
        foreach(['from','to','project_id','user_id','page','mode','role_code'] as $key)if(isset($input[$key])&&!is_scalar($input[$key]))throw new InvalidArgumentException('Bộ lọc không hợp lệ.');
        $today=new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh'));$f=['from'=>(string)($input['from']??$today->format('Y-m-01')),'to'=>(string)($input['to']??$today->format('Y-m-d'))];
        foreach(['from','to'] as $key){$d=DateTimeImmutable::createFromFormat('!Y-m-d',$f[$key]);if(!$d||$d->format('Y-m-d')!==$f[$key]||$f[$key]<'1000-01-01')throw new InvalidArgumentException('Ngày không hợp lệ.');}
        if($f['from']>$f['to'])throw new InvalidArgumentException('Khoảng ngày đảo ngược.');
        foreach(['project_id','user_id'] as $key){$v=$input[$key]??'';if($v!==''&&$v!==null&&!preg_match('/^[1-9]\d{0,9}$/D',(string)$v))throw new InvalidArgumentException('ID không hợp lệ.');$f[$key]=$v===''||$v===null?null:(int)$v;}
        $page=(string)($input['page']??'1');if(!preg_match('/^[1-9]\d{0,5}$/D',$page))throw new InvalidArgumentException('Trang không hợp lệ.');$f['page']=(int)$page;
        $mode=$input['mode']??'hours';if(!in_array($mode,['hours','tasks','costs','users','projects'],true))throw new InvalidArgumentException('Loại báo cáo không hợp lệ.');$f['mode']=$mode;
        $f['role_code']=(string)($input['role_code']??'');if($f['role_code']!==''&&!in_array($f['role_code'],['ADMIN','PM','HR','EMPLOYEE'],true))throw new InvalidArgumentException('Role không hợp lệ.');return $f;
    }
    public function report(array $input=[],bool $export=false): array {
        $this->permission($export);$f=$this->filter($input);$scope=$this->access->hasRole('ADMIN')?'admin':($this->access->hasRole('PM')?'pm':'own');
        if(in_array($f['mode'],['users','projects'],true)){
            if(!$this->access->hasPermission($f['mode']==='users'?'pm.team.view':'pm.projects.view'))throw new DomainException('Không có quyền xuất danh sách.');
            if($f['mode']==='users'&&$f['project_id']!==null)throw new InvalidArgumentException('Danh sách nhân sự không lọc dự án.');
            if($f['mode']==='projects'&&($f['user_id']!==null||$f['role_code']!==''))throw new InvalidArgumentException('Danh sách dự án lọc ngày và ID dự án.');
            $f['store_id']=$this->storeId;$f['actor_id']=$this->actorId;$f['scope']=$scope;
            $this->db->beginTransaction();try{$data=$this->reports->directory($f,$export);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
            unset($f['store_id'],$f['actor_id'],$f['scope']);return ['mode'=>$f['mode'],'filters'=>$f,'data'=>$data];
        }
        if($f['mode']==='costs'){
            $costInput=['from'=>$f['from'],'to'=>$f['to'],'project_id'=>$f['project_id'],'user_id'=>$f['user_id'],'role_code'=>$f['role_code'],'page'=>$f['page']];
            $costs=(new PmCostService($this->database,$this->storeId,$this->actorId))->dashboard($costInput);
            if($export){if($costs['total_pages']*20>5000)throw new InvalidArgumentException('Export tối đa 5000 dự án. Vui lòng thu hẹp bộ lọc.');$all=[];for($page=1;$page<=$costs['total_pages'];$page++){$part=$page===$costs['page']?$costs:(new PmCostService($this->database,$this->storeId,$this->actorId))->dashboard(array_replace($costInput,['page'=>$page]));$all=array_merge($all,$part['projects']);}$costs['projects']=$all;}
            return ['mode'=>'costs','filters'=>$f,'data'=>$costs];
        }
        if($scope==='own'&&$f['user_id']!==null&&$f['user_id']!==$this->actorId)throw new DomainException('Chỉ xem báo cáo cá nhân.');
        if($f['project_id']!==null){
            $p=$this->db->fetchOne('SELECT manager_id FROM dc_pm_projects WHERE store_id=? AND id=?','ii',[$this->storeId,$f['project_id']]);
            $ownedSql=$f['mode']==='tasks'?'SELECT id FROM dc_pm_tasks WHERE store_id=? AND assignee_id=? AND project_id=? AND deleted_at IS NULL LIMIT 1':'SELECT id FROM dc_pm_timesheets WHERE store_id=? AND user_id=? AND project_id=? AND deleted_at IS NULL LIMIT 1';
            if(!$p||($scope==='pm'&&(int)$p['manager_id']!==$this->actorId)||($scope==='own'&&!$this->db->fetchOne($ownedSql,'iii',[$this->storeId,$this->actorId,$f['project_id']])))throw new DomainException('Dự án ngoài phạm vi.');
        }
        $f['store_id']=$this->storeId;$f['actor_id']=$this->actorId;$f['scope']=$scope;
        $this->db->beginTransaction();try{$data=$f['mode']==='tasks'?$this->reports->tasks($f,$export):$this->reports->hours($f,$export);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
        unset($f['store_id'],$f['actor_id'],$f['scope']);return ['mode'=>$f['mode'],'filters'=>$f,'data'=>$data];
    }
    public function xlsx(array $input=[]): string {
        $report=$this->report($input,true);
        include_once(ROOT_PATH.'classes/PhpSpreadSheet/PhpOffice/autoload.php');
        $book=new \PhpOffice\PhpSpreadsheet\Spreadsheet();$sheet=$book->getActiveSheet();$sheet->setTitle('Report');
        $rows=[['DeraSoft PM',$report['mode'],$report['filters']['from'],$report['filters']['to']]];
        if(in_array($report['mode'],['users','projects'],true)){
            $rows[]=array_values($report['data']['fields']);foreach($report['data']['rows'] as $r){$row=[];foreach($report['data']['fields'] as $key=>$label)$row[]=$r[$key]??'';$rows[]=$row;}
        }elseif($report['mode']==='hours'){
            $rows[]=['Ngày','Nhân viên','Dự án','Task','Ca','Giờ','Giờ thường','OT','Mô tả','Lịch sử'];
            foreach($report['data']['rows'] as $r)$rows[]=[$r['work_date'],$r['user_name'],$r['project_name'],$r['task_name'],$r['shift_label'],$r['hours'],$r['regular_hours'],$r['ot_hours'],$r['description'], $r['historical']?'Lịch sử':''];
            $s=$report['data']['summary'];$rows[]=['Tổng giờ','','','','',$s['hours'],$s['regular_hours'],$s['ot_hours']];
            $rows[]=['Theo tuần','Nhân viên','Giờ','Thường','OT'];foreach($report['data']['by_week'] as $w)$rows[]=[$w['week_start'],$w['user_name'],$w['hours'],$w['regular_hours'],$w['ot_hours']];
        }elseif($report['mode']==='tasks'){
            $rows[]=['Task','Dự án','Nhân viên','Trạng thái','Bắt đầu','Hạn','Hoàn thành','Hoàn thành trong kỳ','Quá hạn','Hoàn thành trễ'];
            foreach($report['data']['rows'] as $r)$rows[]=[$r['name'],$r['project_name'],$r['user_name'],$r['status'],$r['start_date'],$r['due_date'],$r['completed_at'],(string)$r['completed_in_period'],(string)$r['overdue'],(string)$r['completed_late']];
            $rows[]=['Tuần bắt đầu','Nhân viên','Task hoàn thành'];foreach($report['data']['by_week'] as $w)$rows[]=[$w['week_start'],$w['user_name'],$w['completed']];
        }else{
            $rows[]=['Dự án','Budget VND','Actual khoảng lọc','Currency','Actual lifetime','Currency','Ước tính','Currency'];
            foreach($report['data']['projects'] as $p)$rows[]=[$p['name'],$p['budget'],$p['actual']['cost']??'Không khả dụng',$p['actual']['currency']??'',$p['lifetime']['cost']??'Không khả dụng',$p['lifetime']['currency']??'',$p['estimate']['cost']??'Chưa đủ dữ liệu',$p['estimate']['currency']??''];
            $rows[]=['Ước tính tại ngày',$report['data']['valuation_date']];foreach($report['data']['warnings'] as $w)$rows[]=['Cảnh báo',$w];
            foreach(['department','role','task'] as $kind){$rows[]=[$kind,'ID','Giờ','Chi phí','Currency'];foreach($report['data']['groups'][$kind] as $id=>$g)$rows[]=[$kind,(string)$id,$g['hours'],$g['cost']??'Không khả dụng',$g['currency']??''];}
        }
        $path=tempnam(sys_get_temp_dir(),'pm-report-');if($path===false)throw new RuntimeException('Không thể tạo file tạm.');
        try{
            foreach($rows as $i=>$row)foreach($row as $j=>$value)$sheet->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j+1).($i+1),(string)($value??''),\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->freezePane('A3');$sheet->getStyle('A2:J2')->getFont()->setBold(true);
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);$bytes=file_get_contents($path);if($bytes===false)throw new RuntimeException('Không thể đọc báo cáo.');return $bytes;
        }finally{$book->disconnectWorksheets();if(is_file($path))unlink($path);}
    }
}
