<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');
include_once(ROOT_PATH.'classes/services/pmrateservice.class.php');
class PmTimesheetService {
    private PmDb $db;private PmAccess $access;private PmRateService $rates;
    public function __construct($database,private int $storeId,private int $actorId){$this->db=new PmDb($database);$this->access=new PmAccess($database,$storeId,$actorId);$this->rates=new PmRateService($database);}
    private function ownPermission(): void {if(!$this->access->hasPermission('pm.timesheets.own'))throw new DomainException('Bạn không có quyền chấm công.');}
    public function settings(): array {
        $values=['standard_hours_per_day'=>'8.00','ot_multiplier'=>'1.00'];
        foreach($this->db->fetchAll('SELECT setting_key,setting_value FROM dc_pm_system_settings WHERE store_id=?','i',[$this->storeId]) as $row)if(array_key_exists($row['setting_key'],$values))$values[$row['setting_key']]=$row['setting_value'];
        return $values;
    }
    public function saveSettings(array $data): void {
        if(!$this->access->hasRole('ADMIN'))throw new DomainException('Chỉ Admin được thay đổi quy tắc OT.');
        $standard=$this->quantity($data['standard_hours_per_day']??'',24);$multiplier=$this->quantity($data['ot_multiplier']??'',10);
        $this->db->beginTransaction();try{
            $old=$this->settings();foreach(['standard_hours_per_day'=>$standard,'ot_multiplier'=>$multiplier] as $key=>$value)$this->db->execute('INSERT INTO dc_pm_system_settings(store_id,setting_key,setting_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)','iss',[$this->storeId,$key,$value]);
            $this->audit('settings',0,'update',$old,$this->settings());$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    private function quantity($value,int $max): string {
        $raw=(string)$value;if(!preg_match('/^\d{1,2}(?:\.\d{1,2})?$/D',$raw)||(float)$raw<=0||(float)$raw>$max)throw new InvalidArgumentException('Số giờ hoặc hệ số không hợp lệ.');
        return number_format((float)$raw,2,'.','');
    }
    private function validDate(string $date): void {$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date)throw new InvalidArgumentException('Ngày làm việc không hợp lệ.');}
    protected function currentDate(): DateTimeImmutable {
        return new DateTimeImmutable('today', new DateTimeZone('Asia/Ho_Chi_Minh'));
    }
    public function today(): string { return $this->currentDate()->format('Y-m-d'); }
    public function isLocked(string $date): bool {
        $this->validDate($date);
        $workDate=new DateTimeImmutable($date, new DateTimeZone('Asia/Ho_Chi_Minh'));
        return $this->currentDate() >= $workDate->modify('+3 days');
    }
    public function canEdit(string $date): bool { return $this->access->hasRole('ADMIN') || (!$this->isLocked($date) && $date<=$this->today()); }
    private function requireEditable(string $date): void {
        if(!$this->access->hasRole('ADMIN') && $date>$this->today())throw new InvalidArgumentException('Không thể ghi công cho ngày trong tương lai.');
        if(!$this->canEdit($date))throw new DomainException('Chấm công đã khóa sau 3 ngày. Chỉ Admin được sửa hoặc xóa.');
    }
    public function assignedTasks(?int $userId=null): array {
        $this->ownPermission();
        $userId=$userId??$this->actorId;
        if($userId!==$this->actorId&&!$this->access->hasRole('ADMIN'))throw new DomainException('Không có quyền truy cập nhân sự này.');
        return $this->db->fetchAll('SELECT t.id,t.name,p.name project_name FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id JOIN dc_pm_project_members m ON m.store_id=t.store_id AND m.project_id=t.project_id AND m.user_id=t.assignee_id AND m.status=1 WHERE t.store_id=? AND t.assignee_id=? AND t.deleted_at IS NULL AND p.deleted_at IS NULL ORDER BY p.name,t.name','ii',[$this->storeId,$userId]);
    }
    public function listOwn(int $page=1): array {
        $this->ownPermission();
        $total=(int)$this->db->fetchOne('SELECT COUNT(*) total FROM dc_pm_timesheets WHERE store_id=? AND user_id=? AND deleted_at IS NULL','ii',[$this->storeId,$this->actorId])['total'];
        $rows=$this->db->fetchAll('SELECT s.*,t.name task_name,p.name project_name FROM dc_pm_timesheets s LEFT JOIN dc_pm_tasks t ON t.store_id=s.store_id AND t.id=s.task_id LEFT JOIN dc_pm_projects p ON p.store_id=s.store_id AND p.id=s.project_id WHERE s.store_id=? AND s.user_id=? AND s.deleted_at IS NULL ORDER BY s.work_date DESC,s.id DESC LIMIT 20 OFFSET ?','iii',[$this->storeId,$this->actorId,(max(1,$page)-1)*20]);
        return ['rows'=>$rows,'total'=>$total];
    }
    public function getOwn(int $id): array {
        $this->ownPermission();
        $row=$this->db->fetchOne('SELECT * FROM dc_pm_timesheets WHERE store_id=? AND user_id=? AND id=? AND deleted_at IS NULL','iii',[$this->storeId,$this->actorId,$id]);if(!$row)throw new OutOfBoundsException('Không tìm thấy bản ghi chấm công của bạn.');return $row;
    }
    public function getTimesheet(int $id): array {
        $this->ownPermission();
        if(!$this->access->hasRole('ADMIN'))return $this->getOwn($id);
        $row=$this->db->fetchOne('SELECT * FROM dc_pm_timesheets WHERE store_id=? AND id=? AND deleted_at IS NULL','ii',[$this->storeId,$id]);
        if(!$row)throw new OutOfBoundsException('Không tìm thấy bản ghi chấm công.');
        return $row;
    }
    public function listTimesheets(int $page=1): array {
        $this->ownPermission();
        if(!$this->access->hasRole('ADMIN'))return $this->listOwn($page);
        $total=(int)$this->db->fetchOne('SELECT COUNT(*) total FROM dc_pm_timesheets WHERE store_id=? AND deleted_at IS NULL','i',[$this->storeId])['total'];
        $rows=$this->db->fetchAll('SELECT s.*,t.name task_name,p.name project_name FROM dc_pm_timesheets s LEFT JOIN dc_pm_tasks t ON t.store_id=s.store_id AND t.id=s.task_id LEFT JOIN dc_pm_projects p ON p.store_id=s.store_id AND p.id=s.project_id WHERE s.store_id=? AND s.deleted_at IS NULL ORDER BY s.work_date DESC,s.id DESC LIMIT 20 OFFSET ?','ii',[$this->storeId,(max(1,$page)-1)*20]);
        return ['rows'=>$rows,'total'=>$total];
    }
    public function weeklySummary(string $date,?int $userId=null): array {
        $this->ownPermission();$this->validDate($date);$userId=$userId??$this->actorId;
        if($userId!==$this->actorId&&!$this->access->hasRole('ADMIN'))throw new DomainException('Không có quyền truy cập nhân sự này.');
        $selected=new DateTimeImmutable($date,new DateTimeZone('Asia/Ho_Chi_Minh'));
        $start=$selected->modify('-'.((int)$selected->format('N')-1).' days');$end=$start->modify('+6 days');
        $rows=$this->db->fetchAll('SELECT work_date,SUM(hours) hours,SUM(regular_hours) regular_hours,SUM(ot_hours) ot_hours FROM dc_pm_timesheets WHERE store_id=? AND user_id=? AND work_date BETWEEN ? AND ? AND deleted_at IS NULL GROUP BY work_date','iiss',[$this->storeId,$userId,$start->format('Y-m-d'),$end->format('Y-m-d')]);
        $byDate=array_column($rows,null,'work_date');$days=[];
        foreach(['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7','Chủ nhật'] as $i=>$label){
            $day=$start->modify('+'.$i.' days');$key=$day->format('Y-m-d');$row=$byDate[$key]??[];
            $days[]=['date'=>$key,'label'=>$label,'short_date'=>$day->format('d/m'),'is_today'=>$key===$this->today(),'has_entries'=>isset($byDate[$key]),'hours'=>$row['hours']??'0.00','regular_hours'=>$row['regular_hours']??'0.00','ot_hours'=>$row['ot_hours']??'0.00','has_ot'=>isset($row['ot_hours'])&&(float)$row['ot_hours']>0];
        }
        return ['start'=>$start->format('d/m/Y'),'end'=>$end->format('d/m/Y'),'user_id'=>$userId,'days'=>$days];
    }
    private function lockDay(string $date,int $userId): array {
        $settings=$this->settings();
        $this->db->execute('INSERT INTO dc_pm_timesheet_days(store_id,user_id,work_date,standard_hours,ot_multiplier) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE user_id=user_id','iisss',[$this->storeId,$userId,$date,$settings['standard_hours_per_day'],$settings['ot_multiplier']]);
        return $this->db->fetchOne('SELECT * FROM dc_pm_timesheet_days WHERE store_id=? AND user_id=? AND work_date=? FOR UPDATE','iis',[$this->storeId,$userId,$date]);
    }
    public function save(array $data,?int $id=null): int {
        $this->ownPermission();$date=(string)($data['work_date']??'');$this->validDate($date);$hours=$this->quantity($data['hours']??'',24);$shift=trim((string)($data['shift_label']??''));$description=(string)($data['description']??'');$taskId=(int)($data['task_id']??0);
        if($shift===''||mb_strlen($shift)>30||mb_strlen($description)>5000)throw new InvalidArgumentException('Ca hoặc mô tả không hợp lệ.');
        $before=$id?$this->getTimesheet($id):null;$userId=$before?(int)$before['user_id']:$this->actorId;$dates=array_unique([$date,$before['work_date']??$date]);sort($dates);
        $this->db->beginTransaction();try{
            foreach($dates as $dayDate){$this->requireEditable($dayDate);$this->lockDay($dayDate,$userId);}
            // A write may have waited on the ledger lock across the midnight boundary.
            foreach($dates as $dayDate)$this->requireEditable($dayDate);
            $old=$id?$this->getTimesheet($id):null;
            if($old&&$old['work_date']!==$before['work_date'])throw new DomainException('Bản ghi vừa thay đổi. Vui lòng tải lại trang.');
            $task=$this->db->fetchOne('SELECT t.project_id FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id JOIN dc_pm_project_members m ON m.store_id=t.store_id AND m.project_id=t.project_id AND m.user_id=t.assignee_id AND m.status=1 WHERE t.store_id=? AND t.id=? AND t.assignee_id=? AND t.deleted_at IS NULL AND p.deleted_at IS NULL FOR UPDATE','iii',[$this->storeId,$taskId,$userId]);
            if(!$task && $old && $this->access->hasRole('ADMIN') && $taskId===(int)$old['task_id'])$task=['project_id'=>$old['project_id']];
            if(!$task)throw new DomainException('Chỉ có thể ghi công cho task được giao cho bạn trong dự án đang hoạt động.');
            $rate=(!$old||$old['work_date']!==$date)?$this->rates->resolveRate($this->storeId,$userId,$date):['rate_decimal'=>$old['rate_snapshot'],'source'=>$old['rate_source'],'currency'=>$old['currency'],'warning'=>$old['rate_warning']];
            if($rate['currency']!=='VND')throw new DomainException('Chấm công hiện chỉ hỗ trợ đơn giá VND.');
            if($id)$this->db->execute('UPDATE dc_pm_timesheets SET project_id=?,task_id=?,work_date=?,shift_label=?,hours=?,description=?,rate_snapshot=?,rate_source=?,currency=?,rate_warning=? WHERE store_id=? AND user_id=? AND id=?','iissssssssiii',[(int)$task['project_id'],$taskId,$date,$shift,$hours,$description,$rate['rate_decimal'],$rate['source'],$rate['currency'],$rate['warning'],$this->storeId,$userId,$id]);
            else{$day=$this->lockDay($date,$userId);$this->db->execute('INSERT INTO dc_pm_timesheets(store_id,user_id,project_id,task_id,work_date,shift_label,hours,description,rate_snapshot,rate_source,currency,rate_warning,ot_multiplier_snapshot) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)','iiiisssssssss',[$this->storeId,$userId,(int)$task['project_id'],$taskId,$date,$shift,$hours,$description,$rate['rate_decimal'],$rate['source'],$rate['currency'],$rate['warning'],$day['ot_multiplier']]);$id=(int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];}
            foreach($dates as $dayDate)$this->recalculate($dayDate,$id,$userId);
            $action=$old?'update':'create';
            if($this->access->hasRole('ADMIN') && array_filter($dates,fn($d)=>$this->isLocked($d)))$action=$old?'admin_update_locked':'admin_create_locked';
            $this->audit('timesheet',$id,$action,$old,$this->getTimesheet($id));$this->db->commit();return $id;
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function delete(int $id): void {
        $this->ownPermission();$before=$this->getTimesheet($id);$userId=(int)$before['user_id'];$this->db->beginTransaction();try{
            $this->requireEditable($before['work_date']);$this->lockDay($before['work_date'],$userId);$old=$this->getTimesheet($id);if($old['work_date']!==$before['work_date'])throw new DomainException('Bản ghi vừa thay đổi. Vui lòng tải lại trang.');
            $this->requireEditable($old['work_date']);
            $this->db->execute('UPDATE dc_pm_timesheets SET deleted_at=NOW() WHERE store_id=? AND user_id=? AND id=?','iii',[$this->storeId,$userId,$id]);
            $this->recalculate($old['work_date'],$id,$userId);$new=$this->db->fetchOne('SELECT * FROM dc_pm_timesheets WHERE store_id=? AND id=?','ii',[$this->storeId,$id]);$action=$this->isLocked($old['work_date'])?'admin_delete_locked':'soft_delete';$this->audit('timesheet',$id,$action,$old,$new);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    private function recalculate(string $date,int $changedId,int $userId): void {
        $day=$this->lockDay($date,$userId);$rows=$this->db->fetchAll('SELECT * FROM dc_pm_timesheets WHERE store_id=? AND user_id=? AND work_date=? AND deleted_at IS NULL ORDER BY id FOR UPDATE','iis',[$this->storeId,$userId,$date]);
        $total=array_sum(array_map(fn($r)=>(int)round((float)$r['hours']*100),$rows));if($total>2400)throw new DomainException('Tổng giờ trong một ngày không được vượt quá 24.');
        $remaining=(int)round((float)$day['standard_hours']*100);
        foreach($rows as $row){$quantity=(int)round((float)$row['hours']*100);$regular=min($remaining,$quantity);$remaining-=$regular;$ot=$quantity-$regular;
            $this->db->execute('UPDATE dc_pm_timesheets SET regular_hours=?,ot_hours=?,ot_multiplier_snapshot=?,cost=ROUND((CAST(? AS DECIMAL(4,2)) + CAST(? AS DECIMAL(4,2)) * CAST(? AS DECIMAL(4,2))) * rate_snapshot,2) WHERE store_id=? AND id=?','ssssssii',[number_format($regular/100,2,'.',''),number_format($ot/100,2,'.',''),$day['ot_multiplier'],number_format($regular/100,2,'.',''),number_format($ot/100,2,'.',''),$day['ot_multiplier'],$this->storeId,(int)$row['id']]);
            $new=$this->getTimesheet((int)$row['id']);if((int)$row['id']!==$changedId&&($new['regular_hours']!==$row['regular_hours']||$new['ot_hours']!==$row['ot_hours']||$new['cost']!==$row['cost']||$new['ot_multiplier_snapshot']!==$row['ot_multiplier_snapshot']))$this->audit('timesheet',(int)$row['id'],'recalculate',$row,$new);
        }
    }
    private function audit(string $entity,int $id,string $action,?array $old,?array $new): void {
        $this->db->execute('INSERT INTO dc_pm_audit_logs(store_id,actor_id,entity_type,entity_id,action,old_values,new_values) VALUES(?,?,?,?,?,?,?)','iisisss',[$this->storeId,$this->actorId,$entity,$id,$action,$old===null?null:json_encode($old,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$new===null?null:json_encode($new,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
    }
}
