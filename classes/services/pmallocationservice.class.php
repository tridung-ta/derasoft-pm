<?php
include_once(ROOT_PATH.'classes/dao/pmallocations.class.php');
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');

class PmAllocationService {
    private PmAllocations $dao;
    private PmDb $db;
    private PmAccess $access;
    public function __construct($database,private int $storeId,private int $actorId) {
        $this->dao=new PmAllocations($database,$storeId);$this->db=$this->dao->db;
        $this->access=new PmAccess($database,$storeId,$actorId);
    }
    private function permission(bool $write=false): void {
        if(!$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->storeId,$this->actorId])
            ||!$this->access->hasPermission($write?'pm.allocations.manage':'pm.allocations.view')
            ||!($this->access->hasRole('ADMIN')||$this->access->hasRole('PM')||(!$write&&$this->access->hasRole('EMPLOYEE'))))throw new DomainException('Bạn không có quyền phân bổ nguồn lực.');
    }
    private function admin(): void {$this->permission(true);if(!$this->access->hasRole('ADMIN'))throw new DomainException('Chỉ Admin quản lý ngưỡng phân bổ.');}
    public static function date(string $value): string {
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value,new DateTimeZone('Asia/Ho_Chi_Minh'));
        if(!$d||$d->format('Y-m-d')!==$value||$value<'1000-01-02')throw new InvalidArgumentException('Ngày không hợp lệ.');return $value;
    }
    public static function hundredths($value,int $max=2400): int {
        if(!is_scalar($value)||!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D',(string)$value))throw new InvalidArgumentException('Giờ phải có tối đa hai chữ số thập phân.');
        $parts=explode('.',(string)$value);$n=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
        if($n<=0||$n>$max)throw new InvalidArgumentException('Giờ vượt phạm vi cho phép.');return $n;
    }
    public static function decimal(int $n): string {return intdiv($n,100).'.'.str_pad((string)($n%100),2,'0',STR_PAD_LEFT);}
    private function id($value): int {if(!is_scalar($value)||!preg_match('/^[1-9]\d{0,9}$/D',(string)$value))throw new InvalidArgumentException('ID không hợp lệ.');return (int)$value;}
    private function week(string $day): array {$d=new DateTimeImmutable(self::date($day));$m=$d->modify('-'.((int)$d->format('N')-1).' days');$from=$m->format('Y-m-d');$to=$m->modify('+6 days')->format('Y-m-d');if($from<'1000-01-02'||strlen($to)!==10||$to>'9999-12-31')throw new InvalidArgumentException('Tuần ngoài phạm vi ngày hỗ trợ.');return [$from,$to];}
    private function project(int $id,bool $write=false,bool $lock=false): array {
        $p=$this->db->fetchOne('SELECT * FROM dc_pm_projects WHERE store_id=? AND id=?'.($lock?' FOR UPDATE':''),'ii',[$this->storeId,$id]);
        if(!$p||(!$this->access->hasRole('ADMIN')&&(!$this->access->hasRole('PM')||(int)$p['manager_id']!==$this->actorId))||($write&&$p['deleted_at']!==null))throw new DomainException('Dự án ngoài phạm vi hoặc đã ẩn.');return $p;
    }
    public function get(int $id): array {
        $this->permission();$row=$this->dao->find($id);if(!$row)throw new OutOfBoundsException('Không tìm thấy phân bổ.');
        if($this->access->hasRole('ADMIN')||$this->access->hasRole('PM'))$this->project((int)$row['project_id']);
        elseif((int)$row['user_id']!==$this->actorId)throw new DomainException('Phân bổ ngoài phạm vi.');return $row;
    }
    private function audit(string $entity,int $id,string $action,?array $old,?array $new): void {
        $this->db->execute('INSERT INTO dc_pm_audit_logs(store_id,actor_id,entity_type,entity_id,action,old_values,new_values) VALUES(?,?,?,?,?,?,?)','iisisss',[$this->storeId,$this->actorId,$entity,$id,$action,$old?json_encode($old,JSON_THROW_ON_ERROR):null,$new?json_encode($new,JSON_THROW_ON_ERROR):null]);
    }
    public function save(array $data,?int $id=null): array {
        foreach(['task_id','user_id','work_date','hours','start_time','end_time'] as $key)if(isset($data[$key])&&!is_scalar($data[$key]))throw new InvalidArgumentException('Input không hợp lệ.');
        $this->permission(true);$task=$this->id($data['task_id']??'');$user=$this->id($data['user_id']??'');
        $day=self::date((string)($data['work_date']??''));$hours=self::hundredths($data['hours']??'');
        $start=$data['start_time']??null;$end=$data['end_time']??null;$start=$start===''?null:$start;$end=$end===''?null:$end;
        if(($start===null)!==($end===null))throw new InvalidArgumentException('Nhập cả giờ bắt đầu và kết thúc hoặc bỏ trống cả hai.');
        if($start!==null){
            foreach([$start,$end] as $t)if(!is_string($t)||!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D',$t))throw new InvalidArgumentException('Giờ phải theo HH:mm.');
            $minutes=fn($t)=>(int)substr($t,0,2)*60+(int)substr($t,3,2);$duration=$minutes($end)-$minutes($start);
            if($duration<=0||(int)round($duration*100/60)!==$hours)throw new InvalidArgumentException('Khoảng giờ phải khớp thời lượng, không qua đêm.');
        }
        $old=$id?$this->get($id):null;$keys=[[$user,$day]];if($old)$keys[]=[(int)$old['user_id'],$old['work_date']];
        $this->db->beginTransaction();try{
            $this->dao->lockUsers($keys);
            if($old){$locked=$this->dao->find($id,true);if($locked!==$old)throw new DomainException('Phân bổ đã thay đổi, vui lòng tải lại.');$this->project((int)$old['project_id']);}
            $t=$this->db->fetchOne('SELECT * FROM dc_pm_tasks WHERE store_id=? AND id=?','ii',[$this->storeId,$task]);
            if(!$t)throw new OutOfBoundsException('Không tìm thấy task.');
            $projectIds=[(int)$t['project_id']];if($old)$projectIds[]=(int)$old['project_id'];$projectIds=array_unique($projectIds);sort($projectIds,SORT_NUMERIC);
            foreach($projectIds as $projectId)$this->project($projectId,$projectId===(int)$t['project_id'],true);
            $t=$this->db->fetchOne('SELECT * FROM dc_pm_tasks WHERE store_id=? AND id=? FOR UPDATE','ii',[$this->storeId,$task]);
            if($t['deleted_at']!==null||(int)$t['assignee_id']!==$user
                ||!$this->db->fetchOne('SELECT m.user_id FROM dc_pm_project_members m JOIN dc_users u ON u.store_id=m.store_id AND u.id=m.user_id WHERE m.store_id=? AND m.user_id=? AND m.project_id=? AND m.status=1 AND u.status=1 FOR UPDATE','iii',[$this->storeId,$user,(int)$t['project_id']]))throw new DomainException('Task, nhân viên hoặc membership không active/không phù hợp.');
            $overlap=$this->dao->overlapping($user,$day,$start,$end,$id);
            $new=['project_id'=>(int)$t['project_id'],'task_id'=>$task,'user_id'=>$user,'work_date'=>$day,'hours'=>self::decimal($hours),'start_time'=>$start,'end_time'=>$end];
            if($id)$this->db->execute('UPDATE dc_pm_allocations SET project_id=?,task_id=?,user_id=?,work_date=?,hours=?,start_time=?,end_time=?,updated_by=? WHERE store_id=? AND id=?','iiissssiii',[...array_values($new),$this->actorId,$this->storeId,$id]);
            else{$this->db->execute('INSERT INTO dc_pm_allocations(store_id,project_id,task_id,user_id,work_date,hours,start_time,end_time,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?,?,?)','iiiissssii',[$this->storeId,...array_values($new),$this->actorId,$this->actorId]);$id=(int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];}
            $warnings=$this->warnings($user,$day,true);if($overlap)$warnings[]='Trùng khoảng giờ với một phân bổ khác.';
            if($start===null)$warnings[]='Chưa đủ lịch chi tiết để xác nhận trùng giờ.';
            $this->audit('allocation',$id,$old?'update':'create',$old,$new);$this->db->commit();return ['id'=>$id,'warnings'=>array_values(array_unique($warnings))];
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function delete(int $id): void {
        $this->permission(true);$old=$this->get($id);$this->db->beginTransaction();try{
            $this->dao->lockUsers([[(int)$old['user_id'],$old['work_date']]]);$row=$this->dao->find($id,true);
            if($row!==$old)throw new DomainException('Phân bổ đã thay đổi, vui lòng tải lại.');$this->project((int)$old['project_id']);
            $this->db->execute('UPDATE dc_pm_allocations SET deleted_at=NOW(),updated_by=? WHERE store_id=? AND id=?','iii',[$this->actorId,$this->storeId,$id]);
            $this->audit('allocation',$id,'soft_delete',$old,null);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function hasOverlappingCapacity(int $userId,string $from,?string $to,?int $excludeId=null): bool {
        return $this->db->fetchOne('SELECT id FROM dc_pm_capacity_overrides WHERE store_id=? AND user_id=? AND deleted_at IS NULL AND (effective_to IS NULL OR effective_to >= ?) AND (? IS NULL OR ? >= effective_from) AND (? IS NULL OR id<>?) LIMIT 1 FOR UPDATE','iisssii',[$this->storeId,$userId,$from,$to,$to,$excludeId,$excludeId])!==null;
    }
    public function saveCapacity(array $data,?int $id=null): int {
        foreach(['user_id','effective_from','effective_to','daily_limit_hours','weekly_limit_hours'] as $key)if(isset($data[$key])&&!is_scalar($data[$key]))throw new InvalidArgumentException('Input không hợp lệ.');
        $this->admin();$user=$this->id($data['user_id']??'');$from=self::date((string)($data['effective_from']??''));$to=empty($data['effective_to'])?null:self::date((string)$data['effective_to']);
        if($to!==null&&$to<$from)throw new InvalidArgumentException('Khoảng hiệu lực đảo ngược.');
        $daily=self::decimal(self::hundredths($data['daily_limit_hours']??''));$weekly=self::decimal(self::hundredths($data['weekly_limit_hours']??'',16800));
        $this->db->beginTransaction();try{
            $this->dao->lockUsers([[$user,$from]]);
            if(!$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->storeId,$user]))throw new DomainException('Nhân viên không active trong tenant.');
            $old=$id?$this->db->fetchOne('SELECT * FROM dc_pm_capacity_overrides WHERE store_id=? AND id=? AND deleted_at IS NULL FOR UPDATE','ii',[$this->storeId,$id]):null;
            if($id&&(!$old||(int)$old['user_id']!==$user))throw new DomainException('Không thể đổi chủ sở hữu capacity.');
            if($this->hasOverlappingCapacity($user,$from,$to,$id))throw new DomainException('Khoảng capacity chồng lấn.');
            $new=['user_id'=>$user,'daily_limit_hours'=>$daily,'weekly_limit_hours'=>$weekly,'effective_from'=>$from,'effective_to'=>$to];
            if($id)$this->db->execute('UPDATE dc_pm_capacity_overrides SET daily_limit_hours=?,weekly_limit_hours=?,effective_from=?,effective_to=?,updated_by=? WHERE store_id=? AND id=?','ssssiii',[$daily,$weekly,$from,$to,$this->actorId,$this->storeId,$id]);
            else{$this->db->execute('INSERT INTO dc_pm_capacity_overrides(store_id,user_id,daily_limit_hours,weekly_limit_hours,effective_from,effective_to,created_by,updated_by) VALUES(?,?,?,?,?,?,?,?)','iissssii',[$this->storeId,...array_values($new),$this->actorId,$this->actorId]);$id=(int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];}
            $this->audit('capacity',$id,$old?'update':'create',$old,$new);$this->db->commit();return $id;
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function deleteCapacity(int $id): void {
        $this->admin();$old=$this->db->fetchOne('SELECT * FROM dc_pm_capacity_overrides WHERE store_id=? AND id=? AND deleted_at IS NULL','ii',[$this->storeId,$id]);if(!$old)throw new OutOfBoundsException('Không tìm thấy capacity.');
        $this->db->beginTransaction();try{$this->dao->lockUsers([[(int)$old['user_id'],$old['effective_from']]]);
            $current=$this->db->fetchOne('SELECT * FROM dc_pm_capacity_overrides WHERE store_id=? AND id=? AND deleted_at IS NULL FOR UPDATE','ii',[$this->storeId,$id]);if($current!==$old)throw new DomainException('Capacity đã thay đổi.');
            $this->db->execute('UPDATE dc_pm_capacity_overrides SET deleted_at=NOW(),updated_by=? WHERE store_id=? AND id=?','iii',[$this->actorId,$this->storeId,$id]);$this->audit('capacity',$id,'soft_delete',$old,null);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    private function capacity(int $user,string $day): array {
        $u=$this->db->fetchOne('SELECT weekly_limit_hours FROM dc_users WHERE store_id=? AND id=?','ii',[$this->storeId,$user]);
        $o=$this->db->fetchOne('SELECT daily_limit_hours,weekly_limit_hours FROM dc_pm_capacity_overrides WHERE store_id=? AND user_id=? AND deleted_at IS NULL AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?) ORDER BY effective_from DESC LIMIT 1','iiss',[$this->storeId,$user,$day,$day]);
        return ['daily'=>self::hundredths($o['daily_limit_hours']??'8'),'weekly'=>self::hundredths($o['weekly_limit_hours']??$u['weekly_limit_hours']??'40',16800)];
    }
    private function usage(int $user,string $day,bool $current=false): array {
        [$from,$to]=$this->week($day);$rows=$this->dao->load($user,$from,$to,$current);$daily=0;$weekly=0;$unknown=false;$intervals=[];$overlap=false;
        foreach($rows as $r){$h=self::hundredths($r['hours']);$weekly+=$h;if($r['work_date']===$day){$daily+=$h;if($r['start_time']===null||$r['end_time']===null)$unknown=true;else $intervals[]=[$r['start_time'],$r['end_time']];}}
        foreach($intervals as $i=>$a)foreach(array_slice($intervals,$i+1) as $b)if($a[0]<$b[1]&&$b[0]<$a[1])$overlap=true;
        return ['daily'=>$daily,'weekly'=>$weekly,'unknown'=>$unknown,'overlap'=>$overlap,'daily_limit'=>$this->capacity($user,$day)['daily'],'weekly_limit'=>$this->capacity($user,$from)['weekly']];
    }
    private function warnings(int $user,string $day,bool $current=false): array {
        return $this->usageWarnings($this->usage($user,$day,$current));
    }
    private function usageWarnings(array $u): array {
        $w=[];
        if($u['daily']>$u['daily_limit'])$w[]='Vượt ngưỡng phân bổ ngày.';
        if($u['weekly']>$u['weekly_limit'])$w[]='Vượt ngưỡng phân bổ tuần (ngưỡng tại thứ Hai).';
        if($u['unknown'])$w[]='Chưa đủ lịch chi tiết để xác nhận trùng giờ.';
        if($u['overlap'])$w[]='Trùng khoảng giờ với một phân bổ khác.';return $w;
    }
    public function dashboard(array $filters=[]): array {
        $this->db->beginTransaction();try{$result=$this->dashboardData($filters);$this->db->commit();return $result;}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    private function dashboardData(array $filters): array {
        $this->permission();foreach(['week','project_id','user_id'] as $key)if(isset($filters[$key])&&!is_scalar($filters[$key]))throw new InvalidArgumentException('Bộ lọc không hợp lệ.');
        [$from,$to]=$this->week((string)($filters['week']??(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d')));
        $project=empty($filters['project_id'])?null:$this->id($filters['project_id']);$user=empty($filters['user_id'])?null:$this->id($filters['user_id']);
        $where='a.store_id=? AND a.deleted_at IS NULL AND a.work_date BETWEEN ? AND ?';$types='iss';$params=[$this->storeId,$from,$to];
        if(!$this->access->hasRole('ADMIN')){if($this->access->hasRole('PM')){$where.=' AND p.manager_id=?';$types.='i';$params[]=$this->actorId;}else{$where.=' AND a.user_id=?';$types.='i';$params[]=$this->actorId;if($user&&$user!==$this->actorId)throw new DomainException('Nhân viên chỉ đọc lịch của mình.');}}
        if($project){if($this->access->hasRole('ADMIN')||$this->access->hasRole('PM'))$this->project($project);elseif(!$this->db->fetchOne('SELECT id FROM dc_pm_allocations WHERE store_id=? AND project_id=? AND user_id=? AND deleted_at IS NULL LIMIT 1','iii',[$this->storeId,$project,$this->actorId]))throw new DomainException('Dự án ngoài phạm vi.');$where.=' AND a.project_id=?';$types.='i';$params[]=$project;}
        if($user){$where.=' AND a.user_id=?';$types.='i';$params[]=$user;}
        $rows=$this->db->fetchAll('SELECT a.*,p.name project_name,t.name task_name,u.fullname user_name,(p.deleted_at IS NOT NULL OR t.deleted_at IS NOT NULL) historical FROM dc_pm_allocations a JOIN dc_pm_projects p ON p.store_id=a.store_id AND p.id=a.project_id JOIN dc_pm_tasks t ON t.store_id=a.store_id AND t.id=a.task_id JOIN dc_users u ON u.store_id=a.store_id AND u.id=a.user_id WHERE '.$where.' ORDER BY a.work_date,a.user_id,a.id LIMIT 1001',$types,$params);
        $truncated=count($rows)>1000;$rows=array_slice($rows,0,1000);
        // Read totals in one transaction snapshot; mutex writes use current reads separately.
        $usageCache=[];
        foreach($rows as &$row){$key=$row['user_id'].'/'.$row['work_date'];$usage=$usageCache[$key]??=$this->usage((int)$row['user_id'],$row['work_date']);$row['warnings']=$this->usageWarnings($usage);$row['day_total']=self::decimal($usage['daily']);$row['week_total']=self::decimal($usage['weekly']);$row['daily_limit']=self::decimal($usage['daily_limit']);$row['weekly_limit']=self::decimal($usage['weekly_limit']);}unset($row);
        $manager=$this->access->hasRole('ADMIN')||$this->access->hasRole('PM');
        $projects=$manager?$this->db->fetchAll('SELECT id,name FROM dc_pm_projects WHERE store_id=? AND deleted_at IS NULL'.($this->access->hasRole('ADMIN')?'':' AND manager_id=?').' ORDER BY name','i'.($this->access->hasRole('ADMIN')?'':'i'),$this->access->hasRole('ADMIN')?[$this->storeId]:[$this->storeId,$this->actorId]):[];
        $tasks=$manager?$this->db->fetchAll('SELECT t.id,t.name,t.project_id,t.assignee_id,u.fullname user_name FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id JOIN dc_users u ON u.store_id=t.store_id AND u.id=t.assignee_id WHERE t.store_id=? AND t.deleted_at IS NULL AND p.deleted_at IS NULL AND u.status=1 AND EXISTS(SELECT 1 FROM dc_pm_project_members m WHERE m.store_id=t.store_id AND m.project_id=t.project_id AND m.user_id=t.assignee_id AND m.status=1)'.($this->access->hasRole('ADMIN')?'':' AND p.manager_id=?').' ORDER BY t.id LIMIT 1000','i'.($this->access->hasRole('ADMIN')?'':'i'),$this->access->hasRole('ADMIN')?[$this->storeId]:[$this->storeId,$this->actorId]):[];
        $capacities=$this->access->hasRole('ADMIN')?$this->db->fetchAll('SELECT c.*,u.fullname user_name FROM dc_pm_capacity_overrides c JOIN dc_users u ON u.store_id=c.store_id AND u.id=c.user_id WHERE c.store_id=? AND c.deleted_at IS NULL ORDER BY c.id DESC LIMIT 200','i',[$this->storeId]):[];
        $users=$this->access->hasRole('ADMIN')?$this->db->fetchAll('SELECT id,fullname FROM dc_users WHERE store_id=? AND status=1 ORDER BY id','i',[$this->storeId]):[];
        return ['from'=>$from,'to'=>$to,'project_id'=>$project,'user_id'=>$user,'rows'=>$rows,'projects'=>$projects,'tasks'=>$tasks,'users'=>$users,'capacities'=>$capacities,'can_manage'=>$manager&&$this->access->hasPermission('pm.allocations.manage'),'is_admin'=>$this->access->hasRole('ADMIN'),'truncated'=>$truncated];
    }
    public function suggestions(int $projectId,string $day,$hours): array {
        $this->permission(true);$this->project($projectId,true);$day=self::date($day);$required=self::hundredths($hours);
        $users=$this->db->fetchAll('SELECT u.id,u.fullname FROM dc_users u WHERE u.store_id=? AND u.status=1 AND EXISTS(SELECT 1 FROM dc_pm_project_members m WHERE m.store_id=u.store_id AND m.project_id=? AND m.user_id=u.id AND m.status=1) ORDER BY u.id','ii',[$this->storeId,$projectId]);$result=[];
        foreach($users as $u){$usage=$this->usage((int)$u['id'],$day);$available=max(0,min($usage['daily_limit']-$usage['daily'],$usage['weekly_limit']-$usage['weekly']));if($available>=$required)$result[]=['user_id'=>(int)$u['id'],'name'=>$u['fullname'],'available_hours'=>self::decimal($available),'schedule_unknown'=>$usage['unknown']];}return $result;
    }
}
